<?php

declare(strict_types=1);

/**
 * @class formUrl
 * @brief HTML Forms url field creation helpers
 *
 * @package Clearbricks
 * @subpackage html.form
 *
 * @since 1.2 First time this was introduced.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class formUrl extends formInput
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $id     The identifier
     */
    public function __construct(string $id = null)
    {
        parent::__construct($id, 'url');
    }
}
