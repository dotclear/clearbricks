<?php
/**
 * @interface InterfaceSchema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Layer\Schema;

interface InterfaceSchema
{
    /**
     * This method should return an array of all tables in database for the current connection.
     *
     * @return     array<string>
     */
    public function db_get_tables(): array;

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
    public function db_get_columns(string $table): array;

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
    public function db_get_keys(string $table): array;

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
    public function db_get_indexes(string $table): array;

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
    public function db_get_references(string $table): array;

    public function db_create_table(string $name, array $fields): void;

    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void;

    public function db_create_primary(string $table, string $name, array $cols): void;

    public function db_create_unique(string $table, string $name, array $cols): void;

    public function db_create_index(string $table, string $name, string $type, array $cols): void;

    public function db_create_reference(string $name, string $c_table, array $c_cols, string $p_table, array $p_cols, string $update, string $delete): void;

    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void;

    public function db_alter_primary(string $table, string $name, string $newname, array $cols): void;

    public function db_alter_unique(string $table, string $name, string $newname, array $cols): void;

    public function db_alter_index(string $table, string $name, string $newname, string $type, array $cols): void;

    public function db_alter_reference(string $name, string $newname, string $c_table, array $c_cols, string $p_table, array $p_cols, string $update, string $delete): void;

    public function db_drop_unique(string $table, string $name): void;
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Layer\Schema\InterfaceSchema', 'i_dbSchema');
