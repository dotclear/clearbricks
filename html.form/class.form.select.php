<?php

declare(strict_types=1);

/**
 * @class formSelect
 * @brief HTML Forms select creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formSelect extends formComponent
{
    private const DEFAULT_ELEMENT = 'select';

    /**
     * Constructs a new instance.
     *
     * @param      null|string  $id       The identifier
     * @param      null|string  $element  The element
     */
    public function __construct(?string $id = null, ?string $element = null)
    {
        parent::__construct(__CLASS__, $element ?? self::DEFAULT_ELEMENT);
        if ($id !== null) {
            $this
                ->id($id)
                ->name($id);
        }
    }

    /**
     * Renders the HTML component (including select options).
     *
     * @param      null|string  $default   The default value
     *
     * @return     string
     */
    public function render(?string $default = null): string
    {
        if (!$this->checkMandatoryAttributes()) {
            return '';
        }

        $buffer = '<' . ($this->getElement() ?? self::DEFAULT_ELEMENT) . $this->renderCommonAttributes() . '>' . "\n";

        if (isset($this->items) && is_array($this->items)) {
            foreach ($this->items as $item => $value) {
                if ($value instanceof formOption || $value instanceof formOptgroup) {
                    /* @phpstan-ignore-next-line */
                    $buffer .= $value->render($this->default ?? $default ?? null);
                } elseif (is_array($value)) {
                    /* @phpstan-ignore-next-line */
                    $buffer .= (new formOptgroup($item))->items($value)->render($this->default ?? $default ?? null);
                } else {
                    /* @phpstan-ignore-next-line */
                    $buffer .= (new formOption($item, $value))->render($this->default ?? $default ?? null);
                }
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
