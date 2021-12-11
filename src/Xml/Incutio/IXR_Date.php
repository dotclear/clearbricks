<?php
/**
 * @brief IXR - The Incutio XML-RPC Library (http://scripts.incutio.com/xmlrpc/)
 * @version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 *
 * @package Clearbricks
 * @subpackage Filemanager
 *
 * @copyright Incutio Ltd 2002
 * @copyright Artistic License: http://www.opensource.org/licenses/artistic-license.php
 */
namespace Clearbricks\Xml\Incutio;

class IXR_Date
{
    public $year;
    public $month;
    public $day;
    public $hour;
    public $minute;
    public $second;
    public $ts;
    public function __construct($time)
    {
        // $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this->parseTimestamp($time);
        } else {
            $this->parseTimestamp(strtotime($time));
        }
    }
    public function parseTimestamp($timestamp)
    {
        $this->year   = date('Y', $timestamp);
        $this->month  = date('m', $timestamp);
        $this->day    = date('d', $timestamp);
        $this->hour   = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
        $this->second = date('s', $timestamp);
        $this->ts     = $timestamp;
    }

    public function getIso()
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second;
    }
    public function getXml()
    {
        return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
    }
    public function getTimestamp()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Xml\Incutio\IXR_Date', 'IXR_Date');
