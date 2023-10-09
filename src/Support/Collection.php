<?php

namespace Alisa\Support;

class Collection
{
    /**
     * @param array $items
     */
    public function __construct(protected array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = $this->items;

        $findValuesByWildcard = function ($current, $segments) use (&$findValuesByWildcard) {
            $segment = array_shift($segments);

            if (isset($current[$segment])) {
                if (empty($segments)) {
                    return $current[$segment];
                } else {
                    return $findValuesByWildcard($current[$segment], $segments);
                }
            } elseif ($segment === '*') {
                $results = null;
                foreach ($current as $value) {
                    if (is_array($value)) {
                        $result = $findValuesByWildcard($value, $segments);
                        if ($result !== null) {
                            if ($results === null) {
                                $results = $result;
                            } else {
                                if (is_array($results)) {
                                    $results[] = $result;
                                } else {
                                    $tmp = $results;
                                    $results = [];
                                    $results[] = $tmp;
                                    $results[] = $result;
                                }
                            }
                        }
                    }
                }
                return !empty($results) ? $results : null;
            } else {
                return null;
            }
        };

        return $findValuesByWildcard($current, $segments) ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $segments = explode('.', $key);
        $current = &$this->items;

        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;

        return $this;
    }

    public function all(): array
    {
        return $this->items;
    }
}