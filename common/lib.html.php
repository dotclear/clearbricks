<?php
/**
 * @class html
 * @brief HTML utilities
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class html
{
    public static $url_root;
    public static $absolute_regs = []; ///< Array of regular expression for {@link absoluteURLs()}

    /**
     * HTML escape
     *
     * Replaces HTML special characters by entities.
     *
     * @param string $str    String to escape
     * @return    string
     */
    public static function escapeHTML($str)
    {
        return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Decode HTML entities
     *
     * Returns a string with all entities decoded.
     *
     * @param string    $str            String to protect
     * @param string    $keep_special    Keep special characters: &gt; &lt; &amp;
     * @return    string
     */
    public static function decodeEntities($str, $keep_special = false)
    {
        if ($keep_special) {
            $str = str_replace(
                ['&amp;', '&gt;', '&lt;'],
                ['&amp;amp;', '&amp;gt;', '&amp;lt;'],
                $str);
        }

        # Some extra replacements
        $extra = [
            '&apos;' => "'"
        ];

        $str = str_replace(array_keys($extra), array_values($extra), $str);

        return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Remove markup
     *
     * Removes every tags, comments, cdata from string
     *
     * @param string    $str        String to clean
     * @return    string
     */
    public static function clean($str)
    {
        $str = strip_tags($str);
        return $str;
    }

    /**
     * Javascript escape
     *
     * Returns a protected JavaScript string
     *
     * @param string    $str        String to protect
     * @return    string
     */
    public static function escapeJS($str)
    {
        $str = htmlspecialchars($str, ENT_NOQUOTES, 'UTF-8');
        $str = str_replace("'", "\'", $str);
        $str = str_replace('"', '\"', $str);
        return $str;
    }

    /**
     * URL escape
     *
     * Returns an escaped URL string for HTML content
     *
     * @param string    $str        String to escape
     * @return    string
     */
    public static function escapeURL($str)
    {
        return str_replace('&', '&amp;', $str);
    }

    /**
     * URL sanitize
     *
     * Encode every parts between / in url
     *
     * @param string    $str        String to satinyze
     * @return    string
     */
    public static function sanitizeURL($str)
    {
        return str_replace('%2F', '/', rawurlencode($str));
    }

    /**
     * Remove host in URL
     *
     * Removes host part in URL
     *
     * @param string    $url        URL to transform
     * @return    string
     */
    public static function stripHostURL($url)
    {
        return preg_replace('|^[a-z]{3,}://.*?(/.*$)|', '$1', $url);
    }

    /**
     * Set links to absolute ones
     *
     * Appends $root URL to URIs attributes in $str.
     *
     * @param string    $str        HTML to transform
     * @param string    $root    Base URL
     * @return    string
     */
    public static function absoluteURLs($str, $root)
    {
        self::$url_root = $root;
        $attr           = 'action|background|cite|classid|code|codebase|data|download|formaction|href|longdesc|profile|src|usemap';

        $str = preg_replace_callback('/((?:' . $attr . ')=")(.*?)(")/msu', ['self', 'absoluteURLHandler'], $str);

        foreach (self::$absolute_regs as $r) {
            $str = preg_replace_callback($r, ['self', 'absoluteURLHandler'], $str);
        }

        self::$url_root = null;
        return $str;
    }

    private static function absoluteURLHandler($m)
    {
        $url = $m[2];

        $link = str_replace('%', '%%', $m[1]) . '%s' . str_replace('%', '%%', $m[3]);
        $host = preg_replace('|^([a-z]{3,}://)(.*?)/(.*)$|', '$1$2', self::$url_root);

        $parse = parse_url($m[2]);
        if (empty($parse['scheme'])) {
            if (strpos($url, '//') === 0) {
                // Nothing to do. Already an absolute URL.
            } elseif (strpos($url, '/') === 0) {
                // Beginning by a / return host + url
                $url = $host . $url;
            } elseif (strpos($url, '#') === 0) {
                // Beginning by a # return root + hash
                $url = self::$url_root . $url;
            } elseif (preg_match('|/$|', self::$url_root)) {
                // Root is ending by / return root + url
                $url = self::$url_root . $url;
            } else {
                $url = dirname(self::$url_root) . '/' . $url;
            }
        }

        return sprintf($link, $url);
    }
}
