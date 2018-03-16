<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
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

require_once CLEARBRICKS_PATH . '/dbschema/class.dbschema.php';

class dbSchema extends atoum
{
    private $prefix = 'dc_';
    private $index  = 0;

    private function getConnection($driver)
    {
        $controller              = new \atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    public function testQueryForCreateTable($driver, $query)
    {
        $con = $this->getConnection($driver);

        $table_name = $this->prefix . 'blog';
        $fields     = array('status' => array('type' => 'smallint', 'len' => 0, 'null' => false, 'default' => -2));

        $this
            ->if($schema = \dbSchema::init($con))
            ->and($schema->createTable($table_name, $fields))
            ->then()
            ->mock($con)->call('execute')
            ->withIdenticalArguments($query)
            ->once();
    }

    public function testQueryForRetrieveFields($driver, $query)
    {
        $con = $this->getConnection($driver);

        $table_name = $this->prefix . 'blog';

        $this
            ->if($schema = \dbSchema::init($con))
            ->and($schema->getColumns($table_name))
            ->then()
            ->mock($con)->call('select')
            ->withIdenticalArguments($query)
            ->once();
    }

    public function testGetColumns($driver, $row, $result)
    {
        $con = $this->getConnection($driver);

        $rs_controller              = new \atoum\mock\controller();
        $rs_controller->__construct = function () {
            $this->__fetch = false;
        };

        $rs                          = new \mock\record(true, array('con' => $con), $rs_controller);
        $this->calling($con)->select = function () use ($rs) {
            return $rs;
        };

        $this->calling($rs)->fetch = function () use ($row) {
            // need to deal with several rows
            if (!$this->__fetch) {
                $this->__fetch = true;
                return true;
            } else {
                return false;
            }
        };

        $this->calling($rs)->__get = function ($n) use ($row) {
            return $row[$n];
        };
        $this->calling($rs)->f = function ($n) use ($row) {
            return $row[$n];
        };

        $table_name = $this->prefix . 'blog';

        $schema  = \dbSchema::init($con);
        $columns = $schema->getColumns($table_name);

        $this
            ->string($columns['status']['type'])
            ->isIdenticalTo($result['status']['type'])
            ->integer((int) $columns['status']['len']) // len can be null
            ->isIdenticalTo($result['status']['len'])
            ->boolean($columns['status']['null'])
            ->isIdenticalTo($result['status']['null'])
            ->string($columns['status']['default'])
            ->isIdenticalTo($result['status']['default']);
    }

    public function testDefaultNullMustBeNullNotString($driver, $row, $result)
    {
        $con = $this->getConnection($driver);

        $rs_controller              = new \atoum\mock\controller();
        $rs_controller->__construct = function () {
            $this->__fetch = false;
        };

        $rs                          = new \mock\record(true, array('con' => $con), $rs_controller);
        $this->calling($con)->select = function () use ($rs) {
            return $rs;
        };

        $this->calling($rs)->fetch = function () use ($row) {
            // need to deal with several rows
            if (!$this->__fetch) {
                $this->__fetch = true;
                return true;
            } else {
                return false;
            }
        };

        $this->calling($rs)->__get = function ($n) use ($row) {
            return $row[$n];
        };
        $this->calling($rs)->f = function ($n) use ($row) {
            return $row[$n];
        };

        $table_name = $this->prefix . 'blog';

        $schema  = \dbSchema::init($con);
        $columns = $schema->getColumns($table_name);

        $this
            ->string($columns['status']['type'])
            ->isIdenticalTo($result['status']['type'])
            ->integer((int) $columns['status']['len']) // len can be null
            ->isIdenticalTo($result['status']['len'])
            ->boolean($columns['status']['null'])
            ->isIdenticalTo($result['status']['null'])
            ->variable($columns['status']['default'])
            ->isNull($result['status']['default']);
    }

    /*
     * providers
     **/
    protected function testQueryForCreateTableDataProvider()
    {
        $query['pgsql'] = sprintf('CREATE TABLE "%sblog" (' . "\n", $this->prefix);
        $query['pgsql'] .= 'status smallint NOT NULL DEFAULT -2 ' . "\n";
        $query['pgsql'] .= ')';

        $query['mysql'] = sprintf('CREATE TABLE `%sblog` (' . "\n", $this->prefix);
        $query['mysql'] .= '`status` smallint NOT NULL DEFAULT -2 ' . "\n";
        $query['mysql'] .= ') ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin ';

        $query['mysqli'] = $query['mysql'];

        $query['mysqlimb4'] = sprintf('CREATE TABLE `%sblog` (' . "\n", $this->prefix);
        $query['mysqlimb4'] .= '`status` smallint NOT NULL DEFAULT -2 ' . "\n";
        $query['mysqlimb4'] .= ') ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        return array(
            array('pgsql', $query['pgsql']),
            array('mysql', $query['mysql']),
            array('mysqli', $query['mysqli']),
            array('mysqlimb4', $query['mysqlimb4']),
        );
    }

    protected function testQueryForRetrieveFieldsDataProvider()
    {
        $query['pgsql'] = sprintf("SELECT column_name, udt_name, character_maximum_length, is_nullable, column_default FROM information_schema.columns WHERE table_name = '%sblog' ", $this->prefix);

        $query['mysql'] = sprintf('SHOW COLUMNS FROM `%sblog`', $this->prefix);

        $query['mysqli'] = $query['mysql'];

        $query['mysqlimb4'] = $query['mysql'];

        return array(
            array('pgsql', $query['pgsql']),
            array('mysql', $query['mysql']),
            array('mysqli', $query['mysqli']),
            array('mysqlimb4', $query['mysqlimb4']),
        );
    }

    protected function testGetColumnsDataProvider()
    {
        $row['pgsql'] = array(
            'column_name'              => 'status',
            'udt_name'                 => 'int2',
            'is_nullable'              => 'NO',
            'column_default'           => '(-2)',
            'character_maximum_length' => null,
        );
        $result['pgsql'] = array('status' => array(
            'type'    => 'int2',
            'len'     => 0,
            'null'    => false,
            'default' => '-2',
        ));

        $row['mysql'] = array(
            'Field'   => 'status',
            'Type'    => 'smallint(6)',
            'Null'    => 'NO',
            'Default' => '-2',
        );
        $result['mysql'] = array('status' => array(
            'type'    => 'smallint',
            'len'     => 6,
            'null'    => false,
            'default' => '-2',
        ));

        $result['mysqli'] = $result['mysql'];
        $row['mysqli']    = $row['mysql'];

        $result['mysqlimb4'] = $result['mysql'];
        $row['mysqlimb4']    = $row['mysql'];

        return array(
            array('pgsql', $row['pgsql'], $result['pgsql']),
            array('mysql', $row['mysql'], $result['mysql']),
            array('mysqli', $row['mysqli'], $result['mysqli']),
            array('mysqlimb4', $row['mysqlimb4'], $result['mysqlimb4']),
        );
    }

    protected function testDefaultNullMustBeNullNotStringDataProvider()
    {
        $row['pgsql'] = array(
            'column_name'              => 'status',
            'udt_name'                 => 'int2',
            'is_nullable'              => 'NO',
            'column_default'           => 'NULL::character varying',
            'character_maximum_length' => null,
        );
        $result['pgsql'] = array('status' => array(
            'type'    => 'int2',
            'len'     => 0,
            'null'    => false,
            'default' => null,
        ));

        $row['mysql'] = array(
            'Field'   => 'status',
            'Type'    => 'smallint(6)',
            'Null'    => 'NO',
            'Default' => 'NULL',
        );
        $result['mysql'] = array('status' => array(
            'type'    => 'smallint',
            'len'     => 6,
            'null'    => false,
            'default' => null,
        ));

        $result['mysqli'] = $result['mysql'];
        $row['mysqli']    = $row['mysql'];

        $result['mysqlimb4'] = $result['mysql'];
        $row['mysqlimb4']    = $row['mysql'];

        return array(
            array('pgsql', $row['pgsql'], $result['pgsql']),
            array('mysql', $row['mysql'], $result['mysql']),
            array('mysqli', $row['mysqli'], $result['mysqli']),
            array('mysqlimb4', $row['mysqlimb4'], $result['mysqlimb4']),
        );
    }
}
