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
#

/**
 * @class tplNodeBlock
 * @brief Block node, for all <tpl:Tag>...</tpl:Tag>
 *
 * @package Clearbricks
 * @subpackage Template
 */
class tplNodeBlock extends tplNode
{
    protected $attr;
    protected $tag;
    protected $closed;

    public function __construct($tag, $attr)
    {
        parent::__construct();
        $this->content = '';
        $this->tag     = $tag;
        $this->attr    = $attr;
        $this->closed  = false;
    }
    public function setClosing()
    {
        $this->closed = true;
    }
    public function isClosed()
    {
        return $this->closed;
    }
    public function compile($tpl)
    {
        if ($this->closed) {
            $content = parent::compile($tpl);
            return $tpl->compileBlockNode($this->tag, $this->attr, $content);
        } else {
            // if tag has not been closed, silently ignore its content...
            return '';
        }
    }
    public function getTag()
    {
        return $this->tag;
    }
}
