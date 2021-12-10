<?php
/**
 * @class dbLayer
 * @brief Database Abstraction Layer class
 *
 * Base class for database abstraction. Each driver extends this class and
 * implements {@link i_dbLayer} interface.
 *
 * @package Clearbricks
 * @subpackage DBLayer
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Layer;

abstract class AbstractLayer
{
    protected $__driver  = null; ///< string: Driver name
    protected $__syntax  = null; ///< string: SQL syntax name
    protected $__version = null; ///< string: Database version
    protected $__link;           ///< resource: Database resource link
    protected $__last_result;    ///< resource: Last result resource link
    protected $__database;

    /**
     * Start connection
     *
     * Static function to use to init database layer. Returns a object extending
     * dbLayer.
     *
     * @param string    $driver        Driver name
     * @param string    $host        Database hostname
     * @param string    $database        Database name
     * @param string    $user        User ID
     * @param string    $password        Password
     * @param boolean   $persistent    Persistent connection
     * @return object
     */
    public static function init($driver, $host, $database, $user = '', $password = '', $persistent = false)
    {
        // PHP 7.0 mysql driver is obsolete, map to mysqli
        if ($driver === 'mysql') {
            $driver = 'mysqli';
        }

        $class = __NAMESPACE__ . '\\Driver\\' . ucfirst($driver);
        if (!class_exists($class)) {
            trigger_error('Unable to load DB layer for ' . $driver, E_USER_ERROR);
            exit(1);
        }

        return new $class($host, $database, $user, $password, $persistent);
    }

    /**
     * @param string    $host        Database hostname
     * @param string    $database        Database name
     * @param string    $user        User ID
     * @param string    $password        Password
     * @param boolean   $persistent    Persistent connection
     */
    public function __construct($host, $database, $user = '', $password = '', $persistent = false)
    {
        if ($persistent) {
            /* @phpstan-ignore-next-line */
            $this->__link = $this->db_pconnect($host, $user, $password, $database);
        } else {
            /* @phpstan-ignore-next-line */
            $this->__link = $this->db_connect($host, $user, $password, $database);
        }

        /* @phpstan-ignore-next-line */
        $this->__version  = $this->db_version($this->__link);
        $this->__database = $database;
    }

    /**
     * Closes database connection.
     */
    public function close()
    {
        /* @phpstan-ignore-next-line */
        $this->db_close($this->__link);
    }

    /**
     * Returns database driver name
     *
     * @return string
     */
    public function driver()
    {
        return $this->__driver;
    }

    /**
     * Returns database SQL syntax name
     *
     * @return string
     */
    public function syntax()
    {
        return $this->__syntax;
    }

    /**
     * Returns database driver version
     *
     * @return string
     */
    public function version()
    {
        return $this->__version;
    }

    /**
     * Returns current database name
     *
     * @return string
     */
    public function database()
    {
        return $this->__database;
    }

    /**
     * Returns link resource
     *
     * @return resource
     */
    public function link()
    {
        return $this->__link;
    }

    /**
     * Run query and get results
     *
     * Executes a query and return a {@link record} object.
     *
     * @param string    $sql            SQL query
     * @return record
     */
    public function select($sql)
    {
        /* @phpstan-ignore-next-line */
        $result = $this->db_query($this->__link, $sql);

        $this->__last_result = &$result;

        $info        = [];
        $info['con'] = &$this;
        /* @phpstan-ignore-next-line */
        $info['cols'] = $this->db_num_fields($result);
        /* @phpstan-ignore-next-line */
        $info['rows'] = $this->db_num_rows($result);
        $info['info'] = [];

        for ($i = 0; $i < $info['cols']; $i++) {
            /* @phpstan-ignore-next-line */
            $info['info']['name'][] = $this->db_field_name($result, $i);
            /* @phpstan-ignore-next-line */
            $info['info']['type'][] = $this->db_field_type($result, $i);
        }

        return new Record($result, $info);
    }

    /**
     * Return an empty record
     *
     * Return an empty {@link record} object (without any information).
     *
     * @return record
     */
    public function nullRecord()
    {
        $result = false;

        $info         = [];
        $info['con']  = &$this;
        $info['cols'] = 0; // no fields
        $info['rows'] = 0; // no rows
        $info['info'] = ['name' => [], 'type' => []];

        return new Record($result, $info);
    }

    /**
     * Run query
     *
     * Executes a query and return true if succeed
     *
     * @param string    $sql            SQL query
     * @return true
     */
    public function execute($sql)
    {
        /* @phpstan-ignore-next-line */
        $result = $this->db_exec($this->__link, $sql);

        $this->__last_result = &$result;

        return true;
    }

    /**
     * Begin transaction
     *
     * Begins a transaction. Transaction should be {@link commit() commited}
     * or {@link rollback() rollbacked}.
     */
    public function begin()
    {
        $this->execute('BEGIN');
    }

    /**
     * Commit transaction
     *
     * Commits a previoulsy started transaction.
     */
    public function commit()
    {
        $this->execute('COMMIT');
    }

    /**
     * Rollback transaction
     *
     * Rollbacks a previously started transaction.
     */
    public function rollback()
    {
        $this->execute('ROLLBACK');
    }

    /**
     * Aquiere write lock
     *
     * This method lock the given table in write access.
     *
     * @param string    $table        Table name
     */
    public function writeLock($table)
    {
        /* @phpstan-ignore-next-line */
        $this->db_write_lock($table);
    }

    /**
     * Release lock
     *
     * This method releases an acquiered lock.
     */
    public function unlock()
    {
        /* @phpstan-ignore-next-line */
        $this->db_unlock();
    }

    /**
     * Vacuum the table given in argument.
     *
     * @param string    $table        Table name
     */
    public function vacuum($table)
    {
    }

    /**
     * Changed rows
     *
     * Returns the number of lines affected by the last DELETE, INSERT or UPDATE
     * query.
     *
     * @return integer
     */
    public function changes()
    {
        /* @phpstan-ignore-next-line */
        return $this->db_changes($this->__link, $this->__last_result);
    }

    /**
     * Last error
     *
     * Returns the last database error or false if no error.
     *
     * @return string|false
     */
    public function error()
    {
        /* @phpstan-ignore-next-line */
        $err = $this->db_last_error($this->__link);

        if (!$err) {
            return false;
        }

        return $err;
    }

    /**
     * Date formatting
     *
     * Returns a query fragment with date formater.
     *
     * The following modifiers are accepted:
     *
     * - %d : Day of the month, numeric
     * - %H : Hour 24 (00..23)
     * - %M : Minute (00..59)
     * - %m : Month numeric (01..12)
     * - %S : Seconds (00..59)
     * - %Y : Year, numeric, four digits
     *
     * @param string    $field            Field name
     * @param string    $pattern            Date format
     * @return string
     */
    public function dateFormat($field, $pattern)
    {
        return
        'TO_CHAR(' . $field . ',' . "'" . $this->escape($pattern) . "') ";
    }

    /**
     * Query Limit
     *
     * Returns a LIMIT query fragment. <var>$arg1</var> could be an array of
     * offset and limit or an integer which is only limit. If <var>$arg2</var>
     * is given and <var>$arg1</var> is an integer, it would become limit.
     *
     * @param array|integer    $arg1        array or integer with limit intervals
     * @param array|null        $arg2        integer or null
     * @return string
     */
    public function limit($arg1, $arg2 = null)
    {
        if (is_array($arg1)) {
            $arg1 = array_values($arg1);
            $arg2 = $arg1[1] ?? null;
            $arg1 = $arg1[0];
        }

        if ($arg2 === null) {
            $sql = ' LIMIT ' . (int) $arg1 . ' ';
        } else {
            $sql = ' LIMIT ' . (int) $arg2 . ' OFFSET ' . (int) $arg1 . ' ';
        }

        return $sql;
    }

    /**
     * IN fragment
     *
     * Returns a IN query fragment where $in could be an array, a string,
     * an integer or null
     *
     * @param array|string|integer|null        $in        "IN" values
     * @return string
     */
    public function in($in)
    {
        if (is_null($in)) {
            return ' IN (NULL) ';
        } elseif (is_string($in)) {
            return " IN ('" . $this->escape($in) . "') ";
        } elseif (is_array($in)) {
            foreach ($in as $i => $v) {
                if (is_null($v)) {
                    $in[$i] = 'NULL';
                } elseif (is_string($v)) {
                    $in[$i] = "'" . $this->escape($v) . "'";
                }
            }

            return ' IN (' . implode(',', $in) . ') ';
        }

        return ' IN ( ' . (int) $in . ') ';
    }

    /**
     * ORDER BY fragment
     *
     * Returns a ORDER BY query fragment where arguments could be an array or a string
     *
     * array param:
     *    key        : decription
     *    field    : field name (string)
     *    collate    : True or False (boolean) (Alphabetical order / Binary order)
     *    order    : ASC or DESC (string) (Ascending order / Descending order)
     *
     * string param field name (Binary ascending order)
     *
     * @return string
     */
    public function orderBy()
    {
        $default = [
            'order'   => '',
            'collate' => false,
        ];
        foreach (func_get_args() as $v) {
            if (is_string($v)) {
                $res[] = $v;
            } elseif (is_array($v) && !empty($v['field'])) {
                $v          = array_merge($default, $v);
                $v['order'] = (strtoupper($v['order']) == 'DESC' ? 'DESC' : '');
                $res[]      = ($v['collate'] ? 'LOWER(' . $v['field'] . ')' : $v['field']) . ' ' . $v['order'];
            }
        }

        return empty($res) ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    /**
     * Field name(s) fragment (using generic UTF8 collating sequence if available else using SQL LOWER function)
     *
     * Returns a fields list where args could be an array or a string
     *
     * array param: list of field names
     * string param: field name
     *
     * @return string
     */
    public function lexFields()
    {
        $fmt = 'LOWER(%s)';
        foreach (func_get_args() as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {
                $res = array_map(function ($i) use ($fmt) {return sprintf($fmt, $i);}, $v);
            }
        }

        return empty($res) ? '' : implode(',', $res);
    }

    /**
     * Concat strings
     *
     * Returns SQL concatenation of methods arguments. Theses arguments
     * should be properly escaped when needed.
     *
     * @return string
     */
    public function concat()
    {
        $args = func_get_args();

        return implode(' || ', $args);
    }

    /**
     * Escape string
     *
     * Returns SQL protected string or array values.
     *
     * @param string|array    $i        String or array to protect
     * @return string|array
     */
    public function escape($i)
    {
        if (is_array($i)) {
            foreach ($i as $k => $s) {
                /* @phpstan-ignore-next-line */
                $i[$k] = $this->db_escape_string($s, $this->__link);
            }

            return $i;
        }

        /* @phpstan-ignore-next-line */
        return $this->db_escape_string($i, $this->__link);
    }

    /**
     * System escape string
     *
     * Returns SQL system protected string.
     *
     * @param string        $str        String to protect
     * @return string
     */
    public function escapeSystem($str)
    {
        return '"' . $str . '"';
    }

    /**
     * Cursor object
     *
     * Returns a new instance of {@link cursor} class on <var>$table</var> for
     * the current connection.
     *
     * @param string        $table    Target table
     * @return cursor
     */
    public function openCursor($table)
    {
        return new Cursor($this, $table);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Layer\AbstractLayer', 'dbLayer');
