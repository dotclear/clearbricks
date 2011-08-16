<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Clearbricks.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

/**
* XML-RPC Client and Server
*
* This class library is fully based on Simon Willison's IXR library.
* {@link http://scripts.incutio.com/xmlrpc/}
*
* @package Clearbricks
* @subpackage XML-RPC
*/

/**
* XML-RPC Exception
*/
class xmlrpcException extends Exception
{
	/**
	* @param string	$message		Exception message
	* @param integer	$code		Exception code
	*/
	public function __construct($message,$code=0)
	{
		parent::__construct($message,$code);
	}
}

/**
* XML-RPC Value
*/
class xmlrpcValue
{
	/** @var mixed Data value */	protected  $data;
	/** @var string Data type */	protected  $type;
	
	/**
	* Constructor
	*
	* @param mixed		$data		Data value
	* @param string	$type		Data type
	*/
	public function __construct($data, $type = false)
	{
		$this->data = $data;
		if (!$type) {
			$type = $this->calculateType();
		}
		$this->type = $type;
		if ($type == 'struct') {
			# Turn all the values in the array in to new xmlrpcValue objects
			foreach ($this->data as $key => $value) {
				$this->data[$key] = new xmlrpcValue($value);
			}
		}
		if ($type == 'array') {
			for ($i = 0, $j = count($this->data); $i < $j; $i++) {
				$this->data[$i] = new xmlrpcValue($this->data[$i]);
			}
		}
	}
	
	/**
	* XML Data
	*
	* Returns an XML subset of the Value.
	*
	* @return string
	*/
	public function getXml()
	{
		# Return XML for this value
		switch ($this->type)
		{
			case 'boolean':
				return '<boolean>'.(($this->data) ? '1' : '0').'</boolean>';
				break;
			case 'int':
				return '<int>'.$this->data.'</int>';
				break;
			case 'double':
				return '<double>'.$this->data.'</double>';
				break;
			case 'string':
				return '<string>'.htmlspecialchars($this->data).'</string>';
				break;
			case 'array':
				$return = '<array><data>'."\n";
				foreach ($this->data as $item) {
					$return .= '  <value>'.$item->getXml()."</value>\n";
				}
				$return .= '</data></array>';
				return $return;
				break;
			case 'struct':
				$return = '<struct>'."\n";
				foreach ($this->data as $name => $value) {
					$return .= "  <member><name>$name</name><value>";
					$return .= $value->getXml()."</value></member>\n";
				}
				$return .= '</struct>';
				return $return;
				break;
			case 'date':
			case 'base64':
				return $this->data->getXml();
				break;
		}
		return false;
	}
	
	/**
	* Calculate Type
	*
	* Returns the type of the value if it was not given in constructor.
	*
	* @return string
	*/
	protected function calculateType()
	{
		if ($this->data === true || $this->data === false) {
			return 'boolean';
		}
		if (is_integer($this->data)) {
			return 'int';
		}
		if (is_double($this->data)) {
			return 'double';
		}
		# Deal with xmlrpc object types base64 and date
		if (is_object($this->data) && $this->data instanceof xmlrpcDate) {
			return 'date';
		}
		if (is_object($this->data) && $this->data instanceof xmlrpcBase64) {
			return 'base64';
		}
		# If it is a normal PHP object convert it in to a struct
		if (is_object($this->data)) {
			$this->data = get_object_vars($this->data);
			return 'struct';
		}
		if (!is_array($this->data)) {
			return 'string';
		}
		# We have an array - is it an array or a struct ?
		if ($this->isStruct($this->data)) {
			return 'struct';
		} else {
			return 'array';
		}
	}
	
	/**
	* Data is struct
	*
	* Returns true if <var>$array</var> is a Struct and not only an Array.
	*
	* @param array		$array		Array
	* @return boolean
	*/
	protected function isStruct($array)
	{
		# Nasty function to check if an array is a struct or not
		$expected = 0;
		foreach ($array as $key => $value) {
			if ((string)$key != (string)$expected) {
				return true;
			}
			$expected++;
		}
		return false;
	}
}

/**
* XML-RPC Message
*/
class xmlrpcMessage
{
	/** @var string Brut XML message*/		protected $brutxml;
	/** @var string XML message */		protected $message;
		    
	/** @var string Type of message */		public $messageType;  # methodCall / methodResponse / fault
	/** @var string Fault code */			public $faultCode;
	/** @var string Fault string */		public $faultString;
	/** @var string Method name */		public $methodName;
	/** @var array Method parameters */	public $params = array();
		    
	# Currentstring variable stacks
	/** @@ignore */	protected $_arraystructs = array();   # The stack used to keep track of the current array/struct
	/** @@ignore */	protected $_arraystructstypes = array(); # Stack keeping track of if things are structs or array
	/** @@ignore */	protected $_currentStructName = array();  # A stack as well
	/** @@ignore */	protected $_param;
	/** @@ignore */	protected $_value;
	/** @@ignore */	protected $_currentTag;
	/** @@ignore */	protected $_currentTagContents;
	
	# The XML parser
	/** @ignore */		protected $_parser;
	
	/**
	* Constructor
	*
	* @param string		$message		XML Message
	*/
	public function __construct($message)
	{
		$this->brutxml = $this->message = $message;
	}
	
	/**
	* Message parser
	*/
	public function parse()
	{
		// first remove the XML declaration
		$this->message = preg_replace('/<\?xml(.*)?\?'.'>/', '', $this->message);
		
		if (trim($this->message) == '') {
			throw new Exception('XML Parser Error. Empty message');
		}
		
		$this->_parser = xml_parser_create();
		
		# Set XML parser to take the case of tags in to account
		xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
		
		# Set XML parser callback functions
		xml_set_object($this->_parser, $this);
		xml_set_element_handler($this->_parser, 'tag_open','tag_close');
		xml_set_character_data_handler($this->_parser, 'cdata');
		
		if (!xml_parse($this->_parser, $this->message))
		{
			$c = xml_get_error_code($this->_parser);
			$e = xml_error_string($c);
			$e .= ' on line '.xml_get_current_line_number($this->_parser);
			throw new Exception('XML Parser Error. '.$e,$c);
		}
		
		xml_parser_free($this->_parser);
		
		# Grab the error messages, if any
		if ($this->messageType == 'fault')
		{
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}
	
	/** @ignore */
	protected function tag_open($parser,$tag,$attr)
	{
		$this->currentTag = $tag;
		
		switch($tag)
		{
			case 'methodCall':
			case 'methodResponse':
			case 'fault':
				$this->messageType = $tag;
				break;
			# Deal with stacks of arrays and structs
			case 'data':    # data is to all intents and puposes more interesting than array
				$this->_arraystructstypes[] = 'array';
				$this->_arraystructs[] = array();
				break;
			case 'struct':
				$this->_arraystructstypes[] = 'struct';
				$this->_arraystructs[] = array();
				break;
		}
	}
	
	/** @ignore */
	protected function cdata($parser,$cdata)
	{
		$this->_currentTagContents .= $cdata;
	}
	
	/** @ignore */
	protected function tag_close($parser,$tag)
	{
		$valueFlag = false;
		
		switch($tag)
		{
			case 'int':
			case 'i4':
				$value = (int)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'double':
				$value = (double)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'string':
				$value = (string)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'dateTime.iso8601':
				$value = new xmlrpcDate(trim($this->_currentTagContents));
				# $value = $iso->getTimestamp();
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'value':
				# "If no type is indicated, the type is string."
				if (trim($this->_currentTagContents) != '')
				{
					$value = (string)$this->_currentTagContents;
					$this->_currentTagContents = '';
					$valueFlag = true;
				}
				break;
			case 'boolean':
				$value = (boolean)trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			case 'base64':
				$value = base64_decode($this->_currentTagContents);
				$this->_currentTagContents = '';
				$valueFlag = true;
				break;
			# Deal with stacks of arrays and structs
			case 'data':
			case 'struct':
				$value = array_pop($this->_arraystructs);
				array_pop($this->_arraystructstypes);
				$valueFlag = true;
				break;
			case 'member':
				array_pop($this->_currentStructName);
				break;
			case 'name':
				$this->_currentStructName[] = trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				break;
			case 'methodName':
				$this->methodName = trim($this->_currentTagContents);
				$this->_currentTagContents = '';
				break;
		}
		
		if ($valueFlag)
		{
			if (count($this->_arraystructs) > 0)
			{
				# Add value to struct or array
				if ($this->_arraystructstypes[count($this->_arraystructstypes)-1] == 'struct') {
					# Add to struct
					$this->_arraystructs[count($this->_arraystructs)-1][$this->_currentStructName[count($this->_currentStructName)-1]] = $value;
				} else {
					# Add to array
					$this->_arraystructs[count($this->_arraystructs)-1][] = $value;
				}
			}
			else
			{
				# Just add as a paramater
				$this->params[] = $value;
			}
		}
	}       
}

/**
* XML-RPC Request
*/
class xmlrpcRequest
{
	/** @var string Request method name */		public $method;
	/** @var array Request method arguments */	public $args;
	/** @var string Request XML string */		public $xml;
	
	/**
	* Constructor
	*
	* @param string	$method		Method name
	* @param array		$args		Method arguments
	*/
	function __construct($method, $args)
	{
		$this->method = $method;
		$this->args = $args;
		
		$this->xml =
		'<?xml version="1.0"?>'."\n".
		"<methodCall>\n".
		'  <methodName>'.$this->method."</methodName>\n".
		"  <params>\n";
		
		foreach ($this->args as $arg)
		{
			$this->xml .= '    <param><value>';
			$v = new xmlrpcValue($arg);
			$this->xml .= $v->getXml();
			$this->xml .= "</value></param>\n";
		}
		
		$this->xml .= '  </params></methodCall>';
	}
	
	/**
	* Request length
	*
	* Returns {@link $xml} content length.
	*
	* @return integer
	*/
	public function getLength()
	{
		return strlen($this->xml);
	}
	
	/**
	* Request XML
	*
	* Returns request XML version.
	*
	* @return string
	*/
	public function getXml()
	{
		return $this->xml;
	}
}

/**
* XML-RPC Date object
*/
class xmlrpcDate
{
	/** @var string */ protected $year;
	/** @var string */ protected $month;
	/** @var string */ protected $day;
	/** @var string */ protected $hour;
	/** @var string */ protected $minute;
	/** @var string */ protected $second;
	
	/**
	* Constructor
	*
	* Creates a new instance of xmlrpcDate. <var>$time</var> could be a
	* timestamp or a litteral date.
	*
	* @param integer|string	$time		Timestamp or litteral date.
	*/
	public function __construct($time)
	{
		# $time can be a PHP timestamp or an ISO one
		if (is_numeric($time)) {
			$this->parseTimestamp($time);
		} else {
			$this->parseTimestamp(strtotime($time));
		}
	}
	
	/**
	* Timestamp parser
	*
	* @param integer		$timestamp	Timestamp
	*/
	protected function parseTimestamp($timestamp)
	{
		$this->year = date('Y', $timestamp);
		$this->month = date('m', $timestamp);
		$this->day = date('d', $timestamp);
		$this->hour = date('H', $timestamp);
		$this->minute = date('i', $timestamp);
		$this->second = date('s', $timestamp);
		$this->ts = $timestamp;
	}
	
	/**
	* ISO Date
	*
	* Returns the date in ISO-8601 format.
	*
	* @return string
	*/
	public function getIso()
	{
		return $this->year.$this->month.$this->day.'T'.$this->hour.':'.$this->minute.':'.$this->second;
	}
	
	/**
	* XML Date
	*
	* Returns the XML fragment for XML-RPC message inclusion.
	*
	* @return string
	*/
	public function getXml()
	{
		return '<dateTime.iso8601>'.$this->getIso().'</dateTime.iso8601>';
	}
	
	/**
	* Timestamp
	*
	* Returns the date timestamp.
	*
	* @return integer
	*/
	public function getTimestamp()
	{
		return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
	}
}

/**
* XML-RPC Base 64 object
*/
class xmlrpcBase64
{
	/** @var string */ protected $data;
	
	/**
	* Constructor
	*
	* Create a new instance of xmlrpcBase64.
	*
	* @param string		$data		Data
	*/
	public function __construct($data)
	{
		$this->data = $data;
	}
	
	/**
	* XML Data
	*
	* Returns the XML fragment for XML-RPC message inclusion.
	*
	* @return string
	*/
	public function getXml()
	{
		return '<base64>'.base64_encode($this->data).'</base64>';
	}
}

/**
* XML-RPC Client
*
* Basic XML-RPC Client.
*/
if (class_exists('netHttp'))
{
	class xmlrpcClient extends netHttp
	{
		/** @var xmlrpcRequest XML-RPC Request object */ protected $request;
		/** @var xmlrpcMessage XML-RPC Message object */ protected $message;
		
		/**
		* Constructor
		*
		* Creates a new instance. <var>$url</var> is the XML-RPC Server end point.
		*
		* @param string		$url			Service URL
		*/
		public function __construct($url)
		{
			if (!$this->readUrl($url,$ssl,$host,$port,$path,$user,$pass)) {
				return false;
			}
			
			parent::__construct($host,$port);
			$this->useSSL($ssl);
			$this->setAuthorization($user,$pass);
			
			$this->path = $path;
			$this->user_agent = 'Clearbricks XML/RPC Client';
		}
		
		/**
		* XML-RPC Query
		*
		* This method calls the given query (first argument) on XML-RPC Server.
		* All other arguments of this method are XML-RPC method arguments.
		* This method throws an exception if XML-RPC method returns an error or
		* returns the server's response.
		*
		* Example:
		* <code>
		* <?php
		* $o = new xmlrpcClient('http://example.com/xmlrpc');
		* $r = $o->query('method1','hello','world');
		* ?>
		* </code>
		*
		* @param string		$method		Method name
		* @return mixed
		*/
		public function query()
		{
			$args = func_get_args();
			$method = array_shift($args);
			$this->request = new xmlrpcRequest($method, $args);
			
			$this->doRequest();
			
			if ($this->status != 200) {
				throw new Exception('HTTP Error. '.$this->status.' '.$this->status_string);
			}
			
			# Now parse what we've got back
			$this->message = new xmlrpcMessage($this->content);
			$this->message->parse();
			
			# Is the message a fault?
			if ($this->message->messageType == 'fault')
			{
				throw new xmlrpcException($this->message->faultString,$this->message->faultCode);
			}
			
			return $this->message->params[0];
		}
		
		# Overloading netHttp::buildRequest method, we don't need all the stuff of
		# HTTP client.
		/** @ignore */
		protected function buildRequest()
		{
			if ($this->proxy_host) {
				$path = $this->getRequestURL();
			} else {
				$path = $this->path;
			}
			
			return array(
				'POST '.$path.' HTTP/1.0',
				'Host: '.$this->host,
				'Content-Type: text/xml',
				'User-Agent: '.$this->user_agent,
				'Content-Length: '.$this->request->getLength(),
				'',
				$this->request->getXML()
			);
		}
	}
}

/**
* Multicall XML-RPC Client
*
* Multicall client using system.multicall method of server.
*/
if (class_exists('xmlrpcClient'))
{
	class xmlrpcClientMulticall extends xmlrpcClient
	{
		/** @var array */	protected $calls = array();
		
		/** @ignore */
		function __construct($url)
		{
			parent::__construct($url);
		}
		
		/**
		* Add call to stack
		*
		* This method adds a method call for the given query (first argument) to
		* calls stack.
		* All other arguments of this method are XML-RPC method arguments.
		*
		* Example:
		* <code>
		* <?php
		* $o = new xmlrpcClient('http://example.com/xmlrpc');
		* $o->addCall('method1','hello','world');
		* $o->addCall('method2','foo','bar');
		* $r = $o->query();
		* ?>
		* </code>
		*
		* @param string		$method		Method name
		* @return mixed
		*/
		function addCall()
		{
			$args = func_get_args();
			$methodName = array_shift($args);
			
			$struct = array(
				'methodName' => $methodName,
				'params' => $args
			);
			
			$this->calls[] = $struct;
		}
		
		/**
		* XML-RPC Query
		*
		* This method sends calls stack to XML-RPC system.multicall method.
		* See {@link xmlrpcServer::multiCall()} for details and links about it.
		*
		* @return array
		*/
		function query()
		{
			# Prepare multicall, then call the parent::query() method
			return parent::query('system.multicall',$this->calls);
		}
	}
}

/**
* Basic XML-RPC Server
*
* This is the most basic XML-RPC server you can create. Built-in methods are:
*
* - system.getCapabilities
* - system.listMethods
* - system.multicall
*/
class xmlrpcServer
{
	/** @var array	Server methods */			protected $callbacks = array();
	/** @var string Received data */			protected $data;
	/** @var string Server encoding */			protected $encoding;
	/** @var xmlrpcMessage Returned message */	protected $message;
	/** @var array Server capabilities */		protected $capabilities;
	
	/** @var boolean Strict XML-RPC checks */
	public $strict_check = false;
	
	/**
	* Constructor
	*
	* @param array		callbacks		Server callbacks
	* @param string	data			Server data
	* @param string	encoding		Server encoding
	*/
	public function __construct($callbacks=false,$data=false,$encoding='UTF-8')
	{
		$this->encoding = $encoding;
		$this->setCapabilities();
		if ($callbacks) {
			$this->callbacks = $callbacks;
		}
		$this->setCallbacks();
		$this->serve($data);
	}
	
	/**
	* Start XML-RPC Server
	*
	* This method starts the XML-RPC Server. It could take a data argument
	* which should be a valid XML-RPC raw stream. If data is not specified, it
	* take values from raw POST data.
	*
	* @param string	data			XML-RPC raw stream
	*/
	public function serve($data=false)
	{
		if (!$data)
		{
			try
			{
				# Check HTTP Method
				if ($_SERVER['REQUEST_METHOD'] != 'POST') {
					throw new Exception('XML-RPC server accepts POST requests only.',405);
				}
				
				# Check HTTP_HOST
				if (!isset($_SERVER['HTTP_HOST'])) {
					throw new Exception('No Host Specified',400);
				}
				
				global $HTTP_RAW_POST_DATA;
				if (!$HTTP_RAW_POST_DATA) {
					$HTTP_RAW_POST_DATA = @file_get_contents('php://input');
					if (!$HTTP_RAW_POST_DATA) {
						throw new Exception('No Message',400);
					}
				}
				
				if ($this->strict_check)
				{
					# Check USER_AGENT
					if (!isset($_SERVER['HTTP_USER_AGENT'])) {
						throw new Exception('No User Agent Specified',400);
					}
					
					# Check CONTENT_TYPE
					if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'],'text/xml') !== 0) {
						throw new Exception('Invalid Content-Type',400);
					}
					
					# Check CONTENT_LENGTH
					if (!isset($_SERVER['CONTENT_LENGTH']) || $_SERVER['CONTENT_LENGTH'] != strlen($HTTP_RAW_POST_DATA)) {
						throw new Exception('Invalid Content-Lenth',400);
					}
				}
				
				$data = $HTTP_RAW_POST_DATA;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 400) {
					$this->head(400,'Bad Request');
				} elseif ($e->getCode() == 405) {
					$this->head(405,'Method Not Allowed');
					header('Allow: POST');
				}
				
				header('Content-Type: text/plain');
				echo $e->getMessage();
				exit;
			}
		}
		
		$this->message = new xmlrpcMessage($data);
		
		try
		{
			$this->message->parse();
			
			if ($this->message->messageType != 'methodCall') {
				throw new xmlrpcException('Server error. Invalid xml-rpc. not conforming to spec. Request must be a methodCall',-32600);
			}
			
			$result = $this->call($this->message->methodName,$this->message->params);
		}
		catch (Exception $e)
		{
			$this->error($e);
		}
		
		# Encode the result
		$r = new xmlrpcValue($result);
		$resultxml = $r->getXml();
		
		# Create the XML
		$xml =
		"<methodResponse>\n".
		"<params>\n".
		"<param>\n".
		"  <value>\n".
		'   '.$resultxml."\n".
		"  </value>\n".
		"</param>\n".
		"</params>\n".
		"</methodResponse>";
		
		# Send it
		$this->output($xml);
	}
	
	/**
	* Send HTTP Headers
	*
	* This method sends a HTTP Header
	*
	* @param integer	code			HTTP Status Code
	* @param string	msg			Header message
	*/
	protected function head($code,$msg)
	{
		$status_mode = preg_match('/cgi/',PHP_SAPI);
		
		if ($status_mode) {
			header('Status: '.$code.' '.$msg);
		} else {
			header($msg,true,$code);
		}
	}
	
	/**
	* Method call
	*
	* This method calls the given XML-RPC method with arguments.
	*
	* @param string	methodname	Method name
	* @param array		args			Method arguments
	* @return mixed
	*/
	protected function call($methodname,$args)
	{
		if (!$this->hasMethod($methodname)) {
			throw new xmlrpcException('server error. requested method "'.$methodname.'" does not exist.',-32601);
		}
		
		$method = $this->callbacks[$methodname];
		
		# Perform the callback and send the response
		if (!is_callable($method)) {
			throw new xmlrpcException('server error. internal requested function for "'.$methodname.'" does not exist.',-32601);
		}
		
		return call_user_func_array($method,$args);
	}
	
	/**
	* XML-RPC Error
	*
	* This method create an XML-RPC error message from a PHP Exception object.
	* You should avoid using this in your own method and throw exceptions
	* instead.
	*
	* @param Exception	e			Exception object
	*/
	protected function error($e)
	{
		$msg = $e->getMessage();
		
		$this->output(
		"<methodResponse>\n".
		"  <fault>\n".
		"    <value>\n".
		"      <struct>\n".
		"        <member>\n".
		"          <name>faultCode</name>\n".
		'          <value><int>'.$e->getCode()."</int></value>\n".
		"        </member>\n".
		"        <member>\n".
		"          <name>faultString</name>\n".
		'          <value><string>'.$msg."</string></value>\n".
		"        </member>\n".
		"      </struct>\n".
		"    </value>\n".
		"  </fault>\n".
		"</methodResponse>\n"
		);
	}
	
	/**
	* Output response
	*
	* This method sends the whole XML-RPC response through HTTP.
	*
	* @param string	xml			XML Content
	*/
	protected function output($xml)
	{
		$xml = '<?xml version="1.0" encoding="'.$this->encoding.'"?>'."\n".$xml;
		$length = strlen($xml);
		header('Connection: close');
		header('Content-Length: '.$length);
		header('Content-Type: text/xml');
		header('Date: '.date('r'));
		echo $xml;
		exit;
	}
	
	/**
	* XML-RPC Server has method?
	*
	* Returns true if the server has the given method <var>$method</var>
	*
	* @param string	$method		Method name
	* @return boolean
	*/
	protected function hasMethod($method)
	{
		return in_array($method, array_keys($this->callbacks));
	}
	
	/**
	* Server Capabilities
	*
	* This method initiates the server capabilities:
	* - xmlrpc
	* - faults_interop
	* - system.multicall
	*/
	protected function setCapabilities()
	{
		# Initialises capabilities array
		$this->capabilities = array(
			'xmlrpc' => array(
				'specUrl' => 'http://www.xmlrpc.com/spec',
				'specVersion' => 1
			),
			'faults_interop' => array(
				'specUrl' => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
				'specVersion' => 20010516
			),
			'system.multicall' => array(
				'specUrl' => 'http://www.xmlrpc.com/discuss/msgReader$1208',
				'specVersion' => 1
			)
		);   
	}
	
	/**
	* Server Methods
	*
	* This method creates the three main server's methods:
	* - system.getCapabilities
	* - system.listMethods
	* - system.multicall
	*
	* @see getCapabilities()
	* @see listMethods()
	* @see multiCall()
	*/
	protected function setCallbacks()
	{
		$this->callbacks['system.getCapabilities'] = array($this,'getCapabilities');
		$this->callbacks['system.listMethods'] = array($this,'listMethods');
		$this->callbacks['system.multicall'] = array($this,'multiCall');
	}
	
	/**
	* Server Capabilities
	*
	* Returns server capabilities
	*
	* @return array
	*/
	protected function getCapabilities()
	{
		return $this->capabilities;
	}
	
	
	/**
	* Server methods
	*
	* Returns all server methods
	*
	* @return array
	*/
	protected function listMethods()
	{
		# Returns a list of methods - uses array_reverse to ensure user defined
		# methods are listed before server defined methods
		return array_reverse(array_keys($this->callbacks));
	}
	
	/**
	* Multicall
	*
	* This method handles a multi-methods call
	*
	*  @see http://www.xmlrpc.com/discuss/msgReader$1208
	*
	* @param array		$methodcalls		Array of methods
	* @return array
	*/
	protected function multiCall($methodcalls)
	{
		$return = array();
		foreach ($methodcalls as $call)
		{
			$method = $call['methodName'];
			$params = $call['params'];
			
			try
			{
				if ($method == 'system.multicall') {
					throw new xmlrpcException('Recursive calls to system.multicall are forbidden',-32600);
				}
				
				$result = $this->call($method, $params);
				$return[] = array($result);
			}
			catch (Exception $e)
			{
				$return[] = array(
					'faultCode' => $e->getCode(),
					'faultString' => $e->getMessage()
				);
			}
		}
		
		return $return;
	}
}

/**
* XML-RPC Introspection Server
*
* This class implements the most used type of XML-RPC Server.
* It allows you to create classes inherited from this one and add methods
* with {@link addCallback() addCallBack method}.
*
* This server class implements the following XML-RPC methods:
* - system.methodSignature
* - system.getCapabilities
* - system.listMethods
* - system.methodHelp
* - system.multicall
*/

if (class_exists('xmlrpcServer'))
{
	class xmlrpcIntrospectionServer extends xmlrpcServer
	{
		protected $signatures;
		protected $help;
		
		/**
		* Constructor
		*
		* This method should be inherited to add new callbacks with
		* {@link addCallback()}.
		*
		* @param string	$encoding			Server encoding
		*/
		public function __construct($encoding='UTF-8')
		{
			$this->encoding = $encoding;
			$this->setCallbacks();
			$this->setCapabilities();
			
			$this->capabilities['introspection'] = array (
				'specUrl' => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
				'specVersion' => 1
			);
			
			$this->addCallback(
				'system.methodSignature', 
				array($this,'methodSignature'), 
				array('array','string'), 
				'Returns an array describing the return type and required parameters of a method'
			);
			
			$this->addCallback(
				'system.getCapabilities', 
				array($this,'getCapabilities'), 
				array('struct'), 
				'Returns a struct describing the XML-RPC specifications supported by this server'
			);
			
			$this->addCallback(
				'system.listMethods', 
				array($this,'listMethods'), 
				array('array'), 
				'Returns an array of available methods on this server'
			);
			
			$this->addCallback(
				'system.methodHelp', 
				array($this,'methodHelp'), 
				array('string','string'), 
				'Returns a documentation string for the specified method'
			);
			
			$this->addCallback(
				'system.multicall',
				array($this,'multiCall'),
				array('struct','array'),
				'Returns result of multiple methods calls'
			);
		}
		
		/**
		* Add Server Callback
		*
		* This method creates a new XML-RPC method which references a class
		* callback. <var>$callback</var> should be a valid PHP callback.
		*
		* @param string	$method			Method name
		* @param callback	$callback			Method callback
		* @param array		$args			Array of arguments type. The first is the returned one.
		* @param string	$help			Method help string
		*/
		protected function addCallback($method, $callback, $args, $help)
		{
			$this->callbacks[$method] = $callback;
			$this->signatures[$method] = $args;
			$this->help[$method] = $help;
		}
		
		/**
		* Method call
		*
		* This method calls the callbacks function or method for the given XML-RPC
		* method <var>$methodname</var> with arguments in <var>$args</var> array.
		*
		* @param string	$methodname		Method name
		* @param array		$args			Arguments
		* @return mixed
		*/
		protected function call($methodname,$args)
		{
			# Make sure it's in an array
			if ($args && !is_array($args)) {
				$args = array($args);
			}
			
			# Over-rides default call method, adds signature check
			if (!$this->hasMethod($methodname)) {
				throw new xmlrpcException('Server error. Requested method "'.$methodname.'" not specified.',-32601);
			}
			
			$method = $this->callbacks[$methodname];
			$signature = $this->signatures[$methodname];
			
			if (!is_array($signature)) {
				throw new xmlrpcException('Server error. Wrong method signature',-36600);
			}
			
			$return_type = array_shift($signature);
			
			# Check the number of arguments
			if (count($args) > count($signature)) {
				throw new xmlrpcException('Server error. Wrong number of method parameters',-32602);
			}
			
			# Check the argument types
			if (!$this->checkArgs($args,$signature)) {
				throw new xmlrpcException('Server error. Invalid method parameters',-32602);
			}
			
			# It passed the test - run the "real" method call
			return parent::call($methodname, $args);
		}
		
		/**
		* Method Arguments Check
		*
		* This method checks the validity of method arguments.
		*
		* @param array		$args			Method given arguments
		* @param array		$signature		Method defined arguments
		* @return boolean
		*/
		protected function checkArgs($args,$signature)
		{
			for ($i = 0, $j = count($args); $i < $j; $i++)
			{
				$arg = array_shift($args);
				$type = array_shift($signature);
				
				switch ($type)
				{
					case 'int':
					case 'i4':
						if (is_array($arg) || !is_int($arg)) {
							return false;
						}
						break;
					case 'base64':
					case 'string':
						if (!is_string($arg)) {
							return false;
						}
						break;
					case 'boolean':
						if ($arg !== false && $arg !== true) {
							return false;
						}
						break;
					case 'float':
					case 'double':
						if (!is_float($arg)) {
							return false;
						}
						break;
					case 'date':
					case 'dateTime.iso8601':
						if (!($arg instanceof xmlrpcDate)) {
							return false;
						}
						break;
				}
			}
			return true;
		}
		
		/**
		* Method Signature
		*
		* This method return given XML-RPC method signature.
		*
		* @param string	$method		Method name
		* @return array
		*/
		protected function methodSignature($method)
		{
			if (!$this->hasMethod($method)) {
				throw new xmlrpcException('Server error. Requested method "'.$method.'" not specified.',-32601);
			
			}
			
			# We should be returning an array of types
			$types = $this->signatures[$method];
			$return = array();
			
			foreach ($types as $type)
			{
				switch ($type)
				{
					case 'string':
						$return[] = 'string';
						break;
					case 'int':
					case 'i4':
						$return[] = 42;
						break;
					case 'double':
						$return[] = 3.1415;
						break;
					case 'dateTime.iso8601':
						$return[] = new xmlrpcDate(time());
						break;
					case 'boolean':
						$return[] = true;
						break;
					case 'base64':
						$return[] = new xmlrpcBase64('base64');
						break;
					case 'array':
						$return[] = array('array');
						break;
					case 'struct':
						$return[] = array('struct' => 'struct');
						break;
				}
			}
			return $return;
		}
		
		/**
		* Method Help
		*
		* This method return given XML-RPC method help string.
		*
		* @param string	$method		Method name
		* @return string
		*/
		protected function methodHelp($method)
		{
			return $this->help[$method];
		}
	}
}
?>