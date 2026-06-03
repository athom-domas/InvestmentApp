<?php

namespace Tests\Unit\Scoring;

use App\Models\Security;
use App\Services\Scoring\DTO\FactorResult;
use App\Services\Scoring\ExplanationBuilder;
use Tests\TestCase;

class ExplanationBuilderTest extends TestCase
{
    private ExplanationBuilder $builder;

    private const FACTOR_CODES = ['quality', 'value', 'growth', 'momentum', 'financial_strength', 'risk'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ExplanationBuilder();
    }

    private function makeSecurity(string $ticker): Security
    {
        $s         = new Security();
        $s->ticker = $ticker;
        return $s;
    }

    /** @param array<string, float> $overrides */
    private function normalized(array $overrides = []): array
    {
        return array_merge([
            'quality'            => 75.0,
            'value'              => 65.0,
            'growth'             => 60.0,
            'momentum'           => 55.0,
            'financial_strength' => 50.0,
            'risk'               => 40.0,
        ], $overrides);
    }

    /** @param array<string, float> $confidenceOverrides */
    private function factorResults(array $confidenceOverrides = []): array
    {
        $results = [];
        foreach (self::FACTOR_CODES as $code) {
            $results[$code] = new FactorResult(
                factor_code: $code,
                raw_value:   0.5,
                confidence:  $confidenceOverrides[$code] ?? 1.0,
            );
        }
        return $results;
    }

    public function test_returns_summary_reasons_risks_keys(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('AAPL'),
            $this->normalized(),
            $this->factorResults(),
        );

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('reasons', $result);
        $this->assertArrayHasKey('risks', $result);
        $this->assertIsString($result['summary']);
        $this->assertIsArray($result['reasons']);
        $this->assertIsArray($result['risks']);
    }

    public function test_reasons_has_at_least_three_items(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(),
            $this->factorResults(),
        );

        $this->assertGreaterThanOrEqual(3, count($result['reasons']));
    }

    public function test_reasons_has_at_most_five_items(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(['quality' => 95, 'value' => 90, 'growth' => 85, 'momentum' => 80, 'financial_strength' => 75, 'risk' => 70]),
            $this->factorResults(),
        );

        $this->assertLessThanOrEqual(5, count($result['reasons']));
    }

    public function test_risks_has_at_least_one_item(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(),
            $this->factorResults(),
        );

        $this->assertGreaterThanOrEqual(1, count($result['risks']));
    }

    public function test_risks_has_at_most_five_items(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(['quality' => 10, 'value' => 10, 'growth' => 10, 'momentum' => 10, 'financial_strength' => 10, 'risk' => 10]),
            $this->factorResults(array_fill_keys(self::FACTOR_CODES, 0.2)),
        );

        $this->assertLessThanOrEqual(5, count($result['risks']));
    }

    public function test_weak_value_score_adds_valuation_risk(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(['value' => 20.0]),
            $this->factorResults(),
        );

        $allRisks = strtolower(implode(' ', $result['risks']));
        $this->assertStringContainsString('valutazione', $allRisks);
    }

    public function test_low_confidence_adds_data_unavailable_risk(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(),
            $this->factorResults(['quality' => 0.3]),
        );

        $allRisks = strtolower(implode(' ', $result['risks']));
        $this->assertStringContainsString('qualità aziendale', $allRisks);
    }

    public function test_summary_contains_ticker(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('MSFT'),
            $this->normalized(),
            $this->factorResults(),
        );

        $this->assertStringContainsString('MSFT', $result['summary']);
    }

    public function test_summary_contains_screening_language(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TST'),
            $this->normalized(),
            $this->factorResults(),
        );

        $this->assertStringContainsString('screening', strtolower($result['summary']));
    }

    public function test_no_forbidden_words_in_output(): void
    {
        $forbidden = [
            'compra', 'vendi', 'consiglio di investimento',
            'garantito', 'garantita', 'sicuro', 'sicura',
            'rendimento certo', 'migliore azione',
        ];

        $result = $this->builder->build(
            $this->makeSecurity('TST'),
            $this->normalized(),
            $this->factorResults(),
        );

        $allText = strtolower(
            $result['summary']
            . ' ' . implode(' ', $result['reasons'])
            . ' ' . implode(' ', $result['risks'])
        );

        foreach ($forbidden as $word) {
            $this->assertStringNotContainsString(
                $word,
                $allText,
                "Parola vietata '{$word}' trovata nell'output."
            );
        }
    }

    public function test_all_scores_high_produces_no_placeholder_risk(): void
    {
        $result = $this->builder->build(
            $this->makeSecurity('TEST'),
            $this->normalized(['quality' => 90, 'value' => 85, 'growth' => 80, 'momentum' => 75, 'financial_strength' => 70, 'risk' => 65]),
            $this->factorResults(),
        );

        $this->assertSame(['Nessun rischio significativo rilevato dai dati disponibili.'], $result['risks']);
    }
}
