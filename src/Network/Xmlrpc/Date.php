<?php
/**
 * @class Date
 * @brief XML-RPC Date object
 *
 * @package Clearbricks
 * @subpackage XML-RPC
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Xmlrpc;

class Date
{
    protected $year;   ///< string
    protected $month;  ///< string
    protected $day;    ///< string
    protected $hour;   ///< string
    protected $minute; ///< string
    protected $second; ///< string
    protected $ts;

    /**
     * Constructor
     *
     * Creates a new instance of xmlrpcDate. <var>$time</var> could be a
     * timestamp or a litteral date.
     *
     * @param integer|string    $time        Timestamp or litteral date.
     */
    public function __construct($time)
    {
        # $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this->parseTimestamp($time);
        } else {
            $this->parseTimestamp(strtotime($time));
        }
    }

    /**
     * Timestamp parser
     *
     * @param integer        $timestamp    Timestamp
     */
    protected function parseTimestamp($timestamp)
    {
        $this->year   = date('Y', $timestamp);
        $this->month  = date('m', $timestamp);
        $this->day    = date('d', $timestamp);
        $this->hour   = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
        $this->second = date('s', $timestamp);
        $this->ts     = $timestamp;
    }

    /**
     * ISO Date
     *
     * Returns the date in ISO-8601 format.
     *
     * @return string
     */
    public function getIso()
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second;
    }

    /**
     * XML Date
     *
     * Returns the XML fragment for XML-RPC message inclusion.
     *
     * @return string
     */
    public function getXml()
    {
        return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
    }

    /**
     * Timestamp
     *
     * Returns the date timestamp.
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Xmlrpc\Date', 'xmlrpcDate');
