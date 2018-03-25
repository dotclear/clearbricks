<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) Franck Paul & Association Dotclear
# All rights reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
#
# ***** END LICENSE BLOCK *****

namespace tests\unit;

use atoum;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/dbquery/class.dbquery.php';

class dbQueryValueList extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver)
    {
        return \dbQuery::make($this->getConnection($driver));
    }

    public function testList()
    {
        $values = \dbQueryValueList::make(array(1, 2, 3));
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($values->sql())
            ->isIdenticalTo('(?, ?, ?)');
        $this
            ->array($values->params())
            ->hasSize(3)
            ->containsValues(array(1, 2, 3));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($values->sql())
            ->isIdenticalTo('(1, 2, 3)');
    }

    public function testPlaceholders($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $values = \dbQueryValueList::make(array(
            true,
            false,
            null,
            \dbQueryExpression::make($query, 'NOW()')
        ));

        $this
            ->string($values->sql())
            ->isIdenticalTo('(TRUE, FALSE, NULL, NOW())');
        $this
            ->array($values->params())
            ->isEmpty();
    }
    protected function testPlaceholdersDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testMap()
    {
        $values = \dbQueryValueList::make(array('a' => 'A', 'b' => 'B', 'c' => 'C'));
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($values->sql())
            ->isIdenticalTo('(?, ?, ?)');
        $this
            ->array($values->params())
            ->hasSize(3)
            ->containsValues(array('A', 'B', 'C'));
        $this
            ->integer($values->count())
            ->isEqualTo(3);

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($values->sql())
            ->isIdenticalTo("('A', 'B', 'C')");
    }
}

class dbQueryExpression extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver)
    {
        return \dbQuery::make($this->getConnection($driver));
    }

    public function testExpression($driver, $result1, $result2)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $expression = \dbQueryExpression::make($query, 'COUNT(*) AS %s', 'total');

        $this
            ->string($expression->sql())
            ->isIdenticalTo($result1);
        $this
            ->array($expression->params())
            ->isEmpty();

        $expression = \dbQueryExpression::make($query, 'COUNT(DISTINCT %s) AS %s', 'id', 'total');

        $this
            ->string($expression->sql())
            ->isIdenticalTo($result2);
        $this
            ->array($expression->params())
            ->isEmpty();
    }
    protected function testExpressionDataProvider()
    {
        return array(
            array('mysql', 'COUNT(*) AS `total`', 'COUNT(DISTINCT `id`) AS `total`'),
            array('mysqli', 'COUNT(*) AS `total`', 'COUNT(DISTINCT `id`) AS `total`'),
            array('mysqlimb4', 'COUNT(*) AS `total`', 'COUNT(DISTINCT `id`) AS `total`'),
            array('pgsql', 'COUNT(*) AS "total"', 'COUNT(DISTINCT "id") AS "total"'),
            array('sqlite', 'COUNT(*) AS "total"', 'COUNT(DISTINCT "id") AS "total"')
        );
    }
}

class dbQueryReference extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver)
    {
        return \dbQuery::make($this->getConnection($driver));
    }

    public function testStatement($driver, $result)
    {
        $query = $this->getQuery($driver)
            ->from('users');
        \dbQueryHelper::setPdoBinding(true);

        $ref = \dbQueryReference::make($query, $query);

        $this
            ->object($ref)
            ->isInstanceOf('\dbQueryReference');
        $this
            ->string($ref->sql())
            ->isEqualTo($result);
        $this
            ->array($ref->params())
            ->isEmpty();
    }
    protected function testStatementDataProvider()
    {
        return array(
            array('mysql', 'SELECT * FROM `users`'),
            array('mysqli', 'SELECT * FROM `users`'),
            array('mysqlimb4', 'SELECT * FROM `users`'),
            array('pgsql', 'SELECT * FROM "users"'),
            array('sqlite', 'SELECT * FROM "users"')
        );
    }

    public function testReference($driver, $result)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $ref = \dbQueryReference::make($query, 'users');

        $this
            ->object($ref)
            ->isInstanceOf('\dbQueryReference');
        $this
            ->string($ref->sql())
            ->isEqualTo($result);
        $this
            ->array($ref->params())
            ->isEmpty();
    }
    protected function testReferenceDataProvider()
    {
        return array(
            array('mysql', '`users`'),
            array('mysqli', '`users`'),
            array('mysqlimb4', '`users`'),
            array('pgsql', '"users"'),
            array('sqlite', '"users"')
        );
    }

    public function testAlias($driver, $result)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $alias = \dbQueryAlias::make($query, 'users', 'u');
        $ref   = \dbQueryReference::make($query, $alias);

        $this
            ->object($ref)
            ->isInstanceOf('\dbQueryReference');
        $this
            ->string($ref->sql())
            ->isEqualTo($result);
        $this
            ->array($ref->params())
            ->isEmpty();
    }
    protected function testAliasDataProvider()
    {
        return array(
            array('mysql', '`users` AS `u`'),
            array('mysqli', '`users` AS `u`'),
            array('mysqlimb4', '`users` AS `u`'),
            array('pgsql', '"users" AS "u"'),
            array('sqlite', '"users" AS "u"')
        );
    }
}

class dbQueryAlias extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver)
    {
        return \dbQuery::make($this->getConnection($driver));
    }

    public function testAlias($driver, $result)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $alias_from    = \dbQueryAlias::make($query, 'users', 'u');
        $alias_columns = \dbQueryAlias::make($query, 'user_id', 'id');

        $query
            ->columns($alias_columns)
            ->from($alias_from);

        $this
            ->string($query->select())
            ->isIdenticalTo($result);

        $this
            ->array($alias_columns->params())
            ->isEmpty();
    }
    protected function testAliasDataProvider()
    {
        return array(
            array('mysql', 'SELECT `user_id` AS `id` FROM `users` AS `u`'),
            array('mysqli', 'SELECT `user_id` AS `id` FROM `users` AS `u`'),
            array('mysqlimb4', 'SELECT `user_id` AS `id` FROM `users` AS `u`'),
            array('pgsql', 'SELECT "user_id" AS "id" FROM "users" AS "u"'),
            array('sqlite', 'SELECT "user_id" AS "id" FROM "users" AS "u"')
        );
    }
}

class dbQueryConditions extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver)
    {
        return \dbQuery::make($this->getConnection($driver));
    }

    public function testBasic($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query, 'id = ?', 2);

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id = ?');

        $this
            ->array($conditions->params())
            ->containsValues(array(2));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id = 2');
    }
    protected function testBasicDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testBasicAndOr($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query)
            ->with('id = ?', 1)
            ->andWith('last_login > ?', 'today')
            ->orWith('last_login IS NULL');

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id = ? AND last_login > ? OR last_login IS NULL');

        $this
            ->array($conditions->params())
            ->containsValues(array(1, 'today'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id = 1 AND last_login > \'today\' OR last_login IS NULL');
    }
    protected function testBasicAndOrDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testLogicalIn($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query)
            ->with('role_id IN ?', \dbQueryValueList::make(array(1, 2, 3)))
            ->orWith('user_id IN ?', \dbQueryValueList::make(array(100)));

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('role_id IN (?, ?, ?) OR user_id IN (?)');
        $this
            ->array($conditions->params())
            ->containsValues(array(1, 2, 3, 100));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('role_id IN (1, 2, 3) OR user_id IN (100)');

        $conditions = \dbQueryConditions::make($query)
            ->with('role_id IN ?', \dbQueryValueList::make(array(4, 5, 6)));

        \dbQueryHelper::setPdoBinding(true);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('role_id IN (?, ?, ?)');
        $this
            ->array($conditions->params())
            ->containsValues(array(4, 5, 6));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('role_id IN (4, 5, 6)');
    }
    protected function testLogicalInDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testCombinedStatement($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query)
            ->with('id IN ? OR id = ?', \dbQueryValueList::make(array(5, 10)), 1);

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id IN (?, ?) OR id = ?');
        $this
            ->array($conditions->params())
            ->containsValues(array(5, 10, 1));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id IN (5, 10) OR id = 1');
    }
    protected function testCombinedStatementDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testGroupingWithAnd($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query)
            ->with('id = ?', 1);

        $group = $conditions->group()
            ->with('last_login > ?', 'today')
            ->orWith('last_login IS NULL');

        $this
            ->object($conditions)
            ->isIdenticalTo($group->end());

        $this
            ->string($group->sql())
            ->isIdenticalTo('last_login > ? OR last_login IS NULL');

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id = ? AND (' . $group->sql() . ')');

        $this
            ->array($conditions->params())
            ->containsValues(array(1, 'today'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($group->sql())
            ->isIdenticalTo('last_login > \'today\' OR last_login IS NULL');

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('id = 1 AND (' . $group->sql() . ')');
    }
    protected function testGroupingWithAndDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testGroupingWithOr($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query)
            ->group()
            ->with('failed_logins > ?', 5)
            ->andWith('last_login IS NULL')
            ->end()
            ->orGroup()
            ->with('role = ?', 'banned')
            ->end();

        $this
            ->string($conditions->sql())
            ->isIdenticalTo('(failed_logins > ? AND last_login IS NULL) OR (role = ?)');

        $this
            ->array($conditions->params())
            ->containsValues(array(5, 'banned'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($conditions->sql())
            ->isIdenticalTo('(failed_logins > 5 AND last_login IS NULL) OR (role = \'banned\')');
    }
    protected function testGroupingWithOrDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testGroupParent($driver)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query);

        $this
            ->object($conditions)
            ->isIdenticalTo($conditions->end());

        $subconditions = $conditions->group();

        $this
            ->object($conditions)
            ->isIdenticalTo($subconditions->end());
    }
    protected function testGroupParentDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testSubConditionIdentifier($driver, $result)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $conditions = \dbQueryConditions::make($query)
            ->with('u.id = ?')
            ->orGroup()
            ->with('u.username = ?')
            ->end();

        $this
            ->string($conditions->sql())
            ->isIdenticalTo($result);
    }
    protected function testSubConditionIdentifierDataProvider()
    {
        return array(
            array('mysql', '`u`.`id` = ? OR (`u`.`username` = ?)'),
            array('mysqli', '`u`.`id` = ? OR (`u`.`username` = ?)'),
            array('mysqlimb4', '`u`.`id` = ? OR (`u`.`username` = ?)'),
            array('pgsql', '"u"."id" = ? OR ("u"."username" = ?)'),
            array('sqlite', '"u"."id" = ? OR ("u"."username" = ?)')
        );
    }
}

class dbQueryLikeValue extends atoum
{
    public function testEscape()
    {
        $this
            ->string(\dbQueryLikeValue::escape('string_not%escaped'))
            ->isEqualTo('string\\_not\\%escaped');
    }

    public function testAny()
    {
        $this
            ->string(\dbQueryLikeValue::any('a % string'))
            ->isEqualTo('%a \\% string%');
    }

    public function testStarts()
    {
        $this
            ->string(\dbQueryLikeValue::starts('a % string'))
            ->isEqualTo('a \\% string%');
    }

    public function testEnds()
    {
        $this
            ->string(\dbQueryLikeValue::ends('a % string'))
            ->isEqualTo('%a \\% string');
    }
}

class dbQueryHelper extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver)
    {
        return \dbQuery::make($this->getConnection($driver));
    }

    public function testpdoBinding()
    {
        // Default value
        $this
            ->boolean(\dbQueryHelper::pdoBinding())
            ->isEqualTo(false);

        // False value
        $this
            ->boolean(\dbQueryHelper::setPdoBinding(false))
            ->isEqualTo(false);

        $this
            ->boolean(\dbQueryHelper::pdoBinding())
            ->isEqualTo(false);

        // True value
        $this
            ->boolean(\dbQueryHelper::setPdoBinding(true))
            ->isEqualTo(true);

        $this
            ->boolean(\dbQueryHelper::pdoBinding())
            ->isEqualTo(true);
    }

    public function testStringifyArray()
    {
        $this
            ->string(\dbQueryHelper::stringifyArray(array('A', 'B', 'C')))
            ->isEqualTo('A, B, C');

        $this
            ->string(\dbQueryHelper::stringifyArray(array('A', 'B', 'C'), ':'))
            ->isEqualTo('A:B:C');
    }

    public function testIsPlaceholderValue($value, $result)
    {
        \dbQueryHelper::setPdoBinding(true);
        $this
            ->boolean(\dbQueryHelper::isPlaceholderValue($value))
            ->isEqualTo($result);

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->boolean(\dbQueryHelper::isPlaceholderValue($value))
            ->isEqualTo(false);
    }
    protected function testIsPlaceholderValueDataProvider()
    {
        return array(
            array(true, false),
            array(false, false),
            array(null, false),
            array(\dbQueryExpression::make(null, null), false),
            array(1, true)
        );
    }

    public function testIsQuery()
    {
        $query = $this->getQuery('pgsql');
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->boolean(\dbQueryHelper::isQuery($query))
            ->isEqualTo(true);
    }

    public function testIsStatement()
    {
        $query = $this->getQuery('pgsql');
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->boolean(\dbQueryHelper::isStatement($query))
            ->isEqualTo(true);
    }

    public function testIsAlias()
    {
        $query = $this->getQuery('pgsql');
        \dbQueryHelper::setPdoBinding(true);

        $alias = \dbQueryAlias::make($query, 'users', 'u');

        $this
            ->boolean(\dbQueryHelper::isAlias($alias))
            ->isEqualTo(true);
    }

    public function testReference()
    {
        $query = $this->getQuery('pgsql');
        \dbQueryHelper::setPdoBinding(true);

        $ref = \dbQueryHelper::reference($query, $query->from('users'));
        $this
            ->object($ref)
            ->isInstanceOf('\dbQuery')
            ->string($ref->sql())
            ->isIdenticalTo('SELECT * FROM "users"');

        $ref = \dbQueryHelper::reference($query, 'users');
        $this
            ->object($ref)
            ->isInstanceOf('\dbQueryReference')
            ->string($ref->sql())
            ->isIdenticalTo('"users"');

        $ref = \dbQueryHelper::reference($query, 'users AS u');
        $this
            ->object($ref)
            ->isInstanceOf('\dbQueryAlias')
            ->string($ref->sql())
            ->isIdenticalTo('"users" AS "u"');
    }

    public function testFlatten()
    {
        $this
            ->array(\dbQueryHelper::flatten(array(1, 2, array(3), 4, array(array(5, 6), array(7)), 8)))
            ->containsValues(array(1, 2, 3, 4, 5, 6, 7, 8));

        $this
            ->array(\dbQueryHelper::flatten(1))
            ->containsValues(array(1));

        $this
            ->array(\dbQueryHelper::flatten(array('a' => 1, array('b' => 2, 'c' => 3)), true))
            ->containsValues(array(1, 2, 3))
            ->hasKeys(array('a', 'b', 'c'));
    }

    public function testEscapeValue($value, $noquote, $quote)
    {
        if (is_numeric($value)) {
            $this
                ->integer(\dbQueryHelper::escapeValue($value, false))
                ->isIdenticalTo($noquote);

            $this
                ->integer(\dbQueryHelper::escapeValue($value))
                ->isIdenticalTo($quote);
        } else {
            $this
                ->string(\dbQueryHelper::escapeValue($value, false))
                ->isIdenticalTo($noquote);

            $this
                ->string(\dbQueryHelper::escapeValue($value))
                ->isIdenticalTo($quote);
        }
    }
    protected function testEscapeValueDataProvider()
    {
        return array(
            array(1, 1, 1),
            array(null, 'NULL', 'NULL'),
            array(true, 'TRUE', 'TRUE'),
            array(false, 'FALSE', 'FALSE'),
            array('null', 'NULL', 'NULL'),
            array('true', 'TRUE', 'TRUE'),
            array('false', 'FALSE', 'FALSE'),
            array('void', "void", "'void'")
        );
    }
}

class dbQuery extends atoum
{
    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    private function getQuery($driver, $mode = 'select')
    {
        return \dbQuery::make($this->getConnection($driver), $mode);
    }

    public function testSimpleSelect($driver, $result, $where)
    {
        $con = $this->getConnection($driver);

        $sql = \dbQuery::make($con)
            ->from('mytable');

        $this
            ->string($sql->select())
            ->isIdenticalTo($result);

        $cond = \dbQueryConditions::make($sql, 'id = 1');
        $sql->where($cond);

        $this
            ->string($sql->select())
            ->isIdenticalTo($result . ' ' . $where);
    }
    protected function testSimpleSelectDataProvider()
    {
        return array(
            array('mysql', 'SELECT * FROM `mytable`', 'WHERE id = 1'),
            array('mysqli', 'SELECT * FROM `mytable`', 'WHERE id = 1'),
            array('mysqlimb4', 'SELECT * FROM `mytable`', 'WHERE id = 1'),
            array('pgsql', 'SELECT * FROM "mytable"', 'WHERE id = 1'),
            array('sqlite', 'SELECT * FROM "mytable"', 'WHERE id = 1')
        );
    }

    public function testEscapeAlias($driver, $result1, $result2, $result3)
    {
        $escapeAlias = new \ReflectionMethod('\dbQuery', 'escapeAlias');
        $escapeAlias->setAccessible(true);

        $aliases = array(
            'id userId',
            'id as userId',
            'id AS userId'
        );

        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        foreach ($aliases as $alias) {
            $this
                ->string($escapeAlias->invokeArgs($query, array($alias)))
                ->isIdenticalTo($result1);
        }

        $this
            ->string($escapeAlias->invokeArgs($query, array('users.id userId')))
            ->isIdenticalTo($result2);

        $expr = \dbQueryExpression::make($query, 'COUNT(*)');
        $this
            ->string($escapeAlias->invokeArgs($query, array($expr)))
            ->isIdenticalTo('COUNT(*)');

        $alias = \dbQueryAlias::make($query, 'user', 'u');
        $this
            ->string($escapeAlias->invokeArgs($query, array($alias)))
            ->isIdenticalTo($result3);
    }
    protected function testEscapeAliasDataProvider()
    {
        return array(
            array('mysql', '`id` AS `userId`', '`users`.`id` AS `userId`', '`user` AS `u`'),
            array('mysqli', '`id` AS `userId`', '`users`.`id` AS `userId`', '`user` AS `u`'),
            array('mysqlimb4', '`id` AS `userId`', '`users`.`id` AS `userId`', '`user` AS `u`'),
            array('pgsql', '"id" AS "userId"', '"users"."id" AS "userId"', '"user" AS "u"'),
            array('sqlite', '"id" AS "userId"', '"users"."id" AS "userId"', '"user" AS "u"')
        );
    }

    public function testAll($driver)
    {
        $escape = new \ReflectionMethod('\dbQuery', 'escape');
        $escape->setAccessible(true);

        $all = new \ReflectionMethod('\dbQuery', 'all');
        $all->setAccessible(true);

        $ids   = array('users');
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        foreach ($ids as $id) {
            $eids[] = $escape->invokeArgs($query, array($id));
        }
        $aids = $all->invokeArgs($query, array($ids));

        $this
            ->array($aids)
            ->isIdenticalTo($eids);
    }
    protected function testAllDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testAllQualified($driver)
    {
        $allQualified = new \ReflectionMethod('\dbQuery', 'allQualified');
        $allQualified->setAccessible(true);

        $ids   = array('users', 'users.id');
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $eids = array_map(array($query, 'escapeQualified'), $ids);
        $aids = $allQualified->invokeArgs($query, array($ids));

        $this
            ->array($aids)
            ->isIdenticalTo($eids);
    }
    protected function testAllQualifiedDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testAllAliases($driver)
    {
        $escapeAlias = new \ReflectionMethod('\dbQuery', 'escapeAlias');
        $escapeAlias->setAccessible(true);

        $allAliases = new \ReflectionMethod('\dbQuery', 'allAliases');
        $allAliases->setAccessible(true);

        $ids   = array('users u', 'users.id userId');
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        foreach ($ids as $id) {
            $eids[] = $escapeAlias->invokeArgs($query, array($id));
        }
        $aids = $allAliases->invokeArgs($query, array($ids));

        $this
            ->array($aids)
            ->isIdenticalTo($eids);
    }
    protected function testAllAliasesDataProvider()
    {
        return array(
            'mysql',
            'mysqli',
            'mysqlimb4',
            'pgsql',
            'sqlite'
        );
    }

    public function testInvalidIdentifier($id)
    {
        $escape = new \ReflectionMethod('\dbQuery', 'escape');
        $escape->setAccessible(true);

        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->when(function () use ($escape, $query, $id) {
                $escape->invokeArgs($query, array($id));
            })
            ->error()
            ->withMessage('Invalid SQL identifier: ' . $id)
            ->exists();
    }
    protected function testInvalidIdentifierDataProvider()
    {
        return array(
            '0col',
            'bad!'
        );
    }

    public function testEscapeExpression($driver, $result)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($query->escapeExpression('table.col = other.col'))
            ->isIdenticalTo($result);
    }
    protected function testEscapeExpressionDataProvider()
    {
        return array(
            array('mysql', '`table`.`col` = `other`.`col`'),
            array('mysqli', '`table`.`col` = `other`.`col`'),
            array('mysqlimb4', '`table`.`col` = `other`.`col`'),
            array('pgsql', '"table"."col" = "other"."col"'),
            array('sqlite', '"table"."col" = "other"."col"')
        );
    }

    public function testEscapeQualified($driver, $result1, $result2, $result3)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $expr = \dbQueryExpression::make($query, 'COUNT(*)');
        $this
            ->string($query->escapeQualified($expr))
            ->isIdenticalTo('COUNT(*)');

        $alias = \dbQueryAlias::make($query, 'user', 'u');
        $this
            ->string($query->escapeQualified($alias))
            ->isIdenticalTo($result1);

        $this
            ->string($query->escapeQualified('user'))
            ->isIdenticalTo($result2);

        $this
            ->string($query->escapeQualified('users.id'))
            ->isIdenticalTo($result3);
    }
    protected function testEscapeQualifiedDataProvider()
    {
        return array(
            array('mysql', '`user` AS `u`', '`user`', '`users`.`id`'),
            array('mysqli', '`user` AS `u`', '`user`', '`users`.`id`'),
            array('mysqlimb4', '`user` AS `u`', '`user`', '`users`.`id`'),
            array('pgsql', '"user" AS "u"', '"user"', '"users"."id"'),
            array('sqlite', '"user" AS "u"', '"user"', '"users"."id"')
        );
    }

    public function testEscape($driver, $result)
    {
        $escape = new \ReflectionMethod('\dbQuery', 'escape');
        $escape->setAccessible(true);

        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($escape->invokeArgs($query, array('user')))
            ->isIdenticalTo($result);

        $this
            ->string($escape->invokeArgs($query, array('*')))
            ->isIdenticalTo('*');
    }
    protected function testEscapeDataProvider()
    {
        return array(
            array('mysql', '`user`'),
            array('mysqli', '`user`'),
            array('mysqlimb4', '`user`'),
            array('pgsql', '"user"'),
            array('sqlite', '"user"')
        );
    }

    public function driver()
    {
        return 'void';
    }
    public function testBadDriver()
    {
        $driver = 'void';
        $this
            ->when(function () {
                \dbQuery::make($this);
            })
            ->error()
            ->withMessage('Unable to load DB query builder for ' . $driver)
            ->exists();
    }

    public function testSetAndGet()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($query->mode)
            ->isEqualTo('select');

        $this
            ->when(function () use ($query) {
                $query->mode = 'insert';
            })
            ->string($query->mode)
            ->isEqualTo('insert');

        $this
            ->when(function () use ($query) {
                $value = $query->unknown;
            })
            ->error()
            ->withMessage('Unknown property ' . 'unknown')
            ->exists();

        $this
            ->when(function () use ($query) {
                $query->unknown = 'unknown';
            })
            ->error()
            ->withMessage('Unknown property ' . 'unknown')
            ->exists();
    }

    public function testSql()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver)
            ->from('users');
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users"');
    }

    public function testTable()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver)
            ->table('users');
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($query->table)
            ->isEqualTo('users');
    }

    public function testUpdate()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'update');
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';
        $map   = array('username' => 'mr-smith');

        $query
            ->table($table)
            ->map($map)
            ->where(\dbQueryConditions::make($query, 'username = ?', 'jsmith'));

        $this
            ->string($query->update())
            ->isEqualTo('UPDATE "users" SET "username" = ? WHERE username = ?');
        $this
            ->string($query->sql())
            ->isEqualTo('UPDATE "users" SET "username" = ? WHERE username = ?');
        $this
            ->array($query->params())
            ->containsValues(array('mr-smith', 'jsmith'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->isEqualTo('UPDATE "users" SET "username" = \'mr-smith\' WHERE username = \'jsmith\'');
    }

    public function testUpdateBooleanAndNull($value, $expect)
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';
        $map   = array(
            'is_vip' => $value,
            'dt'     => \dbQueryExpression::make($query, 'NOW()')
        );

        $query
            ->table($table)
            ->map($map)
            ->where(\dbQueryConditions::make($query, 'username = ?', 'jsmith'));

        $this
            ->string($query->update())
            ->contains($expect);
        $this
            ->array($query->params())
            ->containsValues(array('jsmith'));
    }
    protected function testUpdateBooleanAndNullDataProvider()
    {
        return array(
            // value, expected sql fragment
            'null value'  => array(null, '"is_vip" = NULL'),
            'true value'  => array(true, '"is_vip" = TRUE'),
            'false value' => array(false, '"is_vip" = FALSE')
        );
    }

    public function testUpdateWithoutWhere()
    {
        $this
            ->when(function () {
                $query = $this->getQuery('pgsql', 'update');
                \dbQueryHelper::setPdoBinding(true);
                $sql   = $query->sql();
            })
            ->error()
            ->withMessage('UPDATE queries require a WHERE clause')
            ->exists();
    }

    public function testDelete()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'delete');
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';

        $query
            ->table($table)
            ->where(\dbQueryConditions::make($query, 'last_login IS NULL'));

        $this
            ->string($query->delete())
            ->isEqualTo('DELETE FROM "users" WHERE last_login IS NULL');
        $this
            ->string($query->sql())
            ->isEqualTo('DELETE FROM "users" WHERE last_login IS NULL');
        $this
            ->array($query->params())
            ->isEmpty();
    }

    public function testDeleteWithoutWhere()
    {
        $this
            ->when(function () {
                $query = $this->getQuery('pgsql', 'delete');
                \dbQueryHelper::setPdoBinding(true);
                $sql   = $query->sql();
            })
            ->error()
            ->withMessage('DELETE queries require a WHERE clause')
            ->exists();
    }

    public function testInsert()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'insert');
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';
        $map   = array(
            'username' => 'jsmith',
            'password' => 'i-should-be-a-hash'
        );

        $query
            ->table($table)
            ->data($map);

        $this
            ->string($query->insert())
            ->isEqualTo('INSERT INTO "users" ("username", "password") VALUES (?, ?)');
        $this
            ->array($query->params())
            ->containsValues(array_values($map));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->insert())
            ->isEqualTo('INSERT INTO "users" ("username", "password") VALUES (\'jsmith\', \'i-should-be-a-hash\')');
    }

    public function testInsertBooleanAndNull($value, $expect)
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';
        $map   = array(
            'username' => 'jsmith',
            'is_vip'   => $value
        );

        $query
            ->table($table)
            ->data($map);

        $this
            ->string($query->insert())
            ->contains("VALUES (?, $expect)");
        $this
            ->array($query->params())
            ->containsValues(array('jsmith'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->insert())
            ->contains("VALUES ('jsmith', $expect)");
    }
    protected function testInsertBooleanAndNullDataProvider()
    {
        return array(
            // value, expected sql fragment
            'null value'  => array(null, 'NULL'),
            'true value'  => array(true, 'TRUE'),
            'false value' => array(false, 'FALSE')
        );
    }

    public function testInsertExpression()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'insert');
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';
        $map   = array(
            'username'   => 'jsmith',
            'created_at' => \dbQueryExpression::make($query, 'NOW()')
        );

        $query
            ->table($table)
            ->data($map);

        $this
            ->string($query->insert())
            ->isEqualTo('INSERT INTO "users" ("username", "created_at") VALUES (?, NOW())');
        $this
            ->array($query->params())
            ->containsValues(array('jsmith'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->insert())
            ->isEqualTo('INSERT INTO "users" ("username", "created_at") VALUES (\'jsmith\', NOW())');
    }

    public function testMultipleCompile()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'insert');
        \dbQueryHelper::setPdoBinding(true);

        $table = 'users';
        $map   = [
            'username'    => 'jdoe',
            'is_employee' => false,
            'is_manager'  => true,
            'created_at'  => \dbQueryExpression::make($query, 'NOW()'),
            'updated_at'  => null
        ];

        $query
            ->table($table)
            ->data($map);

        $sql    = $query->sql();
        $params = $query->params();

        $this
            ->string($sql)
            ->contains('(?, FALSE, TRUE, NOW(), NULL)');
        $this
            ->array($params)
            ->containsValues(array('jdoe'));

        // Compile again, verifying the same output
        $this
            ->string($query->sql())
            ->isEqualTo($sql);
        $this
            ->array($query->params())
            ->isIdenticalTo($params);

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->contains('(\'jdoe\', FALSE, TRUE, NOW(), NULL)');
    }

    public function testInsertMultiple()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'insert');
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->table('tokens')
            ->columns('token');

        $query
            ->values('a')
            ->values('b')
            ->values('c');

        $this
            ->string($query->insert())
            ->isEqualTo('INSERT INTO "tokens" ("token") VALUES (?), (?), (?)');
        $this
            ->array($query->params())
            ->containsValues(array('a', 'b', 'c'));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->insert())
            ->isEqualTo('INSERT INTO "tokens" ("token") VALUES (\'a\'), (\'b\'), (\'c\')');
    }

    public function testInsertQualified()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'insert');
        \dbQueryHelper::setPdoBinding(true);

        $table = 'public.users';
        $map   = array(
            'username' => 'jsmith'
        );

        $query
            ->table($table)
            ->data($map);
        $this
            ->string($query->insert())
            ->Contains('"public"."users"');
    }

    public function testInsertCountMismatch()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver, 'insert');
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->table('tokens')
            ->columns('token');

        $this
            ->when(function () use ($query) {
                $query->values(array('a', 'b'));
            })
            ->error()
            ->withMessage('Number of values (2) does not match number of columns (1)')
            ->exists();

        $this
            ->when(function () use ($query) {
                $query->values('a', 'b');
            })
            ->error()
            ->withMessage('Number of values (2) does not match number of columns (1)')
            ->exists();
    }

    public function testSelect()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users');

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users"');
        $this
            ->array($query->params())
            ->isEmpty();
    }

    public function testSelectDistinct()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->distinct(true)
            ->from('users');

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT DISTINCT * FROM "users"');
        $this
            ->array($query->params())
            ->isEmpty();
    }

    public function testSelectDistinctFalse()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->distinct(false)
            ->from('users');

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users"');
        $this
            ->array($query->params())
            ->isEmpty();
    }

    public function testSelectMultipleTables()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users', 'roles');

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users", "roles"');
    }

    public function testSelectColumns()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users')
            ->columns('id', 'username');

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT "id", "username" FROM "users"');
        $this
            ->array($query->params())
            ->isEmpty();
    }

    public function testSelectParams()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('employees e')
            ->columns(
                'e.id',
                'e.first_name',
                'e.last_name',
                \dbQueryExpression::make($query, 'COUNT(%s) AS %s', 's.id', 'shift_count')
            )
            ->where(\dbQueryConditions::make($query, 'e.id > ?', 3))
            ->having(\dbQueryConditions::make($query, 'shift_count > ?', 4))
            ->join('shifts s', \dbQueryConditions::make($query, 's.type = ?', 1)->orWith('s.day = ?', 2));

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT "e"."id", "e"."first_name", "e"."last_name", COUNT("s"."id") AS "shift_count" FROM "employees" AS "e" JOIN "shifts" AS "s" ON "s"."type" = ? OR "s"."day" = ? WHERE "e"."id" > ? HAVING shift_count > ?');
        $this
            ->array($query->params())
            ->containsValues(array(1, 2, 3, 4));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->isEqualTo('SELECT "e"."id", "e"."first_name", "e"."last_name", COUNT("s"."id") AS "shift_count" FROM "employees" AS "e" JOIN "shifts" AS "s" ON "s"."type" = 1 OR "s"."day" = 2 WHERE "e"."id" > 3 HAVING shift_count > 4');
    }

    public function testSelectJoin($method, $type)
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users')
            ->$method('roles', \dbQueryConditions::make($query, 'users.role_id = roles.id'))
            ->$method('devices', \dbQueryConditions::make($query, 'users.devices_id = devices.id'));

        $this
            ->string($query->sql())
            ->isEqualTo("SELECT * FROM \"users\" $type \"roles\" ON \"users\".\"role_id\" = \"roles\".\"id\" $type \"devices\" ON \"users\".\"devices_id\" = \"devices\".\"id\"");
    }
    protected function testSelectJoinDataProvider()
    {
        return array(
            // method, type
            array('join', 'JOIN'),
            array('innerJoin', 'INNER JOIN'),
            array('outerJoin', 'OUTER JOIN'),
            array('rightJoin', 'RIGHT JOIN'),
            array('rightOuterJoin', 'RIGHT OUTER JOIN'),
            array('leftJoin', 'LEFT JOIN'),
            array('leftOuterJoin', 'LEFT OUTER JOIN'),
            array('fullJoin', 'FULL JOIN'),
            array('fullOuterJoin', 'FULL OUTER JOIN')
        );
    }

    public function testSelectWhere()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users')
            ->where(\dbQueryConditions::make($query, 'id = ?', 1));

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users" WHERE id = ?');
        $this
            ->array($query->params())
            ->containsValues(array(1));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users" WHERE id = 1');
    }

    public function testSelectGroupByHaving()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users')
            ->columns(\dbQueryExpression::make($query, 'COUNT(*) AS %s', 'total'))
            ->groupBy('role_id')
            ->having(\dbQueryConditions::make($query, 'total > ?', 5));

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT COUNT(*) AS "total" FROM "users" GROUP BY "role_id" HAVING total > ?');
        $this
            ->array($query->params())
            ->containsValues(array(5));

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->isEqualTo('SELECT COUNT(*) AS "total" FROM "users" GROUP BY "role_id" HAVING total > 5');
    }

    public function testSelectGroupByWithExpression()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users')
            ->columns(
                \dbQueryExpression::make($query, 'COUNT(*) AS %s', 'total'),
                \dbQueryExpression::make($query, 'DATE(created_at) AS %s', 'date')
            )
            ->groupBy(\dbQueryExpression::make($query, 'DATE(created_at)'));

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT COUNT(*) AS "total", DATE(created_at) AS "date" FROM "users" GROUP BY DATE(created_at)');
    }

    public function testSelectOrderBy()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users')
            ->orderBy(array('last_login', 'desc'), array('id'));

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users" ORDER BY "last_login" DESC, "id"');
    }

    public function testSelectOrderByWithExpression()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $query
            ->from('users u')
            ->orderBy(array(\dbQueryExpression::make($query, 'LOWER(u.period)'), 'desc'));

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users" AS "u" ORDER BY LOWER(u.period) DESC');
    }

    public function testSelectLimitOffset()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);

        $query
            ->from('users')
            ->limit(50)
            ->offset(0);

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users" LIMIT 50 OFFSET 0');
    }

    public function testSelectLimitReset()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);

        $query
            ->from('users')
            ->limit(50);

        $query->limit(null);

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users"');
    }

    public function testSelectResetOffset()
    {
        $driver = 'pgsql';
        $query  = $this->getQuery($driver);

        $query
            ->from('users')
            ->offset(100)
            ->limit(50);

        $query->offset(null);

        $this
            ->string($query->sql())
            ->isEqualTo('SELECT * FROM "users" LIMIT 50');
    }

    public function testSelectSubSelect()
    {
        $driver = 'pgsql';
        \dbQueryHelper::setPdoBinding(true);

        $sub_query = $this->getQuery($driver);
        $sub_query
            ->from('orders')
            ->columns('user_id')
            ->where(\dbQueryConditions::make($sub_query, 'placed_at BETWEEN ? AND ?', '2017-01-01', '2017-12-31'));

        $query = $this->getQuery($driver);
        $query
            ->from('users')
            ->where(
                \dbQueryConditions::make($query, 'id IN (?)', $sub_query)
                    ->with('deleted_at IS NULL')
                    ->with('created_at BETWEEN ? AND ?', '2016-12-15', '2016-12-25')
            );

        $expected = 'SELECT * FROM "users" WHERE id IN (' .
            'SELECT "user_id" FROM "orders" WHERE placed_at BETWEEN ? AND ?' .
            ') AND deleted_at IS NULL AND created_at BETWEEN ? AND ?';

        $this
            ->string($query->sql())
            ->isEqualTo($expected);
        $this
            ->array($query->params())
            ->containsValues(array(
                '2017-01-01',
                '2017-12-31',
                '2016-12-15',
                '2016-12-25'
            ));

        $expected = 'SELECT * FROM "users" WHERE id IN (' .
            'SELECT "user_id" FROM "orders" WHERE placed_at BETWEEN \'2017-01-01\' AND \'2017-12-31\'' .
            ') AND deleted_at IS NULL AND created_at BETWEEN \'2016-12-15\' AND \'2016-12-25\'';

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->isEqualTo($expected);
    }

    public function testSelectSubSelectJoin()
    {
        $driver = 'pgsql';
        \dbQueryHelper::setPdoBinding(true);

        $join = $this->getQuery($driver);
        $join
            ->columns(
                'fullname',
                \dbQueryExpression::make($join, 'MAX(%s)', 'score')
            )
            ->from('scores')
            ->where(\dbQueryConditions::make($join, 'fullname = ?', 'Jane Doe'))
            ->groupBy('fullname');

        $query = $this->getQuery($driver);
        $query
            ->columns(
                'a.fullname',
                'a.id',
                'a.score'
            )
            ->from('scores a')
            ->innerJoin(
                \dbQueryAlias::make($query, $join, 'b'),
                \dbQueryConditions::make($query, 'a.fullname = b.fullname')
                    ->with('a.score = b.score'))
            ->where(\dbQueryConditions::make($query, 'a.score > ?', 50));

        $expected = 'SELECT "a"."fullname", "a"."id", "a"."score" FROM "scores" AS "a" INNER JOIN (' .
            'SELECT "fullname", MAX("score") FROM "scores" WHERE fullname = ? GROUP BY "fullname") ' .
            'AS "b" ON "a"."fullname" = "b"."fullname" AND "a"."score" = "b"."score" WHERE "a"."score" > ?';

        $this
            ->string($query->sql())
            ->isEqualTo($expected);
        $this
            ->array($query->params())
            ->containsValues(array('Jane Doe', 50));

        $expected = 'SELECT "a"."fullname", "a"."id", "a"."score" FROM "scores" AS "a" INNER JOIN (' .
            'SELECT "fullname", MAX("score") FROM "scores" WHERE fullname = \'Jane Doe\' GROUP BY "fullname") ' .
            'AS "b" ON "a"."fullname" = "b"."fullname" AND "a"."score" = "b"."score" WHERE "a"."score" > 50';

        \dbQueryHelper::setPdoBinding(false);
        $this
            ->string($query->sql())
            ->isEqualTo($expected);
    }

    public function testSurround($driver, $expected)
    {
        $query = $this->getQuery($driver);
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($query->surround('user'))
            ->isEqualTo($expected);
    }
    protected function testSurroundDataProvider()
    {
        return array(
            array('mysql', '`user`'),
            array('mysqli', '`user`'),
            array('mysqlimb4', '`user`'),
            array('pgsql', '"user"'),
            array('sqlite', '"user"')
        );
    }

    public function testSurroundCommon()
    {
        $query = new \dbQuery(null);
        \dbQueryHelper::setPdoBinding(true);

        $this
            ->string($query->surround('user'))
            ->isEqualTo('user');
    }
}
