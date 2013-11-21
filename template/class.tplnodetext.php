<?php
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
