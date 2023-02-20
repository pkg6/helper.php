<?php


namespace Pkg6\Helper;


class System
{
    /**
     * Check is current user ROOT
     * @return bool
     */
    public static function isRoot()
    {
        if (function_exists('posix_geteuid')) {
            return 0 === posix_geteuid();
        }
        return false;
    }

    /**
     * Returns current linux user who runs script
     * @return mixed|null
     */
    public static function getUserName()
    {
        $userInfo = posix_getpwuid(posix_geteuid());
        if ($userInfo && isset($userInfo['name'])) {
            return $userInfo['name'];
        }
        return null;
    }

    /**
     * Returns a home directory of current user.
     * @return mixed|string|null
     */
    public static function getHome()
    {
        $userInfo = posix_getpwuid(posix_geteuid());
        if ($userInfo && isset($userInfo['dir'])) {
            return $userInfo['dir'];
        }
        if (array_key_exists('HOMEDRIVE', $_SERVER)) {
            return $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
        }
        return isset($_SERVER['HOME']) ? $_SERVER['HOME'] : null;
    }

    /**
     * @param $limit
     * @return void
     */
    public static function setMemory($limit = '256M')
    {
        ini_set('memory_limit', $limit);
    }

    /**
     * Get usage memory
     * @param $isPeak
     * @return string
     */
    public static function getMemory($isPeak = true)
    {
        if ($isPeak) {
            $memory = memory_get_peak_usage(false);
        } else {
            $memory = memory_get_usage(false);
        }
        return FileSystem::format($memory);
    }

    /**
     * @return array|string
     */
    public static function getBinary()
    {
        if ($customPath = getenv('PHP_BINARY_CUSTOM')) {
            return $customPath;
        }
        // HHVM
        if (defined('HHVM_VERSION')) {
            if (($binary = getenv('PHP_BINARY')) === false) {
                $binary = PHP_BINARY;
            }
            return escapeshellarg($binary) . ' --php';
        }
        if (defined('PHP_BINARY')) {
            return escapeshellarg(PHP_BINARY);
        }
        $binaryLocations = [PHP_BINDIR . '/php', PHP_BINDIR . '/php-cli.exe', PHP_BINDIR . '/php.exe',];
        foreach ($binaryLocations as $binary) {
            if (is_readable($binary)) {
                return $binary;
            }
        }
        return 'php';
    }

    /**
     * @return string|null
     */
    public static function getVersion()
    {
        return defined('PHP_VERSION') ? PHP_VERSION : null;
    }
}