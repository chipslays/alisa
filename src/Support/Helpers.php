<?php

namespace Alisa\Support\Helpers;

function plural(string|int|float $n, array $forms): string {
    return $n . ' ' . (is_float($n) ? $forms[1] : ($n % 10 == 1 && $n % 100 != 11 ? $forms[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $forms[1] : $forms[2])));
}

function array_sort_by_priority(array $array): array {
    ksort($array);

    return call_user_func_array('array_merge', $array);
}