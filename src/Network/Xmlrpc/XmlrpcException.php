<?php
/**
 * @class Exception
 * @brief XML-RPC Exception
 *
 * @package Clearbricks
 * @subpackage XML-RPC
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Xmlrpc;

use Clearbricks\Common\Exception;

class XmlrpcException extends Exception
{
    /**
     * @param string    $message        Exception message
     * @param integer    $code        Exception code
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Xmlrpc\XmlrpcException', 'xmlrpcException');
