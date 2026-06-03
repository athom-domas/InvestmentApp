<?php

namespace Tests\Unit\Scoring;

use App\Services\Scoring\Normalizer;
use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    private Normalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new Normalizer();
    }

    public function test_null_values_receive_zero(): void
    {
        $result = $this->normalizer->normalize([1 => null, 2 => 50.0, 3 => 100.0]);
        $this->assertSame(0.0, $result[1]);
    }

    public function test_all_null_returns_zeros(): void
    {
        $result = $this->normalizer->normalize([1 => null, 2 => null]);
        $this->assertSame([1 => 0.0, 2 => 0.0], $result);
    }

    public function test_all_identical_values_return_50(): void
    {
        $result = $this->normalizer->normalize([1 => 10.0, 2 => 10.0, 3 => 10.0]);
        $this->assertSame(50.0, $result[1]);
        $this->assertSame(50.0, $result[3]);
    }

    public function test_scores_bounded_0_to_100(): void
    {
        $values = array_combine(range(1, 10), [5, 3, 99, 1, 50, 2, 80, 10, 20, 0.1]);
        $result = $this->normalizer->normalize($values);
        foreach ($result as $score) {
            $this->assertGreaterThanOrEqual(0.0, $score);
            $this->assertLessThanOrEqual(100.0, $score);
        }
    }

    public function test_highest_value_gets_100_lowest_gets_0(): void
    {
        $result = $this->normalizer->normalize([1 => 10.0, 2 => 50.0, 3 => 100.0]);
        $this->assertSame(100.0, $result[3]);
        $this->assertSame(0.0, $result[1]);
    }

    public function test_lower_is_better_inverts_ranking(): void
    {
        // id=1 has value 10 (lower = better), id=2 has 100
        $result = $this->normalizer->normalize([1 => 10.0, 2 => 100.0], higherIsBetter: false);
        $this->assertGreaterThan($result[2], $result[1]);
    }

    public function test_single_non_null_value_gets_50(): void
    {
        $result = $this->normalizer->normalize([1 => 42.0]);
        $this->assertSame(50.0, $result[1]);
    }

    public function test_empty_input_returns_empty(): void
    {
        $result = $this->normalizer->normalize([]);
        $this->assertSame([], $result);
    }

    public function test_null_mixed_with_valid_values(): void
    {
        $result = $this->normalizer->normalize([1 => null, 2 => 10.0, 3 => 20.0]);
        $this->assertSame(0.0, $result[1]);
        $this->assertSame(0.0, $result[2]);
        $this->assertSame(100.0, $result[3]);
    }
}
