<?php
/**
 * @class tplNodeBlock
 * @brief Block node, for all <tpl:Tag>...</tpl:Tag>
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
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
