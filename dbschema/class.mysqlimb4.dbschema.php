<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is a mysqli dbchema for Clearbrick.
#
# Mysql and mysqli DBSchema are the same, so this class only
# extends original mysql DBSchema class.
#
# Copyright (c) 2011 Maxime Varinard @ Vaisonet
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require_once('class.mysql.dbschema.php');
class mysqlimb4Schema extends mysqlSchema
{
		public function db_create_table($name,$fields)
		{
			$a = array();

			foreach ($fields as $n => $f)
			{
				$type = $f['type'];
				$len = (integer) $f['len'];
				$default = $f['default'];
				$null = $f['null'];

				$type = $this->udt2dbt($type,$len,$default);
				$len = $len > 0 ? '('.$len.')' : '';
				$null = $null ? 'NULL' : 'NOT NULL';

				if ($default === null) {
					$default = 'DEFAULT NULL';
				} elseif ($default !== false) {
					$default = 'DEFAULT '.$default.' ';
				} else {
					$default = '';
				}

				$a[] =
				$this->con->escapeSystem($n).' '.
				$type.$len.' '.$null.' '.$default;
			}

			$sql =
			'CREATE TABLE '.$this->con->escapeSystem($name)." (\n".
				implode(",\n",$a).
			"\n) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

			$this->con->execute($sql);
		}
}
