<?php

namespace Tests\Unit;

use App\DTOs\InvoiceData;
use App\Services\SaftHashVerifierService;
use App\Services\SaftParserService;
use App\DTOs\VerificationStatus;
use Tests\TestCase;

class SaftVerificationTest extends TestCase
{
    private SaftParserService $parser;
    private SaftHashVerifierService $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SaftParserService();
        $this->verifier = new SaftHashVerifierService();
    }

    private function getTestSaftXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<AuditFile xmlns="urn:OECD:StandardAuditFile-Tax:PT_1.04_01">
  <Header>
    <AuditFileVersion>1.04_01</AuditFileVersion>
    <CompanyID>999999990</CompanyID>
    <TaxRegistrationNumber>999999990</TaxRegistrationNumber>
    <TaxAccountingBasis>F</TaxAccountingBasis>
    <CompanyName>Empresa Teste Lda</CompanyName>
    <FiscalYear>2024</FiscalYear>
    <StartDate>2024-01-01</StartDate>
    <EndDate>2024-12-31</EndDate>
    <CurrencyCode>EUR</CurrencyCode>
    <DateCreated>2024-12-31</DateCreated>
    <TaxEntity>Global</TaxEntity>
    <ProductCompanyTaxID>123456789</ProductCompanyTaxID>
    <SoftwareCertificateNumber>9999</SoftwareCertificateNumber>
    <ProductID>SoftwareTeste/SoftwareTeste</ProductID>
    <ProductVersion>1.0</ProductVersion>
  </Header>
  <SourceDocuments>
    <SalesInvoices>
      <NumberOfEntries>3</NumberOfEntries>
      <TotalDebit>0.00</TotalDebit>
      <TotalCredit>3690.00</TotalCredit>
      <Invoice>
        <InvoiceNo>FT A/1</InvoiceNo>
        <Hash>dGVzdGhhc2gx</Hash>
        <HashControl>1</HashControl>
        <Period>1</Period>
        <InvoiceDate>2024-01-15</InvoiceDate>
        <InvoiceType>FT</InvoiceType>
        <SystemEntryDate>2024-01-15T10:00:00</SystemEntryDate>
        <CustomerID>C001</CustomerID>
        <DocumentTotals>
          <TaxPayable>230.00</TaxPayable>
          <NetTotal>1000.00</NetTotal>
          <GrossTotal>1230.00</GrossTotal>
        </DocumentTotals>
      </Invoice>
      <Invoice>
        <InvoiceNo>FT A/2</InvoiceNo>
        <Hash>dGVzdGhhc2gy</Hash>
        <HashControl>1</HashControl>
        <Period>1</Period>
        <InvoiceDate>2024-01-20</InvoiceDate>
        <InvoiceType>FT</InvoiceType>
        <SystemEntryDate>2024-01-20T14:30:00</SystemEntryDate>
        <CustomerID>C001</CustomerID>
        <DocumentTotals>
          <TaxPayable>460.00</TaxPayable>
          <NetTotal>2000.00</NetTotal>
          <GrossTotal>2460.00</GrossTotal>
        </DocumentTotals>
      </Invoice>
      <Invoice>
        <InvoiceNo>FT B/1</InvoiceNo>
        <Hash>dGVzdGhhc2gz</Hash>
        <HashControl>1</HashControl>
        <Period>1</Period>
        <InvoiceDate>2024-01-25</InvoiceDate>
        <InvoiceType>FT</InvoiceType>
        <SystemEntryDate>2024-01-25T09:15:00</SystemEntryDate>
        <CustomerID>C002</CustomerID>
        <DocumentTotals>
          <TaxPayable>0.00</TaxPayable>
          <NetTotal>0.00</NetTotal>
          <GrossTotal>0.00</GrossTotal>
        </DocumentTotals>
      </Invoice>
    </SalesInvoices>
  </SourceDocuments>
</AuditFile>
XML;
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_parses_saft_header(): void
    {
        $header = $this->parser->parseHeader($this->getTestSaftXml());

        $this->assertEquals('Empresa Teste Lda', $header->companyName);
        $this->assertEquals('999999990', $header->taxRegistrationNumber);
        $this->assertEquals('2024', $header->fiscalYear);
        $this->assertEquals('2024-01-01', $header->startDate);
        $this->assertEquals('2024-12-31', $header->endDate);
        $this->assertEquals('9999', $header->softwareCertificateNumber);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_parses_invoices_from_saft(): void
    {
        $invoices = $this->parser->parseInvoices($this->getTestSaftXml());

        $this->assertCount(3, $invoices);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_extracts_series_and_sequence(): void
    {
        $invoices = $this->parser->parseInvoices($this->getTestSaftXml());

        $invoiceA1 = $invoices[0];
        $this->assertEquals('FT A/1', $invoiceA1->invoiceNo);
        $this->assertEquals('A', $invoiceA1->series);
        $this->assertEquals(1, $invoiceA1->sequentialNumber);

        $invoiceA2 = $invoices[1];
        $this->assertEquals('FT A/2', $invoiceA2->invoiceNo);
        $this->assertEquals('A', $invoiceA2->series);
        $this->assertEquals(2, $invoiceA2->sequentialNumber);

        $invoiceB1 = $invoices[2];
        $this->assertEquals('FT B/1', $invoiceB1->invoiceNo);
        $this->assertEquals('B', $invoiceB1->series);
        $this->assertEquals(1, $invoiceB1->sequentialNumber);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_marks_first_document_as_not_verifiable(): void
    {
        $invoices = $this->parser->parseInvoices($this->getTestSaftXml());
        $seriesResults = $this->verifier->verify($invoices);

        // Find series A
        $seriesA = null;
        foreach ($seriesResults as $s) {
            if ($s->series === 'A') {
                $seriesA = $s;
                break;
            }
        }

        $this->assertNotNull($seriesA);
        $firstDoc = $seriesA->results[0];
        $this->assertEquals(VerificationStatus::NotVerifiable, $firstDoc->status);
        $this->assertEquals('FT A/1', $firstDoc->invoice->invoiceNo);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_groups_invoices_by_series(): void
    {
        $invoices = $this->parser->parseInvoices($this->getTestSaftXml());
        $seriesResults = $this->verifier->verify($invoices);

        $this->assertCount(2, $seriesResults);

        $seriesNames = array_map(fn ($s) => $s->series, $seriesResults);
        $this->assertContains('A', $seriesNames);
        $this->assertContains('B', $seriesNames);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_builds_correct_signature_string_for_second_document(): void
    {
        $invoices = $this->parser->parseInvoices($this->getTestSaftXml());
        $seriesResults = $this->verifier->verify($invoices);

        $seriesA = null;
        foreach ($seriesResults as $s) {
            if ($s->series === 'A') {
                $seriesA = $s;
                break;
            }
        }

        $this->assertNotNull($seriesA);
        $secondDoc = $seriesA->results[1];

        // The signature string should use the hash of FT A/1
        $this->assertEquals(
            '2024-01-20;2024-01-20T14:30:00;FT A/2;2460.00;dGVzdGhhc2gx',
            $secondDoc->signatureString
        );
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_builds_correct_signature_string_using_build_method(): void
    {
        $signatureString = $this->verifier->buildSignatureString(
            '2024-01-20',
            '2024-01-20T14:30:00',
            'FT A/2',
            '2460.00',
            'previousHash123'
        );

        $this->assertEquals(
            '2024-01-20;2024-01-20T14:30:00;FT A/2;2460.00;previousHash123',
            $signatureString
        );
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_invalid_xml(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->parser->parseHeader('this is not xml');
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_wrong_namespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $xml = '<?xml version="1.0"?><AuditFile xmlns="urn:WRONG:NAMESPACE"><Header/></AuditFile>';
        $this->parser->parseHeader($xml);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_no_sales_invoices(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<AuditFile xmlns="urn:OECD:StandardAuditFile-Tax:PT_1.04_01">
  <Header>
    <CompanyName>Test</CompanyName>
    <TaxRegistrationNumber>123456789</TaxRegistrationNumber>
    <FiscalYear>2024</FiscalYear>
    <StartDate>2024-01-01</StartDate>
    <EndDate>2024-12-31</EndDate>
    <CurrencyCode>EUR</CurrencyCode>
    <SoftwareCertificateNumber>1</SoftwareCertificateNumber>
    <CompanyID>123</CompanyID>
  </Header>
  <SourceDocuments>
  </SourceDocuments>
</AuditFile>
XML;
        $this->parser->parseInvoices($xml);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_formats_gross_total_to_two_decimal_places(): void
    {
        $invoices = $this->parser->parseInvoices($this->getTestSaftXml());

        $invoiceA1 = $invoices[0];
        $this->assertEquals('1230.00', $invoiceA1->grossTotal);
    }

        #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_empty_invoice_list(): void
    {
        $seriesResults = $this->verifier->verify([]);
        $this->assertCount(0, $seriesResults);
    }
}
