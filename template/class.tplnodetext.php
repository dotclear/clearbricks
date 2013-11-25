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

// Text node, for any non-tpl content
class tplNodeText extends tplNode
{
	// Simple text node, only holds its content
	protected $content;

	public function __construct($text) {
		parent::__construct();
		$this->content=$text;
	}

	public function compile($tpl) {
		return $this->content;
	}

	public function getTag() {
		return "TEXT";
	}
}
