<?php

declare(strict_types=1);

namespace SmartAdd\Util;

final class ArrayUtil
{
    public static function deepFindKey(array $data, string $key): mixed
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = self::deepFindKey($value, $key);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
