<?php

namespace App\DTOs;

class SeriesResult
{
    /** @var VerificationResult[] */
    public readonly array $results;

    public readonly int $totalDocuments;
    public readonly int $validDocuments;
    public readonly int $invalidDocuments;
    public readonly int $notVerifiableDocuments;

    /**
     * @param VerificationResult[] $results
     */
    public function __construct(
        public readonly string $series,
        array $results,
    ) {
        $this->results = $results;
        $this->totalDocuments = count($results);
        $this->validDocuments = count(array_filter($results, fn ($r) => $r->status === VerificationStatus::Valid));
        $this->invalidDocuments = count(array_filter($results, fn ($r) => $r->status === VerificationStatus::Invalid));
        $this->notVerifiableDocuments = count(array_filter($results, fn ($r) => $r->status === VerificationStatus::NotVerifiable));
    }
}
