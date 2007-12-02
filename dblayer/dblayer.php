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

/// @defgroup CB_DBLAYER Clearbricks Database Abstraction Layer
/// @ingroup CLEARBRICKS

/**
@ingroup CB_DBLAYER
@brief Clearbricks Database Abstraction Layer interface

All methods in this interface should be implemented in your database driver.

Database driver is a class that extends dbLayer, implements i_dbLayer and has
a name of the form (driver name)Connection.
*/
interface i_dbLayer
{
	/**
	This method should open a database connection and return a new resource
	link.
	
	@param	host		<b>string</b>		Database server host
	@param	user		<b>string</b>		Database user name
	@param	password	<b>string</b>		Database password
	@param	database	<b>string</b>		Database name
	@returns	<b>resource</b>
	*/
	function db_connect($host,$user,$password,$database);
	
	/**
	This method should open a persistent database connection and return a new
	resource link.
	
	@param	host		<b>string</b>		Database server host
	@param	user		<b>string</b>		Database user name
	@param	password	<b>string</b>		Database password
	@param	database	<b>string</b>		Database name
	@returns	<b>resource</b>
	*/
	function db_pconnect($host,$user,$password,$database);
	
	/**
	This method should close resource link.
	
	@param	handle	<b>resource</b>	Resource link
	*/
	function db_close($handle);
	
	/**
	This method should return database version number.
	
	@param	handle	<b>resource</b>	Resource link
	@returns	<b>string</b>
	*/
	function db_version($handle);
	
	/**
	This method should run an SQL query and return a resource result.
	
	@param	handle	<b>resource</b>	Resource link
	@param	query	<b>string</b>		SQL query string
	@return	<b>resource</b>
	*/
	function db_query($handle,$query);
	
	/**
	This method should return the number of fields in a result.
	
	@param	res		<b>resource</b>	Resource result
	@return	<b>integer</b>
	*/
	function db_num_fields($res);
	
	/**
	This method should return the number of rows in a result.
	
	@param	res		<b>resource</b>	Resource result
	@return	<b>integer</b>
	*/
	function db_num_rows($res);
	
	/**
	This method should return the name of the field at the given position
	<var>$position</var>.
	
	@param	res		<b>resource</b>	Resource result
	@param	position	<b>integer</b>		Field position
	@return	<b>string</b>
	*/
	function db_field_name($res,$position);
	
	/**
	This method should return the field type a the given position
	<var>$position</var>.
	
	@param	res		<b>resource</b>	Resource result
	@param	position	<b>integer</b>		Field position
	@return	<b>string</b>
	*/
	function db_field_type($res,$position);
	
	/**
	This method should fetch one line of result and return an associative array
	with field name as key and field value as value.
	
	@param	res		<b>resource</b>	Resource result
	@return	<b>array</b>
	*/
	function db_fetch_assoc($res);
	
	/**
	This method should move result cursor on given row position <var>$row</var>
	and return true on success.
	
	@param	res		<b>resource</b>	Resource result
	@param	row		<b>integer</b>		Row position
	@return	<b>boolean</b>
	*/
	function db_result_seek($res,$row);
	
	/**
	This method should return number of rows affected by INSERT, UPDATE or
	DELETE queries.
	
	@param	handle	<b>resource</b>	Resource link
	@param	res		<b>resource</b>	Resource result
	@return	<b>integer</b>
	*/
	function db_changes($handle,$res);
	
	/**
	This method should return the last error string for the current connection.
	
	@param	handle	<b>resource</b>	Resource link
	@return	<b>string</b>
	*/
	function db_last_error($handle);
	
	/**
	This method should return an escaped string for the current connection.
	
	@param	str		<b>string</b>		String to escape
	@param	handle	<b>resource</b>	Resource link
	@return	<b>string</b>
	*/
	function db_escape_string($str,$handle=null);
}

/**
@ingroup CB_DBLAYER
@brief Database Abstraction Layer class

Base class for database abstraction. Each driver extends this class and
implements i_dbLayer interface.
*/
abstract class dbLayer
{
	protected $__driver = null;		///< <b>string</b>		Driver name
	protected $__version = null;		///< <b>string</b>		Database version
	
	protected $__link;				///< <b>resource</b>	Database resource link
	protected $__last_result;		///< <b>resource</b>	Last result resource
	
	/**
	Static function to use to init database layer. Returns a object extending
	dbLayer.
	
	@param	driver		<b>string</b>		Driver name
	@param	host			<b>string</b>		Database hostname
	@param	database		<b>string</b>		Database name
	@param	user			<b>string</b>		User ID
	@param	password		<b>string</b>		Password
	@param	persistent	<b>boolean</b>		Persistent connection (false)
	@return	<b>object</b>
	*/
	public static function init($driver,$host,$database,$user='',$password='',$persistent=false)
	{
		if (file_exists(dirname(__FILE__).'/class.'.$driver.'.php')) {
			require_once dirname(__FILE__).'/class.'.$driver.'.php';
			$driver_class = $driver.'Connection';
		} else {
			trigger_error('Unable to load DB layer for '.$driver,E_USER_ERROR);
			exit(1);
		}
		
		return new $driver_class($host,$database,$user,$password,$persistent);
	}
	
	/**
	Inits database connection.
	
	@param	host			<b>string</b>		User ID
	@param	database		<b>string</b>		Password
	@param	user			<b>string</b>		Server to connect
	@param	password		<b>string</b>		Database name
	@param	persistent	<b>boolean</b>		Open a persistent connection
	*/
	public function __construct($host,$database,$user='',$password='',$persistent=false)
	{
		if ($persistent) {
			$this->__link = $this->db_pconnect($host,$user,$password,$database);
		} else {
			$this->__link = $this->db_connect($host,$user,$password,$database);
		}
		
		$this->__version = $this->db_version($this->__link);
		$this->__database = $database;
	}
	
	/**
	Closes database connection.
	*/
	public function close()
	{
		$this->db_close($this->__link);
	}
	
	/**
	Returns database driver name
	
	@return	<b>string</b>
	*/
	public function driver()
	{
		return $this->__driver;
	}
	
	/**
	Returns database driver version
	
	@return	<b>string</b>
	*/
	public function version()
	{
		return $this->__version;
	}
	
	/**
	Returns current database name
	
	@return	<b>string</b>
	*/
	public function database()
	{
		return $this->__database;
	}
	
	/**
	Returns link resource
	
	@return	<b>resource</b>
	*/
	public function link()
	{
		return $this->__link;
	}
	
	/**
	Executes a query and return a dbRecordset object.
	$sql could be a string or a prepared statement. In this last case,
	<var>$params</var> is supplied.
	
	@param	sql		<b>mixed</b>		SQL query or dbStatement instance
	@param	params	<b>array</b>		Data array
	@return	<b>dbRecordset</b>
	*/
	public function query($sql,$params=array())
	{
		if ($sql instanceof dbStatement) {
			if (!is_array($params)) {
				throw new Exception('Invalid statement parameters');
			}
			$result = $sql->execute($params);
		} else {
			$result = $this->db_query($this->__link,$sql);
		}
		
		$this->__last_result =& $result;
		
		$info = array();
		$info['con'] =& $this;
		$info['cols'] = $this->db_num_fields($result);
		$info['rows'] = $this->db_num_rows($result);
		$info['info'] = array();
		
		for ($i=0; $i<$info['cols']; $i++) {
			$info['info']['name'][] = $this->db_field_name($result,$i);
			$info['info']['type'][] = $this->db_field_type($result,$i);
		}
		
		return new dbRecordset($result,$info);
	}
	
	/**
	Begins a transaction.
	*/
	public function begin()
	{
		$this->query('BEGIN');
	}
	
	/**
	Commits a transaction.
	*/
	public function commit()
	{
		$this->query('COMMIT');
	}
	
	/**
	Rollbacks a transaction.
	*/
	public function rollback()
	{
		$this->query('ROLLBACK');
	}
	
	/**
	Vacuum the table given in argument.
	
	@param	table	<b>string</b>		Table name
	*/
	public function vacuum($table)
	{
	}
	
	/**
	Returns the number of lines affected by the last DELETE, INSERT or UPDATE
	query.
	
	@return	<b>integer</b>
	*/
	public function changes()
	{
		return $this->db_changes($this->__link,$this->__last_result);
	}
	
	/**
	Returns the last database error or false if no error.
	
	@returns <b>string</b>
	*/
	public function error()
	{
		$err = $this->db_last_error($this->__link);
		
		if (!$err) {
			return false;
		}
		
		return $err;
	}

	/**
	Returns a query fragment with date formater.
	
	The following modifiers are accepted:
	
	- %d : Day of the month, numeric
	- %H : Hour 24 (00..23)
	- %M : Minute (00..59)
	- %m : Month numeric (01..12)
	- %S : Seconds (00..59)
	- %Y : Year, numeric, four digits
	
	@param	field	<b>string</b>		Field name
	@param	pattern	<b>string</b>		Date format
	@return	<b>string</b>
	*/
	public function dateFormat($field,$pattern)
	{
		return
		'TO_CHAR('.$field.','."'".$this->escape($pattern)."') ";
	}
	
	/**
	Returns a LIMIT query fragment.
	
	@param	arg1		<b>mixed</b>		array or integer with limit intervals
	@param	arg2		<b>mixed</b>		integer or null (null)
	@return	<b>string</b>
	*/
	public function limit($arg1,$arg2=null)
	{
		if (is_array($arg1))
		{
			$arg1 = array_values($arg1);
			$arg2 = isset($arg1[1]) ? $arg1[1] : null;
			$arg1 = $arg1[0];
		}
		
		if ($arg2 === null) {
			$sql = ' LIMIT '.(integer) $arg1.' ';
		} else {
			$sql = ' LIMIT '.(integer) $arg2.' OFFSET '.$arg1.' ';
		}
		
		return $sql;
	}
	
	/**
	Returns a IN query fragment where $in could be an array, a string,
	an integer or null
	
	@param	in		<b>mixed</b>		array, string, integer or null
	@return	<b>string</b>
	*/
	public function in($in)
	{
		if (is_null($in))
		{
			return ' IN (NULL) ';
		}
		elseif (is_string($in))
		{
			return " IN ('".$this->escape($in)."') ";
		}
		elseif (is_array($in))
		{
			foreach ($in as $i => $v) {
				if (is_null($v)) {
					$in[$i] = 'NULL';
				} elseif (is_string($v)) {
					$in[$i] = "'".$this->escape($v)."'";
				}
			}
			return ' IN ('.implode(',',$in).') ';
		}
		else
		{
			return ' IN ( '.(integer) $in.') ';
		}
	}
	
	/**
	Returns SQL concatenation of methods arguments. Theses arguments
	should be properly escaped when needed.
	
	@return	<b>string</b>
	*/
	public function concat()
	{
		$args = func_get_args();
		return implode(' || ',$args);
	}
	
	/**
	Returns SQL protected string or array values.
	
	@param	i		<b>mixed</b>		String or array to protect
	@return	<b>mixed</b>
	*/
	public function escape($i)
	{
		if (is_array($i)) {
			foreach ($i as $k => $s) {
				$i[$k] = $this->db_escape_string($s,$this->__link);
			}
			return $i;
		}
		
		return $this->db_escape_string($i,$this->__link);
	}
	
	/**
	Returns SQL system protected string.
	
	@param	str		<b>string</b>		String to protect
	@return	<b>string</b>
	*/
	public function escapeSystem($str)
	{
		return '"'.$str.'"';
	}
	
	/**
	Returns a new instance of dbStatement class for prepared query
	<var>$query</var>.
	
	@param	query	<b>string</b>		Query to prepare
	@return	<b>dbStatement</b>
	*/
	public function prepare($query)
	{
		return new dbStatement($this,$query);
	}
	
	/**
	Returns a new instance of dbCursor class on <var>$table</var> for the current
	connection.
	
	@param	table	<b>string</b>		Cursor table
	@return	<b>dbCursor</b>
	*/
	public function cursor($table)
	{
		return new dbCursor($this,$table);
	}
}

/**
@ingroup CB_DBLAYER
@brief Query statement class

This class handles prepared queries. It is called by dbLayer::prepare() method.
*/
class dbStatement
{
	protected $con;			///< <b>dbLayer</b>		dbLayer instance
	protected $query;			///< <b>string</b>		Query
	
	/**
	Inits query statement.
	
	@param	con			<b>dbLayer</b>		dbLayer instance
	@param	query		<b>string</b>		Query to prepare
	*/
	public function __construct(&$con,$query)
	{
		$this->con =& $con;
		$this->query = $query;
	}
	
	/**
	Execute prepared query with given parameters <b>$params</b>. This method
	is called by dbLayer::query().
	
	@param	params		<b>array</b>		Parameters
	*/
	public function execute($params)
	{
		$query = $this->query;
		foreach ($params as $k => $v)
		{
			if (is_null($v)) {
				$v = 'NULL';
			} elseif (is_string($v)) {
				$v = "'".$this->con->escape($v)."'";
			}
			
			$query = preg_replace('/:'.preg_quote($k,'/').'/',$v,$query);
		}
		
		return $this->con->db_query($this->con->link(),$query);
	}
}

/**
@ingroup CB_DBLAYER
@brief Query Results Reccordset Class

This class acts as an iterator over database query result. It does not fetch
all results on instantiation and thus, depending on database engine, should not
fill PHP process memory.
*/
class dbRecordset implements Iterator
{
	protected $link;				///< <b>resource</b>	Database resource link
	protected $result;				///< <b>resource</b>	Query result resource
	protected $info;				///< <b>array</b>		Result information array
	
	protected $index = 0;			///< <b>integer</b>		Current result position
	protected $row = false;			///< <b>array</b>		Current result row content
	
	protected $record_class;			///< <b>string</b>		Record class name
	
	/**
	Creates class instance from result link and some informations.
	<var>$info</var> is an array with the following content:
	
	- con => database object instance
	- cols => number of columns
	- rows => number of rows
	- info
	  - name => an array with columns names
	  - type => an array with columns types
	
	@param	result	<b>resource</b>	Resource result
	@param	info		<b>array</b>		Information array
	*/
	public function __construct($result,$info)
	{
		$this->result = $result;
		$this->info = $info;
		$this->setRecordClass('dbRecord');
	}
	
	/**
	This method allows to declare a class name for objects returned while
	fetching results. The class shoul inherit from dbRecord.
	
	@param	n		<b>string</b>		Class name
	*/
	public function setRecordClass($n)
	{
		if (class_exists($n,true) && is_subclass_of($n,'dbRecord')) {
			$this->record_class = $n;
		} else {
			$this->record_class = 'dbRecord';
		}
	}
	
	/**
	Returns true if record contains no result.
	
	@return	<b>boolean</b>
	*/
	public function isEmpty()
	{
		return $this->count() == 0;
	}
	
	/**
	Returns number of rows in record.
	
	@return	<b>integer</b>
	*/
	public function count()
	{
		return $this->info['rows'];
	}
	
	/**
	Returns an array of columns, with name as key and type as value.
	
	@return	<b>array</b>
	*/
	public function columns()
	{
		return $this->info['info']['name'];
	}
	
	/**
	Returns an array of all rows in record.
	
	@return	<b>array</b>
	*/
	public function rows()
	{
		return $this->getData();
	}
	
	private function getRecord($rows,&$con)
	{
		return new $this->record_class($rows,$con);
	}
	
	private function setRow()
	{
		if (is_array($this->row)) {
			return $this->row;
		}
		
		$this->row = $this->info['con']->db_fetch_assoc($this->result);
		
		if ($this->row !== false)
		{
			foreach ($this->row as $k => $v) {
				$this->row[] =& $this->row[$k];
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	
	private function getData()
	{
		$res = array();
		foreach ($this as $k => $v) {
			$res[$k] = $v->data();
		}
		
		return $res;
	}
	
	/* Iterator methods
	--------------------------------------------------- */
	/// @cond
	public function rewind() {
		# Nothing
	}
	
	public function valid() {
		return $this->index < $this->info['rows'];
	}
	
	public function next() {
		$this->index++;
		$this->row = false;
	}
	
	public function key() {
		return $this->index;
	}
	
	public function current() {
		$this->setRow();
		return $this->getRecord($this->row,$this->info['con']);
	}
	/// @endcond
}

/**
@ingroup CB_DBLAYER
@brief Row Query Result Record Class
*/
class dbRecord
{
	protected $__row;				///< <b>array</b>		Data result row
	protected $__con;				///< <b>dbLayer</b>		dbLayer instance
	
	/**
	Creates record instance for given row and dbLayer.
	
	@param	row		<b>array</b>		Data result row
	@param	con		<b>dbLayer</b>		dbLayer instance
	*/
	public function __construct($row,&$con)
	{
		$this->__row = $row;
		$this->__con =& $con;
	}
	
	/**
	Magic get method. Alias for field().
	
	@param	n		<b>string</b>		Field name
	@return	<b>string</b>
	*/
	public function __get($n)
	{
		return $this->field($n);
	}
	
	/**
	Alias for field().
	
	@param	n		<b>string</b>		Field name
	@return	<b>string</b>
	*/
	public function f($n)
	{
		return $this->field($n);
	}
	
	/**
	Retrieve named <var>$n</var> field value.
	
	@param	n		<b>string</b>		Field name
	@return	<b>string</b>
	*/
	public function field($n)
	{
		return $this->__row[$n];
	}
	
	/**
	Returns true if a field exists.
	
	@param	n		<b>string</b>		Field name
	@return	<b>string</b>
	*/
	public function exists($n)
	{
		return isset($this->__row[$n]);
	}
	
	/**
	Returns the raw data array.
	
	@return	<b>array</b>
	*/
	public function data()
	{
		return $this->__row;
	}
}

/**
@ingroup CB_DBLAYER
@brief Database INSERT/UPDATE helper
*/
class dbCursor
{
	protected $__con;				///< <b>dbLayer</b>		dbLayer instance
	protected $__data = array();		///< <b>array</b>		Data to insert
	protected $__table;				///< <b>string</b>		Table name
	protected $__stmt;				///< <b>dbStatement</b>	dbStatement instance
	protected $__stmt_query;			///< <b>string</b>		Full query for statement
	
	/**
	Creates class instance from dbLayer instance and table name.
	
	@param	con		<b>dbLayer</b>		dbLayer instance
	@param	table	<b>string</b>		Table name
	*/
	public function __construct(&$con,$table)
	{
		$this->__con =& $con;
		$this->__table = $table;
	}
	
	/**
	Sets field value.
	
	@param	n		<b>string</b>		Field name
	@param	v		<b>mixed</b>		Field value
	*/
	public function set($n,$v)
	{
		if (!preg_match('/^[a-zA-Z0-9_]+$/',$n)) {
			throw new Exception('Invalid field name');
		}
		$this->__data[$n] = $v;
	}
	
	/**
	Retrieve named <var>$n</var> field value.
	
	@param	n		<b>string</b>		Field name
	@return	<b>mixed</b>
	*/
	public function get($n)
	{
		if (isset($this->__data[$n])) {
			return $this->__data[$n];
		}
		
		return null;
	}
	
	/**
	Returns true if a field named <var>$n</var> exists.
	
	@param	n		<b>string</b>		Field name
	@return	<b>boolean</b>
	*/
	public function exists($n)
	{
		return isset($this->__data[$n]);
	}
	
	/**
	Sets field value. Alias for set().
	
	@param	n		<b>string</b>		Field name
	@param	v		<b>mixed</b>		Field value
	*/
	public function __set($n,$v)
	{
		$this->set($n,$v);
	}
	
	/**
	Magic get method. Alias for get().
	
	@param	n		<b>string</b>		Field name
	@return	<b>mixed</b>
	*/
	public function __get($n)
	{
		return $this->get($n);
	}
	
	/**
	Sets all fields to null values.
	*/
	public function resetData()
	{
		foreach ($this->__data as &$v) {
			$v = null;
		}
	}
	
	/**
	Returns insert query for statement.
	
	@return	<b>string</b>
	*/
	public function getInsert()
	{
		$fields = $this->formatFields();
		
		return 'INSERT INTO '.$this->__con->escapeSystem($this->__table)." (\n".
				implode(",\n",array_keys($fields))."\n) VALUES (\n".
				implode(",\n",array_values($fields))."\n) ";
	}
	
	/**
	Returns update query for statement.
	
	@param	where		<b>string</b>		Where clause
	@return	<b>string</b>
	*/
	public function getUpdate($where)
	{
		$data = $this->formatFields();
		$fields = array();
		
		$query = 'UPDATE '.$this->__con->escapeSystem($this->__table)." SET \n";
		
		foreach ($data as $k => $v) {
			$fields[] = $k.' = '.$v."";
		}
		
		$query .= implode(",\n",$fields);
		$query .= "\n".$where;
		
		return $query;
	}
	
	/**
	Inserts data into table.
	*/
	public function insert()
	{
		if (!$this->__table) {
			throw new Exception('No table name.');
		}
		
		$query = $this->getInsert();
		
		if (!$this->__stmt || $query != $this->__stmt_query) {
			$this->__stmt = $this->__con->prepare($query);
			$this->__stmt_query = $query;
		}
		
		$this->__con->query($this->__stmt,$this->__data);
		
		return true;
	}
	
	/**
	Updates data on table.
	
	@param	where		<b>string</b>		Where clause
	@param	params		<b>array</b>		Where parameters
	*/
	public function update($where,$params=array())
	{
		if (!$this->__table) {
			throw new Exception('No table name.');
		}
		
		$query = $this->getUpdate($where);
		
		if (!$this->__stmt || $query != $this->__stmt_query) {
			$this->__stmt = $this->__con->prepare($query);
			$this->__stmt_query = $query;
		}
		
		$this->__con->query($this->__stmt,array_merge($this->__data,$params));
		
		return true;
	}
	
	private function formatFields()
	{
		$fields = array();
		foreach ($this->__data as $k => $v)
		{
			if ($v instanceof sqlStatement) {
				$fields[$k] = $v->s;
			} else {
				$fields[$k] = ':'.$k;
			}
		}
		return $fields;
	}
}

/**
@ingroup CB_DBLAYER
@brief A simple object you can use as value in dbCursor
*/
class sqlStatement
{
	public $s;					///< <b>string</b>		SQL statement
	
	/**
	@param	s			<b>string</b>		SQL statement
	*/
	public function __construct($s)
	{
		$this->s = $s;
	}
}
?>