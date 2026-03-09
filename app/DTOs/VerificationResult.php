<?php

namespace App\DTOs;

enum VerificationStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case NotVerifiable = 'not_verifiable';
}

class VerificationResult
{
    public function __construct(
        public readonly InvoiceData $invoice,
        public readonly VerificationStatus $status,
        public readonly string $message,
        public readonly ?string $signatureString = null,
        public readonly bool $chainValid = false,
    ) {}
}
