<?php
/**
 * @class mysqlimb4Schema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require_once 'class.mysql.dbschema.php';

class mysqlimb4Schema extends mysqlSchema
{
    public function db_create_table($name, $fields)
    {
        $a = [];

        foreach ($fields as $n => $f) {
            $type    = $f['type'];
            $len     = (integer) $f['len'];
            $default = $f['default'];
            $null    = $f['null'];

            $type = $this->udt2dbt($type, $len, $default);
            $len  = $len > 0 ? '(' . $len . ')' : '';
            $null = $null ? 'NULL' : 'NOT NULL';

            if ($default === null) {
                $default = 'DEFAULT NULL';
            } elseif ($default !== false) {
                $default = 'DEFAULT ' . $default . ' ';
            } else {
                $default = '';
            }

            $a[] =
            $this->con->escapeSystem($n) . ' ' .
                $type . $len . ' ' . $null . ' ' . $default;
        }

        $sql =
        'CREATE TABLE ' . $this->con->escapeSystem($name) . " (\n" .
        implode(",\n", $a) .
            "\n) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        $this->con->execute($sql);
    }
}
