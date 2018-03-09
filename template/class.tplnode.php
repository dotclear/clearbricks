<?php
/**
 * @class tplNode
 * @brief Template nodes, for parsing purposes
 *
 * Generic list node, this one may only be instanciated once for root element
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class tplNode
{
    # Basic tree structure : links to parent, children forrest
    protected $parentNode;
    protected $children;

    public function __construct()
    {
        $this->children   = new ArrayObject();
        $this->parentNode = null;
    }

    // Returns compiled block
    public function compile($tpl)
    {
        $res = '';
        foreach ($this->children as $child) {
            $res .= $child->compile($tpl);
        }
        return $res;
    }

    # Add a children to current node
    public function addChild($child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    # Set current node children
    public function setChildren($children)
    {
        $this->children = $children;
        foreach ($this->children as $child) {
            $child->setParent($this);
        }

    }

    # Defines parent for current node
    protected function setParent($parent)
    {
        $this->parentNode = $parent;
    }

    # Retrieves current node parent.
    # If parent is root node, null is returned
    public function getParent()
    {
        return $this->parentNode;
    }

    # Current node tag
    public function getTag()
    {
        return "ROOT";
    }
}
