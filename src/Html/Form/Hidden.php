<?php

declare(strict_types=1);

/**
 * @class Hidden
 * @brief HTML Forms hidden field creation helpers
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

class Hidden extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $id     The identifier
     */
    public function __construct(string $id = null, ?string $value = null)
    {
        // Label should not be rendered for an input type="hidden"
        parent::__construct($id, 'hidden', false);
        if ($value !== null) {
            $this->value($value);
        }
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Html\Form\Hidden', 'formHidden');
