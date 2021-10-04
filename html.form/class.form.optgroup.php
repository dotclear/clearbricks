<?php
declare(strict_types = 1);

/**
 * @class formOptgroup
 * @brief HTML Forms optgroup creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formOptgroup extends formComponent
{
    private const DEFAULT_ELEMENT = 'optgroup';

    /**
     * Constructs a new instance.
     *
     * @param      string       $name     The optgroup name
     * @param      null|string  $element  The element
     */
    public function __construct(string $name, ?string $element = null)
    {
        parent::__construct(__CLASS__, $element ?? self::DEFAULT_ELEMENT);
        $this
            ->text($name);
    }

    /**
     * Renders the HTML component.
     *
     * @param      null|string  $default   The default value
     *
     * @return     string
     */
    public function render(?string $default = null): string
    {
        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) .
            (isset($this->text) ? ' label="' . $this->text . '"' : '') .
            $this->renderCommonAttributes() . '>' . "\n";

        if (isset($this->items) && is_array($this->items)) {
            foreach ($this->items as $item) {
                $buffer .= $item->render($default);
            }
        }

        $buffer .= '</' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . '>' . "\n";

        return $buffer;
    }

    /**
     * Gets the default element.
     *
     * @return     string  The default element.
     */
    public function getDefaultElement(): string
    {
        return self::DEFAULT_ELEMENT;
    }
}
