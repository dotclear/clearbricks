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
     * Timestamp formating
     *
     * Returns a date formated like PHP <a href="http://www.php.net/manual/en/function.strftime.php">strftime</a>
     * function.
     * Special cases %a, %A, %b and %B are handled by {@link l10n} library.
     *
     * @param string            $pattern        Format pattern
     * @param integer|boolean   $timestamp       Timestamp
     * @param string            $timezone       Timezone
     * @return    string
     */
    public static function str(string $pattern, $timestamp = null, string $timezone = null): string
    {
        if ($timestamp === null || $timestamp === false) {
            $timestamp = time();
        }

        $hash    = '799b4e471dc78154865706469d23d512';
        $pattern = preg_replace('/(?<!%)%(a|A)/', '{{' . $hash . '__$1%w__}}', $pattern);
        $pattern = preg_replace('/(?<!%)%(b|B)/', '{{' . $hash . '__$1%m__}}', $pattern);

        if ($timezone) {
            $current_timezone = self::getTZ();
            self::setTZ($timezone);
        }

        // Avoid deprecated notice until PHP 9 should be supported or a correct strftime() replacement
        $res = @strftime($pattern, $timestamp);

        if ($timezone) {
            self::setTZ($current_timezone);
        }

        $res = preg_replace_callback(
            '/{{' . $hash . '__(a|A|b|B)([0-9]{1,2})__}}/',
            function ($args) {
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
            },
            $res
        );

        return $res;
    }

    /**
     * Date to date
     *
     * Format a literal date to another literal date.
     *
     * @param string    $pattern         Format pattern
     * @param string    $datetime        Date
     * @param string    $timezone        Timezone
     *
     * @return    string
     */
    public static function dt2str(string $pattern, string $datetime, ?string $timezone = null): string
    {
        return self::str($pattern, strtotime($datetime), $timezone);
    }

    /**
     * ISO-8601 formatting
     *
     * Returns a timestamp converted to ISO-8601 format.
     *
     * @param integer    $timestamp        Timestamp
     * @param string     $timezone         Timezone
     *
     * @return    string
     */
    public static function iso8601(int $timestamp, string $timezone = 'UTC'): string
    {
        $offset         = self::getTimeOffset($timezone, $timestamp);
        $printed_offset = sprintf('%02u:%02u', abs($offset) / 3600, (abs($offset) % 3600) / 60);

        return date('Y-m-d\\TH:i:s', $timestamp) . ($offset < 0 ? '-' : '+') . $printed_offset;
    }

    /**
     * RFC-822 formatting
     *
     * Returns a timestamp converted to RFC-822 format.
     *
     * @param integer    $timestamp        Timestamp
     * @param string     $timezone         Timezone
     *
     * @return    string
     */
    public static function rfc822(int $timestamp, string $timezone = 'UTC'): string
    {
        # Get offset
        $offset         = self::getTimeOffset($timezone, $timestamp);
        $printed_offset = sprintf('%02u%02u', abs($offset) / 3600, (abs($offset) % 3600) / 60);

        // Avoid deprecated notice until PHP 9 should be supported or a correct strftime() replacement
        return @strftime('%a, %d %b %Y %H:%M:%S ' . ($offset < 0 ? '-' : '+') . $printed_offset, $timestamp);
    }

    /**
     * Timezone set
     *
     * Set timezone during script execution.
     *
     * @param    string    $timezone        Timezone
     */
    public static function setTZ(string $timezone)
    {
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($timezone);

            return;
        }

        if (!ini_get('safe_mode')) {
            putenv('TZ=' . $timezone);
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
     * @param string            $timezone        Timezone
     * @param integer|boolean   $timestamp       Timestamp
     *
     * @return integer
     */
    public static function getTimeOffset(string $timezone, $timestamp = false): int
    {
        if (!$timestamp) {
            $timestamp = time();
        }

        $server_timezone = self::getTZ();
        $server_offset   = date('Z', $timestamp);

        self::setTZ($timezone);
        $current_offset = date('Z', $timestamp);

        self::setTZ($server_timezone);

        return $current_offset - $server_offset;
    }

    /**
     * UTC conversion
     *
     * Returns any timestamp from current timezone to UTC timestamp.
     *
     * @param integer    $timestamp        Timestamp
     *
     * @return integer
     */
    public static function toUTC(int $timestamp): int
    {
        return $timestamp + self::getTimeOffset('UTC', $timestamp);
    }

    /**
     * Add timezone
     *
     * Returns a timestamp with its timezone offset.
     *
     * @param string             $timezone         Timezone
     * @param integer|boolean    $timestamp        Timestamp
     *
     * @return integer
     */
    public static function addTimeZone(string $timezone, $timestamp = false): int
    {
        if ($timestamp === false) {
            $timestamp = time();
        }

        return $timestamp + self::getTimeOffset($timezone, $timestamp);
    }

    /**
     * Timzones
     *
     * Returns an array of supported timezones, codes are keys and names are values.
     *
     * @param boolean    $flip      Names are keys and codes are values
     * @param boolean    $groups    Return timezones in arrays of continents
     *
     * @return array
     */
    public static function getZones(bool $flip = false, bool $groups = false): array
    {
        if (is_null(self::$timezones)) {
            // Read timezones from file
            if (!is_readable($file = dirname(__FILE__) . '/tz.dat')) {
                return [];
            }
            $timezones = file($file);
            $res       = [];
            foreach ($timezones as $timezone) {
                $timezone = trim($timezone);
                if ($timezone) {
                    $res[$timezone] = str_replace('_', ' ', $timezone);
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
                foreach ($res as $code => $timezone) {
                    $group                 = explode('/', $code);
                    $tmp[$group[0]][$code] = $timezone;
                }
                $res = $tmp;
            }
        }

        return $res;
    }
}
