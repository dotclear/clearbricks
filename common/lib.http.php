<?php
/**
 * @class http
 * @brief HTTP utilities
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class http
{
    public static $https_scheme_on_443 = false; ///< boolean: Force HTTPS scheme on server port 443 in {@link getHost()}
    public static $cache_max_age       = 0;     ///< integer: Cache max age for {@link cache()}

    /**
     * Self root URI
     *
     * Returns current scheme, host and port.
     *
     * @return string
     */
    public static function getHost()
    {
        $server_name = explode(':', $_SERVER['HTTP_HOST']);
        $server_name = $server_name[0];
        if (self::$https_scheme_on_443 && $_SERVER['SERVER_PORT'] == '443') {
            $scheme = 'https';
            $port   = '';
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $scheme = 'https';
            $port   = !in_array($_SERVER['SERVER_PORT'], ['80', '443']) ? ':' . $_SERVER['SERVER_PORT'] : '';
        } else {
            $scheme = 'http';
            $port   = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
        }

        return $scheme . '://' . $server_name . $port;
    }

    /**
     * Self root URI
     *
     * Returns current scheme and host from a static URL.
     *
     * @param string    $url URL to retrieve the host from.
     *
     * @return string
     */
    public static function getHostFromURL($url)
    {
        preg_match('~^(?:((?:[a-z]+:)?//)|:(//))?(?:([^:\r\n]*?)/[^:\r\n]*|([^:\r\n]*))$~', $url, $matches);
        array_shift($matches);
        return join($matches);
    }

    /**
     * Self URI
     *
     * Returns current URI with full hostname.
     *
     * @return string
     */
    public static function getSelfURI()
    {
        if (substr($_SERVER['REQUEST_URI'], 0, 1) != '/') {
            return self::getHost() . '/' . $_SERVER['REQUEST_URI'];
        } else {
            return self::getHost() . $_SERVER['REQUEST_URI'];
        }
    }

    /**
     * Prepare a full redirect URI from a relative or absolute URL
     *
     * @param      string $page Relative URL
     * @return     string full URI
     */
    protected static function prepareRedirect($page)
    {
        if (preg_match('%^http[s]?://%', $page)) {
            $redir = $page;
        } else {
            $host = self::getHost();

            if (substr($page, 0, 1) == '/') {
                $redir = $host . $page;
            } else {
                $dir  = str_replace(DIRECTORY_SEPARATOR, '/', dirname($_SERVER['PHP_SELF']));
                if (substr($dir, -1) == '/') {
                    $dir = substr($dir, 0, -1);
                }
                if ($dir == '.') {
                    $dir = '';
                }
                $redir = $host . $dir . '/' . $page;
            }
        }
        return $redir;
    }

    /**
     * Redirect
     *
     * Performs a conforming HTTP redirect for a relative URL.
     *
     * @param string    $page        Relative URL
     */
    public static function redirect($page)
    {
        # Close session if exists
        if (session_id()) {
            session_write_close();
        }

        header('Location: ' . self::prepareRedirect($page));
        exit;
    }

    /**
     * Concat URL and path
     *
     * Appends a path to a given URL. If path begins with "/" it will replace the
     * original URL path.
     *
     * @param string    $url        URL
     * @param string    $path    Path to append
     * @return string
     */
    public static function concatURL($url, $path)
    {
        if (substr($url, -1, 1) != '/') {
            $url .= '/';
        }

        if (substr($path, 0, 1) != '/') {
            return $url . $path;
        }

        return preg_replace('#^(.+?//.+?)/(.*)$#', '$1' . $path, $url);
    }

    /**
     * Real IP
     *
     * Returns the real client IP (or tries to do its best).
     *
     * @return string
     */
    public static function realIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * Client unique ID
     *
     * Returns a "almost" safe client unique ID.
     *
     * @param string    $key        HMAC key
     * @return string
     */
    public static function browserUID($key)
    {
        $uid = '';
        $uid .= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $uid .= isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : '';

        return crypt::hmac($key, $uid);
    }

    /**
     * Client language
     *
     * Returns a two letters language code take from HTTP_ACCEPT_LANGUAGE.
     *
     * @return string
     */
    public static function getAcceptLanguage()
    {
        $dlang = '';
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acclang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $L       = explode(';', $acclang[0]);
            $dlang   = substr(trim($L[0]), 0, 2);
        }

        return $dlang;
    }

    /**
     * Client languages
     *
     * Returns an array of accepted langages ordered by priority.
     * can be a two letters language code or a xx-xx variant.
     *
     * @return array
     */
    public static function getAcceptLanguages()
    {
        $langs = [];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);

                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val) {
                    if ($val === '') {
                        $langs[$lang] = 1;
                    }
                }

                // sort list based on value
                arsort($langs, SORT_NUMERIC);
                $langs = array_map('strtolower', array_keys($langs));
            }
        }
        return $langs;
    }

    /**
     * HTTP Cache
     *
     * Sends HTTP cache headers (304) according to a list of files and an optionnal.
     * list of timestamps.
     *
     * @param array        $files        Files on which check mtime
     * @param array        $mod_ts        List of timestamps
     */
    public static function cache($files, $mod_ts = [])
    {
        if (empty($files) || !is_array($files)) {
            return;
        }

        array_walk($files, function (&$v) {$v = filemtime($v);});

        $array_ts = array_merge($mod_ts, $files);

        rsort($array_ts);
        $now = time();
        $ts  = min($array_ts[0], $now);

        $since = null;
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            $since = preg_replace('/^(.*)(Mon|Tue|Wed|Thu|Fri|Sat|Sun)(.*)(GMT)(.*)/', '$2$3 GMT', $since);
            $since = strtotime($since);
            $since = ($since <= $now) ? $since : null;
        }

        # Common headers list
        $headers[] = 'Last-Modified: ' . gmdate('D, d M Y H:i:s', $ts) . ' GMT';
        $headers[] = 'Cache-Control: must-revalidate, max-age=' . abs((integer) self::$cache_max_age);
        $headers[] = 'Pragma:';

        if ($since >= $ts) {
            self::head(304, 'Not Modified');
            foreach ($headers as $v) {
                header($v);
            }
            exit;
        } else {
            header('Date: ' . gmdate('D, d M Y H:i:s', $now) . ' GMT');
            foreach ($headers as $v) {
                header($v);
            }
        }
    }

    /**
     * HTTP Etag
     *
     * Sends HTTP cache headers (304) according to a list of etags in client request.
     */
    public static function etag()
    {
        # We create an etag from all arguments
        $args = func_get_args();
        if (empty($args)) {
            return;
        }

        $etag = '"' . md5(implode('', $args)) . '"';
        unset($args);

        header('ETag: ' . $etag);

        # Do we have a previously sent content?
        if (!empty($_SERVER['HTTP_IF_NONE_MATCH'])) {
            foreach (explode(',', $_SERVER['HTTP_IF_NONE_MATCH']) as $i) {
                if (stripslashes(trim($i)) == $etag) {
                    self::head(304, 'Not Modified');
                    exit;
                }
            }
        }
    }

    /**
     * HTTP Header
     *
     * Sends an HTTP code and message to client.
     *
     * @param string    $code        HTTP code
     * @param string    $msg            Message
     */
    public static function head($code, $msg = null)
    {
        $status_mode = preg_match('/cgi/', PHP_SAPI);

        if (!$msg) {
            $msg_codes = [
                100 => 'Continue',
                101 => 'Switching Protocols',
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                307 => 'Temporary Redirect',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested Range Not Satisfiable',
                417 => 'Expectation Failed',
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported'
            ];

            $msg = isset($msg_codes[$code]) ? $msg_codes[$code] : '-';
        }

        if ($status_mode) {
            header('Status: ' . $code . ' ' . $msg);
        } else {
            header($msg, true, $code);
        }
    }

    /**
     * Trim request
     *
     * Trims every value in GET, POST, REQUEST and COOKIE vars.
     * Removes magic quotes if magic_quote_gpc is on.
     */
    public static function trimRequest()
    {
        if (!empty($_GET)) {
            array_walk($_GET, ['self', 'trimRequestInVar']);
        }
        if (!empty($_POST)) {
            array_walk($_POST, ['self', 'trimRequestInVar']);
        }
        if (!empty($_REQUEST)) {
            array_walk($_REQUEST, ['self', 'trimRequestInVar']);
        }
        if (!empty($_COOKIE)) {
            array_walk($_COOKIE, ['self', 'trimRequestInVar']);
        }
    }

    private static function trimRequestInVar(&$value, $key)
    {
        if (is_array($value)) {
            foreach ($value as $k => &$v) {
                if (is_array($v)) {
                    self::trimRequestInVar($v, $k);
                } else {
                    if (get_magic_quotes_gpc()) {
                        $v = stripslashes($v);
                    }
                    $v = trim($v);
                }
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $value = stripslashes($value);
            }
            $value = trim($value);
        }
    }

    /**
     * Unset global variables
     *
     * If register_globals is on, removes every GET, POST, COOKIE, REQUEST, SERVER,
     * ENV, FILES vars from GLOBALS.
     */
    public static function unsetGlobals()
    {
        if (!ini_get('register_globals')) {
            return;
        }

        if (isset($_REQUEST['GLOBALS'])) {
            throw new Exception('GLOBALS overwrite attempt detected');
        }

        # Variables that shouldn't be unset
        $no_unset = ['GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST',
            '_SERVER', '_ENV', '_FILES'];

        $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES,
            (isset($_SESSION) && is_array($_SESSION) ? $_SESSION : []));

        foreach ($input as $k => $v) {
            if (!in_array($k, $no_unset) && isset($GLOBALS[$k])) {
                $GLOBALS[$k] = null;
                unset($GLOBALS[$k]);
            }
        }
    }
}
