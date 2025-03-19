<?php

namespace Cocoon\View\Extensions;

use Cocoon\View\AbstractExtension;

class ArrayExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            'sort' => function ($array, $key = null) {
                if ($key) {
                    usort($array, function ($a, $b) use ($key) {
                        return $a[$key] <=> $b[$key];
                    });
                } else {
                    sort($array);
                }
                return $array;
            },
            'filter' => function ($array, $key, $value) {
                return array_filter($array, function ($item) use ($key, $value) {
                    return $item[$key] === $value;
                });
            },
            'map' => function ($array, $key) {
                return array_column($array, $key);
            },
            'unique' => function ($array, $key = null) {
                if ($key) {
                    $array = array_column($array, null, $key);
                    return array_values($array);
                }
                return array_unique($array);
            },
            'first' => function ($array) {
                return reset($array);
            },
            'last' => function ($array) {
                return end($array);
            }
        ];
    }

    public function getFunctions(): array
    {
        return [
            'array_contains' => function ($array, $value) {
                if (is_array($value)) {
                    foreach ($array as $item) {
                        if (array_intersect_assoc($item, $value) === $value) {
                            return true;
                        }
                    }
                    return false;
                }
                return in_array($value, $array);
            },
            'array_keys' => function ($array) {
                return array_keys($array);
            },
            'array_values' => function ($array) {
                return array_values($array);
            },
            'array_sum' => function ($array, $key = null) {
                if ($key) {
                    return array_sum(array_column($array, $key));
                }
                return array_sum($array);
            },
            'array_avg' => function ($array, $key = null) {
                if (empty($array)) {
                    return 0;
                }
                if ($key) {
                    $values = array_column($array, $key);
                } else {
                    $values = $array;
                }
                return array_sum($values) / count($values);
            }
        ];
    }

    public function getDirectives(): array
    {
        return [];
    }
}
