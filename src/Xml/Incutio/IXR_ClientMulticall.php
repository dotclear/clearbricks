<?php
/**
 * @brief IXR - The Incutio XML-RPC Library (http://scripts.incutio.com/xmlrpc/)
 * @version 1.61 - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
 *
 * @package Clearbricks
 * @subpackage Filemanager
 *
 * @copyright Incutio Ltd 2002
 * @copyright Artistic License: http://www.opensource.org/licenses/artistic-license.php
 */
namespace Clearbricks\Xml\Incutio;

class IXR_ClientMulticall extends IXR_Client
{
    public $calls = [];
    public function __construct($server, $path = false, $port = 80)
    {
        parent::__construct($server, $path, $port);
        $this->useragent = 'The Incutio XML-RPC PHP Library (multicall client)';
    }
    public function addCall($method, ...$args)
    {
        $struct = [
            'methodName' => $method,
            'params'     => $args
        ];
        $this->calls[] = $struct;
    }
    public function query()
    {
        // Prepare multicall, then call the parent::query() method
        return parent::query('system.multicall', $this->calls);
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Xml\Incutio\IXR_ClientMulticall', 'IXR_ClientMulticall');
