<?php

namespace App\Services\Scoring\DTO;

readonly class FactorResult
{
    public function __construct(
        public string $factor_code,
        public ?float $raw_value,
        public float  $confidence,
        public array  $components = [],
    ) {}
}
