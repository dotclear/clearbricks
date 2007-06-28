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

class files
{
	public static function scandir($d,$order=0)
	{
		$res = array();
		$dh = @opendir($d);
		
		if ($dh === false) {
			throw new Exception(__('Unable to open directory.'));
		}
		
		while (($f = readdir($dh)) !== false) {
			$res[] = $f;
		}
		closedir($dh);
		
		sort($res);
		if ($order == 1) {
			rsort($res);
		}
		
		return $res;
	}
	
	public static function getExtension($f)
	{
		$f = explode('.',basename($f));
		
		if (count($f) <= 1) { return ''; }
		
		return strtolower($f[count($f)-1]);
	}
	
	public static function getMimeType($f)
	{
		$ext = self::getExtension($f);
		$types = self::mimeTypes();
		
		if (isset($types[$ext])) {
			return $types[$ext];
		} else {
			return 'text/plain';
		}
	}
	
	public static function mimeTypes()
	{
		return array(
			'odt'	=> 'application/vnd.oasis.opendocument.text',
			'odp'	=> 'application/vnd.oasis.opendocument.presentation',
			'ods'	=> 'application/vnd.oasis.opendocument.spreadsheet',
			
			'sxw'	=> 'application/vnd.sun.xml.writer',
			'sxc'	=> 'application/vnd.sun.xml.calc',
			'sxi'	=> 'application/vnd.sun.xml.impress',
			
			'ppt' 	=> 'application/mspowerpoint',
			'doc'	=> 'application/msword',
			'xls'	=> 'application/msexcel',			
			'rtf'	=> 'application/rtf',
			
			'pdf'	=> 'application/pdf',
			'ps'		=> 'application/postscript',
			'ai'		=> 'application/postscript',
			'eps'	=> 'application/postscript',
			
			'bin'	=> 'application/octet-stream',
			'exe'	=> 'application/octet-stream',
			
			'deb'	=> 'application/x-debian-package',
			'gz'		=> 'application/x-gzip',
			'jar'	=> 'application/x-java-archive',
			'rar'	=> 'application/rar',
			'rpm'	=> 'application/x-redhat-package-manager',
			'tar'	=> 'application/x-tar',
			'tgz'	=> 'application/x-gtar',
			'zip'	=> 'application/zip',
			
			'aiff'	=> 'audio/x-aiff',
			'ua'		=> 'audio/basic',
			'mp3'	=> 'audio/mpeg3',
			'mid'	=> 'audio/x-midi',
			'midi'	=> 'audio/x-midi',
			'ogg'	=> 'application/ogg',
			'wav'	=> 'audio/x-wav',
			
			'swf'	=> 'application/x-shockwave-flash',
			'swfl'	=> 'application/x-shockwave-flash',
			
			'bmp'	=> 'image/bmp',
			'gif'	=> 'image/gif',
			'jpeg'	=> 'image/jpeg',
			'jpg'	=> 'image/jpeg',
			'jpe'	=> 'image/jpeg',
			'png'	=> 'image/png',
			'tiff'	=> 'image/tiff',
			'tif'	=> 'image/tiff',
			'xbm'	=> 'image/x-xbitmap',
			
			'css'	=> 'text/css',
			'js'		=> 'text/javascript',
			'html'	=> 'text/html',
			'htm'	=> 'text/html',
			'txt'	=> 'text/plain',
			'rtf'	=> 'text/richtext',
			'rtx'	=> 'text/richtext',
			
			'mpg'	=> 'video/mpeg',
			'mpeg'	=> 'video/mpeg',
			'mpe'	=> 'video/mpeg',
			'viv'	=> 'video/vnd.vivo',
			'vivo'	=> 'video/vnd.vivo',
			'qt'		=> 'video/quicktime',
			'mov'	=> 'video/quicktime',
			'flv'	=> 'video/x-flv',
			'avi'	=> 'video/x-msvideo'
		);
	}
	
	public static function isDeletable($f)
	{
		if (is_file($f)) {
			return is_writable(dirname($f));
		} elseif (is_dir($f)) {
			return (is_writable(dirname($f)) && count(files::scandir($f)) <= 2);
		}
	}
	
	# Recusive remove (rm -rf)
	public static function deltree($dir)
	{
		$current_dir = opendir($dir);
		while($entryname = readdir($current_dir))
		{
			if (is_dir($dir.'/'.$entryname) and ($entryname != '.' and $entryname!='..'))
			{
				if (!files::deltree($dir.'/'.$entryname)) {
					return false;
				}
			}
			elseif ($entryname != '.' and $entryname!='..')
			{
				if (!@unlink($dir.'/'.$entryname)) {
					return false;
				}
			}
		}
		closedir($current_dir);
		return @rmdir($dir);
	}
	
	public static function touch($f)
	{
		if (is_writable($f)) {
			if (function_exists('touch')) {
				@touch($f);
			} else {
				# Very bad hack
				@file_put_contents($f,file_get_contents($f));
			}
		}
	}
	
	public static function makeDir($f,$r=false)
	{
		if (empty($f)) {
			return;
		}
		
		if (DIRECTORY_SEPARATOR == '\\') {
			$f = str_replace('/','\\',$f);
		}
		
		if (is_dir($f)) {
			return;
		}
		
		if ($r)
		{
			$dir = path::real($f,false);
			$dirs = array();
			
			while (!is_dir($dir)) {
				array_unshift($dirs,basename($dir));
				$dir = dirname($dir);
			}
			
			foreach ($dirs as $d)
			{
				$dir .= DIRECTORY_SEPARATOR.$d;
				if ($d != '' && !is_dir($dir)) {
					self::makeDir($dir);
				}
			}
		}
		else
		{
			if (@mkdir($f) === false) {
				throw new Exception(__('Unable to create directory.'));
			}
			chmod($f,fileperms(dirname($f)));
		}
	}
	
	public static function putContent($f, $f_content)
	{
		if (file_exists($f) && !is_writable($f)) {	
			throw new Exception(__('File is not writable.'));
		}
		
		$fp = @fopen($f, 'w');
		
		if ($fp === false) {
			throw new Exception(__('Unable to open file.'));
		}
		
		fwrite($fp,$f_content,strlen($f_content));
		fclose($fp);
		return true;
	}
	
	public static function size($size)
	{
		$kb = 1024;
		$mb = 1024 * $kb;
		$gb = 1024 * $mb;
		$tb = 1024 * $gb;
		
		if($size < $kb) {
			return $size." B";
		}
		else if($size < $mb) {
			return round($size/$kb,2)." KB";
		}
		else if($size < $gb) {
			return round($size/$mb,2)." MB";
		}
		else if($size < $tb) {
			return round($size/$gb,2)." GB";
		}
		else {
			return round($size/$tb,2)." TB";
		}
	}
	
	public static function str2bytes($v)
	{
		$v = trim($v);
		$last = strtolower(substr($v,-1,1));
		
		switch($last)
		{
			case 'g':
				$v *= 1024;
			case 'm':
				$v *= 1024;
			case 'k':
				$v *= 1024;
		}
		
		return $v;
	}
	
	public static function uploadStatus($file)
	{
		if (!isset($file['error'])) {
			throw new Exception(__('Not an uploaded file.'));
		}
		
		switch ($file['error']) {
			case UPLOAD_ERR_OK:
				return true;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new Exception(__('The uploaded file exceeds the maximum file size allowed.'));
				return false;
			case UPLOAD_ERR_PARTIAL:
				throw new Exception(__('The uploaded file was only partially uploaded.'));
				return false;
			case UPLOAD_ERR_NO_FILE:
				throw new Exception(__('No file was uploaded.'));
				return false;
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new Exception(__('Missing a temporary folder.'));
				return false;
			case UPLOAD_ERR_CANT_WRITE:
				throw new Exception(__('Failed to write file to disk.'));
				return false;
			default:
				return true;
		}
	}
	
	# Packages generation methods
	#
	public static function getDirList($dirName, &$contents = null)
	{
		if (!$contents) {
			$contents = array('dirs'=> array(),'files' => array());
		}			
		
		$exclude_list=array('.','..','.svn');
		
		if (empty($res)) {
			$res = array();
		}
		
		$dirName = preg_replace('|/$|','',$dirName);
		
		if (!is_dir($dirName)) {
			throw new Exception(sprintf(__('%s is not a directory.'),$dirName));
		}
		
		$contents['dirs'][] = $dirName;
		
		$d = @dir($dirName);
		
		if ($d === false) {
			throw new Exception(__('Unable to open directory.'));
		}
		
		while($entry = $d->read())
		{
			if (!in_array($entry,$exclude_list))
			{
				if (is_dir($dirName.'/'.$entry))
				{
					files::getDirList($dirName.'/'.$entry, $contents);
				}
				else
				{
					$contents['files'][] = $dirName.'/'.$entry;
				}
			}
		}
		$d->close();
		
		return $contents;
	}
	
	public static function makePackage($name,$dir,$remove_path='',$gzip=true)
	{
		if ($gzip && !function_exists('gzcompress')) {
			throw new Exception(__('No compression functions'));
		}
		
		if (($filelist = files::getDirList($dir)) === false) {
			throw new Exception(__('Unable to list directory.'));
		}
		
		$res = array ('name' => $name, 'dirs' => array(), 'files' => array());
		
		foreach ($filelist['dirs'] as $v) {
			$res['dirs'][] = preg_replace('/^'.preg_quote($remove_path,'/').'/','',$v);
		}
		
		foreach ($filelist['files'] as $v) {
			$f_content = base64_encode(file_get_contents($v));
			$v = preg_replace('/^'.preg_quote($remove_path,'/').'/','',$v);
			$res['files'][$v] = $f_content;
		}
		
		$res = serialize($res);
		
		if ($gzip) {
			$res = gzencode($res);
		}
		
		return $res;
	}
	
	public static function installPackage($package,$with_remove=true)
	{
		$dest_path = dirname($package);
		
		if (($content = @implode('',@gzfile($package))) === false) {
			if ($with_remove) {
				unlink($package);
			}
			throw new Exception(__('Cannot open file.'));
		}
		
		if (($list = @unserialize($content)) === false) {
			if ($with_remove) {
				unlink($package);
			}
			throw new Exception(__('Package not valid.'));
		}
		unset($content);
		
		if (is_dir($dest_path.'/'.$list['name'])) {
			unlink($package);
			throw new Exception(__('This package is already installed. Delete it before.'));
		}
				
		foreach ($list['dirs'] as $d) {
			mkdir($dest_path.'/'.$d,fileperms($dest_path));
			chmod($dest_path.'/'.$d,fileperms($dest_path));
		}
		
		foreach ($list['files'] as $f => $v) {
			$v = base64_decode($v);
			$fp = fopen($dest_path.'/'.$f,'w');
			fwrite($fp,$v,strlen($v));
			fclose($fp);
			chmod($dest_path.'/'.$f,fileperms($dest_path) & ~0111);
		}
		
		unlink($package);

		return true;
	}
	
	public static function tidyFileName($n)
	{
		$n = preg_replace('/^[.]/u','',$n);
		return preg_replace('/[^A-Za-z0-9._-]/u','_',$n);
	}
}


class path
{
	public static function real($p,$strict=true)
	{
		$os = (DIRECTORY_SEPARATOR == '\\') ? 'win' : 'nix';
		
		# Absolute path?
		if ($os == 'win') {
			$_abs = preg_match('/^\w+:/',$p);
		} else {
			$_abs = substr($p,0,1) == '/';
		}
		
		# Standard path form
		if ($os == 'win') {
			$p = str_replace('\\','/',$p);
		}
		
		# Adding root if !$_abs 
		if (!$_abs) {
			$p = dirname($_SERVER['SCRIPT_FILENAME']).'/'.$p;
		}
		
		# Clean up
		$p = preg_replace('|/+|','/',$p);
		
		if (strlen($p) > 1) {
			$p = preg_replace('|/$|','',$p);
		}
		
		$_start = '';
		if ($os == 'win') {
			list($_start,$p) = explode(':',$p);
			$_start .= ':/';
		} else {
			$_start = '/';
		}
		$p = substr($p,1);
		
		# Go through
		$P = explode('/',$p);
		$res = array();
		
		for ($i=0;$i<count($P);$i++)
		{
			if ($P[$i] == '.') {
				continue;
			}
			
			if ($P[$i] == '..') {
				if (count($res) > 0) {
					array_pop($res);
				}
			} else {
				array_push($res,$P[$i]);
			}
		}
		
		$p = $_start.implode('/',$res);
		
		if ($strict && !@file_exists($p)) {
			return false;
		}
		
		return $p;
	}
	
	public static function clean($p)
	{
		$p = str_replace('..','',$p);
		$p = preg_replace('|/{2,}|','/',$p);
		$p = preg_replace('|/$|','',$p);
		
		return $p;
	}
	
	public static function info($f)
	{
		$p = pathinfo($f);
		$res = array();
		
		$res['dirname'] = $p['dirname'];
		$res['basename'] = $p['basename'];
		$res['extension'] = isset($p['extension']) ? $p['extension'] : '';
		$res['base'] = preg_replace('/\.'.preg_quote($res['extension'],'/').'$/','',$res['basename']);
		
		return $res;
	}
	
	public static function fullFromRoot($p,$root)
	{
		if (substr($p,0,1) == '/') {
			return $p;
		}
		
		return $root.'/'.$p;
	}
}
?>