<?php


if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param string|object $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param mixed $value
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_numeric($value) || is_bool($value)) {
            return false;
        }
        if ($value instanceof Countable) {
            return count($value) === 0;
        }
        return empty($value);
    }
}
if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled".
     *
     * @param mixed $value
     * @return bool
     */
    function filled($value)
    {
        return !blank($value);
    }
}
if (!function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param int|array $times
     * @param callable $callback
     * @param int|Closure $sleepMilliseconds
     * @param callable|null $when
     * @return mixed
     * @throws Exception
     */
    function retry($times, callable $callback, $sleepMilliseconds = 0, $when = null)
    {
        $attempts = 0;
        $backoff = [];
        if (is_array($times)) {
            $backoff = $times;
            $times = count($times) + 1;
        }
        beginning:
        $attempts++;
        $times--;
        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($times < 1 || ($when && !$when($e))) {
                throw $e;
            }
            $sleepMilliseconds = isset($backoff[$attempts - 1]) ? $backoff[$attempts - 1] : $sleepMilliseconds;
            if ($sleepMilliseconds) {
                usleep(value($sleepMilliseconds, $attempts) * 1000);
            }
            goto beginning;
        }
    }
}
if (!function_exists('head')) {
    /**
     * Get the first element of an array. Useful for method chaining.
     *
     * @param array $array
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}

if (!function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param array $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (!function_exists('call')) {
    /**
     * Call a callback with the arguments.
     *
     * @param mixed $callback
     * @return null|mixed
     */
    function call($callback, array $args = [])
    {
        if ($callback instanceof Closure) {
            $result = $callback(...$args);
        } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
            $result = $callback(...$args);
        } elseif (is_array($callback)) {
            list($object, $method) = $callback;
            $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
        } else {
            $result = call_user_func_array($callback, $args);
        }
        return $result;
    }
}

if (!function_exists('tap')) {
    /**
     * 对一个值调用给定的闭包，然后返回该值
     *
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return $value;
        }
        $callback($value);
        return $value;
    }
}

if (!function_exists('cpu_count')) {

    /**
     * @return int
     */
    function cpu_count()
    {
        if (\DIRECTORY_SEPARATOR === '\\') {
            return 1;
        }
        $count = 4;
        if (is_callable('shell_exec')) {
            if (strtolower(PHP_OS) === 'darwin') {
                $count = (int)shell_exec('sysctl -n machdep.cpu.core_count');
            } else {
                $count = (int)shell_exec('nproc');
            }
        }
        return $count > 0 ? $count : 4;
    }
}


if (!function_exists('mkdirs')) {
    /**
     * 创建多级目录
     * @param string $path 目录路径
     * @param integer $mod 目录权限（windows忽略）
     * @return   true|false
     */
    function mkdirs($path, $mod = 0777)
    {
        if (!is_dir($path)) {
            return mkdir($path, $mod, true);
        }
        return false;
    }
}

if (!function_exists('mkdirs')) {
    /**
     * 创建多级目录
     * @param string $path 目录路径
     * @param integer $mod 目录权限（windows忽略）
     * @return   true|false
     */
    function mkdirs($path, $mod = 0777)
    {
        if (!is_dir($path)) {
            return mkdir($path, $mod, true);
        }
        return false;
    }
}

if (!function_exists('is_https')) {

    /**
     * @return bool
     */
    function is_https()
    {
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https') {
            return true;
        }
        if (isset($_SERVER['HTTP_FROM_HTTPS']) && strtolower($_SERVER['HTTP_FROM_HTTPS']) != 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        return false;
    }
}
if (!function_exists('dz_authcode')) {

    /**
     * @param $string
     * @param $operation
     * @param $key
     * @param $expiry
     * @return false|string
     */
    function dz_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}

if (!function_exists('compute')) {
    function compute($v1, $v2, $glue = '+')
    {
        switch ($glue) {
            case '+':
                return $v1 + $v2;
            case '-':
                return $v1 - $v2;
            case '.':
                return $v1 . $v2;
            case '=':
            case '==':
                return $v1 == $v2;
            case 'merge':
                return array_merge((array)$v1, (array)$v2);
            case '===':
                return $v1 === $v2;
            case '!==':
                return $v1 !== $v2;
            case '&&':
                return $v1 && $v2;
            case '||':
                return $v1 || $v2;
            case 'and':
                return $v1 and $v2;
            case 'xor':
                return $v1 xor $v2;
            case '|':
                return $v1 | $v2;
            case '&':
                return $v1 & $v2;
            case '^':
                return $v1 ^ $v2;
            case '>':
                return $v1 > $v2;
            case '<':
                return $v1 < $v2;
            case '<>':
                return $v1 <> $v2;
            case '!=':
                return $v1 != $v2;
            case '<=':
                return $v1 <= $v2;
            case '>=':
                return $v1 >= $v2;
            case '*':
                return $v1 * $v2;
            case '/':
                return $v1 / $v2;
            case '%':
                return $v1 % $v2;
            case 'or':
                return $v1 or $v2;
            case '<<':
                return $v1 << $v2;
            case '>>':
                return $v1 >> $v2;
            default:
                return null;
        }
    }
}
