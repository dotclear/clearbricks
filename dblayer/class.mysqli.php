<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is a mysqli driver for Clearbrick.
#
# Copyright (c) 2011 Maxime Varinard @ Vaisonet
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

/**
* MySQLi Database Driver
*
* See the {@link dbLayer} documentation for common methods.
*
* @package Clearbricks
* @subpackage DBLayer
*/
if (class_exists('dbLayer'))
{
	class mysqliConnection extends dbLayer implements i_dbLayer
	{
		/** @var boolean	Enables weak locks if true */
		public static $weak_locks = true;
		
		/** @ignore */
		protected $__driver = 'mysqli';
		
				
		/** @ignore */
		public function db_connect($host,$user,$password,$database)
		{
			if (!function_exists('mysqli_connect')) {
				throw new Exception('PHP MySQLi functions are not available');
			}
			
			if (($link = @mysqli_connect($host,$user,$password,$database)) === false) {
				throw new Exception('Unable to connect to database');
			}
			
			$this->db_post_connect($link,$database);
			
			return $link;
		}
		
		/** @ignore */
		public function db_pconnect($host,$user,$password,$database)
		{
			// No pconnect wtih mysqli, below code is for comatibility 
			return $this->db_connect($host,$user,$password,$database);
		}
		
		/** @ignore */
		private function db_post_connect($link,$database)
		{
			if (version_compare($this->db_version($link),'4.1','>='))
			{
				$this->db_query($link,'SET NAMES utf8');
				$this->db_query($link,'SET CHARACTER SET utf8');
				$this->db_query($link,"SET COLLATION_CONNECTION = 'utf8_general_ci'");
				$this->db_query($link,"SET COLLATION_SERVER = 'utf8_general_ci'");
				$this->db_query($link,"SET CHARACTER_SET_SERVER = 'utf8'");
				$this->db_query($link,"SET CHARACTER_SET_DATABASE = 'utf8'");
				$link->set_charset("utf8");
			}
		}
		
		/** @ignore */
		public function db_close($handle)
		{
			if ($handle instanceof MySQLi) {
				mysqli_close($handle);
			}
		}
		
		/** @ignore */
		public function db_version($handle)
		{
			if ($handle instanceof MySQLi) {
				return mysqli_get_server_info($handle);
			}
			return null;
		}
		
		/** @ignore */
		public function db_query($handle,$query)
		{
			if ($handle instanceof MySQLi)
			{
			
				$res = @mysqli_query($handle, $query);
				if ($res === false) {
					$e = new Exception($this->db_last_error($handle));
					$e->sql = $query;
					throw $e;
				}
				return $res;
			}
		}
		
		/** @ignore */
		public function db_exec($handle,$query)
		{
			return $this->db_query($handle,$query);
		}
		
		/** @ignore */
		public function db_num_fields($res)
		{
			if ($res instanceof MySQLi_Result) {
				//return mysql_num_fields($res);
				return $res->field_count;
			}
			return 0;
		}
		
		/** @ignore */
		public function db_num_rows($res)
		{
			if ($res instanceof MySQLi_Result) {
				return $res->num_rows;
			}
			return 0;
		}
		
		/** @ignore */
		public function db_field_name($res,$position)
		{
			if ($res instanceof MySQLi_Result) {
				$res->field_seek($position);
				$finfo = $res->fetch_field();
				return $finfo->name;
			}
		}
		
		/** @ignore */
		public function db_field_type($res,$position)
		{
			if ($res instanceof MySQLi_Result) {
				$res->field_seek($position);
				$finfo = $res->fetch_field();
				return $this->_convert_types($finfo->type);
			}
		}
		
		/** @ignore */
		public function db_fetch_assoc($res)
		{
			if ($res instanceof MySQLi_Result) {
				$v = $res->fetch_assoc();
				return($v === NULL) ? false : $v;
			}
		}
		
		/** @ignore */
		public function db_result_seek($res,$row)
		{
			if ($res instanceof MySQLi_Result) {
				return $res->data_seek($row);
			}
		}
		
		/** @ignore */
		public function db_changes($handle,$res)
		{
			if ($handle instanceof MySQLi) {
				return mysqli_affected_rows($handle);
			}
		}
		
		/** @ignore */
		public function db_last_error($handle)
		{
			if ($handle instanceof MySQLi)
			{
				$e = mysqli_error($handle);
				if ($e) {
					return $e.' ('.mysqli_errno($handle).')';
				}
			}		
			return false;
		}
		
		/** @ignore */
		public function db_escape_string($str,$handle=null)
		{
			if ($handle instanceof MySQLi) {
				
				return mysqli_real_escape_string($handle, $str);
			}
			return addslashes($str);
		}
		
		/** @ignore */
		public function db_write_lock($table)
		{
			try {
				$this->execute('LOCK TABLES '.$this->escapeSystem($table).' WRITE');
			} catch (Exception $e) {
				# As lock is a privilege in MySQL, we can avoid errors with weak_locks static var
				if (!self::$weak_locks) {
					throw $e;
				}
			}
		}
		
		/** @ignore */
		public function db_unlock()
		{
			try {
				$this->execute('UNLOCK TABLES');
			} catch (Exception $e) {
				if (!self::$weak_locks) {
					throw $e;
				}
			}
		}
		
		/** @ignore */
		public function vacuum($table)
		{
			$this->execute('OPTIMIZE TABLE '.$this->escapeSystem($table));
		}
		
		/** @ignore */
		public function dateFormat($field,$pattern)
		{
			$pattern = str_replace('%M','%i',$pattern);
			
			return 'DATE_FORMAT('.$field.','."'".$this->escape($pattern)."') ";
		}
		
		/** @ignore */
		public function concat()
		{
			$args = func_get_args();
			return 'CONCAT('.implode(',',$args).')';
		}
		
		/** @ignore */
		public function escapeSystem($str)
		{
			return '`'.$str.'`';
		}
		
		protected function _convert_types($id) {
			$id2type = array(
				'1'=>'int',
				'2'=>'int',
				'3'=>'int',
				'8'=>'int',
				'9'=>'int',
		
				'16'=>'int', //BIT type recognized as unknown with mysql adapter
				
				'4'=>'real',
				'5'=>'real',
				'246'=>'real',
			
				'253'=>'string',
				'254'=>'string',
				
				'10'=>'date',
				'11'=>'time',
				'12'=>'datetime',
				'13'=>'year',
			
				'7'=>'timestamp',
			
				'252'=>'blob'
				
			); 
			$type = 'unknown';
			
			if(isset($id2type[$id])) $type = $id2type[$id];
			
			return $type;
		}
		
	}
}
?>