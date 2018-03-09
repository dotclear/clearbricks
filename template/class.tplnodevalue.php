<?php
/**
 * @class tplNodeValue
 * @brief Value node, for all {{tpl:Tag}}
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class tplNodeValue extends tplNode
{
    protected $attr;
    protected $str_attr;
    protected $tag;

    public function __construct($tag, $attr, $str_attr)
    {
        parent::__construct();
        $this->content  = '';
        $this->tag      = $tag;
        $this->attr     = $attr;
        $this->str_attr = $str_attr;
    }

    public function compile($tpl)
    {
        return $tpl->compileValueNode($this->tag, $this->attr, $this->str_attr);
    }

    public function getTag()
    {
        return $this->tag;
    }
}
