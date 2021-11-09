<?php

declare(strict_types=1);

/**
 * @class formNumber
 * @brief HTML Forms number field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formNumber extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $id     The identifier
     */
    public function __construct(string $id = null, ?int $min = null, ?int $max = null, ?int $value = null)
    {
        parent::__construct($id, 'number');
        $this
            ->min($min)
            ->max($max);
        if ($value !== null) {
            $this->value($value);
        }
    }
}
