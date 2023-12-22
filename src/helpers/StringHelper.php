<?php

namespace carono\telegram\helpers;

class StringHelper
{
    public static function mb_ucfirst($string, $encoding = 'UTF-8')
    {
        $firstChar = mb_substr((string)$string, 0, 1, $encoding);
        $rest = mb_substr((string)$string, 1, null, $encoding);

        return mb_strtoupper($firstChar, $encoding) . $rest;
    }

    public static function mb_ucwords($string, $encoding = 'UTF-8')
    {
        $string = (string)$string;
        if (empty($string)) {
            return $string;
        }

        $parts = preg_split('/(\s+\W+\s+|^\W+\s+|\s+)/u', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $ucfirstEven = trim(mb_substr($parts[0], -1, 1, $encoding)) === '';
        foreach ($parts as $key => $value) {
            $isEven = (bool)($key % 2);
            if ($ucfirstEven === $isEven) {
                $parts[$key] = static::mb_ucfirst($value, $encoding);
            }
        }

        return implode('', $parts);
    }

    public static function camelize($word, $encoding = 'UTF-8')
    {
        if (empty($word)) {
            return (string)$word;
        }
        return str_replace(' ', '', static::mb_ucwords(preg_replace('/[^\pL\pN]+/u', ' ', $word), $encoding));
    }

    public static function parseQuery($str, $urlEncoding = true)
    {
        $result = [];

        if ($str === '') {
            return $result;
        }

        if ($urlEncoding === true) {
            $decoder = function ($value) {
                return rawurldecode(str_replace('+', ' ', $value));
            };
        } elseif ($urlEncoding == PHP_QUERY_RFC3986) {
            $decoder = 'rawurldecode';
        } elseif ($urlEncoding == PHP_QUERY_RFC1738) {
            $decoder = 'urldecode';
        } else {
            $decoder = function ($str) {
                return $str;
            };
        }

        foreach (explode('&', $str) as $kvp) {
            $parts = explode('=', $kvp, 2);
            $key = $decoder($parts[0]);
            $value = isset($parts[1]) ? $decoder($parts[1]) : null;
            if (!isset($result[$key])) {
                $result[$key] = $value;
            } else {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            }
        }

        return $result;
    }
}