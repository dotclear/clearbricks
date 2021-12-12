<?php
/**
 * @interface Schema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Schema;

class Schema
{
    protected $con;

    public function __construct($con)
    {
        $this->con = &$con;
    }

    public static function init($con)
    {
        $driver       = $con->driver();

        $class = __NAMESPACE__ . '\\Driver\\' . ucfirst($driver);
        if (!class_exists($class)) {
            trigger_error('Unable to load DB schema for ' . $driver, E_USER_ERROR);
            exit(1);
        }

        return new $class($con);
    }

    /**
     * Database data type to universal data type conversion.
     *
     * @param      string $type Type name
     * @param      integer $len Field length (in/out)
     * @param      string $default Default field value (in/out)
     * @return     string
     */
    public function dbt2udt(string $type, ?int &$len, &$default): string
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
    public function udt2dbt(string $type, ?int &$len, &$default): string
    {
        return $type;
    }

    /**
     * Returns an array of all table names.
     *
     * @see        i_dbSchema::db_get_tables
     * @return     array<string>
     */
    public function getTables(): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_tables();
    }

    /**
     * Returns an array of columns (name and type) of a given table.
     *
     * @see        i_dbSchema::db_get_columns
     *
     * @param      string $table Table name
     * @return     array<string>
     */
    public function getColumns(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_columns($table);
    }

    /**
     * Returns an array of index of a given table.
     *
     * @see        i_dbSchema::db_get_keys
     *
     * @param      string $table Table name
     * @return     array<string>
     */
    public function getKeys(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_keys($table);
    }

    /**
     * Returns an array of indexes of a given table.
     *
     * @see        i_dbSchema::db_get_index
     *
     * @param      string $table Table name
     * @return     array<string>
     */
    public function getIndexes(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_indexes($table);
    }

    /**
     * Returns an array of foreign keys of a given table.
     *
     * @see        i_dbSchema::db_get_references
     *
     * @param      string $table Table name
     * @return     array<string>
     */
    public function getReferences(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_references($table);
    }

    public function createTable(string $name, array $fields)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_create_table($name, $fields);
    }

    public function createField(string $table, string $name, string $type, ?int $len, bool $null, $default)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_create_field($table, $name, $type, $len, $null, $default);
    }

    public function createPrimary(string $table, string $name, array $cols)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_create_primary($table, $name, $cols);
    }

    public function createUnique(string $table, string $name, array $cols)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_create_unique($table, $name, $cols);
    }

    public function createIndex(string $table, string $name, string $type, array $cols)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_create_index($table, $name, $type, $cols);
    }

    public function createReference(string $name, string $c_table, array $c_cols, string $p_table, array $p_cols, string $update, string $delete)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_create_reference($name, $c_table, $c_cols, $p_table, $p_cols, $update, $delete);
    }

    public function alterField(string $table, string $name, string $type, ?int $len, bool $null, $default)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_alter_field($table, $name, $type, $len, $null, $default);
    }

    public function alterPrimary(string $table, string $name, string $newname, array $cols)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_alter_primary($table, $name, $newname, $cols);
    }

    public function alterUnique(string $table, string $name, string $newname, array $cols)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_alter_unique($table, $name, $newname, $cols);
    }

    public function alterIndex(string $table, string $name, string $newname, string $type, array $cols)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_alter_index($table, $name, $newname, $type, $cols);
    }

    public function alterReference(string $name, string $newname, string $c_table, array $c_cols, string $p_table, array $p_cols, string $update, string $delete)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_alter_reference($name, $newname, $c_table, $c_cols, $p_table, $p_cols, $update, $delete);
    }

    public function dropUnique(string $table, string $name)
    {
        /* @phpstan-ignore-next-line */
        return $this->db_drop_unique($table, $name);
    }

    public function flushStack()
    {
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Schema\Schema', 'dbSchema');
