<?php
/**
 * @class nntpMessage
 *
 * @package Clearbricks
 * @subpackage Network
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class nntpMessage extends mimeMessage
{
    public function getTS()
    {
        if (isset($this->headers['date'])) {
            return strtotime($this->headers['date']);
        }

        return time();
    }

    public function getReferences()
    {
        if (!isset($this->headers['references'])) {
            return [];
        }

        return explode(' ', $this->headers['references']);
    }
}
