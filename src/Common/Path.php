<?php
/**
 * @class Path
 * @brief Path manipulation utilities
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Common;

class Path
{
    /**
     * Returns the real path of a file.
     *
     * If parameter $strict is true, file should exist. Returns false if
     * file does not exist.
     *
     * @param string    $p        Filename
     * @param boolean    $strict    File should exists
     * @return string|false
     */
    public static function real(string $p, bool $strict = true)
    {
        $os = (DIRECTORY_SEPARATOR == '\\') ? 'win' : 'nix';

        # Absolute path?
        if ($os == 'win') {
            $_abs = preg_match('/^\w+:/', $p);
        } else {
            $_abs = substr($p, 0, 1) == '/';
        }

        # Standard path form
        if ($os == 'win') {
            $p = str_replace('\\', '/', $p);
        }

        # Adding root if !$_abs
        if (!$_abs) {
            $p = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $p;
        }

        # Clean up
        $p = preg_replace('|/+|', '/', $p);

        if (strlen($p) > 1) {
            $p = preg_replace('|/$|', '', $p);
        }

        $_start = '';
        if ($os == 'win') {
            [$_start, $p] = explode(':', $p);
            $_start .= ':/';
        } else {
            $_start = '/';
        }
        $p = substr($p, 1);

        # Go through
        $P   = explode('/', $p);
        $res = [];

        for ($i = 0; $i < count($P); $i++) {
            if ($P[$i] == '.') {
                continue;
            }

            if ($P[$i] == '..') {
                if (count($res) > 0) {
                    array_pop($res);
                }
            } else {
                array_push($res, $P[$i]);
            }
        }

        $p = $_start . implode('/', $res);

        if ($strict && !@file_exists($p)) {
            return false;
        }

        return $p;
    }

    /**
     * Returns a clean file path
     *
     * @param string    $p        File path
     * @return string
     */
    public static function clean(?string $p): string
    {
        $p = preg_replace(['|^\.\.|', '|/\.\.|', '|\.\.$|'], '', (string) $p);   // Remove double point (upper directory)
        $p = preg_replace('|/{2,}|', '/', (string) $p);                          // Replace double slashes by one
        $p = preg_replace('|/$|', '', (string) $p);                              // Remove trailing slash

        return $p;
    }

    /**
     * Path information
     *
     * Returns an array of information:
     * - dirname
     * - basename
     * - extension
     * - base (basename without extension)
     *
     * @param string    $f        File path
     */
    public static function info(string $f): array
    {
        $p   = pathinfo($f);
        $res = [];

        $res['dirname']   = (string) $p['dirname'];
        $res['basename']  = (string) $p['basename'];
        $res['extension'] = $p['extension'] ?? '';
        $res['base']      = preg_replace('/\.' . preg_quote($res['extension'], '/') . '$/', '', $res['basename']);

        return $res;
    }

    /**
     * Full path with root
     *
     * Returns a path with root concatenation unless path begins with a slash
     *
     * @param string    $p        File path
     * @param string    $root    Root path
     * @return string
     */
    public static function fullFromRoot(string $p, string $root): string
    {
        if (substr($p, 0, 1) == '/') {
            return $p;
        }

        return $root . '/' . $p;
    }
}
