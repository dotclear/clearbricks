<?php

declare(strict_types=1);

/**
 * @class Password
 * @brief HTML Forms password field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Html\Form;

class Password extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $id     The identifier
     */
    public function __construct(?string $id = null, ?string $value = null)
    {
        parent::__construct($id, 'password');
        if ($value !== null) {
            $this->value($value);
        }
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Html\Form\Password', 'formPassword');
