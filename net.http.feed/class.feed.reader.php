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
* Feed Reader
*
* Features:
*
* - Reads RSS 1.0 (rdf), RSS 2.0 and Atom feeds.
* - HTTP cache negociation support
* - Cache TTL.
*
* @package Clearbricks
* @subpackage Feeds
*
* @author Florent Cotton
* @author Olivier Meunier
*
* @uses feedParser
*/
if (class_exists('netHttp'))
{
	class feedReader extends netHttp
	{
		/** @ignore */							protected $user_agent = 'Clearbricks Feed Reader/0.2';
		/** @ignore */							protected $timeout = 5;
		/** @var array HTTP Cache validators */		protected $validators = null;
		
		/** @var string Cache directory path */		protected $cache_dir = null;
		/** @var string Cache file prefix */		protected $cache_file_prefix = 'cbfeed';
		/** @var string Cache TTL */				protected $cache_ttl = '-30 minutes';
		
		
		/**
		* Constructor.
		*
		* Does nothing. See {@link parse()} method for URL handling.
		*/
		public function __construct()
		{
			parent::__construct('');
		}
		
		/**
		* Parse Feed
		*
		* Returns a new feedParser instance for given URL or false if source URL is
		* not a valid feed.
		*
		* @param string	$url			Feed URL
		* @return feedParser|false
		*/
		public function parse($url)
		{
			$this->validators = array();
			if ($this->cache_dir)
			{
				return $this->withCache($url);
			}
			else
			{
				if (!$this->getFeed($url)) {
					return false;
				}
				
				if ($this->getStatus() != '200') {
					return false;
				}
				
				return new feedParser($this->getContent());
			}
		}
		
		/**
		* Quick Parse
		*
		* This static method returns a new {@link feedParser} instance for given URL. If a
		* <var>$cache_dir</var> is specified, cache will be activated.
		*
		* @param string	$url			Feed URL
		* @param string	$cache_dir	Cache directory
		* @return feedParser|false
		*/
		public static function quickParse($url,$cache_dir=null)
		{
			$parser = new self();
			if ($cache_dir) {
				$parser->setCacheDir($cache_dir);
			}
			
			return $parser->parse($url);
		}
		
		/**
		* Set Cache Directory
		*
		* Returns true and sets {@link $cache_dir} property if <var>$dir</var> is
		* a writable directory. Otherwise, returns false.
		*
		* @param string	$dir			Cache directory
		* @return boolean
		*/
		public function setCacheDir($dir)
		{
			$this->cache_dir = null;
			
			if (!empty($dir) && is_dir($dir) && is_writeable($dir))
			{
				$this->cache_dir = $dir;
				return true;
			}
			
			return false;
		}
		
		/**
		* Set Cache TTL
		*
		* Sets cache TTL. <var>$str</var> is a interval readable by strtotime
		* (-3 minutes, -2 hours, etc.)
		*
		* @param string	$str			TTL
		*/
		public function setCacheTTL($str)
		{
			$str = trim($str);
			if (!empty($str))
			{
				if (substr($str,0,1) != '-') {
					$str = '-'.$str;
				}
				$this->cache_ttl = $str;
			}
		}
		
		/**
		* Feed Content
		*
		* Returns feed content for given URL.
		*
		* @param string	$url			Feed URL
		* @return string
		*/
		protected function getFeed($url)
		{
			if (!self::readURL($url,$ssl,$host,$port,$path,$user,$pass)) {
				return false;
			}
			$this->setHost($host,$port);
			$this->useSSL($ssl);
			$this->setAuthorization($user,$pass);
			
			return $this->get($path);
		}
		
		/**
		* Cache content
		*
		* Returns feedParser object from cache if present or write it to cache and
		* returns result.
		*
		* @param string	$url			Feed URL
		* @return feedParser
		*/
		protected function withCache($url)
		{
			$url_md5 = md5($url);
			$cached_file = sprintf('%s/%s/%s/%s/%s.php',
				$this->cache_dir,
				$this->cache_file_prefix,
				substr($url_md5,0,2),
				substr($url_md5,2,2),
				$url_md5
			);
			
			$may_use_cached = false;
			
			if (@file_exists($cached_file))
			{
				$may_use_cached = true;
				$ts = @filemtime($cached_file);
				if ($ts > strtotime($this->cache_ttl))
				{
					# Direct cache
					return unserialize(file_get_contents($cached_file));
				}
				$this->setValidator('IfModifiedSince', $ts);
			}
			
			if (!$this->getFeed($url))
			{
				if ($may_use_cached)
				{
					# connection failed - fetched from cache
					return unserialize(file_get_contents($cached_file));
				}
				return false;
			}
			
			switch ($this->getStatus())
			{
				case '304':
					@files::touch($cached_file);
					return unserialize(file_get_contents($cached_file));
				case '200':
					if ($feed = new feedParser($this->getContent()))
					{
						try {
							files::makeDir(dirname($cached_file),true);
						} catch (Exception $e) {
							return $feed;
						}
						
						if (($fp = @fopen($cached_file, 'wb')))
						{
							fwrite($fp, serialize($feed));
							fclose($fp);
							files::inheritChmod($cached_file);
						}
						return $feed;
					}
			}
			
			return false;
		}
		
		/**
		* Build request
		*
		* Adds HTTP cache headers to common headers.
		* 
		* {@inheritdoc}
		*/
		protected function buildRequest()
		{
			$headers = parent::buildRequest();
			
			# Cache validators
			if (!empty($this->validators))
			{
				if (isset($this->validators['IfModifiedSince'])) {
					$headers[] = 'If-Modified-Since: '.$this->validators['IfModifiedSince'];
				}
				if (isset($this->validators['IfNoneMatch'])) {
					if (is_array($this->validators['IfNoneMatch'])) {
						$etags = implode(',',$this->validators['IfNoneMatch']);
					} else {
						$etags = $this->validators['IfNoneMatch'];
					}
					$headers[] = '';
				}
			}
			
			return $headers;
		}
		
		private function setValidator($key,$value)
		{
			if ($key == 'IfModifiedSince') {
				$value = gmdate('D, d M Y H:i:s',$value).' GMT';
			}
			
			$this->validators[$key] = $value;
		}
	}
}
?>