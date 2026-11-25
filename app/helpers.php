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
    /** Format sesuai mata uang (IDR / AUD). */
    function money(float|int|string|null $value, string $currency = 'IDR', bool $signed = false): string
    {
        return strtoupper($currency) === 'AUD' ? aud($value, $signed) : rupiah($value, $signed);
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
    /** Format kurs: 10.512,35 */
    function rate_fmt(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '.');
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
            // Hanya koma: desimal bila 1-2 digit di belakang, selain itu pemisah ribuan
            $s = (strlen($s) - $lastComma - 1) <= 2 && substr_count($s, ',') === 1
                ? str_replace(',', '.', $s)
                : str_replace(',', '', $s);
        } elseif ($lastDot !== false) {
            // Hanya titik: pemisah ribuan bila tepat 3 digit di belakang atau lebih dari satu titik
            if (substr_count($s, '.') > 1 || (strlen($s) - $lastDot - 1) === 3) {
                $s = str_replace('.', '', $s);
            }
        }

        return is_numeric($s) ? (float) $s : null;
    }
}
