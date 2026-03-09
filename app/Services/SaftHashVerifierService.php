<?php

namespace App\Services;

use App\DTOs\InvoiceData;
use App\DTOs\SeriesResult;
use App\DTOs\VerificationResult;
use App\DTOs\VerificationStatus;

class SaftHashVerifierService
{
    /**
     * Verify invoices and return results grouped by series.
     *
     * @param InvoiceData[] $invoices
     * @param string|null $publicKeyPem Optional RSA public key in PEM format
     * @return SeriesResult[]
     */
    public function verify(array $invoices, ?string $publicKeyPem = null): array
    {
        $publicKey = null;
        if ($publicKeyPem !== null) {
            $publicKey = $this->loadPublicKey($publicKeyPem);
        }

        // Group invoices by series
        $seriesGroups = $this->groupBySeries($invoices);

        $seriesResults = [];

        foreach ($seriesGroups as $series => $seriesInvoices) {
            // Sort by sequential number within each series
            usort($seriesInvoices, fn ($a, $b) => $a->sequentialNumber <=> $b->sequentialNumber);

            $results = [];
            $previousHash = '';

            foreach ($seriesInvoices as $index => $invoice) {
                $isFirst = ($index === 0);

                if ($isFirst) {
                    // First document in the series: cannot verify cryptographically
                    // because we don't know the previous hash (it was "" when created)
                    $signatureString = $this->buildSignatureString(
                        $invoice->invoiceDate,
                        $invoice->systemEntryDate,
                        $invoice->invoiceNo,
                        $invoice->grossTotal,
                        '' // previous hash is empty for first document
                    );

                    $results[] = new VerificationResult(
                        invoice: $invoice,
                        status: VerificationStatus::NotVerifiable,
                        message: 'Não verificável — 1º documento da série. Não é possível determinar o hash anterior.',
                        signatureString: $signatureString,
                        chainValid: false,
                    );

                    $previousHash = $invoice->hash;
                    continue;
                }

                // For subsequent documents, verify using the previous hash
                $signatureString = $this->buildSignatureString(
                    $invoice->invoiceDate,
                    $invoice->systemEntryDate,
                    $invoice->invoiceNo,
                    $invoice->grossTotal,
                    $previousHash
                );

                $verificationResult = $this->verifyDocument(
                    invoice: $invoice,
                    signatureString: $signatureString,
                    publicKey: $publicKey,
                );

                $results[] = $verificationResult;
                $previousHash = $invoice->hash;
            }

            $seriesResults[] = new SeriesResult(series: $series, results: $results);
        }

        return $seriesResults;
    }

    /**
     * Build the signature string for a document.
     * Format: InvoiceDate;SystemEntryDate;InvoiceNo;GrossTotal;PreviousHash
     */
    public function buildSignatureString(
        string $invoiceDate,
        string $systemEntryDate,
        string $invoiceNo,
        string $grossTotal,
        string $previousHash
    ): string {
        return implode(';', [
            $invoiceDate,
            $systemEntryDate,
            $invoiceNo,
            $grossTotal,
            $previousHash,
        ]);
    }

    /**
     * Group invoices by their series.
     *
     * @param InvoiceData[] $invoices
     * @return array<string, InvoiceData[]>
     */
    private function groupBySeries(array $invoices): array
    {
        $groups = [];
        foreach ($invoices as $invoice) {
            $groups[$invoice->series][] = $invoice;
        }
        return $groups;
    }

    /**
     * Load and validate the RSA public key.
     *
     * @throws \InvalidArgumentException
     */
    private function loadPublicKey(string $publicKeyPem): \OpenSSLAsymmetricKey
    {
        $key = openssl_pkey_get_public($publicKeyPem);
        if ($key === false) {
            throw new \InvalidArgumentException(
                'Chave pública RSA inválida. Certifique-se de que o ficheiro está no formato PEM correto.'
            );
        }

        $keyDetails = openssl_pkey_get_details($key);
        if ($keyDetails === false || $keyDetails['type'] !== OPENSSL_KEYTYPE_RSA) {
            throw new \InvalidArgumentException(
                'A chave fornecida não é uma chave RSA válida.'
            );
        }

        return $key;
    }

    /**
     * Verify a single document's hash.
     */
    private function verifyDocument(
        InvoiceData $invoice,
        string $signatureString,
        ?\OpenSSLAsymmetricKey $publicKey
    ): VerificationResult {
        if ($publicKey === null) {
            // No public key provided: we can only report that chain data is reconstructed
            // but cannot cryptographically verify the RSA signature
            return new VerificationResult(
                invoice: $invoice,
                status: VerificationStatus::NotVerifiable,
                message: 'Cadeia reconstruída mas não verificada criptograficamente — nenhuma chave pública fornecida.',
                signatureString: $signatureString,
                chainValid: true,
            );
        }

        // Decode the Base64 hash
        $signature = base64_decode($invoice->hash, true);
        if ($signature === false) {
            return new VerificationResult(
                invoice: $invoice,
                status: VerificationStatus::Invalid,
                message: 'Hash inválido — não é um valor Base64 válido.',
                signatureString: $signatureString,
                chainValid: false,
            );
        }

        // Try SHA-256 first (newer versions), then SHA-1 (older versions)
        $verifiedWith = null;
        foreach ([OPENSSL_ALGO_SHA256, OPENSSL_ALGO_SHA1] as $algorithm) {
            $result = openssl_verify($signatureString, $signature, $publicKey, $algorithm);
            if ($result === 1) {
                $verifiedWith = $algorithm === OPENSSL_ALGO_SHA256 ? 'SHA-256' : 'SHA-1';
                break;
            }
        }

        if ($verifiedWith !== null) {
            return new VerificationResult(
                invoice: $invoice,
                status: VerificationStatus::Valid,
                message: "Assinatura RSA válida (algoritmo: {$verifiedWith}).",
                signatureString: $signatureString,
                chainValid: true,
            );
        }

        return new VerificationResult(
            invoice: $invoice,
            status: VerificationStatus::Invalid,
            message: 'Assinatura RSA inválida — o hash não corresponde à chave pública fornecida.',
            signatureString: $signatureString,
            chainValid: false,
        );
    }
}
