<?php

namespace App\Services\Scoring\DTO;

readonly class SecurityScoreResult
{
    /**
     * @param  FactorResult[]           $factor_results  keyed by factor_code
     * @param  array<string, float>     $normalized      factor_code => 0-100
     */
    public function __construct(
        public int    $security_id,
        public float  $final_score,
        public array  $factor_results,
        public array  $normalized,
        public string $summary,
        public array  $reasons,
        public array  $risks,
    ) {}
}
