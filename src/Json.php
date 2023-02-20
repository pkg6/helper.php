<?php


namespace Pkg6\Helper;


class Json
{
    const FORCE_ARRAY    = JSON_OBJECT_AS_ARRAY;
    const PRETTY         = JSON_PRETTY_PRINT;
    const ESCAPE_UNICODE = 1 << 19;


    /**
     * Converts value to JSON format. The flag can be Json::PRETTY, which formats JSON for easier reading and clarity,
     * and Json::ESCAPE_UNICODE for ASCII output.
     * @param mixed $value
     * @param int $flags
     * @return false|string
     * @throws \ErrorException
     */
    public static function encode($value, $flags = 0)
    {
        $flags = ($flags & self::ESCAPE_UNICODE ? 0 : JSON_UNESCAPED_UNICODE)
            | JSON_UNESCAPED_SLASHES
            | ($flags & ~self::ESCAPE_UNICODE)
            | (defined('JSON_PRESERVE_ZERO_FRACTION') ? JSON_PRESERVE_ZERO_FRACTION : 0); // since PHP 5.6.6 & PECL JSON-C 1.3.7

        $json = json_encode($value, $flags);
        if ($error = json_last_error()) {
            throw new \ErrorException(json_last_error_msg());
        }

        return $json;
    }


    /**
     * Parses JSON to PHP value. The flag can be Json::FORCE_ARRAY, which forces an array instead of an object as the return value.
     * @param $json
     * @param int $flags
     * @return mixed
     * @throws \ErrorException
     */
    public static function decode($json, $flags = 0)
    {
        $value = json_decode($json, null, 512, $flags | JSON_BIGINT_AS_STRING);
        if ($error = json_last_error()) {
            throw new \ErrorException(json_last_error_msg());
        }

        return $value;
    }
}