<?php

declare(strict_types=1);

/**
 * @class formColor
 * @brief HTML Forms color field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formColor extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $id     The identifier
     */
    public function __construct(?string $id = null, ?string $value = null)
    {
        parent::__construct($id, 'color');
        $this
            ->size(7)
            ->maxlength(7);
        if ($value !== null) {
            $this->value($value);
        }
    }
}
