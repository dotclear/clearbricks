<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2006 Olivier Meunier and contributors. All rights
# reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****
/// @cond

class pgsqlConnection extends dbLayer implements i_dbLayer
{
	protected $__driver = 'pgsql';
	
	private function get_connection_string($host,$user,$password,$database)
	{
		$str = '';
		$port = false;
		
		if ($host)
		{
			if (strpos($host,':') !== false) {
				$bits = explode(':',$host);
				$host = array_shift($bits);
				$port = abs((integer) array_shift($bits));
			}
			$str .= "host = '".addslashes($host)."' ";
			
			if ($port) {
				$str .= 'port = '.$port.' ';
			}
		}
		if ($user) {
			$str .= "user = '".addslashes($user)."' ";
		}
		if ($password) {
			$str .= "password = '".addslashes($password)."' ";
		}
		if ($database) {
			$str .= "dbname = '".addslashes($database)."' ";
		}
		
		return $str;
	}
	
	public function db_connect($host,$user,$password,$database)
	{
		if (!function_exists('pg_connect')) {
			throw new Exception('PHP PostgreSQL functions are not available');
		}
		
		$str = $this->get_connection_string($host,$user,$password,$database);
		
		if (($link = @pg_connect($str)) === false) {
			throw new Exception('Unable to connect to database');
		}
		
		return $link;
	}
	
	public function db_pconnect($host,$user,$password,$database)
	{
		if (!function_exists('pg_pconnect')) {
			throw new Exception('PHP PostgreSQL functions are not available');
		}
		
		$str = $this->get_connection_string($host,$user,$password,$database);
		
		if (($link = @pg_pconnect($str)) === false) {
			throw new Exception('Unable to connect to database');
		}
		
		return $link;
	}
	
	public function db_close($handle)
	{
		if (is_resource($handle)) {
			pg_close($handle);
		}
	}
	
	public function db_version($handle)
	{
		if (is_resource($handle))
		{
			$v = pg_version($handle);
			if (isset($v['server'])) {
				return $v['server'];
			}
		}
		return null;
	}
	
	public function db_query($handle,$query)
	{
		if (is_resource($handle))
		{
			$res = @pg_query($handle,$query);
			if ($res === false) {
				$e = new Exception($this->db_last_error($handle));
				$e->sql = $query;
				throw $e;
			}
			return $res;
		}
	}
	
	public function db_num_fields($res)
	{
		if (is_resource($res)) {
			return pg_num_fields($res);
		}
		return 0;
	}
	
	public function db_num_rows($res)
	{
		if (is_resource($res)) {
			return pg_num_rows($res);
		}
		return 0;
	}
	
	public function db_field_name($res,$position)
	{
		if (is_resource($res)) {
			return pg_field_name($res,$position);
		}
	}
	
	public function db_field_type($res,$position)
	{
		if (is_resource($res)) {
			return pg_field_type($res,$position);
		}
	}
	
	public function db_fetch_assoc($res)
	{
		if (is_resource($res)) {
			return pg_fetch_assoc($res);
		}
	}
	
	public function db_result_seek($res,$row)
	{
		if (is_resource($res)) {
			return pg_result_seek($res,(int) $row);
		}
		return false;
	}
	
	public function db_changes($handle,$res)
	{
		if (is_resource($handle) && is_resource($res)) {
			return pg_affected_rows($res);
		}
	}
	
	public function db_last_error($handle)
	{
		if (is_resource($handle)) {
			return pg_last_error($handle);
		}
		return false;
	}
	
	public function db_escape_string($str,$handle=null)
	{
		return pg_escape_string($str);
	}
	
	public function vacuum($table)
	{
		$this->execute('VACUUM FULL '.$this->escapeSystem($table));
	}
	
	public function dateFormat($field,$pattern)
	{
		$rep = array(
			'%d' => 'DD',
			'%H' => 'HH24',
			'%M' => 'MI',
			'%m' => 'MM',
			'%S' => 'SS',
			'%Y' => 'YYYY'
		);
		
		$pattern = str_replace(array_keys($rep),array_values($rep),$pattern);
		
		return 'TO_CHAR('.$field.','."'".$this->escape($pattern)."') ";
	}
	
	public function prepare($query)
	{
		return new pgsqldbStatement($this,$query);
	}
}

class pgsqldbStatement extends dbStatement
{
	protected $name;
	protected $subst = array();
	protected $handle;
	
	public function __construct(&$con,$query)
	{
		parent::__construct($con,$query);
		
		$this->name = md5(uniqid());
		$this->handle = $this->con->link();
		
		if (preg_match_all('/:([a-zA-Z0-9_]+)/',$this->query,$m)) {
			foreach ($m[1] as $k => $v) {
				$this->query = preg_replace('/:'.$v.'/','\$'.($k+1),$this->query);
				$this->subst[$v] = $k;
			}
		}
		
		
		if (@pg_prepare($this->handle,$this->name,$this->query) === false) {
			throw new Exception($this->con->db_last_error($this->handle));
		}
	}
	
	public function execute($params)
	{
		$p = array();
		foreach ($params as $k => $v) {
			if (isset($this->subst[$k])) {
				$p[$this->subst[$k]] = $v;
			}
		}
		
		if (is_resource($this->handle))
		{
			$res = @pg_execute($this->handle,$this->name,$p);
			if ($res === false) {
				throw new Exception($this->con->db_last_error($this->handle));
			}
			return $res;
		}
	}
}

/// @endcond
?>