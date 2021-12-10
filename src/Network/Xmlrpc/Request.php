<?php
/**
 * @class Request
 * @brief XML-RPC Request
 *
 * @package Clearbricks
 * @subpackage XML-RPC
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Xmlrpc;

class Request
{
    public $method; ///< string Request method name
    public $args;   ///< array Request method arguments
    public $xml;    ///< string Request XML string

    /**
     * Constructor
     *
     * @param string    $method        Method name
     * @param array        $args        Method arguments
     */
    public function __construct($method, $args)
    {
        $this->method = $method;
        $this->args   = $args;

        $this->xml = '<?xml version="1.0"?>' . "\n" .
        "<methodCall>\n" .
        '  <methodName>' . $this->method . "</methodName>\n" .
            "  <params>\n";

        foreach ($this->args as $arg) {
            $this->xml .= '    <param><value>';
            $v = new Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }

        $this->xml .= '  </params></methodCall>';
    }

    /**
     * Request length
     *
     * Returns {@link $xml} content length.
     *
     * @return integer
     */
    public function getLength()
    {
        return strlen($this->xml);
    }

    /**
     * Request XML
     *
     * Returns request XML version.
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Xmlrpc\Request', 'xmlrpcRequest');
