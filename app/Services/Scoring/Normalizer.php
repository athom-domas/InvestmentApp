<?php

namespace App\Services\Scoring;

class Normalizer
{
    /**
     * Normalize values to [0, 100] via percentile ranking with winsorization.
     *
     * - null values → 0
     * - all-same values → 50
     * - winsorization at 5th/95th percentile when n ≥ 3
     *
     * @param  array<int|string, float|null>  $values  keyed by security id
     * @param  bool  $higherIsBetter
     * @return array<int|string, float>
     */
    public function normalize(array $values, bool $higherIsBetter = true): array
    {
        if (empty($values)) {
            return [];
        }

        $nullKeys    = array_keys(array_filter($values, fn ($v) => $v === null));
        $nonNullKeys = array_keys(array_filter($values, fn ($v) => $v !== null));
        $nonNullVals = array_map(fn ($k) => (float) $values[$k], $nonNullKeys);

        if (empty($nonNullKeys)) {
            return array_fill_keys(array_keys($values), 0.0);
        }

        $n = count($nonNullVals);

        // Single non-null value → 50
        if ($n === 1) {
            $result = array_fill_keys($nullKeys, 0.0);
            $result[$nonNullKeys[0]] = 50.0;
            return $result;
        }

        // Winsorize when n ≥ 3
        if ($n >= 3) {
            $sorted = $nonNullVals;
            sort($sorted);
            $lower = $sorted[(int) floor(0.05 * ($n - 1))];
            $upper = $sorted[(int) ceil(0.95 * ($n - 1))];
            $nonNullVals = array_map(fn ($v) => max($lower, min($upper, $v)), $nonNullVals);
        }

        $min = min($nonNullVals);
        $max = max($nonNullVals);

        // All identical after winsorization
        if ($min === $max) {
            $result = array_fill_keys($nullKeys, 0.0);
            foreach ($nonNullKeys as $k) {
                $result[$k] = 50.0;
            }
            return $result;
        }

        // Rank ascending; invert if lower is better
        $indexed = array_combine($nonNullKeys, $nonNullVals);
        if ($higherIsBetter) {
            asort($indexed);
        } else {
            arsort($indexed);
        }

        $result  = array_fill_keys($nullKeys, 0.0);
        $i       = 0;
        $nMinus1 = $n - 1;
        foreach ($indexed as $k => $v) {
            $result[$k] = round(($i / $nMinus1) * 100.0, 4);
            $i++;
        }

        return $result;
    }
}
