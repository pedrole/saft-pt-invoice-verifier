<?php

namespace App\DTOs;

class InvoiceData
{
    public function __construct(
        public readonly string $invoiceNo,
        public readonly string $hash,
        public readonly string $hashControl,
        public readonly string $period,
        public readonly string $invoiceDate,
        public readonly string $invoiceType,
        public readonly string $systemEntryDate,
        public readonly string $grossTotal,
        public readonly string $series,
        public readonly int $sequentialNumber,
    ) {}
}
