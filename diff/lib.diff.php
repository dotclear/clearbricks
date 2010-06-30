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
 * diff utilities
 *
 * @package Clearbricks
 * @author Alexandre Syenchuk
 **/
class diff
{
	
	/**
	* Finds the shortest edit script using a fast algorithm taken from paper
	* "An O(ND) Difference Algorithm and Its Variations" by Eugene W.Myers,
	* 1986.
	* 
	* @param array			$src			Original data
	* @param array			$dst			New data
	* 
	* @return array
	*/
	public static function SES($src,$dst)
	{
		$cx = count($src);
		$cy = count($dst);
		
		$stack = array();
		$V = array(1=>0);
		$end_reached = false;
		
		# Find LCS length
		for ($D = 0; $D < $cx+$cy+1 && !$end_reached; $D++)
		{
			for ($k = -$D; $k <= $D; $k += 2)
			{
				$x = ($k == -$D || $k != $D && $V[$k-1] < $V[$k+1])
					? $V[$k+1] : $V[$k-1]+1;
				$y = $x-$k;
				
				while ($x < $cx && $y < $cy && $src[$x] == $dst[$y])
				{
					$x++; $y++;
				}
				
				$V[$k] = $x;
				
				if ($x == $cx && $y == $cy) {
					$end_reached = true;
					break;
				}
			}
			$stack[] = $V;
		}
		$D--;
		
		# Recover edit path
		$res = array();
		for ($D = $D; $D > 0; $D--)
		{
			$V = array_pop($stack);
			$cx = $x;
			$cy = $y;
			
			# Try right diagonal
			$k++;
			$x = $V[$k];
			$y = $x-$k;
			$y++;
			
			while ($x < $cx && $y < $cy
			&& isset($src[$x]) && isset($dst[$y]) && $src[$x] == $dst[$y])
			{
				$x++; $y++;
			}
			
			if ($x == $cx && $y == $cy) {
				$x = $V[$k];
				$y = $x-$k;
				
				$res[] = array('i',$x,$y);
				continue;
			}
			
			# Right diagonal wasn't the solution, use left diagonal
			$k -= 2;
			$x = $V[$k];
			$y = $x-$k;
			$res[] = array('d',$x,$y);
		}
		
		return array_reverse($res);
	}
}
?>