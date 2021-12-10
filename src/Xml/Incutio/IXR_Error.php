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

class IXR_Error
{
    public $code;
    public $message;
    public function __construct($code, $message)
    {
        $this->code    = $code;
        $this->message = $message;
    }
    public function getXml()
    {
        $xml = <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;

        return $xml;
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Xml\Incutio\IXR_Error', 'IXR_Error');
