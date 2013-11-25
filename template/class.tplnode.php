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

# Template nodes, for parsing purposes

# Generic list node, this one may only be instanciated
# once for root element
class tplNode
{
	# Basic tree structure : links to parent, children forrest
	protected $parentNode;
	protected $children;

	public function __construct() {
		$this->children = new ArrayObject();
		$this->parentNode = null;
	}

	// Returns compiled block
	public function compile($tpl) {
		$res='';
		foreach ($this->children as $child) {
			$res .= $child->compile($tpl);
		}
		return $res;
	}

	# Add a children to current node
	public function addChild ($child) {
		$this->children[] = $child;
		$child->setParent($this);
	}

	# Set current node children
	public function setChildren($children) {
		$this->children = $children;
		foreach ($this->children as $child) {
			$child->setParent($this);
		}

	}

	# Defines parent for current node
	protected function setParent($parent) {
		$this->parentNode = $parent;
	}

	# Retrieves current node parent.
	# If parent is root node, null is returned
	public function getParent() {
		return $this->parentNode;
	}

	# Current node tag
	public function getTag() {
		return "ROOT";
	}
}

