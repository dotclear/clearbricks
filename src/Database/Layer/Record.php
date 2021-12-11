<?php

/**
 * @class Record
 * @brief Query Result Record Class
 *
 * This class acts as an iterator over database query result. It does not fetch
 * all results on instantiation and thus, depending on database engine, should not
 * fill PHP process memory.
 *
 * @package Clearbricks
 * @subpackage DBLayer
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Database\Layer;

class Record implements \Iterator, \Countable
{
    protected $__link;             ///< resource: Database resource link
    protected $__result;           ///< resource: Query result resource
    protected $__info;             ///< array: Result information array
    protected $__extend = []; ///< array: List of static functions that extend record
    protected $__index  = 0;       ///< integer: Current result position
    protected $__row    = false;   ///< array: Current result row content

    private $__fetch = false;

    /**
     * Constructor
     *
     * Creates class instance from result link and some informations.
     * <var>$info</var> is an array with the following content:
     *
     * - con => database object instance
     * - cols => number of columns
     * - rows => number of rows
     * - info[name] => an array with columns names
     * - info[type] => an array with columns types
     *
     * @param mixed        $result      Resource result
     * @param array        $info        Information array
     */
    public function __construct($result, $info)
    {
        $this->__result = $result;
        $this->__info   = $info;
        $this->__link   = $info['con']->link();
        $this->index(0);
    }

    /**
     * To staticRecord
     *
     * Converts this record to a {@link StaticRecord} instance.
     */
    public function toStatic()
    {
        if ($this instanceof StaticRecord) {
            return $this;
        }

        return new StaticRecord($this->__result, $this->__info);
    }

    /**
     * Magic call
     *
     * Magic call function. Calls function added by {@link extend()} if exists, passing it
     * self object and arguments.
     *
     * @return mixed
     */
    public function __call($f, $args)
    {
        if (isset($this->__extend[$f])) {
            array_unshift($args, $this);

            return call_user_func_array($this->__extend[$f], $args);
        }

        trigger_error('Call to undefined method record::' . $f . '()', E_USER_ERROR);
    }

    /**
     * Magic get
     *
     * Alias for {@link field()}.
     *
     * @param string|integer    $n        Field name
     * @return string
     */
    public function __get($n)
    {
        return $this->field($n);
    }

    /**
     * Get field
     *
     * Alias for {@link field()}.
     *
     * @param string|integer    $n        Field name
     * @return string
     */
    public function f($n)
    {
        return $this->field($n);
    }

    /**
     * Get field
     *
     * Retrieve field value by its name or column position.
     *
     * @param string|integer    $n        Field name
     * @return string
     */
    public function field($n)
    {
        return $this->__row[$n];
    }

    /**
     * Field exists
     *
     * Returns true if a field exists.
     *
     * @param string        $n        Field name
     * @return boolean
     */
    public function exists($n)
    {
        return isset($this->__row[$n]);
    }

    /**
     * Field isset
     *
     * Returns true if a field exists (magic method from PHP 5.1).
     *
     * @param string        $n        Field name
     * @return string
     */
    public function __isset($n)
    {
        return isset($this->__row[$n]);
    }

    /**
     * Extend record
     *
     * Extends this instance capabilities by adding all public static methods of
     * <var>$class</var> to current instance. Class methods should take at least
     * this record as first parameter.
     *
     * @see __call()
     *
     * @param string    $class        Class name
     */
    public function extend($class)
    {
        if (!class_exists($class)) {
            return;
        }

        $c = new \ReflectionClass($class);
        foreach ($c->getMethods() as $m) {
            if ($m->isStatic() && $m->isPublic()) {
                $this->__extend[$m->name] = [$class, $m->name];
            }
        }
    }

    /**
     * Returns record extensions.
     *
     * @return  array
     */
    public function extensions()
    {
        return $this->__extend;
    }

    private function setRow()
    {
        $this->__row = $this->__info['con']->db_fetch_assoc($this->__result);

        if ($this->__row !== false) {
            foreach ($this->__row as $k => $v) {
                $this->__row[] = &$this->__row[$k];
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the current index position (0 is first) or move to <var>$row</var> if
     * specified.
     *
     * @param integer    $row            Row number to move
     * @return integer|boolean
     */
    public function index($row = null)
    {
        if ($row === null) {
            return $this->__index === null ? 0 : $this->__index;
        }

        if ($row < 0 || $row + 1 > $this->__info['rows']) {
            return false;
        }

        if ($this->__info['con']->db_result_seek($this->__result, (int) $row)) {
            $this->__index = $row;
            $this->setRow();
            $this->__info['con']->db_result_seek($this->__result, (int) $row);

            return true;
        }

        return false;
    }

    /**
     * One step move index
     *
     * This method moves index forward and return true until index is not
     * the last one. You can use it to loop over record. Example:
     * <code>
     * <?php
     * while ($rs->fetch()) {
     *     echo $rs->field1;
     * }
     * ?>
     * </code>
     *
     * @return boolean
     */
    public function fetch()
    {
        if (!$this->__fetch) {
            $this->__fetch = true;
            $i             = -1;
        } else {
            $i = $this->__index;
        }

        if (!$this->index($i + 1)) {
            $this->__fetch = false;
            $this->__index = 0;

            return false;
        }

        return true;
    }

    /**
     * Moves index to first position.
     *
     * @return boolean
     */
    public function moveStart()
    {
        $this->__fetch = false;

        return $this->index(0);
    }

    /**
     * Moves index to last position.
     *
     * @return boolean
     */
    public function moveEnd()
    {
        return $this->index($this->__info['rows'] - 1);
    }

    /**
     * Moves index to next position.
     *
     * @return boolean
     */
    public function moveNext()
    {
        return $this->index($this->__index + 1);
    }

    /**
     * Moves index to previous position.
     *
     * @return boolean
     */
    public function movePrev()
    {
        return $this->index($this->__index - 1);
    }

    /**
     * @return boolean    true if index is at last position
     */
    public function isEnd()
    {
        return $this->__index + 1 == $this->count();
    }

    /**
     * @return boolean    true if index is at first position.
     */
    public function isStart()
    {
        return $this->__index <= 0;
    }

    /**
     * @return boolean    true if record contains no result.
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * @return integer    number of rows in record
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->__info['rows'];
    }

    /**
     * @return array    array of columns, with name as key and type as value.
     */
    public function columns()
    {
        return $this->__info['info']['name'];
    }

    /**
     * @return array    all rows in record.
     */
    public function rows()
    {
        return $this->getData();
    }

    /**
     * All data
     *
     * Returns an array of all rows in record. This method is called by rows().
     *
     * @return array
     */
    protected function getData()
    {
        $res = [];

        if ($this->count() == 0) {
            return $res;
        }

        $this->__info['con']->db_result_seek($this->__result, 0);
        while (($r = $this->__info['con']->db_fetch_assoc($this->__result)) !== false) {
            foreach ($r as $k => $v) {
                $r[] = &$r[$k];
            }
            $res[] = $r;
        }
        $this->__info['con']->db_result_seek($this->__result, $this->__index);

        return $res;
    }

    /**
     * @return array    current rows.
     */
    public function row()
    {
        return $this->__row;
    }

    /* Iterator methods */

    /**
     * @see Iterator::current
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this;
    }

    /**
     * @see Iterator::key
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->index();
    }
    /**
     * @see Iterator::next
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->fetch();
    }

    /**
     * @see Iterator::rewind
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->moveStart();
        $this->fetch();
    }

    /**
     * @see Iterator::valid
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->__fetch;
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Database\Layer\Record', 'record');
