<?php

namespace CodeBase;

use Phalcon\Mvc\User\Component;

/**
 * Class Utils
 *
 * @package Website
 *
 * @property \Phalcon\Config $config
 */
class Utils extends Component
{
    /**
     * Converts milliseconds to human readable format
     *
     * @param float $microseconds
     * @param int $precision
     *
     * @return string
     */
    public function timeToHuman($microseconds, $precision = 3)
    {
        $units = ['μs', 'ns', 'ms', 's'];
        $micro = max($microseconds, 0);
        $pow = 0;
        if (1000 < $micro) {
            $pow = floor(($micro ? log($micro) : 0) / log(1000));
            $pow = min($pow, count($units) - 1);
            $micro /= (1 << (10 * $pow));
        }
        return round($micro, $precision) . ' ' . $units[$pow];
    }

    /**
     * Converts bytes to a human readable format
     *
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    public function bytesToHuman($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function curlGet($url, $get = array(), $options = array())
    {
        $url = trim($url);
        if (!empty($get)) {
            $url .= '?' . http_build_query($get);
        }

        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public static function curlPost($url, $post = array(), $options = array())
    {
        $url = trim($url);
        if (!empty($post)) {
            $data = http_build_query($post);
        }
        $defaults = array(
            CURLOPT_POST => true,
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => !empty($data) ? $data : '',
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );

        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}
