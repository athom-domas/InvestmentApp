<?php

namespace App\Services\Scoring;

use App\Models\Security;
use App\Services\Scoring\DTO\FactorResult;

class ExplanationBuilder
{
    private const LABELS = [
        'quality'            => 'profitability',
        'value'              => 'valuation',
        'growth'             => 'growth trajectory',
        'momentum'           => 'price momentum',
        'financial_strength' => 'financial solidity',
        'risk'               => 'risk profile',
    ];

    /**
     * @param  array<string, float>        $normalized    factor_code => 0-100
     * @param  array<string, FactorResult> $factorResults
     * @return array{summary: string, risks: string}
     */
    public function build(Security $security, array $normalized, array $factorResults): array
    {
        arsort($normalized);
        $topCodes    = array_slice(array_keys($normalized), 0, 2);
        $bottomCodes = array_slice(array_keys(array_reverse($normalized, true)), 0, 2);

        $topLabels = array_map(fn ($k) => self::LABELS[$k] ?? $k, $topCodes);
        $summary   = sprintf(
            '%s emerged from the screening with notable characteristics in %s.',
            $security->ticker,
            implode(' and ', $topLabels)
        );

        $risks = [];
        foreach ($bottomCodes as $code) {
            $label = self::LABELS[$code] ?? $code;
            $score = $normalized[$code] ?? 0;
            if ($score < 40) {
                $risks[] = "- Weak {$label}: flagged for further review.";
            }
        }
        foreach ($factorResults as $code => $fr) {
            if ($fr->confidence < 0.5 && $fr->confidence >= 0) {
                $label   = self::LABELS[$code] ?? $code;
                $risks[] = "- Limited data available for {$label} assessment.";
            }
        }

        return [
            'summary' => $summary,
            'risks'   => implode("\n", array_unique($risks)) ?: 'No significant risks identified in the available data.',
        ];
    }
}
