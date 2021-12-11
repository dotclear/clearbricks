<?php
/**
 * @class InterfaceLayer
 * @brief Database Abstraction Layer interface
 *
 * All methods in this interface should be implemented in your database driver.
 *
 * Database driver is a class that extends {@link Layer}, implements
 * {@link InterfaceLayer} and has a name of the form (driver name)Connection.
 *
 * @package Clearbricks
 * @subpackage DBLayer
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Layer;

interface InterfaceLayer
{
    /**
     * Open connection
     *
     * This method should open a database connection and return a new resource
     * link.
     *
     * @param string    $host        Database server host
     * @param string    $user        Database user name
     * @param string    $password        Database password
     * @param string    $database        Database name
     * @return mixed
     */
    public function db_connect($host, $user, $password, $database);

    /**
     * Open persistent connection
     *
     * This method should open a persistent database connection and return a new
     * resource link.
     *
     * @param string    $host        Database server host
     * @param string    $user        Database user name
     * @param string    $password        Database password
     * @param string    $database        Database name
     * @return mixed
     */
    public function db_pconnect($host, $user, $password, $database);

    /**
     * Close connection
     *
     * This method should close resource link.
     *
     * @param mixed    $handle        Resource link
     */
    public function db_close($handle);

    /**
     * Database version
     *
     * This method should return database version number.
     *
     * @param mixed    $handle        Resource link
     * @return string
     */
    public function db_version($handle);

    /**
     * Database query
     *
     * This method should run an SQL query and return a resource result.
     *
     * @param mixed    $handle        Resource link
     * @param string    $query        SQL query string
     * @return mixed
     */
    public function db_query($handle, $query);

    /**
     * Database exec query
     *
     * This method should run an SQL query and return a resource result.
     *
     * @param mixed    $handle        Resource link
     * @param string    $query        SQL query string
     * @return resource
     */
    public function db_exec($handle, $query);

    /**
     * Result columns count
     *
     * This method should return the number of fields in a result.
     *
     * @param mixed    $res            Resource result
     * @return integer
     */
    public function db_num_fields($res);

    /**
     * Result rows count
     *
     * This method should return the number of rows in a result.
     *
     * @param mixed    $res            Resource result
     * @return integer
     */
    public function db_num_rows($res);

    /**
     * Field name
     *
     * This method should return the name of the field at the given position
     * <var>$position</var>.
     *
     * @param mixed    $res            Resource result
     * @param integer    $position        Field position
     * @return string
     */
    public function db_field_name($res, $position);

    /**
     * Field type
     *
     * This method should return the field type a the given position
     * <var>$position</var>.
     *
     * @param mixed    $res            Resource result
     * @param integer    $position        Field position
     * @return string
     */
    public function db_field_type($res, $position);

    /**
     * Fetch result
     *
     * This method should fetch one line of result and return an associative array
     * with field name as key and field value as value.
     *
     * @param mixed    $res            Resource result
     * @return array|false
     */
    public function db_fetch_assoc($res);

    /**
     * Move result cursor
     *
     * This method should move result cursor on given row position <var>$row</var>
     * and return true on success.
     *
     * @param mixed    $res            Resource result
     * @param integer    $row        Row position
     * @return boolean
     */
    public function db_result_seek($res, $row);

    /**
     * Affected rows
     *
     * This method should return number of rows affected by INSERT, UPDATE or
     * DELETE queries.
     *
     * @param mixed    $handle        Resource link
     * @param mixed    $res            Resource result
     * @return integer
     */
    public function db_changes($handle, $res);

    /**
     * Last error
     *
     * This method should return the last error string for the current connection.
     *
     * @param mixed    $handle        Resource link
     * @return string|false
     */
    public function db_last_error($handle);

    /**
     * Escape string
     *
     * This method should return an escaped string for the current connection.
     *
     * @param string    $str            String to escape
     * @param mixed     $handle        Resource link
     * @return string
     */
    public function db_escape_string($str, $handle = null);

    /**
     * Acquiere Write lock
     *
     * This method should lock the given table in write access.
     *
     * @param string    $table        Table name
     */
    public function db_write_lock($table);

    /**
     * Release lock
     *
     * This method should releases an acquiered lock.
     */
    public function db_unlock();
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Layer\InterfaceLayer', 'i_dbLayer');
