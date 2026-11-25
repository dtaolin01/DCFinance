<?php

namespace App\Support;

class Currency
{
    public static function list(): array
    {
        return config('currencies.list', []);
    }

    public static function codes(): array
    {
        return array_keys(static::list());
    }

    public static function exists(?string $code): bool
    {
        return $code !== null && isset(static::list()[strtoupper($code)]);
    }

    public static function name(string $code): string
    {
        return static::list()[strtoupper($code)][0] ?? strtoupper($code);
    }

    public static function symbol(string $code): string
    {
        return static::list()[strtoupper($code)][1] ?? strtoupper($code);
    }

    public static function decimals(string $code): int
    {
        return (int) (static::list()[strtoupper($code)][2] ?? 2);
    }

    public static function popular(): array
    {
        return config('currencies.popular', ['IDR']);
    }

    /** Data ringkas untuk JavaScript: kode => [simbol, desimal, nama]. */
    public static function forJs(): array
    {
        $out = [];
        foreach (static::list() as $code => [$name, $symbol, $dec]) {
            $out[$code] = [$symbol, $dec, $name];
        }

        return $out;
    }

    /**
     * Pilihan untuk <select>: grup "Sering dipakai" (mata uang yang pernah dipakai + populer) lalu "Semua".
     *
     * @param  array<string>  $used
     */
    public static function grouped(array $used = [], bool $withIdr = true): array
    {
        $top = array_values(array_unique(array_merge($withIdr ? ['IDR'] : [], $used, static::popular())));
        if (! $withIdr) {
            $top = array_values(array_diff($top, ['IDR']));
        }
        $rest = array_values(array_diff(static::codes(), $top, $withIdr ? [] : ['IDR']));

        return ['Sering dipakai' => $top, 'Semua mata uang' => $rest];
    }
}
