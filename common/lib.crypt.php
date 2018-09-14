<?php
/**
 * @class crypt
 * @brief Functions to handle passwords or sensitive data
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class crypt
{
    /**
     * SHA1 or MD5 + HMAC
     *
     * Returns an HMAC encoded value of <var>$data</var>, using the said <var>$key</var>
     * and <var>$hashfunc</var> as hash method (sha1 or md5 are accepted if hash_hmac function not exists.)
     *
     * @param    string    $key        Hash key
     * @param    string    $data        Data
     * @param    string    $hashfunc    Hash function (md5 or sha1)
     * @return string
     */
    public static function hmac($key, $data, $hashfunc = 'sha1')
    {

        if (function_exists('hash_hmac')) {
            if (!in_array($hashfunc, hash_algos())) {
                $hashfunc = 'sha1';
            }
            return hash_hmac($hashfunc, $data, $key);
        }

        return self::hmac_legacy($key, $data, $hashfunc);
    }

    public static function hmac_legacy($key, $data, $hashfunc = 'sha1')
    {
        // Legacy way
        if ($hashfunc != 'sha1') {
            $hashfunc = 'md5';
        }
        $blocksize = 64;
        if (strlen($key) > $blocksize) {
            $key = pack('H*', $hashfunc($key));
        }
        $key  = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $data))));
        return bin2hex($hmac);
    }

    /**
     * Password generator
     *
     * Returns an n characters random password.
     *
     * @param      integer $length required length
     * @return     string
     */
    public static function createPassword($length = 8)
    {
        $pwd    = [];
        $chars  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $chars2 = '$!@';

        for ($i = 0; $i < $length; $i++) {
            $pwd[] = $chars[rand(0, strlen($chars) - 1)];
        }
        for ($j = 0; $j < (int)($length / 4); $j++) {
            $pos       = rand(0, 3) + 4 * $j;
            $pwd[$pos] = $chars2[rand(0, strlen($chars2) - 1)];
        }
        return implode('', $pwd);
    }
}
