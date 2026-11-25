<?php

if (! function_exists('rupiah')) {
    /** Format angka sebagai Rupiah: Rp 1.250.000 */
    function rupiah(float|int|string|null $value, bool $signed = false): string
    {
        $value = (float) $value;
        $sign = $value < 0 ? '-' : ($signed && $value > 0 ? '+' : '');

        return $sign.'Rp '.number_format(abs(round($value)), 0, ',', '.');
    }
}

if (! function_exists('aud')) {
    /** Format angka sebagai Dolar Australia: A$ 1.250,50 */
    function aud(float|int|string|null $value, bool $signed = false): string
    {
        $value = (float) $value;
        $sign = $value < 0 ? '-' : ($signed && $value > 0 ? '+' : '');

        return $sign.'A$ '.number_format(abs($value), 2, ',', '.');
    }
}

if (! function_exists('money')) {
    /** Format sesuai mata uang: Rp 1.250.000 · US$ 1.250,50 · ¥ 12.000 */
    function money(float|int|string|null $value, string $currency = 'IDR', bool $signed = false): string
    {
        $currency = strtoupper($currency);
        if ($currency === 'IDR') {
            return rupiah($value, $signed);
        }
        $value = (float) $value;
        $sign = $value < 0 ? '-' : ($signed && $value > 0 ? '+' : '');
        $dec = \App\Support\Currency::decimals($currency);

        return $sign.\App\Support\Currency::symbol($currency).' '.number_format(abs($value), $dec, ',', '.');
    }
}

if (! function_exists('rupiah_short')) {
    /** Format ringkas: Rp 1,2 jt / Rp 3,4 M */
    function rupiah_short(float|int|string|null $value): string
    {
        $value = (float) $value;
        $abs = abs($value);
        $sign = $value < 0 ? '-' : '';

        return match (true) {
            $abs >= 1_000_000_000 => $sign.'Rp '.rtrim(rtrim(number_format($abs / 1_000_000_000, 2, ',', '.'), '0'), ',').' M',
            $abs >= 1_000_000 => $sign.'Rp '.rtrim(rtrim(number_format($abs / 1_000_000, 1, ',', '.'), '0'), ',').' jt',
            $abs >= 1_000 => $sign.'Rp '.rtrim(rtrim(number_format($abs / 1_000, 1, ',', '.'), '0'), ',').' rb',
            default => rupiah($value),
        };
    }
}

if (! function_exists('rate_fmt')) {
    /** Format kurs (Rupiah per 1 unit). Kurs kecil (mis. VND) ditampilkan lebih banyak desimal. */
    function rate_fmt(float|int|string|null $value): string
    {
        $v = (float) $value;
        $dec = match (true) {
            $v == 0.0 => 2,
            $v < 0.01 => 6,
            $v < 1 => 4,
            $v < 100 => 3,
            default => 2,
        };

        return number_format($v, $dec, ',', '.');
    }
}

if (! function_exists('parse_amount')) {
    /**
     * Ubah input angka gaya Indonesia/Inggris menjadi float.
     * "1.500.000" -> 1500000, "150,75" -> 150.75, "1,234.50" -> 1234.5
     */
    function parse_amount(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }
        $s = preg_replace('/[^\d.,\-]/', '', (string) $raw);
        if ($s === '' || $s === '-') {
            return null;
        }
        $lastDot = strrpos($s, '.');
        $lastComma = strrpos($s, ',');
        if ($lastDot !== false && $lastComma !== false) {
            // Pemisah desimal = yang muncul paling akhir
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($lastComma !== false) {
            // Hanya satu koma: desimal, kecuali tepat 3 digit di belakang (pemisah ribuan gaya Inggris)
            $after = strlen($s) - $lastComma - 1;
            $intPart = ltrim(substr($s, 0, $lastComma), '-');
            $isDecimal = substr_count($s, ',') === 1 && ($after !== 3 || $intPart === '0' || $intPart === '');
            $s = $isDecimal ? str_replace(',', '.', $s) : str_replace(',', '', $s);
        } elseif ($lastDot !== false) {
            // Hanya titik: pemisah ribuan bila tepat 3 digit di belakang atau lebih dari satu titik
            $intPart = ltrim(substr($s, 0, $lastDot), '-');
            if (substr_count($s, '.') > 1 || ((strlen($s) - $lastDot - 1) === 3 && $intPart !== '0' && $intPart !== '')) {
                $s = str_replace('.', '', $s);
            }
        }

        return is_numeric($s) ? (float) $s : null;
    }
}
