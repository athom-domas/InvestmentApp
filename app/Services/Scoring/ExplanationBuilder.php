<?php

namespace App\Services\Scoring;

use App\Models\Security;
use App\Services\Scoring\DTO\FactorResult;

class ExplanationBuilder
{
    private const LABELS = [
        'quality'            => 'qualità aziendale',
        'value'              => 'valutazione',
        'growth'             => 'crescita',
        'momentum'           => 'momentum di prezzo',
        'financial_strength' => 'solidità finanziaria',
        'risk'               => 'profilo di rischio',
    ];

    private const REASON_TEMPLATES = [
        'quality'            => 'Qualità aziendale superiore alla media del settore.',
        'value'              => 'Valutazione interessante rispetto ai fondamentali.',
        'growth'             => 'Crescita dei ricavi positiva nel periodo analizzato.',
        'momentum'           => 'Momentum di prezzo positivo negli ultimi mesi.',
        'financial_strength' => 'Solidità finanziaria con debito gestibile.',
        'risk'               => 'Profilo di rischio nella media o inferiore.',
    ];

    private const RISK_TEMPLATES = [
        'quality'            => 'Margini di profitto da monitorare attentamente.',
        'value'              => 'Valutazione elevata rispetto ai fondamentali.',
        'growth'             => 'Crescita debole o in rallentamento.',
        'momentum'           => 'Momentum di prezzo debole o in fase di correzione.',
        'financial_strength' => 'Esposizione al debito da valutare con attenzione.',
        'risk'               => 'Volatilità superiore alla media storica del titolo.',
    ];

    /**
     * @param  array<string, float>        $normalized    factor_code => 0-100
     * @param  array<string, FactorResult> $factorResults keyed by factor_code
     * @return array{summary: string, reasons: list<string>, risks: list<string>}
     */
    public function build(Security $security, array $normalized, array $factorResults): array
    {
        $byScore = $normalized;
        arsort($byScore);

        $reasons = $this->buildReasons($byScore);
        $risks   = $this->buildRisks($byScore, $factorResults);
        $summary = $this->buildSummary($security->ticker, $byScore, $risks);

        return compact('summary', 'reasons', 'risks');
    }

    /** @return list<string> */
    private function buildReasons(array $byScore): array
    {
        $reasons = [];

        foreach ($byScore as $code => $score) {
            if (count($reasons) >= 5) {
                break;
            }
            if ($score >= 55) {
                $reasons[] = self::REASON_TEMPLATES[$code]
                    ?? 'Caratteristiche positive rilevate per: ' . (self::LABELS[$code] ?? $code) . '.';
            }
        }

        // Fill to at least 3 using top factors regardless of score threshold
        foreach ($byScore as $code => $score) {
            if (count($reasons) >= 3) {
                break;
            }
            $candidate = self::REASON_TEMPLATES[$code]
                ?? 'Caratteristiche positive rilevate per: ' . (self::LABELS[$code] ?? $code) . '.';
            if (!in_array($candidate, $reasons, true)) {
                $reasons[] = $candidate;
            }
        }

        return $reasons;
    }

    /** @return list<string> */
    private function buildRisks(array $byScore, array $factorResults): array
    {
        $risks = [];

        // Weak factors (low scores) — ascending order
        $ascScore = array_reverse($byScore, true);
        foreach ($ascScore as $code => $score) {
            if (count($risks) >= 4) {
                break;
            }
            if ($score < 45) {
                $risks[] = self::RISK_TEMPLATES[$code]
                    ?? 'Fattore da approfondire: ' . (self::LABELS[$code] ?? $code) . '.';
            }
        }

        // Low-confidence factors (limited data available)
        foreach ($factorResults as $code => $fr) {
            if (count($risks) >= 5) {
                break;
            }
            if ($fr->confidence >= 0 && $fr->confidence < 0.5) {
                $label = self::LABELS[$code] ?? $code;
                $risk  = "Dato non disponibile o incompleto per: {$label}.";
                if (!in_array($risk, $risks, true)) {
                    $risks[] = $risk;
                }
            }
        }

        if (empty($risks)) {
            $risks[] = 'Nessun rischio significativo rilevato dai dati disponibili.';
        }

        return $risks;
    }

    private function buildSummary(string $ticker, array $byScore, array $risks): string
    {
        $topCodes  = array_slice(array_keys($byScore), 0, 2);
        $topLabels = array_map(fn ($k) => self::LABELS[$k] ?? $k, $topCodes);

        $positiveStr = implode(' e ', $topLabels);

        $hasRealRisk = count($risks) > 0
            && $risks[0] !== 'Nessun rischio significativo rilevato dai dati disponibili.';

        if ($hasRealRisk) {
            $bottomCode  = (string) array_key_last($byScore);
            $bottomLabel = self::LABELS[$bottomCode] ?? $bottomCode;

            return "{$ticker} è emersa dallo screening per {$positiveStr}, "
                . "ma presenta {$bottomLabel} da analizzare con attenzione.";
        }

        return "{$ticker} è emersa dallo screening con caratteristiche interessanti in {$positiveStr}.";
    }
}
