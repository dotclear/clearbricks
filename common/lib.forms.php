<?php
declare(strict_types = 1);

/**
 * @class forms
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
class forms
{
    /**
     * Add common attributes
     *
     * @param      array   $params          The parameters
     *      $params[
     *          'name'          => string name (required if id is not provided).
     *          'id'            => string id (required if name is not provided).
     *          'value'         => string value.
     *          'default'       => string default value (will be used if value is not provided).
     *          'autocomplete'  => string autocomplete type.
     *          'autofocus'     => boolean autofocus.
     *          'class'         => string class(es).
     *          'disabled'      => boolean disabled.
     *          'form'          => string form id.
     *          'lang'          => string lang
     *          'list'          => string list id.
     *          'readonly'      => boolean readonly.
     *          'required'      => boolean required.
     *          'spellcheck'    => boolean spellcheck
     *          'tabindex'      => int tabindex.
     *          'data'          => array data.
     *              [
     *                  key => string data id (rendered as data-<id>).
     *                  value => string data value.
     *              ]
     *          'extra'         => string extra HTML attributes.
     *      ]
     * @param      bool    $includeValue    Includes $params['value'] if exist
     *
     * @return     string
     */
    protected static function commonAttributes(array $params, bool $includeValue = true): string
    {
        $render = '' .

            // Identifier
            // - use $params['name'] for name attribute else $params['id'] if exists
            // - use $params['id'] for id attribute else $params['name'] if exists
            (isset($params['name']) ?
                ' name="' . $params['name'] . '"' :
                (isset($params['id']) ? ' name="' . $params['id'] . '"' : '')) .
            (isset($params['id'])   ?
                ' id="' . $params['id'] . '"' :
                (isset($params['name']) ? ' id="' . $params['name'] . '"' : '')) .

            // Value
            // - $params['default'] will be used as value if exists and $params['value'] does not
            ($includeValue && array_key_exists('value', $params) ?
                ' value="' . $params['value'] . '"' : '') .
            ($includeValue && !array_key_exists('value', $params) && array_key_exists('default', $params) ?
                ' value="' . $params['default'] . '"' : '') .
            (isset($params['checked']) && $params['checked'] ?
                ' checked' : '') .

            // Common attributes
            (isset($params['autocomplete']) ?
                ' autocomplete="' . $params['autocomplete'] . '"' : '') .
            (isset($params['autofocus']) && $params['autofocus'] ?
                ' autofocus' : '') .
            (isset($params['class']) ?
                ' class="' . (is_array($params['class']) ? implode(' ', $params['class']) : $params['class']) . '"' : '') .
            (isset($params['disabled']) && $params['disabled'] ?
                ' disabled' : '') .
            (isset($params['form']) ?
                ' form="' . $params['form'] . '"' : '') .
            (isset($params['list']) ?
                ' list="' . $params['list'] . '"' : '') .
            (isset($params['readonly']) && $params['readonly'] ?
                ' readonly' : '') .
            (isset($params['required']) && $params['required'] ?
                ' required' : '') .
            (isset($params['lang']) ?
                ' lang="' . $params['lang'] . '"' : '') .
            (isset($params['spellcheck']) ?
                ' spellcheck="' . $params['spellcheck'] . '"' : '') .
            (array_key_exists('tabindex', $params) ?
                ' tabindex="' . strval((int) $params['tabindex']) . '"' : '') .

        '';

        if (isset($params['data']) && is_array($params['data'])) {
            // Data attributes
            foreach ($params['data'] as $key => $value) {
                $render .= ' data-' . $key . '="' . $value . '"';
            }
        }

        if (isset($params['extra'])) {
            // Extra HTML
            $render .= ' ' . (is_array($params['extra']) ? implode(' ', $params['extra']) : $params['extra']);
        }

        return $render;
    }

    /**
     * Check mandatory attributes in parameters, at least name or id must be present
     *
     * @param      array  $params  The parameters
     *
     * @return     bool
     */
    protected static function checkAttributes(array $params): bool
    {
        // Check for mandatory info
        return (isset($params['name']) || isset($params['id']));
    }

    /**
     * Select Box
     *
     * Returns HTML code for a select box.
     * **$params['items']** is an array with option titles keys and values in values
     * or an array of object of type {@link formSelectOption}.
     * or an array of object of type {@link formsSelectOption}.
     * If **$params['items']** is an array of arrays, optgroups will be created.
     *
     * @uses form::formSelectOption
     *
     * @param array         $params     Select parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'items'             => mixed combo items (see above).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function combo(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<select' . self::commonAttributes($params) . '>' . "\n" .
            (isset($params['items']) ? self::comboOptions($params['items'], $params['default'] ?? null) : '') .
            '</select>' . "\n";
    }

    private static function comboOptions(array $items, $default = null): string
    {
        $render   = '';
        $option   = '<option value="%1$s"%3$s>%2$s</option>' . "\n";
        $optgroup = '<optgroup label="%1$s">' . "\n" . '%2$s' . "</optgroup>\n";

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $render .= sprintf($optgroup, $key, self::comboOptions($value, $default));
            } elseif ($value instanceof formsSelectOption) {
                $render .= $value->render($default);
            } elseif ($value instanceof formSelectOption) { // Old class, for compatibility purpose
                $render .= $value->render($default);
            } else {
                $selected = ((string) $value == (string) $default) ? ' selected' : '';
                $render .= sprintf($option, $value, $key, $selected);
            }
        }

        return $render;
    }

    /**
     * Radio button
     *
     * Returns HTML code for a radio button.
     *
     * @param array         $params     Radio parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function radio(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<input type="radio"' . self::commonAttributes($params) . '/>' . "\n";
    }

    /**
     * Checkbox
     *
     * Returns HTML code for a checkbox.
     *
     * @param array         $params     Checkbox parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function checkbox(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<input type="checkbox"' . self::commonAttributes($params) . '/>' . "\n";
    }

    /**
     * Input field
     *
     * Returns HTML code for an input field.
     *
     * @param array         $params     Field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'type'              => string type of input (default = text).
     *          'size'              => int number of visible characters.
     *          'maxlength'         => int number of max characters.
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function field(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<input type="' . ($params['type'] ?? 'text') . '"' . self::commonAttributes($params) .
            (isset($params['size']) ? ' size="' . strval((int) $params['size']) . '"' : '') .
            (isset($params['maxlength']) ? ' maxlength="' . strval((int) $params['maxlength']) . '"' : '') .
            '/>' . "\n";
    }

    /**
     * Password field
     *
     * Returns HTML code for a password field.
     *
     * @uses forms::field
     *
     * @param array         $params     Password field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function password(array $params): string
    {
        return self::field(array_merge(
            [
                'type' => 'password'
            ],
            $params
        ));
    }

    /**
     * HTML5 Color field
     *
     * Returns HTML code for an input color field.
     *
     * @uses forms::field
     *
     * @param array         $params     Color field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'size'              => int number of visible characters (default = 7).
     *          'maxlength'         => int number of max characters (default = 7).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function color(array $params): string
    {
        return self::field(array_merge(
            [
                'type'      => 'color',
                'size'      => 7,
                'maxlength' => 7,
            ],
            $params
        ));
    }

    /**
     * HTML5 Email field
     *
     * Returns HTML code for an input email field.
     *
     * @uses forms::field
     *
     * @param array         $params     Email field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'size'              => int number of visible characters (default = 20).
     *          'maxlength'         => int number of max characters (default = 255).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function email(array $params): string
    {
        return self::field(array_merge(
            [
                'type'      => 'email',
                'size'      => 20,
                'maxlength' => 255,
            ],
            $params
        ));
    }

    /**
     * HTML5 URL field
     *
     * Returns HTML code for an input (absolute) URL field.
     *
     * @uses forms::field
     *
     * @param array         $params     Email field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'size'              => int number of visible characters (default = 20).
     *          'maxlength'         => int number of max characters (default = 255).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function url(array $params): string
    {
        return self::field(array_merge(
            [
                'type'      => 'url',
                'size'      => 20,
                'maxlength' => 255,
            ],
            $params
        ));
    }

    /**
     * HTML5 Datetime (local) field
     *
     * Returns HTML code for an input datetime field.
     *
     * @uses forms::field
     *
     * @param array         $params     Email field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'size'              => int number of visible characters (default = 16).
     *          'maxlength'         => int number of max characters (default = 16).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function datetime(array $params): string
    {
        // Cope with unimplemented input type for some browser (type="text" + pattern + placeholder)
        $extra = [];
        if (strpos(strtolower(($params['extra']) ?? ''), 'pattern=') === false) {
            $extra[] = 'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}"';
        }
        if (strpos(strtolower(($params['extra']) ?? ''), 'placeholder') === false) {
            $extra[] = 'placeholder="1962-05-13T14:45"';
        }
        if (count($extra)) {
            $params['extra'] = $params['extra'] ?? '' . implode(' ', $extra);
        }

        return self::field(array_merge(
            [
                'type'      => 'datetime-local',
                'size'      => 16,
                'maxlength' => 16,
            ],
            $params
        ));
    }

    /**
     * HTML5 Date field
     *
     * Returns HTML code for an input date field.
     *
     * @uses forms::field
     *
     * @param array         $params     Email field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'size'              => int number of visible characters (default = 10).
     *          'maxlength'         => int number of max characters (default = 10).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function date(array $params): string
    {
        // Cope with unimplemented input type for some browser (type="text" + pattern + placeholder)
        $extra = [];
        if (strpos(strtolower(($params['extra']) ?? ''), 'pattern=') === false) {
            $extra[] = 'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"';
        }
        if (strpos(strtolower(($params['extra']) ?? ''), 'placeholder') === false) {
            $extra[] = 'placeholder="1962-05-13"';
        }
        if (count($extra)) {
            $params['extra'] = $params['extra'] ?? '' . implode(' ', $extra);
        }

        return self::field(array_merge(
            [
                'type'      => 'date',
                'size'      => 10,
                'maxlength' => 10,
            ],
            $params
        ));
    }

    /**
     * HTML5 Time (local) field
     *
     * Returns HTML code for an input time field.
     *
     * @uses forms::field
     *
     * @param array         $params     Email field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'size'              => int number of visible characters (default = 5).
     *          'maxlength'         => int number of max characters (default = 5).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function time(array $params): string
    {
        // Cope with unimplemented input type for some browser (type="text" + pattern + placeholder)
        $extra = [];
        if (strpos(strtolower(($params['extra']) ?? ''), 'pattern=') === false) {
            $extra[] = 'pattern="[0-9]{2}:[0-9]{2}"';
        }
        if (strpos(strtolower(($params['extra']) ?? ''), 'placeholder') === false) {
            $extra[] = 'placeholder="14:45"';
        }
        if (count($extra)) {
            $params['extra'] = $params['extra'] ?? '' . implode(' ', $extra);
        }

        return self::field(array_merge(
            [
                'type'      => 'time',
                'size'      => 5,
                'maxlength' => 5,
            ],
            $params
        ));
    }

    /**
     * HTML5 file field
     *
     * Returns HTML code for an input file field.
     *
     * @param array         $params     Email field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function file(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<input type="file"' . self::commonAttributes($params) . '/>' . "\n";
    }

    /**
     * HTML5 number input field
     *
     * Returns HTML code for an number input field.
     *
     * @param array         $params     Number field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'min'               => int number of text columns.
     *          'max'               => int number of text raws.
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function number(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<input type="number"' . self::commonAttributes($params) .
            (isset($params['min']) ? ' min="' . strval((int) $params['min']) . '"' : '') .
            (isset($params['max']) ? ' max="' . strval((int) $params['max']) . '"' : '') .
            '/>' . "\n";
    }

    /**
     * Textarea
     *
     * Returns HTML code for a textarea.
     *
     * @param array         $params     Textarea parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          'cols'              => int number of text columns.
     *          'rows'              => int number of text raws.
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function textarea(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<textarea' . self::commonAttributes($params, false) .
            (isset($params['cols']) ? ' cols="' . strval((int) $params['cols']) . '"' : '') .
            (isset($params['rows']) ? ' rows="' . strval((int) $params['rows']) . '"' : '') .
            '>' .
            ($params['value'] ?? '') .
            '</textarea>' . "\n";
    }

    /**
     * Hidden field
     *
     * Returns HTML code for an hidden field.
     *
     * @param array         $params     Hidden field parameters
     *      $param[
     *          'name' and/or 'id'  => string name and or id (required).
     *          …                   => see {@link commonAttributes}.
     *      ]
     *
     * @return string
     *
     * @static
     */
    public static function hidden(array $params): string
    {
        // Check for mandatory info
        if (!self::checkAttributes($params)) {
            return '';
        }

        return '<input type="hidden"' . self::commonAttributes($params) . '/>' . "\n";
    }
}

/**
 * @class formsSelectOption
 * @brief HTML Forms creation helpers
 *
 * @package Clearbricks
 * @subpackage Common
 */
class formsSelectOption
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
