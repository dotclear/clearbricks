<?php
/**
 * @class dbQuery
 * @brief Database SQL Query builder
 *
 * Database driver is an helper class to build SQL queries.
 *
 * @package Clearbricks
 * @subpackage DBQuery
 *
 * @copyright Franck Paul & Association Dotclear
 * @copyright GPL-2.0-only
 */

interface dbQueryStatement
{
    /**
     * Get the SQL statement of the query.
     */
    public function sql();

    /**
     * Get the SQL parameters of the query.
     */
    public function params();
}

class dbQuery implements dbQueryStatement
{
    const IDENTIFIER_REGEX         = '/^[a-zA-Z](?:[a-zA-Z0-9_]+)?$/';
    const IDENTIFIER_CAPTURE_REGEX = '/([a-zA-Z](?:[a-zA-Z0-9_]+)?\.[a-zA-Z](?:[a-zA-Z0-9_]+)?)/';

    private $con;
    private $mode;

    protected $table    = null;
    protected $distinct = null;
    protected $columns  = null;
    protected $params   = null;
    protected $values   = null;
    protected $from     = null;
    protected $join     = null;
    protected $where    = null;
    protected $groupBy  = null;
    protected $having   = null;
    protected $orderBy  = null;
    protected $limit    = null;
    protected $offset   = null;

    public function __construct($con, $mode = 'select')
    {
        $this->con  = &$con;
        $this->mode = $mode;
    }

    public static function make($con, $mode = 'select')
    {
        $driver       = $con->driver();
        $driver_class = $driver . 'Query';

        if (!class_exists($driver_class)) {
            if (file_exists(dirname(__FILE__) . '/class.' . $driver . '.dbquery.php')) {
                require dirname(__FILE__) . '/class.' . $driver . '.dbquery.php';
            } else {
                trigger_error('Unable to load DB query builder for ' . $driver, E_USER_ERROR);
                return;
            }
        }
        return new $driver_class($con, $mode);
    }

    /**
     * Magic getter method
     *
     * @param      string  $property  The property
     *
     * @return     mixed   property value if property exists
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        trigger_error('Unknown property ' . $property, E_USER_ERROR);
        return;
    }

    /**
     * Magic setter method
     *
     * @param      string  $property  The property
     * @param      mixed   $value     The value
     *
     * @return     self
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            trigger_error('Unknown property ' . $property, E_USER_ERROR);
        }
        return $this;
    }

    // Statement
    public function sql()
    {
        return $this->{$this->mode}();
    }

    // Statement
    public function params()
    {
        $args = array();
        switch ($this->mode) {
            case 'select':
                if ($this->join) {
                    $args = array_merge($args, $this->joinParams());
                }
                if ($this->where) {
                    $args = array_merge($args, $this->where->params());
                }
                if ($this->having) {
                    $args = array_merge($args, $this->having->params());
                }
                break;
            case 'update':
                $args = array_merge($this->placeholderParams($this->params), $this->where->params());
                break;
            case 'insert':
                $args = dbQueryHelper::flatten(array_map($this->paramLister(), $this->values));
                break;
            case 'delete':
                $args = $this->where->params();
                break;
        }
        return $args;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function map($map)
    {
        $this->columns = array_keys($map);
        $this->params  = array_values($map);
        return $this;
    }

    public function data($map)
    {
        $this->columns  = array_keys($map);
        $this->values   = array();
        $this->values[] = dbQueryValueList::make(array_values($map));
        return $this;
    }

    public function values($values)
    {
        if (!is_array($values)) {
            $values = func_get_args();
        }
        if (count($values) !== count($this->columns)) {
            trigger_error(sprintf(
                'Number of values (%d) does not match number of columns (%d)',
                count($values),
                count($this->columns)
            ), E_USER_ERROR);
        }
        $this->values[] = dbQueryValueList::make($values);
        return $this;
    }

    public function columns($columns)
    {
        $this->columns = func_get_args();
        return $this;
    }

    public function distinct($distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function from($tables)
    {
        $this->from = func_get_args();
        return $this;
    }

    public function join($table, $conditions, $type = '')
    {
        $this->join[] = array(strtoupper($type), dbQueryHelper::reference($this, $table), $conditions);
        return $this;
    }

    public function innerJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'INNER');
    }

    public function outerJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'OUTER');
    }

    public function leftJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'LEFT');
    }

    public function leftOuterJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'LEFT OUTER');
    }

    public function rightJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'RIGHT');
    }

    public function rightOuterJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'RIGHT OUTER');
    }

    public function fullJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'FULL');
    }

    public function fullOuterJoin($table, $conditions)
    {
        return $this->join($table, $conditions, 'FULL OUTER');
    }

    public function where($where)
    {
        $this->where = $where;
        return $this;
    }

    public function groupBy($columns)
    {
        $this->groupBy = func_get_args();
        return $this;
    }

    public function having($having)
    {
        $this->having = $having;
        return $this;
    }

    public function orderBy($sorting)
    {
        $this->orderBy = func_get_args();
        return $this;
    }

    public function offset($offset = null)
    {
        $this->offset = $offset;
        return $this;
    }

    public function limit($limit = null)
    {
        $this->limit = $limit;
        return $this;
    }

    public function select()
    {
        $this->mode = 'select';

        // SELECT
        if ($this->distinct) {
            $parts = ['SELECT DISTINCT'];
        } else {
            $parts = ['SELECT'];
        }

        if ($this->columns) {
            $parts[] = implode(', ', $this->allAliases($this->columns));
        } else {
            $parts[] = '*';
        }

        // FROM
        $parts[] = 'FROM';
        $parts[] = implode(', ', $this->allAliases($this->from));

        // JOIN
        if (count($this->join)) {
            $parts[] = dbQueryHelper::stringifyArray($this->generateJoins(), ' ');
        }

        // WHERE
        if ($this->where) {
            $parts[] = 'WHERE';
            $parts[] = $this->where->sql();
        }

        // GROUP BY
        if ($this->groupBy) {
            $parts[] = 'GROUP BY';
            $parts[] = implode(', ', $this->allQualified($this->groupBy));
        }

        // HAVING
        if ($this->having) {
            $parts[] = 'HAVING';
            $parts[] = $this->having->sql();
        }

        // ORDER BY
        if ($this->orderBy) {
            $parts[] = $this->orderByAsSql();
        }

        // LIMIT
        if ($this->limit) {
            $parts[] = $this->limitAsSql();
        }

        // OFFSET
        if (isset($this->offset)) {
            $parts[] = 'OFFSET';
            $parts[] = $this->offset;
        }

        return implode(' ', $parts);
    }

    public function update()
    {
        $this->mode = 'update';

        if (!$this->where) {
            trigger_error('UPDATE queries require a WHERE clause', E_USER_ERROR);
            return '';
        }

        return sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->escapeQualified($this->table),
            dbQueryHelper::stringifyArray($this->generateSetList()),
            $this->where->sql()
        );
    }

    public function insert()
    {
        $this->mode = 'insert';

        return sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->escapeQualified($this->table),
            implode(', ', $this->all($this->columns)),
            dbQueryHelper::stringifyArray($this->insertLines())
        );
    }

    public function delete()
    {
        $this->mode = 'delete';

        if (!$this->where) {
            trigger_error('DELETE queries require a WHERE clause', E_USER_ERROR);
            return '';
        }

        return sprintf(
            'DELETE FROM %s WHERE %s',
            $this->escapeQualified($this->table),
            $this->where->sql()
        );
    }

    /**
     * Surround the identifier with escape characters.
     */
    public function surround($identifier)
    {
        return $identifier;
    }

    /**
     * Generate a list of JOIN statements.
     */
    protected function generateJoins()
    {
        $parts = array();
        foreach ($this->join as $join) {
            $parts[] = trim(sprintf(
                '%s JOIN %s ON %s',
                $join[0],
                $join[1]->sql(),
                $join[2]->sql()
            ));
        }
        return $parts;
    }

    /**
     * Get a flattened array of join parameters.
     */
    protected function joinParams()
    {
        $params = array();
        foreach ($this->join as $join) {
            $params[] = $join[1]->params();
            $params[] = $join[2]->params();
        }

        // flatten: [[a, b], [c, ...]] -> [a, b, c]
        return dbQueryHelper::flatten($params);
    }

    protected function limitAsSql()
    {
        return sprintf('LIMIT %d', $this->limit);
    }

    protected function orderByAsSql()
    {
        return sprintf('ORDER BY %s', dbQueryHelper::stringifyArray($this->generateOrderBy()));
    }

    /**
     * Generate a list of ORDER BY statements.
     */
    protected function generateOrderBy()
    {
        $parts = array();
        foreach ($this->orderBy as $sort) {
            if (empty($sort[1])) {
                $parts[] = $this->escapeQualified($sort[0]);
            } else {
                $parts[] = $this->escapeQualified($sort[0]) . ' ' . strtoupper($sort[1]);
            }
        }
        return $parts;
    }

    /**
     * Generate a column and placeholder pair.
     */
    protected function generateSetList()
    {
        $parts = array();
        foreach ($this->columns as $idx => $column) {
            $parts[] = $this->escape($column) . ' = ' . $this->placeholderValue($idx);
        }
        return $parts;
    }

    /**
     * Get a placeholder or string equivalent for null or boolean values.
     *
     * PDO treats indexed parameters as strings when the type is not bound.
     * This will fail for null and boolean values. By replacing the values
     * directly more consistent queries can be built.
     */
    protected function placeholderValue($index)
    {
        $value = $this->params[$index];

        if (dbQueryHelper::isPlaceholderValue($value)) {
            return '?';
        }

        if ($value instanceof dbQueryExpression) {
            return $value->sql();
        }

        // null -> "NULL", true -> "TRUE", etc
        $result = var_export($value, true);
        return dbQueryHelper::pdoBinding() ? strtoupper($result) : $result;
    }

    /**
     * Get all parameters that can be placeholders.
     */
    protected function placeholderParams($params)
    {
        return array_values(
            array_filter($params, function ($value) {
                return dbQueryHelper::isPlaceholderValue($value);
            })
        );
    }

    /**
     * Convert all parameters to an array for flattening.
     */
    protected function paramLister()
    {
        return function ($values) {
            return $values->params();
        };
    }

    /**
     * Generate a list of insert lines.
     */
    protected function insertLines()
    {
        $parts = array();
        foreach ($this->values as $line) {
            $parts[] = $line->sql();
        }
        return $parts;
    }

    /**
     * Escape an unqualified identifier.
     */
    protected function escape($identifier)
    {
        if ($identifier === '*') {
            return $identifier;
        }

        $this->guardIdentifier($identifier);
        return $this->surround($identifier);
    }

    /**
     * Escape a (possibly) qualified identifier.
     */
    public function escapeQualified($identifier)
    {
        if ($this->isExpression($identifier)) {
            return $identifier->sql();
        }

        if (dbQueryHelper::isStatement($identifier)) {
            return $identifier->sql();
        }

        if (strpos($identifier, '.') === false) {
            return $this->escape($identifier);
        }

        $parts = explode('.', $identifier);
        return implode('.', array_map(array($this, 'escape'), $parts));
    }

    /**
     * Escape a identifier alias.
     */
    protected function escapeAlias($alias)
    {
        if ($this->isExpression($alias)) {
            return $alias->sql();
        }

        if (dbQueryHelper::isAlias($alias)) {
            return $alias->sql();
        }

        $parts = preg_split('/ (?:AS )?/i', $alias);
        return implode(' AS ', array_map(array($this, 'escapeQualified'), $parts));
    }

    /**
     * Escape a list of identifiers.
     */
    protected function all(array $identifiers)
    {
        return array_map(array($this, 'escape'), is_array($identifiers) ? $identifiers : array($identifiers));
    }

    /**
     * Escape a list of (possibly) qualified identifiers.
     */
    public function allQualified($identifiers)
    {
        return array_map(array($this, 'escapeQualified'), is_array($identifiers) ? $identifiers : array($identifiers));
    }

    /**
     * Escape a list of identifier aliases.
     */
    protected function allAliases($aliases)
    {
        return array_map(array($this, 'escapeAlias'), is_array($aliases) ? $aliases : array($aliases));
    }

    /**
     * Escape all qualified identifiers in an expression.
     */
    public function escapeExpression($expression)
    {
        if (strpos($expression, '.') === false) {
            return $expression;
        }

        // table.col = other.col -> [table.col, other.col]
        preg_match_all(self::IDENTIFIER_CAPTURE_REGEX, $expression, $matches);
        // [table.col, ...] -> ["table"."col", ...]
        $matches[1] = array_map(array($this, 'escapeQualified'), $matches[1]);
        // table.col = other.col -> "table"."col" = "other"."col"
        return str_replace($matches[0], $matches[1], $expression);
    }

    /**
     * Check if the identifier is an identifier expression.
     */
    protected function isExpression($identifier)
    {
        return is_object($identifier) && $identifier instanceof dbQueryExpression;
    }

    /**
     * Ensure that identifiers match SQL standard.
     */
    protected function guardIdentifier($identifier)
    {
        if (preg_match('/^[a-zA-Z](?:[a-zA-Z0-9_]+)?$/', $identifier) == false) {
            trigger_error('Invalid SQL identifier: ' . $identifier, E_USER_ERROR);
        }
    }
}

class dbQueryValueList implements Countable, dbQueryStatement
{
    protected $params;

    /**
     * Create a new value list.
     */
    public static function make($params)
    {
        $values         = new static();
        $values->params = is_array($params) ? array_values($params) : array($params);
        return $values;
    }

    // Countable
    public function count()
    {
        return count($this->params);
    }

    // Statement
    public function sql($identifier = null)
    {
        return '(' . dbQueryHelper::stringifyArray($this->generatePlaceholders()) . ')';
    }

    // Statement
    public function params()
    {
        return $this->placeholderParams();
    }

    /**
     * Generate a placeholder.
     */
    protected function generatePlaceholders()
    {
        $parts = array();
        foreach (array_keys($this->params) as $index) {
            $parts[] = $this->placeholderValue($index);
        }
        return $parts;
    }

    /**
     * Get a placeholder or string equivalent for null or boolean values.
     *
     * PDO treats indexed parameters as strings when the type is not bound.
     * This will fail for null and boolean values. By replacing the values
     * directly more consistent queries can be built.
     */
    protected function placeholderValue($index)
    {
        $value = $this->params[$index];

        if (dbQueryHelper::isPlaceholderValue($value)) {
            return '?';
        }

        if ($value instanceof dbQueryExpression) {
            return $value->sql();
        }

        // null -> "NULL", true -> "TRUE", etc
        $result = var_export($value, true);
        return dbQueryHelper::pdoBinding() ? strtoupper($result) : dbQueryHelper::escapeValue($result, false);
    }

    /**
     * Get all parameters that can be placeholders.
     */
    protected function placeholderParams()
    {
        return array_values(
            array_filter($this->params, function ($value) {
                return dbQueryHelper::isPlaceholderValue($value);
            })
        );
    }
}

class dbQueryExpression implements dbQueryStatement
{
    protected $db_query;
    protected $template;
    protected $identifiers;

    /**
     * Create a new expression.
     */
    public static function make($db_query, $template, $identifiers = null)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        return new static($db_query, $template, $args);
    }

    // Statement
    public function sql()
    {
        return vsprintf($this->template, $this->db_query->allQualified($this->identifiers));
    }

    // Statement
    public function params()
    {
        return array();
    }

    protected function __construct($db_query, $template, $identifiers = null)
    {
        $this->db_query    = $db_query;
        $this->template    = $template;
        $this->identifiers = $identifiers;
    }
}

class dbQueryReference implements dbQueryStatement
{
    protected $db_query;
    protected $reference;

    /**
     * Create a new table or column reference.
     */
    public static function make($db_query, $reference)
    {
        return new static($db_query, $reference);
    }

    // Statement
    public function sql()
    {
        return $this->db_query->escapeQualified($this->reference);
    }

    // Statement
    public function params()
    {
        return array();
    }

    protected function __construct($db_query, $reference)
    {
        $this->db_query  = $db_query;
        $this->reference = $reference;
    }
}

class dbQueryAlias implements dbQueryStatement
{
    protected $db_query;
    protected $statement;
    protected $alias;

    /**
     * Create a new alias.
     *
     * @param Statement|string $statement
     */
    public static function make($db_query, $statement, $alias)
    {
        return new static($db_query, dbQueryHelper::reference($db_query, $statement), $alias);
    }

    // Statement
    public function sql()
    {
        return sprintf(
            dbQueryHelper::isQuery($this->statement) ? '(%s) AS %s' : '%s AS %s',
            $this->statement->sql(),
            $this->db_query->escapeQualified($this->alias)
        );
    }

    // Statement
    public function params()
    {
        return $this->statement->params();
    }

    protected function __construct($db_query, $statement, $alias)
    {
        $this->db_query  = $db_query;
        $this->statement = $statement;
        $this->alias     = $alias;
    }
}

class dbQueryConditions implements dbQueryStatement
{
    protected $db_query;
    protected $parts = [];
    protected $parent;

    /**
     * Create a new conditions instance.
     */
    public static function make($db_query, $condition = null, $params = null)
    {
        $statement = new static($db_query);
        if ($condition) {
            $args = func_get_args();
            array_shift($args);
            call_user_func_array(array($statement, 'with'), $args);
        }
        return $statement;
    }

    /**
     * Alias of andWith().
     */
    public function with($condition, $params = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'andWith'), $args);
    }

    /**
     * Add a condition that will be applied with a logical "AND".
     */
    public function andWith($condition, $params = null)
    {
        $args = func_get_args();
        array_unshift($args, 'AND');
        return call_user_func_array(array($this, 'addCondition'), $args);
    }

    /**
     * Add a condition that will be applied with a logical "OR".
     */
    public function orWith($condition, $params = null)
    {
        $args = func_get_args();
        array_unshift($args, 'OR');
        return call_user_func_array(array($this, 'addCondition'), $args);
    }

    /**
     * Alias for andGroup().
     */
    public function group()
    {
        return $this->andGroup();
    }

    /**
     * Start a new grouping that will be applied with a logical "AND".
     *
     * Exit the group with end().
     */
    public function andGroup()
    {
        return $this->addConditionGroup('AND');
    }

    /**
     * Start a new grouping that will be applied with a logical "OR".
     *
     * Exit the group with end().
     */
    public function orGroup()
    {
        return $this->addConditionGroup('OR');
    }

    /**
     * Exit the current grouping and return the parent statement.
     *
     * If no parent exists, the current conditions will be returned.
     *
     * @return Conditions
     */
    public function end()
    {
        return $this->parent ?: $this;
    }

    // Statement
    public function sql()
    {
        $expression = array_reduce($this->parts, $this->sqlReducer(), '');
        return $this->db_query->escapeExpression($expression);
    }

    // Statement
    public function params()
    {
        return array_reduce($this->parts, $this->paramReducer(), array());
    }

    protected function __construct($db_query, $parent = null)
    {
        $this->db_query = $db_query;
        $this->parent   = $parent;
    }

    /**
     * Add a condition to the current conditions, expanding IN values.
     */
    protected function addCondition($type, $condition, $params = null)
    {
        $args = func_get_args();
        array_shift($args); // Remove $type
        array_shift($args); // Remove $condition
        $params = $args;

        $this->parts[] = compact('type', 'condition', 'params');
        return $this;
    }

    /**
     * Add a condition group to the current conditions.
     */
    protected function addConditionGroup($type)
    {
        $condition     = new static($this->db_query, $this);
        $this->parts[] = compact('type', 'condition');
        return $condition;
    }

    /**
     * Get a function to reduce condition parts to a SQL string.
     */
    protected function sqlReducer()
    {
        return function ($sql, $part) {
            if ($this->isCondition($part['condition'])) {
                // (...)
                $statement = "({$part['condition']->sql()})";
            } else {
                // foo = ?
                $statement = $this->replaceStatementParams($part['condition'], $part['params']);
            }
            if ($sql) {
                // AND ...
                $statement = "{$part['type']} $statement";
            }
            return trim($sql . ' ' . $statement);
        };
    }

    /**
     * Get a function to reduce parameters to a single list.
     */
    protected function paramReducer()
    {
        return function ($params, $part) {
            if ($this->isCondition($part['condition'])) {
                // Conditions have a parameter list already
                return array_merge($params, $part['condition']->params());
            }
            // Otherwise convert the list to a list of lists for flattening
            $values = is_array($part['params']) ? $part['params'] : array($part['params']);
            $lists  = array_map($this->paramLister(), $values);
            foreach ($lists as $list) {
                $params = array_merge($params, $list);
            }
            return $params;
        };
    }

    /**
     * Convert all parameters to an array for flattening.
     */
    protected function paramLister()
    {
        return function ($param) {
            if ($this->isStatement($param)) {
                // Statements have a parameter list already
                return $param->params();
            }
            // Otherwise convert to a list
            return array($param);
        };
    }

    /**
     * Check if a condition is a sub-condition.
     */
    protected function isCondition($condition)
    {
        if (is_object($condition) === false) {
            return false;
        }
        return $condition instanceof dbQueryConditions;
    }

    /**
     * Check if a parameter is a statement.
     */
    protected function isStatement($param)
    {
        if (is_object($param) === false) {
            return false;
        }
        return $param instanceof dbQueryStatement;
    }

    /**
     * Check if any parameter is a statement.
     */
    protected function hasStatementParam($params)
    {
        if ($params) {
            $list = is_array($params) ? $params : array($params);
            foreach ($list as $param) {
                if ($this->isStatement($param)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Replacement statement parameters with SQL expression.
     */
    protected function replaceStatementParams($statement, $params)
    {
        if ($this->hasStatementParam($params) === false) {
            if (dbQueryHelper::pdoBinding()) {
                return $statement;
            }
        }
        $list = is_array($params) ? $params : array($params);
        // Maintain an offset position, as preg_replace_callback() does not provide one
        $index = 0;
        return preg_replace_callback('/\?/', function ($matches) use (&$index, $list) {
            $idx = $index;
            $index++;
            if ($this->isStatement($list[$idx])) {
                // Replace any statement placeholder with the generated SQL
                return $list[$idx]->sql();
            } else {
                // And leave all other placeholders intact
                return dbQueryHelper::pdoBinding() ? $matches[0] : dbQueryHelper::escapeValue($list[$idx]);
            }
        }, $statement);
    }
}

// Helper (static) classes

class dbQueryLikeValue
{
    /**
     * Escape input for a LIKE condition value.
     */
    public static function escape($value)
    {
        // Backslash is used to escape wildcards.
        $value = str_replace('\\', '\\\\', $value);
        // Standard wildcards are underscore and percent sign.
        $value = str_replace('%', '\\%', $value);
        $value = str_replace('_', '\\_', $value);

        return $value;
    }

    /**
     * Escape input for a LIKE condition, surrounding with wildcards.
     */
    public static function any($value)
    {
        $value = self::escape($value);
        return "%$value%";
    }

    /**
     * Escape input for a LIKE condition, ends with wildcards.
     */
    public static function starts($value)
    {
        $value = self::escape($value);
        return "$value%";
    }

    /**
     * Escape input for a LIKE condition, starts with wildcards.
     */
    public static function ends($value)
    {
        $value = self::escape($value);
        return "%$value";
    }
}

class dbQueryHelper
{
    private static $pdo_binding = false;

    /**
     * Enable or disable use of binding values (useful with PDO::execute())
     *
     * @param      boolean  $enable New value of pdo_binding flag
     */
    public static function setPdoBinding($enable = true)
    {
        self::$pdo_binding = $enable;
        return self::$pdo_binding;
    }

    public static function pdoBinding()
    {
        return self::$pdo_binding;
    }

    /**
     * Convert an array to a string.
     */
    public static function stringifyArray($values, $bind = ', ')
    {
        return implode($bind, $values);
    }

    /**
     * Determine if a value can be represented by a placeholder.
     */
    public static function isPlaceholderValue($value)
    {
        // No binding values
        if (!self::$pdo_binding) {
            return false;
        }

        if (in_array($value, array(true, false, null), true)) {
            return false;
        }

        if ($value instanceof dbQueryExpression) {
            return false;
        }

        return true;
    }

    public static function isQuery($value)
    {
        return $value instanceof dbQuery;
    }

    public static function isStatement($value)
    {
        return $value instanceof dbQueryStatement;
    }

    public static function isAlias($value)
    {
        return $value instanceof dbQueryAlias;
    }

    public static function reference($db_query, $sql)
    {
        if (self::isStatement($sql)) {
            return $sql;
        }

        if (strpos($sql, ' ') === false) {
            return dbQueryReference::make($db_query, $sql);
        }

        $parts = preg_split('/ (?:AS )?/i', $sql);
        return dbQueryAlias::make($db_query, $parts[0], $parts[1]);
    }

    public static function flatten($array, $preserve_keys = false)
    {
        $table = array();
        if (is_array($array)) {
            if ($preserve_keys) {
                array_walk_recursive($array, function ($v, $k) use (&$table) {$table[$k] = $v;});
            } else {
                array_walk_recursive($array, function ($v) use (&$table) {$table[] = $v;});
            }
        } else {
            $table = array($array);
        }
        return $table;
    }

    public static function escapeValue($v, $quote = true)
    {
        return is_numeric($v) || in_array($v, array(null, true, false), true) ?
            (is_numeric($v) ?
                $v :    // numeric value
                strtoupper(var_export($v, true))) : // null -> NULL, true -> TRUE, false -> FALSE
            (in_array(strtoupper($v), array('NULL', 'TRUE', 'FALSE'), true) ? // string value
                strtoupper($v) :
                ($quote ? "'" . $v . "'" : $v));
    }
}
