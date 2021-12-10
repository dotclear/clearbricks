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

use Clearbricks\Common\Exception as CbException;

class Exception extends CbException
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
class_alias('Clearbricks\Network\Xmlrpc\Exception', 'xmlrpcException');
