<?php
/**
 * @class Base64
 * @brief XML-RPC Base 64 object
 *
 * @package Clearbricks
 * @subpackage XML-RPC
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Xmlrpc;

class Base64
{
    protected $data; ///< string

    /**
     * Constructor
     *
     * Create a new instance of xmlrpcBase64.
     *
     * @param string        $data        Data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * XML Data
     *
     * Returns the XML fragment for XML-RPC message inclusion.
     *
     * @return string
     */
    public function getXml()
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Xmlrpc\Base64', 'xmlrpcBase64');
