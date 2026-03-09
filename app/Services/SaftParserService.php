<?php

namespace App\Services;

use App\DTOs\InvoiceData;
use App\DTOs\SaftHeader;
use SimpleXMLElement;

class SaftParserService
{
    private const SAFT_NAMESPACE = 'urn:OECD:StandardAuditFile-Tax:PT_1.04_01';

    /**
     * Parse a SAF-T PT XML file and return header information.
     *
     * @throws \InvalidArgumentException
     */
    public function parseHeader(string $xmlContent): SaftHeader
    {
        $xml = $this->loadXml($xmlContent);

        $header = $xml->Header;

        return new SaftHeader(
            companyName: (string) ($header->CompanyName ?? ''),
            companyId: (string) ($header->CompanyID ?? ''),
            taxRegistrationNumber: (string) ($header->TaxRegistrationNumber ?? ''),
            fiscalYear: (string) ($header->FiscalYear ?? ''),
            startDate: (string) ($header->StartDate ?? ''),
            endDate: (string) ($header->EndDate ?? ''),
            currencyCode: (string) ($header->CurrencyCode ?? ''),
            softwareCertificateNumber: (string) ($header->SoftwareCertificateNumber ?? ''),
        );
    }

    /**
     * Parse all invoices from the SAF-T XML file.
     *
     * @return InvoiceData[]
     * @throws \InvalidArgumentException
     */
    public function parseInvoices(string $xmlContent): array
    {
        $xml = $this->loadXml($xmlContent);

        $salesInvoices = $xml->SourceDocuments->SalesInvoices ?? null;

        if ($salesInvoices === null) {
            throw new \InvalidArgumentException(
                'O ficheiro SAF-T não contém a secção SalesInvoices. '
                . 'Certifique-se de que o ficheiro contém documentos de faturação.'
            );
        }

        $invoices = [];

        foreach ($salesInvoices->Invoice as $invoice) {
            $invoiceNo = (string) $invoice->InvoiceNo;
            [$series, $sequentialNumber] = $this->parseInvoiceNo($invoiceNo);

            $grossTotal = (string) ($invoice->DocumentTotals->GrossTotal ?? '0.00');
            // Ensure 2 decimal places
            $grossTotal = number_format((float) $grossTotal, 2, '.', '');

            $invoices[] = new InvoiceData(
                invoiceNo: $invoiceNo,
                hash: (string) ($invoice->Hash ?? ''),
                hashControl: (string) ($invoice->HashControl ?? ''),
                period: (string) ($invoice->Period ?? ''),
                invoiceDate: (string) ($invoice->InvoiceDate ?? ''),
                invoiceType: (string) ($invoice->InvoiceType ?? ''),
                systemEntryDate: (string) ($invoice->SystemEntryDate ?? ''),
                grossTotal: $grossTotal,
                series: $series,
                sequentialNumber: $sequentialNumber,
            );
        }

        return $invoices;
    }

    /**
     * Parse InvoiceNo to extract series and sequential number.
     * Format: "TipoDocumento Série/Sequencial" e.g. "FT A/1"
     *
     * The series key intentionally includes the document type prefix
     * (e.g. "FT A") so that documents of different types that share the
     * same letter (e.g. "FAC A" vs "FS A") are treated as independent
     * series, each with their own hash chain.
     *
     * @return array{string, int} [series, sequentialNumber]
     */
    private function parseInvoiceNo(string $invoiceNo): array
    {
        // Pattern: TYPE SERIES/SEQUENTIAL (e.g., "FT A/1", "FS B/12", "FAC A/1")
        // Capture "TYPE SERIES" as a single series key so that "FT A" and "FS A"
        // remain separate series with independent hash chains.
        if (preg_match('/^([A-Z]+\s+[^\/]+)\/(\d+)$/', $invoiceNo, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        // Fallback: try SERIES/SEQUENTIAL without type prefix
        if (preg_match('/^([^\/]+)\/(\d+)$/', $invoiceNo, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        // If we can't parse it, use the full invoice number as series with sequence 0
        return [$invoiceNo, 0];
    }

    /**
     * Load and validate the SAF-T XML content.
     *
     * @throws \InvalidArgumentException
     */
    private function loadXml(string $xmlContent): SimpleXMLElement
    {
        // Suppress warnings and use libxml error handling
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMessages = array_map(fn ($e) => trim($e->message), $errors);
            throw new \InvalidArgumentException(
                'XML inválido: ' . implode('; ', $errorMessages)
            );
        }

        // Validate SAF-T PT namespace
        $namespaces = $xml->getNamespaces(false);
        $rootNamespace = $namespaces[''] ?? ($namespaces[array_key_first($namespaces)] ?? '');

        if ($rootNamespace !== self::SAFT_NAMESPACE) {
            // Try to register the namespace and check children
            $xmlString = $xml->asXML();
            if ($xmlString !== false && strpos($xmlString, self::SAFT_NAMESPACE) === false) {
                throw new \InvalidArgumentException(
                    'O ficheiro não é um SAF-T PT válido (namespace inválido). '
                    . 'Esperado: ' . self::SAFT_NAMESPACE
                );
            }
        }

        return $xml;
    }
}
