<?php


namespace Pkg6\Helper;


class Timer
{
    /**
     * 返回当前时间以秒为单位的微秒数
     * @return string
     */
    public static function microTime()
    {
        return sprintf("%f", microtime(true));
    }

    /**
     * 返回当前时间以秒为单位的毫秒数
     * @return string
     */
    public static function milliTime()
    {
        return sprintf("%.3f", microtime(true));
    }

    /**
     * 返回中文格式化的时间
     * @param $timestamp
     * @return mixed|string
     */
    public static function dateFormat($timestamp)
    {
        if (!is_numeric($timestamp)) {
            return '';
        }
        $passTime = time() - $timestamp;
        $format   = [
            ['s' => -PHP_INT_MAX, 'e' => 0, 'msg' => '将来'],
            ['s' => 0, 'e' => 60, 'msg' => '刚刚'],
            ['s' => 60, 'e' => 3600, 'msg' => floor($passTime / 60) . '分钟前'],
            ['s' => 3600, 'e' => 86400, 'msg' => floor($passTime / 3600) . '小时前'],
            ['s' => 86400, 'e' => 2592000, 'msg' => floor($passTime / 86400) . '天前'],
            ['s' => 2592000, 'e' => 31536000, 'msg' => floor($passTime / 2592000) . '月前'],
            ['s' => 31536000, 'e' => PHP_INT_MAX, 'msg' => floor($passTime / 31536000) . '年前'],
        ];
        foreach ($format as $val) {
            if ($val['s'] <= $passTime && $passTime < $val['e']) {
                return $val['msg'];
            }
        }
        return '';
    }
}