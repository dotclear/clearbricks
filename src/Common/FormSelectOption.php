<?php
/**
 * @class FormSelectOption
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Common;

class FormSelectOption
{
    public $name;       ///< string Option name
    public $value;      ///< mixed  Option value
    public $class_name; ///< string Element class name
    public $html;       ///< string Extra HTML attributes

    /**
     * sprintf template for option
     * @var string $option
     * @access private
     */
    private $option = '<option value="%1$s"%3$s>%2$s</option>' . "\n";

    /**
     * Option constructor
     *
     * @param string  $name        Option name
     * @param mixed   $value       Option value
     * @param string  $class_name  Element class name
     * @param string  $html        Extra HTML attributes
     */
    public function __construct(string $name, $value, string $class_name = '', string $html = '')
    {
        $this->name       = $name;
        $this->value      = $value;
        $this->class_name = $class_name;
        $this->html       = $html;
    }

    /**
     * Option renderer
     *
     * Returns option HTML code
     *
     * @param string  $default  Value of selected option
     * @return string
     */
    public function render(?string $default): string
    {
        $attr = $this->html ? ' ' . $this->html : '';
        $attr .= $this->class_name ? ' class="' . $this->class_name . '"' : '';

        if ($this->value == $default) {
            $attr .= ' selected="selected"';
        }

        return sprintf($this->option, $this->value, $this->name, $attr) . "\n";
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Common\FormSelectOption', 'formSelectOption');
