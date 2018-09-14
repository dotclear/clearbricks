<?php
/**
 * @interface i_dbSchema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

interface i_dbSchema
{
    /**
     * This method should return an array of all tables in database for the current connection.
     *
     * @return     array
     */
    public function db_get_tables();

    /**
     * This method should return an associative array of columns in given table
     * <var>$table</var> with column names in keys. Each line value is an array
     * with following values:
     *
     * - [type] data type (string)
     * - [len] data length (integer or null)
     * - [null] is null? (boolean)
     * - [default] default value (string)
     *
     * @param      string $table Table name
     * @return     array
     */
    function db_get_columns($table);

    /**
     * This method should return an array of keys in given table
     * <var>$table</var>. Each line value is an array with following values:
     *
     * - [name] index name (string)
     * - [primary] primary key (boolean)
     * - [unique] unique key (boolean)
     * - [cols] columns (array)
     *
     * @param      string $table Table name
     * @return     array
     */
    function db_get_keys($table);

    /**
     * This method should return an array of indexes in given table
     * <var>$table</var>. Each line value is an array with following values:
     *
     * - [name] index name (string)
     * - [type] index type (string)
     * - [cols] columns (array)
     *
     * @param      string $table Table name
     * @return     array
     */
    function db_get_indexes($table);

    /**
     * This method should return an array of foreign keys in given table
     * <var>$table</var>. Each line value is an array with following values:
     *
     * - [name] key name (string)
     * - [c_cols] child columns (array)
     * - [p_table] parent table (string)
     * - [p_cols] parent columns (array)
     * - [update] on update statement (string)
     * - [delete] on delete statement (string)
     *
     * @param      string $table Table name
     * @return     array
     */
    function db_get_references($table);

    function db_create_table($name, $fields);

    function db_create_field($table, $name, $type, $len, $null, $default);

    function db_create_primary($table, $name, $cols);

    function db_create_unique($table, $name, $cols);

    function db_create_index($table, $name, $type, $cols);

    function db_create_reference($name, $c_table, $c_cols, $p_table, $p_cols, $update, $delete);

    function db_alter_field($table, $name, $type, $len, $null, $default);

    function db_alter_primary($table, $name, $newname, $cols);

    function db_alter_unique($table, $name, $newname, $cols);

    function db_alter_index($table, $name, $newname, $type, $cols);

    function db_alter_reference($name, $newname, $c_table, $c_cols, $p_table, $p_cols, $update, $delete);

    function db_drop_unique($table, $name);
}

/**
 * @class dbSchema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 */
class dbSchema
{
    protected $con;

    public function __construct($con)
    {
        $this->con = &$con;
    }

    public static function init($con)
    {
        $driver       = $con->driver();
        $driver_class = $driver . 'Schema';

        if (!class_exists($driver_class)) {
            if (file_exists(dirname(__FILE__) . '/class.' . $driver . '.dbschema.php')) {
                require dirname(__FILE__) . '/class.' . $driver . '.dbschema.php';
            } else {
                trigger_error('Unable to load DB schema layer for ' . $driver, E_USER_ERROR);
                exit(1);
            }
        }

        return new $driver_class($con);
    }

    /**
     * Database data type to universal data type conversion.
     *
     * @param      string $type Type name
     * @param      integer $len Field length (in/out)
     * @param      string $default Default field value (in/out)
     * @return     string
     */
    public function dbt2udt($type, &$len, &$default)
    {
        $c = [
            'bool'              => 'boolean',
            'int2'              => 'smallint',
            'int'               => 'integer',
            'int4'              => 'integer',
            'int8'              => 'bigint',
            'float4'            => 'real',
            'double precision'  => 'float',
            'float8'            => 'float',
            'decimal'           => 'numeric',
            'character varying' => 'varchar',
            'character'         => 'char'
        ];

        if (isset($c[$type])) {
            return $c[$type];
        }

        return $type;
    }

    /**
     * Universal data type to database data tye conversion.
     *
     * @param      string $type Type name
     * @param      integer $len Field length (in/out)
     * @param      string $default Default field value (in/out)
     * @return     string
     */
    public function udt2dbt($type, &$len, &$default)
    {
        return $type;
    }

    /**
     * Returns an array of all table names.
     *
     * @see        i_dbSchema::db_get_tables
     * @return     array
     */
    public function getTables()
    {
        return $this->db_get_tables();
    }

    /**
     * Returns an array of columns (name and type) of a given table.
     *
     * @see        i_dbSchema::db_get_columns
     *
     * @param      string $table Table name
     * @return     array
     */
    public function getColumns($table)
    {
        return $this->db_get_columns($table);
    }

    /**
     * Returns an array of index of a given table.
     *
     * @see        i_dbSchema::db_get_keys
     *
     * @param      string $table Table name
     * @return     array
     */
    public function getKeys($table)
    {
        return $this->db_get_keys($table);
    }

    /**
     * Returns an array of indexes of a given table.
     *
     * @see        i_dbSchema::db_get_index
     *
     * @param      string $table Table name
     * @return     array
     */
    public function getIndexes($table)
    {
        return $this->db_get_indexes($table);
    }

    /**
     * Returns an array of foreign keys of a given table.
     *
     * @see        i_dbSchema::db_get_references
     *
     * @param      string $table Table name
     * @return     array
     */
    public function getReferences($table)
    {
        return $this->db_get_references($table);
    }

    public function createTable($name, $fields)
    {
        return $this->db_create_table($name, $fields);
    }

    public function createField($table, $name, $type, $len, $null, $default)
    {
        return $this->db_create_field($table, $name, $type, $len, $null, $default);
    }

    public function createPrimary($table, $name, $cols)
    {
        return $this->db_create_primary($table, $name, $cols);
    }

    public function createUnique($table, $name, $cols)
    {
        return $this->db_create_unique($table, $name, $cols);
    }

    public function createIndex($table, $name, $type, $cols)
    {
        return $this->db_create_index($table, $name, $type, $cols);
    }

    public function createReference($name, $c_table, $c_cols, $p_table, $p_cols, $update, $delete)
    {
        return $this->db_create_reference($name, $c_table, $c_cols, $p_table, $p_cols, $update, $delete);
    }

    public function alterField($table, $name, $type, $len, $null, $default)
    {
        return $this->db_alter_field($table, $name, $type, $len, $null, $default);
    }

    public function alterPrimary($table, $name, $newname, $cols)
    {
        return $this->db_alter_primary($table, $name, $newname, $cols);
    }

    public function alterUnique($table, $name, $newname, $cols)
    {
        return $this->db_alter_unique($table, $name, $newname, $cols);
    }

    public function alterIndex($table, $name, $newname, $type, $cols)
    {
        return $this->db_alter_index($table, $name, $newname, $type, $cols);
    }

    public function alterReference($name, $newname, $c_table, $c_cols, $p_table, $p_cols, $update, $delete)
    {
        return $this->db_alter_reference($name, $newname, $c_table, $c_cols, $p_table, $p_cols, $update, $delete);
    }

    public function dropUnique($table, $name)
    {
        return $this->db_drop_unique($table, $name);
    }

    public function flushStack()
    {
    }
}
