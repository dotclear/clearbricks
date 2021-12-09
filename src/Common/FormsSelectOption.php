<?php
declare(strict_types = 1);

/**
 * @class FormsSelectOption
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Common;

class FormsSelectOption
{
    public $name;       ///< string Option name
    public $value;      ///< mixed  Option value
    public $class_name; ///< string Element class name
    public $extra;      ///< string Extra HTML attributes

    /**
     * sprintf template for option
     * @var string $option
     * @access private
     */
    private $option = '<option value="%1$s"%3$s>%2$s</option>' . "\n";

    /**
     * Option constructor
     *
     * @param array   $params       Parameters
     *      $params = [
     *          'name'          => string option name (required).
     *          'value'         => string option value (required).
     *          'class_name'    => string class name.
     *          'extra'         => string extra HTML attributes.
     *      ]
     */
    public function __construct(array $params)
    {
        $this->name       = $params['name'];
        $this->value      = $params['value'];
        $this->class_name = $params['class'] ?? null;
        $this->extra      = $params['extra'] ?? null;
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
        $attr = $this->class_name ? ' class="' . $this->class_name . '"' : '';
        $attr .= $this->extra ? ' ' . $this->extra : '';

        if ($this->value == $default) {
            $attr .= ' selected';
        }

        return sprintf($this->option, $this->value, $this->name, $attr);
    }
}
