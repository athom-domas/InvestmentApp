<?php

return [
    'model_version' => env('SCORING_MODEL_VERSION', '1.0.0'),

    'default_universe' => 'ALL',

    'scheduled_run_enabled'  => env('SCORING_SCHEDULE_ENABLED', true),
    'scheduled_run_time'     => env('SCORING_SCHEDULE_TIME', '06:00'),
    'scheduled_universe'     => env('SCORING_SCHEDULE_UNIVERSE', 'ALL'),

    'factor_weights' => [
        'quality'            => 0.25,
        'value'              => 0.20,
        'momentum'           => 0.20,
        'growth'             => 0.15,
        'financial_strength' => 0.10,
        'risk'               => 0.10,
    ],

    'minimum_price_history_days' => 20,

    'risk_penalty_settings' => [
        'missing_fundamentals_penalty' => 0.30,
        'insufficient_history_penalty' => 0.20,
    ],

    'liquidity_settings' => [
        'min_avg_volume' => 100_000,
    ],

    'score_scale' => [
        'min' => 0,
        'max' => 100,
    ],
];
