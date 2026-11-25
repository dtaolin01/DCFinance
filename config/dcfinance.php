<?php

return [
    // Jumlah akun maksimal. Pendaftaran tertutup otomatis setelah penuh.
    'max_users' => (int) env('DC_MAX_USERS', 2),

    // Kurs cadangan AUD -> IDR bila semua sumber online gagal & belum pernah tersimpan.
    'default_aud_rate' => (float) env('DC_DEFAULT_AUD_RATE', 10500),

    // Lama kurs online disimpan di cache (menit).
    'rate_cache_minutes' => (int) env('DC_RATE_CACHE_MINUTES', 180),

    'currencies' => [
        'IDR' => ['label' => 'Rupiah', 'symbol' => 'Rp', 'flag' => '🇮🇩', 'decimals' => 0],
        'AUD' => ['label' => 'Dolar Australia', 'symbol' => 'A$', 'flag' => '🇦🇺', 'decimals' => 2],
    ],

    'methods' => [
        'cash' => ['label' => 'Cash', 'icon' => '💵'],
        'bank' => ['label' => 'Rekening Bank', 'icon' => '🏦'],
        'ewallet' => ['label' => 'E-Wallet', 'icon' => '📱'],
        'other' => ['label' => 'Lainnya', 'icon' => '🪙'],
    ],

    'colors' => ['#ec4899', '#f472b6', '#db2777', '#e879f9', '#fb7185', '#a855f7', '#f59e0b', '#14b8a6'],

    'goal_emojis' => ['🎯', '✈️', '🏠', '💍', '🚗', '📱', '💻', '🎓', '🏖️', '🐨', '🎁', '💒', '🛡️', '👶', '🛍️', '🍜'],
];
