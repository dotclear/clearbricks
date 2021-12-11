<?php
/**
 * @class HttpClient
 * @brief HTTP Client
 *
 * @package Clearbricks
 * @subpackage Network
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Http;

class HttpClient extends Http
{
    public function getError()
    {
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Http\Client', 'HttpClient');
