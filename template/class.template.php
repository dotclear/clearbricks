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

class template
{
	private $self_name;
	
	public $use_cache = true;
	
	private $blocks = array();
	private $values = array();
	
	protected $remove_php = true;
	
	protected $tag_block = '<tpl:(%1$s)(?:(\s+.*?)>|>)(.*)</tpl:%1$s>';
	protected $tag_value = '{{tpl:(%s)(\s(.*?))?\}}';
	
	protected $tpl_path = array();
	protected $cache_dir;
	
	protected $compile_stack = array();
	
	public function __construct($cache_dir,$self_name)
	{
		$this->setCacheDir($cache_dir);
		
		$this->self_name = $self_name;
		$this->addValue('include',array($this,'includeFile'));
	}
	
	public function includeFile($attr)
	{
		if (!isset($attr['src'])) { return; }
		
		$src = path::clean($attr['src']);
		
		$tpl_file = $this->getFilePath($src);
		if (!$tpl_file) { return; }
		if (in_array($tpl_file,$this->compile_stack)) { return; }
		
		return
		'<?php echo '.
		$this->self_name."->getData('".str_replace("'","\'",$src)."'); ?>";
	}
	
	public function setPath()
	{
		$path = array();
		
		foreach (func_get_args() as $v)
		{
			if (is_array($v)) {
				$path = array_merge($path,array_values($v));
			} else {
				$path[] = $v;
			}
		}
		
		foreach ($path as $k => $v)
		{
			if (($v = path::real($v)) === false) {
				unset($path[$k]);
			}
		}
		
		$this->tpl_path = array_unique($path);
	}
	
	public function getPath()
	{
		return $this->tpl_path;
	}
	
	public function setCacheDir($dir)
	{
		if (!is_dir($dir)) {
			throw new Exception($dir.' is not a valid directory.');
		}
		
		if (!is_writable($dir)) {
			throw new Exception($dir.' is not writable.');
		}
		
		$this->cache_dir = path::real($dir).'/';
	}
	
	public function addBlock($name,$callback)
	{
		if (!is_callable($callback)) {
			throw new Exception('No valid callback for '.$name);
		}
		
		$this->blocks[$name] = $callback;
	}
	
	public function addValue($name,$callback)
	{
		if (!is_callable($callback)) {
			throw new Exception('No valid callback for '.$name);
		}
		
		$this->values[$name] = $callback;
	}
	
	public function getFile($file)
	{
		$tpl_file = $this->getFilePath($file);
		
		if (!$tpl_file) {
			throw new Exception('No template found for '.$file);
			return false;
		}
		
		$file_md5 = md5($tpl_file);
		$dest_file = sprintf('%s/%s/%s/%s/%s.php',
			$this->cache_dir,
			'cbtpl',
			substr($file_md5,0,2),
			substr($file_md5,2,2),
			$file_md5
		);
		
		$create_file = false;
		
		if (!file_exists($dest_file)) {
			$create_file = true;
		} elseif (!$this->use_cache) {
			$create_file = true;
		} elseif (filemtime($tpl_file) > filemtime($dest_file)) {
			$create_file = true;
		}
		
		if ($create_file)
		{
			files::makeDir(dirname($dest_file),true);
			
			if (($fp = @fopen($dest_file,'wb')) === false) {
				throw new Exception('Unable to create cache file');
			}
			
			$fc = $this->compileFile($tpl_file);
			fwrite($fp,$fc);
			fclose($fp);
			chmod($dest_file,fileperms(dirname($dest_file)));
		}
		return $dest_file;
	}
	
	public function getFilePath($file)
	{
		foreach ($this->tpl_path as $p)
		{
			if (file_exists($p.'/'.$file)) {
				return $p.'/'.$file;
			}
		}
		
		return false;
	}
	
	public function getData($________)
	{
		foreach ($GLOBALS as $k => $v) {
			$$k =& $GLOBALS[$k];
			global $$k;
		}
		
		ob_start();
		include $this->getFile($________);
		$res = ob_get_contents();
		ob_end_clean();
		
		return $res;
	}
	
	protected function compileFile($file)
	{
		$fc = file_get_contents($file);
		
		$this->compile_stack[] = $file;
		
		# Remove every PHP tags
		if ($this->remove_php)
		{
			$fc = preg_replace('/<\?(?=php|=|\s).*?\?>/ms','',$fc);
		}
		
		# Transform what could be considered as PHP short tags
		$fc = preg_replace('/(<\?(?!php|=|\s))(.*?)(\?>)/ms',
		'<?php echo "$1"; ?>$2<?php echo "$3"; ?>',$fc);
		
		# Remove template comments <!-- #... -->
		$fc = preg_replace('/(^\s*)?<!-- #(.*?)-->/ms','',$fc);
		
		# Compile blocks
		foreach ($this->blocks as $b => $f) {
			$pattern = sprintf($this->tag_block,preg_quote($b,'#'));
			
			$fc = preg_replace_callback('#'.$pattern.'#ms',
			array($this,'compileBlock'),$fc);
		}
		
		# Compile values
		foreach ($this->values as $v => $f) {
			$pattern = sprintf($this->tag_value,preg_quote($v,'#'));
			
			$fc = preg_replace_callback('#'.$pattern.'#ms',
			array($this,'compileValue'),$fc);
		}
		
		return $fc;
	}
	
	protected function compileBlock($match)
	{
		$b = $match[1];
		$content = $match[3];
		$attr = $this->getAttrs($match[2]);
		
		# Call block function
		return call_user_func($this->blocks[$b],$attr,$content);
	}
	
	protected function compileValue($match)
	{
		$v = $match[1];
		$attr = isset($match[2]) ? $this->getAttrs($match[2]) : array();
		$str_attr = isset($match[2]) ? $match[2] : null;
		
		return call_user_func($this->values[$v],$attr,ltrim($str_attr));
	}
	
	protected function getAttrs($str)
	{
		$res = array();
		if (preg_match_all('|([a-zA-Z0-9_:-]+)="(.+?)"|ms',$str,$m) > 0) {
			foreach ($m[1] as $i => $v) {
				$res[$v] = $m[2][$i];
			}
		}
		return $res;
	}
}
?>