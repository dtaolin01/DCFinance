<?php

/*
| Tema warna yang bisa dipilih di Profil.
| Setiap tema: [label, warna utama 50..700, rgb untuk bayangan, warna teks & garis, latar].
*/

$make = fn (string $label, array $a, string $rgb, array $ink, string $line, array $bg) => [
    'label' => $label,
    'swatch' => $a[6],
    'vars' => [
        'acc-50' => $a[0], 'acc-100' => $a[1], 'acc-150' => $a[2], 'acc-200' => $a[3], 'acc-300' => $a[4],
        'acc-400' => $a[5], 'acc-500' => $a[6], 'acc-600' => $a[7], 'acc-700' => $a[8],
        'acc-rgb' => $rgb,
        'ink' => $ink[0], 'ink-2' => $ink[1], 'ink-3' => $ink[2],
        'line' => $line,
        'bg' => $bg[0], 'bg-1' => $bg[1], 'bg-2' => $bg[2],
    ],
];

return [
    'pink' => $make('Pink', ['#fff5f9', '#ffe4ef', '#ffd6e7', '#fbcfe8', '#f9a8d4', '#f472b6', '#ec4899', '#db2777', '#be185d'], '219, 39, 119',
        ['#3b2a37', '#6b5466', '#a08a9b'], '#f6e3ec', ['#fffbfd', '#ffe4ef', '#fff0f6']),

    'black' => $make('Hitam', ['#f7f7f7', '#ededed', '#e3e3e3', '#d6d6d6', '#a3a3a3', '#404040', '#171717', '#0a0a0a', '#000000'], '0, 0, 0',
        ['#171717', '#525252', '#8a8a8a'], '#ebebeb', ['#fcfcfc', '#efefef', '#f6f6f6']),

    'blue' => $make('Biru', ['#f3f7ff', '#e0ebff', '#d1e1ff', '#bfd4fe', '#93b4fd', '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8'], '37, 99, 235',
        ['#1e293b', '#475569', '#8b98ab'], '#e3eaf5', ['#fafcff', '#e0ebff', '#eef4ff']),

    'purple' => $make('Ungu', ['#faf6ff', '#f1e8ff', '#e9dcff', '#ddd0fe', '#c4b5fd', '#a78bfa', '#8b5cf6', '#7c3aed', '#6d28d9'], '124, 58, 237',
        ['#2e1f47', '#5b4b75', '#9888b0'], '#ece6f7', ['#fcfaff', '#f1e8ff', '#f7f2ff']),

    'green' => $make('Hijau', ['#f2fbf6', '#dcf5e7', '#cdefdc', '#b6e7cb', '#86d5a9', '#34c27a', '#16a34a', '#15803d', '#166534'], '21, 128, 61',
        ['#1a2e22', '#4a6355', '#87a194'], '#e1efe7', ['#fafdfb', '#dcf5e7', '#eefaf3']),

    'teal' => $make('Tosca', ['#f0fbfa', '#d5f5f1', '#c2efe9', '#a7e6de', '#5eead4', '#2dd4bf', '#14b8a6', '#0d9488', '#0f766e'], '13, 148, 136',
        ['#16302d', '#46625e', '#83a09c'], '#dff0ed', ['#f9fdfc', '#d5f5f1', '#ecfaf8']),

    'orange' => $make('Oranye', ['#fff8f1', '#ffedd5', '#ffe4c4', '#fed7aa', '#fdba74', '#fb923c', '#f97316', '#ea580c', '#c2410c'], '234, 88, 12',
        ['#3a2516', '#6b5040', '#a8907f'], '#f6e8dc', ['#fffcf8', '#ffedd5', '#fff5ea']),

    'red' => $make('Merah', ['#fff5f5', '#ffe3e3', '#ffd5d5', '#fecaca', '#fca5a5', '#f87171', '#ef4444', '#dc2626', '#b91c1c'], '220, 38, 38',
        ['#3b2222', '#6b4b4b', '#a68a8a'], '#f6e2e2', ['#fffbfb', '#ffe3e3', '#fff0f0']),
];
