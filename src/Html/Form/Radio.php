<?php

declare(strict_types=1);

/**
 * @class Radio
 * @brief HTML Forms radio button creation helpers
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

class Radio extends Input
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $id     The identifier
     */
    public function __construct(?string $id = null, ?bool $checked = null)
    {
        parent::__construct($id, 'radio');
        if ($checked !== null) {
            $this->checked($checked);
        }
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Html\Form\Radio', 'formRadio');
