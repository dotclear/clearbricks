<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# All rights reserved.
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

// Block node, for all <tpl:Tag>...</tpl:Tag>
class tplNodeBlockDefinition extends tplNodeBlock
{
	static protected $stack=array();
	static protected $current_block=null;
	static protected $c=1;

	protected $name;

	/**
	 * Renders the parent block of currently being displayed block
	 * @param  template $tpl the current template engine instance
	 * @return string      the compiled parent block
	 */
	public static function renderParent($tpl) {
		return self::getStackBlock(self::$current_block,$tpl);
	}

	/**
	 * resets blocks stack
	 */
	public static function reset() {
		self::$stack = array();
		self::$current_block = null;
	}

	/**
	 * Retrieves block defined in call stack
	 * @param  string $name the block name
	 * @param  template $tpl  the template engine instance
	 * @return string       the block (empty string if unavailable)
	 */
	public static function getStackBlock($name,$tpl) {
		$stack =& self::$stack[$name];
		$pos = $stack['pos'];
		// First check if block position is correct
		if (isset($stack['blocks'][$pos])) {
			$saved_current_block = self::$current_block;
			self::$current_block=$name;
			if (!is_string($stack['blocks'][$pos])) {
				// Not a string ==> need to compile the tree

				// Go deeper 1 level in stack, to enable calls to parent
				$stack['pos']++;
				$ret='';
				// Compile each and every children
				foreach ($stack['blocks'][$pos] as $child) {
					$ret .= $child->compile($tpl);
				}
				$stack['pos']--;
				$stack['blocks'][$pos] = $ret;
			} else {
				// Already compiled, nice ! Simply return string
				$ret = $stack['blocks'][$pos];
			}
			return $ret;
		} else {
			// Not found => return empty
			return '';
		}
	}

	/**
	 * Block definition specific constructor : keep block name in mind
	 * @param string $tag  Current tag (might be "Block")
	 * @param array $attr Tag attributes (must contain "name" attribute)
	 */
	public function __construct($tag,$attr) {
		parent::__construct($tag,$attr);
		$this->name='';
		if (isset ($attr['name'])) {
			$this->name = $attr['name'];

		}
	}

	/**
	 * Override tag closing processing. Here we enrich the block stack to
	 * keep block history.
	 */
	public function setClosing() {
		if (!isset(self::$stack[$this->name])) {
			self::$stack[$this->name]=array(
				'pos' => 0, // pos is the pointer to the current block being rendered
				'blocks' => array());
		}
		parent::setClosing();
		self::$stack[$this->name]['blocks'][]=$this->children;
		$this->children=new ArrayObject();
	}

	/**
	 * Compile the block definition : grab latest block content being defined
	 * @param  template $tpl current template engine instance
	 * @return string      the compiled block
	 */
	public function compile($tpl) {
		return $tpl->compileBlockNode(
			$this->tag,
			$this->attr,
			self::getStackBlock($this->name,$tpl)
		);
	}

}

