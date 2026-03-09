<?php

namespace App\DTOs;

class SaftHeader
{
    public function __construct(
        public readonly string $companyName,
        public readonly string $companyId,
        public readonly string $taxRegistrationNumber,
        public readonly string $fiscalYear,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $currencyCode,
        public readonly string $softwareCertificateNumber,
    ) {}
}
