<?php
/**
 * @class StructTable
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Schema;

use Clearbricks\Common\Exception;

class StructTable
{
    protected $name;
    protected $has_primary = false;

    protected $fields     = [];
    protected $keys       = [];
    protected $indexes    = [];
    protected $references = [];

    /**
    Universal data types supported by dbSchema

    SMALLINT    : signed 2 bytes integer
    INTEGER    : signed 4 bytes integer
    BIGINT    : signed 8 bytes integer
    REAL        : signed 4 bytes floating point number
    FLOAT    : signed 8 bytes floating point number
    NUMERIC    : exact numeric type

    DATE        : Calendar date (day, month and year)
    TIME        : Time of day
    TIMESTAMP    : Date and time

    CHAR        : A fixed n-length character string
    VARCHAR    : A variable length character string
    TEXT        : A variable length of text
     */
    protected $allowed_types = [
        'smallint', 'integer', 'bigint', 'real', 'float', 'numeric',
        'date', 'time', 'timestamp',
        'char', 'varchar', 'text'
    ];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getKeys(bool $primary = null): array
    {
        return $this->keys;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getReferences(): array
    {
        return $this->references;
    }

    public function fieldExists(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    public function keyExists(string $name, string $type, $cols)
    {
        # Look for key with the same name
        if (isset($this->keys[$name])) {
            return $name;
        }

        # Look for key with the same columns list and type
        foreach ($this->keys as $n => $k) {
            if ($k['cols'] == $cols && $k['type'] == $type) {
                # Same columns and type, return new name
                return $n;
            }
        }

        return false;
    }

    public function indexExists(string $name, string $type, array $cols)
    {
        # Look for key with the same name
        if (isset($this->indexes[$name])) {
            return $name;
        }

        # Look for index with the same columns list and type
        foreach ($this->indexes as $n => $i) {
            if ($i['cols'] == $cols && $i['type'] == $type) {
                # Same columns and type, return new name
                return $n;
            }
        }

        return false;
    }

    public function referenceExists(string $name, array $c_cols, string $p_table, array $p_cols)
    {
        if (isset($this->references[$name])) {
            return $name;
        }

        # Look for reference with same chil columns, parent table and columns
        foreach ($this->references as $n => $r) {
            if ($c_cols == $r['c_cols'] && $p_table == $r['p_table'] && $p_cols == $r['p_cols']) {
                # Only name differs, return new name
                return $n;
            }
        }

        return false;
    }

    public function field(string $name, string $type, ?int $len, bool $null = true, $default = false, bool $to_null = false)
    {
        $type = strtolower($type);

        if (!in_array($type, $this->allowed_types)) {
            if ($to_null) {
                $type = null;
            } else {
                throw new Exception('Invalid data type ' . $type . ' in schema');
            }
        }

        $this->fields[$name] = [
            'type'    => $type,
            'len'     => (int) $len,
            'default' => $default,
            'null'    => (bool) $null
        ];

        return $this;
    }

    public function __call(string $name, $args)
    {
        array_unshift($args, $name);

        return call_user_func_array([$this, 'field'], $args);
    }

    public function primary(string $name, $col)
    {
        if ($this->has_primary) {
            throw new Exception(sprintf('Table %s already has a primary key', $this->name));
        }

        $cols = func_get_args();
        array_shift($cols);

        return $this->newKey('primary', $name, $cols);
    }

    public function unique(string $name, $col)
    {
        $cols = func_get_args();
        array_shift($cols);

        return $this->newKey('unique', $name, $cols);
    }

    public function index(string $name, string $type, $col)
    {
        $cols = func_get_args();
        array_shift($cols);
        array_shift($cols);

        $this->checkCols($cols);

        $this->indexes[$name] = [
            'type' => strtolower($type),
            'cols' => $cols
        ];

        return $this;
    }

    public function reference(string $name, $c_cols, string $p_table, $p_cols, $update = false, $delete = false)
    {
        if (!is_array($p_cols)) {
            $p_cols = [$p_cols];
        }
        if (!is_array($c_cols)) {
            $c_cols = [$c_cols];
        }

        $this->checkCols($c_cols);

        $this->references[$name] = [
            'c_cols'  => $c_cols,
            'p_table' => $p_table,
            'p_cols'  => $p_cols,
            'update'  => $update,
            'delete'  => $delete
        ];
    }

    protected function newKey(string $type, string $name, array $cols)
    {
        $this->checkCols($cols);

        $this->keys[$name] = [
            'type' => $type,
            'cols' => $cols
        ];

        if ($type == 'primary') {
            $this->has_primary = true;
        }

        return $this;
    }

    protected function checkCols(array $cols)
    {
        foreach ($cols as $v) {
            if (!preg_match('/^\(.*?\)$/', $v) && !isset($this->fields[$v])) {
                throw new Exception(sprintf('Field %s does not exist in table %s', $v, $this->name));
            }
        }
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Schema\StructTable', 'dbStructTable');
