<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Clearbricks.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

/**
 * Unified diff utilities
 *
 * @package Clearbricks
 * @author Alexandre Syenchuk
 **/
class uDiff
{
	public static $s_range = "@@ -%s,%s +%s,%s @@\n";
	public static $s_ctx = " %s\n";
	public static $s_ins = "+%s\n";
	public static $s_del = "-%s\n";
	
	public static $p_range = '/^@@ -([\d]+),([\d]+) \+([\d]+),([\d]+) @@$/';
	public static $p_ctx = '/^ (.*)$/';
	public static $p_ins = '/^\+(.*)$/';
	public static $p_del = '/^-(.*)$/';
	
	/**
	 * Returns unified diff from source $src to destination $dst.
	 * 
	 * @param string		$src		Original data
	 * @param string		$dst		New data
	 * @param string		$ctx		Context length
	 * 
	 * @return string
	 **/
	public static function diff($src,$dst,$ctx=2)
	{
		list($src,$dst) = array(explode("\n",$src),explode("\n",$dst));
		$cx = count($src); $cy = count($dst);
		
		$ses = diff::SES($src,$dst);
		$res = '';
		
		$pos_x = 0;
		$pos_y = 0;
		$old_lines = 0;
		$new_lines = 0;
		$buffer = '';
		
		foreach ($ses as $cmd)
		{
			list($cmd,$x,$y) = array($cmd[0],$cmd[1],$cmd[2]);
			
			# New chunk
			if ($x-$pos_x > 2*$ctx || $pos_x == 0 && $x > $ctx)
			{
				# Footer for current chunk
				for ($i = 0; $buffer && $i < $ctx; $i++)
				{
					$buffer .= sprintf(self::$s_ctx,$src[$pos_x+$i]);
				}
				
				# Header for current chunk
				$res .= sprintf(self::$s_range,
					$pos_x+1-$old_lines,$old_lines+$i,
					$pos_y+1-$new_lines,$new_lines+$i
				).$buffer;
				
				$pos_x = $x;
				$pos_y = $y;
				$old_lines = 0;
				$new_lines = 0;
				$buffer = '';
				
				# Header for next chunk
				for ($i = $ctx; $i > 0 ; $i--)
				{
					$buffer .= sprintf(self::$s_ctx,$src[$pos_x-$i]);
					$old_lines++;
					$new_lines++;
				}
			}
			
			# Context
			while ($x > $pos_x)
			{
				$old_lines++;
				$new_lines++;
				$buffer .= sprintf(self::$s_ctx,$src[$pos_x]);
				$pos_x++;
				$pos_y++;
			}
			# Deletion
			if ($cmd == 'd') {
				$old_lines++;
				$buffer .= sprintf(self::$s_del,$src[$x]);
				$pos_x++;
			}
			# Insertion
			elseif ($cmd == 'i') {
				$new_lines++;
				$buffer .= sprintf(self::$s_ins,$dst[$y]);
				$pos_y++;
			}
		}
		
		# Remaining chunk
		if ($buffer)
		{
			# Footer
			for ($i = 0; $i < $ctx; $i++)
			{
				if (!isset($src[$pos_x+$i])) {
					break;
				}
				$buffer .= sprintf(self::$s_ctx,$src[$pos_x+$i]);
			}
			
			# Header for current chunk
			$res .= sprintf(self::$s_range,
				$pos_x+1-$old_lines,$old_lines+$i,
				$pos_y+1-$new_lines,$new_lines+$i
			).$buffer;
		}
		return $res;
	}
	
	/**
	* Applies a unified patch to a piece of text.
	* Throws an exception on invalid or not applicable diff.
	* 
	* @param string		$src			Source text
	* @param string		$diff		Patch to apply
	* 
	* @return	string
	*/
	public static function patch($src,$diff)
	{
		$dst = array();
		$src = explode("\n",$src);
		$diff = explode("\n",$diff);
		
		$t = count($src);
		$old_length = $new_length = 0;
		
		foreach ($diff as $line)
		{
			# New chunk
			if (preg_match(self::$p_range,$line,$m)) {
				$m[1]--; $m[3]--;
				
				if ($m[1] > $t) {
					throw new Exception('Bad range');
				}
				
				if ($t - count($src) > $m[1]) {
					throw new Exception('Invalid range');
				}
				
				while ($t - count($src) < $m[1])
				{
					$dst[] = array_shift($src);
				}
				
				if (count($dst) !== $m[3]) {
					throw new Exception('Invalid line number');
				}
				
				if ($old_length || $new_length) {
					throw new Exception('Chunk is out of range');
				}
				
				$old_length = (integer) $m[2];
				$new_length = (integer) $m[4];
			}
			# Context
			elseif (preg_match(self::$p_ctx,$line,$m)) {
				if (array_shift($src) !== $m[1]) {
					throw new Exception('Bad context');
				}
				$dst[] = $m[1];
				$old_length--;
				$new_length--;
			}
			# Addition
			elseif (preg_match(self::$p_ins,$line,$m)) {
				$dst[] = $m[1];
				$new_length--;
			}
			# Deletion
			elseif (preg_match(self::$p_del,$line,$m)) {
				if (array_shift($src) !== $m[1]) {
					throw new Exception('Bad context (in deletion)');
				}
				$old_length--;
			}
			elseif ($line == '') {
				continue;
			}
			else {
				throw new Exception('Invalid diff format');
			}
		}
		
		if ($old_length || $new_length) {
			throw new Exception('Chunk is out of range');
		}
		
		return implode("\n",array_merge($dst,$src));
	}
	
	/**
	* Throws an exception on invalid unified diff.
	* 
	* @param string		$diff		Diff text to check
	*/
	public static function check($diff)
	{
		$diff = explode("\n",$diff);
		
		$cur_line = 1; $ins_lines = 0;
		
		# Chunk length
		$old_length = $new_length = 0;
		
		foreach ($diff as $line)
		{
			# New chunk
			if (preg_match(self::$p_range,$line,$m)) {
				if ($cur_line > $m[1]) {
					throw new Exception('Invalid range');
				}
				while ($cur_line < $m[1])
				{
					$ins_lines++; $cur_line++;
				}
				if ($ins_lines+1 != $m[3]) {
					throw new Exception('Invalid line number');
				}
				
				if ($old_length || $new_length) {
					throw new Exception('Chunk is out of range');
				}
				
				$old_length = $m[2];
				$new_length = $m[4];
			}
			# Context
			elseif (preg_match(self::$p_ctx,$line,$m)) {
				$ins_lines++; $cur_line++;
				$old_length--;
				$new_length--;
			}
			# Addition
			elseif (preg_match(self::$p_ins,$line,$m)) {
				$ins_lines++;
				$new_length--;
			}
			# Deletion
			elseif (preg_match(self::$p_del,$line,$m)) {
				$cur_line++;
				$old_length--;
			}
			# Skip empty lines
			elseif ($line == '') {
				continue;
			}
			# Unrecognized diff format
			else {
				throw new Exception('Invalid diff format');
			}
		}
		
		if ($old_length || $new_length) {
			throw new Exception('Chunk is out of range');
		}
	}
}
?>