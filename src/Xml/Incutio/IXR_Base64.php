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

class IXR_Base64
{
    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function getXml()
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Xml\Incutio\IXR_Base64', 'IXR_Base64');
