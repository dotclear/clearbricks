<?php
/**
 * @class dt
 * @brief Date/time utilities
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dt
{
    private static $timezones = null;

    /**
     * Convert strftime() format to date() format
     *
     * @param      string      $src  The strftime() format
     *
     * @throws     \Exception  Thrown if a invalid format is used
     *
     * @return     string       The date() format
     */
    private static function strftimeToDateFormat($src = '')
    {
        $invalid  = ['%U', '%V', '%C', '%g', '%G'];
        $invalids = [];

        // It is important to note that some do not translate accurately ie. lowercase L is supposed to convert to number with a preceding space if it is under 10, there is no accurate conversion so we just use 'g'
        $converts = [
            '%a' => 'D',        // day name of week (3 characters)
            '%A' => 'l',        // day name of week (full)
            '%d' => 'd',        // day of month (2 digits)
            '%e' => 'j',        // day of month (trim with date(), with a space for single digit with strftime())
            '%u' => 'N',        // ISO-8601 numeric representation of the day of the week (1 for monday to 7 for sunday)
            '%w' => 'w',        // day of the week (0 for sunday to 6 for saturday)
            '%W' => 'W',        // week of year
            '%b' => 'M',        // month name (3 characters)
            '%h' => 'M',
            '%B' => 'F',        // month name (full)
            '%m' => 'm',        // month of year (2 digits)
            '%y' => 'y',        // year (last two digits)
            '%Y' => 'Y',        // year
            '%D' => 'm/d/y',    // date
            '%F' => 'Y-m-d',    // date
            '%x' => 'm/d/y',    // date
            '%n' => "\n",       // newline
            '%t' => "\t",       // tab
            '%H' => 'H',        // hour (2 digits)
            '%k' => 'G',        // hour
            '%I' => 'h',        // hour (12 hour format, 2 digits)
            '%l' => 'g',        // hour (12 hour format)
            '%M' => 'i',        // minutes (2 digits)
            '%p' => 'A',        // AM or PM
            '%P' => 'a',        // am or pm
            '%r' => 'h:i:s A',  // time %I:%M:%S %p
            '%R' => 'H:i',      // time %H:%M
            '%S' => 's',        // seconds (2 digits)
            '%T' => 'H:i:s',    // time %H:%M:%S
            '%X' => 'H:i:s',
            '%z' => 'O',                // Timezone offset
            '%Z' => 'T',                // Timezone abbreviation
            '%c' => 'D M j H:i:s Y',    // date as Sun May 13 02:15:10 1962
            '%s' => 'U',                // Unix Epoch Time timestamp
            '%%' => '%',                // literal % character
        ];

        foreach ($invalid as $format) {
            if (strpos($src, $format) !== false) {
                $invalids[] = $format;
            }
        }
        if (!empty($invalids)) {
            throw new \Exception('Found these invalid chars: ' . implode(',', $invalids) . ' in ' . $src);
        }

        return str_replace(array_keys($converts), array_values($converts), $src);
    }

    /**
     * Timestamp formating
     *
     * Returns a date formated like PHP <a href="http://www.php.net/manual/en/function.strftime.php">strftime</a>
     * function.
     * Special cases %a, %A, %b and %B are handled by {@link l10n} library.
     *
     * @param string            $p        Format pattern
     * @param integer|boolean   $ts       Timestamp
     * @param string            $tz       Timezone
     * @return    string
     */
    public static function str(string $p, $ts = null, string $tz = null): string
    {
        if ($ts === null || $ts === false) {
            $ts = time();
        }

        if ($tz) {
            $current_tz = self::getTZ();
            self::setTZ($tz);
        }

        $p = preg_replace('/(?<!%)%a/', '{{__\a%w__}}', $p);
        $p = preg_replace('/(?<!%)%A/', '{{__\A%w__}}', $p);
        $p = preg_replace('/(?<!%)%b/', '{{__\b%m__}}', $p);
        $p = preg_replace('/(?<!%)%B/', '{{__\B%m__}}', $p);

        $res = date(self::strftimeToDateFormat($p), $ts);

        $res = preg_replace_callback('/{{__(a|A|b|B)([0-9]{1,2})__}}/', ['self', '_callback'], $res);

        if ($tz) {
            self::setTZ($current_tz);
        }

        return $res;
    }

    public static function strftime_legacy(string $p, $ts = null)
    {
        return date(dt::strftimeToDateFormat($p), $ts ?? time());
    }

    /**
     * Date to date
     *
     * Format a literal date to another literal date.
     *
     * @param string    $p        Format pattern
     * @param string    $dt        Date
     * @param string    $tz        Timezone
     * @return    string
     */
    public static function dt2str(string $p, string $dt, ?string $tz = null): string
    {
        return dt::str($p, strtotime($dt), $tz);
    }

    /**
     * ISO-8601 formatting
     *
     * Returns a timestamp converted to ISO-8601 format.
     *
     * @param integer    $ts        Timestamp
     * @param string    $tz        Timezone
     * @return    string
     */
    public static function iso8601(int $ts, string $tz = 'UTC'): string
    {
        $o  = self::getTimeOffset($tz, $ts);
        $of = sprintf('%02u:%02u', abs($o) / 3600, (abs($o) % 3600) / 60);

        return date('Y-m-d\\TH:i:s', $ts) . ($o < 0 ? '-' : '+') . $of;
    }

    /**
     * RFC-822 formatting
     *
     * Returns a timestamp converted to RFC-822 format.
     *
     * @param integer    $ts        Timestamp
     * @param string    $tz        Timezone
     * @return    string
     */
    public static function rfc822(int $ts, string $tz = 'UTC'): string
    {
        # Get offset
        $o  = self::getTimeOffset($tz, $ts);
        $of = sprintf('%02u%02u', abs($o) / 3600, (abs($o) % 3600) / 60);

        return strftime('%a, %d %b %Y %H:%M:%S ' . ($o < 0 ? '-' : '+') . $of, $ts);
    }

    /**
     * Timezone set
     *
     * Set timezone during script execution.
     *
     * @param    string    $tz        Timezone
     */
    public static function setTZ(string $tz)
    {
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($tz);

            return;
        }

        if (!ini_get('safe_mode')) {
            putenv('TZ=' . $tz);
        }
    }

    /**
     * Current timezone
     *
     * Returns current timezone.
     *
     * @return string
     */
    public static function getTZ(): string
    {
        if (function_exists('date_default_timezone_get')) {
            return date_default_timezone_get();
        }

        return date('T');
    }

    /**
     * Time offset
     *
     * Get time offset for a timezone and an optionnal $ts timestamp.
     *
     * @param string    $tz        Timezone
     * @param integer|boolean    $ts        Timestamp
     * @return integer
     */
    public static function getTimeOffset(string $tz, $ts = false): int
    {
        if (!$ts) {
            $ts = time();
        }

        $server_tz     = self::getTZ();
        $server_offset = date('Z', $ts);

        self::setTZ($tz);
        $cur_offset = date('Z', $ts);

        self::setTZ($server_tz);

        return $cur_offset - $server_offset;
    }

    /**
     * UTC conversion
     *
     * Returns any timestamp from current timezone to UTC timestamp.
     *
     * @param integer    $ts        Timestamp
     * @return integer
     */
    public static function toUTC(int $ts): int
    {
        return $ts + self::getTimeOffset('UTC', $ts);
    }

    /**
     * Add timezone
     *
     * Returns a timestamp with its timezone offset.
     *
     * @param string    $tz        Timezone
     * @param integer|boolean    $ts        Timestamp
     * @return integer
     */
    public static function addTimeZone(string $tz, $ts = false): int
    {
        if ($ts === false) {
            $ts = time();
        }

        return $ts + self::getTimeOffset($tz, $ts);
    }

    /**
     * Timzones
     *
     * Returns an array of supported timezones, codes are keys and names are values.
     *
     * @param boolean    $flip      Names are keys and codes are values
     * @param boolean    $groups    Return timezones in arrays of continents
     * @return array
     */
    public static function getZones(bool $flip = false, bool $groups = false): array
    {
        if (is_null(self::$timezones)) {
            // Read timezones from file
            if (!is_readable($f = dirname(__FILE__) . '/tz.dat')) {
                return [];
            }
            $tz  = file(dirname(__FILE__) . '/tz.dat');
            $res = [];
            foreach ($tz as $v) {
                $v = trim($v);
                if ($v) {
                    $res[$v] = str_replace('_', ' ', $v);
                }
            }
            // Store timezones for further accesses
            self::$timezones = $res;
        } else {
            // Timezones already read from file
            $res = self::$timezones;
        }

        if ($flip) {
            $res = array_flip($res);
            if ($groups) {
                $tmp = [];
                foreach ($res as $k => $v) {
                    $g              = explode('/', $k);
                    $tmp[$g[0]][$k] = $v;
                }
                $res = $tmp;
            }
        }

        return $res;
    }

    private static function _callback($args): string
    {
        $b = [
            1  => '_Jan',
            2  => '_Feb',
            3  => '_Mar',
            4  => '_Apr',
            5  => '_May',
            6  => '_Jun',
            7  => '_Jul',
            8  => '_Aug',
            9  => '_Sep',
            10 => '_Oct',
            11 => '_Nov',
            12 => '_Dec', ];

        $B = [
            1  => 'January',
            2  => 'February',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December', ];

        $a = [
            1 => '_Mon',
            2 => '_Tue',
            3 => '_Wed',
            4 => '_Thu',
            5 => '_Fri',
            6 => '_Sat',
            0 => '_Sun', ];

        $A = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            0 => 'Sunday', ];

        return __(${$args[1]}[(int) $args[2]]);
    }
}
