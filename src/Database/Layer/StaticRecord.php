<?php

/**
 * @class StaticRecord
 * @brief Query Result Static Record Class
 *
 * Unlike record class, this one contains all results in an associative array.
 *
 * @package Clearbricks
 * @subpackage DBLayer
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Layer;

class StaticRecord extends Record
{
    public $__data = []; ///< array: Data array

    private $__sortfield;
    private $__sortsign;

    public function __construct($result, $info)
    {
        if (is_array($result)) {
            $this->__info = $info;
            $this->__data = $result;
        } else {
            parent::__construct($result, $info);
            $this->__data = parent::getData();
        }

        unset($this->__link, $this->__result);
    }

    /**
     * Static record from array
     *
     * Returns a new instance of object from an associative array.
     *
     * @param array        $data        Data array
     * @return staticRecord
     */
    public static function newFromArray($data)
    {
        if (!is_array($data)) {
            $data = [];
        }

        $data = array_values($data);

        if (empty($data) || !is_array($data[0])) {
            $cols = 0;
        } else {
            $cols = count($data[0]);
        }

        $info = [
            'con'  => null,
            'info' => null,
            'cols' => $cols,
            'rows' => count($data),
        ];

        return new self($data, $info);
    }

    public function field($n)
    {
        return $this->__data[$this->__index][$n];
    }

    public function exists($n)
    {
        return isset($this->__data[$this->__index][$n]);
    }

    public function index($row = null)
    {
        if ($row === null) {
            return $this->__index;
        }

        if ($row < 0 || $row + 1 > $this->__info['rows']) {
            return false;
        }

        $this->__index = $row;

        return true;
    }

    public function rows()
    {
        return $this->__data;
    }

    /**
     * Changes value of a given field in the current row.
     *
     * @param string    $n            Field name
     * @param string    $v            Field value
     */
    public function set($n, $v)
    {
        if ($this->__index === null) {
            return false;
        }

        $this->__data[$this->__index][$n] = $v;
    }

    /**
     * Sorts values by a field in a given order.
     *
     * @param string    $field        Field name
     * @param string    $order        Sort type (asc or desc)
     */
    public function sort($field, $order = 'asc')
    {
        if (!isset($this->__data[0][$field])) {
            return false;
        }

        $this->__sortfield = $field;
        $this->__sortsign  = strtolower($order) == 'asc' ? 1 : -1;

        usort($this->__data, [$this, 'sortCallback']);

        $this->__sortfield = null;
        $this->__sortsign  = null;
    }

    private function sortCallback($a, $b)
    {
        $a = $a[$this->__sortfield];
        $b = $b[$this->__sortfield];

        # Integer values
        if ($a == (string) (int) $a && $b == (string) (int) $b) {
            $a = (int) $a;
            $b = (int) $b;

            return ($a - $b) * $this->__sortsign;
        }

        return strcmp($a, $b) * $this->__sortsign;
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Layer\StaticRecord', 'staticRecord');
