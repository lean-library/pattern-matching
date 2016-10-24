<?php

namespace PHPFunctional\PatternMatching;

class Matcher
{
    private static $rules = [
        "/^(true|false)$/i" => '_parseBooleanConstant',
        "/^(['\"])(?:(?!\\1).)*\\1$/" => '_parseStringConstant',
        "/^[a-zA-Z+]$/" => '_parseIdentifier',
        "/^_$/" => '_parseWildcard',
        "/^\\[.*\\]$/" => '_parseArray',
    ];

    private static function _parseBooleanConstant($value, $pattern)
    {
        return is_bool($value) ? [] : false;
    }

    private static function _parseStringConstant($value, $pattern)
    {
        $string_pattern = substr($pattern, 1, -1);
        return is_string($value) && $string_pattern == $value ? [] : false;
    }

    private static function _parseIdentifier($value, $pattern)
    {
        return [$value];
    }

    private static function _parseWildcard($value, $pattern)
    {
        return [];
    }

    private static function _parseArray($value, $pattern)
    {
        if(! is_array($value)) {
            return false;
        }

        $patterns = array_filter(array_map('trim', split_enclosed(',', '[', ']', substr($pattern, 1, -1))));

        if(count($patterns) === 0) {
            return count($value) === 0 ? [] : false;
        }

        if(count($patterns) > count($value)) {
            return false;
        }

        $index = 0;
        $results = [];
        foreach($value as $v) {
            $new = self::parse($v, $patterns[$index]);

            if($new === false) {
                return false;
            }

            $results = array_merge($results, $new);
            ++$index;
        }

        return $results;
    }

    /**
     * @param mixed $value
     * @param string $pattern
     * @return bool|array
     */
    private static function parse($value, $pattern)
    {
        $pattern = trim($pattern);

        if(is_numeric($pattern) && is_numeric($value)) {
            return [];
        }

        foreach(self::$rules as $regex => $method) {
            if(preg_match($regex, $pattern)) {
                $arguments = call_user_func_array(['static', $method], [$value, $pattern]);

                if($arguments !== false) {
                    return $arguments;
                }
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param array $patterns
     * @return mixed
     */
    public static function match($value, array $patterns)
    {
        foreach($patterns as $pattern => $callback) {
            $match = self::parse($value, $pattern);

            if($match !== false) {
                return call_user_func_array($callback, $match);
            }
        }

        throw new \RuntimeException("Non-exhaustive patterns.");
    }
}