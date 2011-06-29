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
* HTML Validator
*
* This class will perform an HTML validation upon WDG validator.
*
* @package Clearbricks
* @subpackage HTML
*/
if (class_exists('netHttp'))
{
	class htmlValidator extends netHttp
	{
		/** @ignore */
		protected $host = 'www.htmlhelp.com';
		
		/** @ignore */
		protected $path = '/cgi-bin/validate.cgi';
		
		/** @ignore */
		protected $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.3a) Gecko/20021207';
		
		/** @ignore */
		protected $timeout = 2;
		
		/** @ignore */
		protected $html_errors = array();		///<	<b>array</b>		Validation errors list
		
		/**
		* Constructor, no parameters.
		*/
		public function __construct()
		{
			parent::__construct($this->host,80,$this->timeout);
		}
		
		/**
		* HTML Document
		*
		* Returns an HTML document from a <var>$fragment</var>.
		*
		* @param string	$fragment			HTML content
		* @return string
		*/
		public function getDocument($fragment)
		{
			return
			'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '.
			'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n".
			'<html xmlns="http://www.w3.org/1999/xhtml">'."\n".
			'<head>'."\n".
			'<title>validation</title>'."\n".
			'</head>'."\n".
			'<body>'."\n".
			$fragment."\n".
			'</body>'."\n".
			'</html>';
		}
		
		/**
		* HTML validation
		*
		* Performs HTML validation of <var>$html</var>.
		*
		* @param string	$html			HTML document
		* @param string	$charset			Document charset
		* @return boolean
		*/
		public function perform($html,$charset='UTF-8')
		{
			$data = array('area' => $html, 'charset' => $charset);
			$this->post($this->path,$data);
			
			if ($this->getStatus() != 200) {
				throw new Exception('Status code line invalid.');
			}
			
			$result = $this->getContent();
			
			if (strpos($result,'<p class=congratulations><strong>Congratulations, no errors!</strong></p>'))
			{
				return true;
			}
			else
			{
				if ($errors = preg_match('#<h2>Errors</h2>[\s]*(<ul>.*</ul>)#msU',$result,$matches)) {
					$this->html_errors = strip_tags($matches[1],'<ul><li><pre><b>');
				}
				return false;
			}
		}
		
		/**
		* Validation Errors
		*
		* @return array	HTML validation errors list
		*/
		public function getErrors()
		{
			return $this->html_errors;
		}
		
		/**
		* Static HTML validation
		*
		* Static validation method of an HTML fragment. Returns an array with the
		* following parameters:
		*
		* - valid (boolean)
		* - errors (string)
		*
		* @param string	$fragment			HTML content
		* @param string	$charset			Document charset
		* @return array
		*/
		public static function validate($fragment,$charset='UTF-8')
		{
			$o = new self;
			$fragment = $o->getDocument($fragment,$charset);
			
			if ($o->perform($fragment,$charset))
			{
				return array('valid' => true, 'errors' => null);
			}
			else
			{
				return array('valid' => false, 'errors' => $o->getErrors());
			}
		}
	}
}
?>