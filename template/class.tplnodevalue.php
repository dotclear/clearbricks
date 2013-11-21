<?php
// Value node, for all {{tpl:Tag}}
class tplNodeValue extends tplNode
{
	protected $attr;
	protected $str_attr;
	protected $tag;

	public function __construct($tag,$attr,$str_attr) {
		parent::__construct();
		$this->content='';
		$this->tag = $tag;
		$this->attr = $attr;
		$this->str_attr = $str_attr;
	}

	public function compile($tpl) {
		return $tpl->compileValueNode($this->tag,$this->attr,$this->str_attr);
	}

	public function getTag() {
		return $this->tag;
	}
}
