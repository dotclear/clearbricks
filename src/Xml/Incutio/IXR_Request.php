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

class IXR_Request
{
    public $method;
    public $args;
    public $xml;
    public function __construct($method, $args)
    {
        $this->method = $method;
        $this->args   = $args;
        $this->xml    = <<<EOD
<?xml version="1.0"?>
<methodCall>
<methodName>{$this->method}</methodName>
<params>

EOD;
        foreach ($this->args as $arg) {
            $this->xml .= '<param><value>';
            $v = new IXR_Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }
        $this->xml .= '</params></methodCall>';
    }
    public function getLength()
    {
        return strlen($this->xml);
    }
    public function getXml()
    {
        return $this->xml;
    }
}

class IXR_Client
{
    public $server;
    public $port;
    public $path;
    public $useragent;
    protected $proxy_host;
    protected $proxy_port;
    public $timeout = 10;
    public $response;
    public $message = false;
    public $debug   = false;
    // Storage place for an error message
    public $error = false;
    public function __construct($server, $path = false, $port = 80)
    {
        if (!$path) {
            // Assume we have been given a URL instead
            $bits         = parse_url($server);
            $this->server = $bits['host'];
            $this->port   = $bits['port'] ?? 80;
            $this->path   = $bits['path'] ?? '/';
            // Make absolutely sure we have a path
            if (!$this->path) {
                $this->path = '/';
            }
        } else {
            $this->server = $server;
            $this->path   = $path;
            $this->port   = $port;
        }
        $this->useragent = 'The Incutio XML-RPC PHP Library';

        if (defined('HTTP_PROXY_HOST') && defined('HTTP_PROXY_PORT')) {
            $this->setProxy(HTTP_PROXY_HOST, HTTP_PROXY_PORT);
        }
    }
    public function setProxy($host, $port = '8080')
    {
        $this->proxy_host = $host;
        $this->proxy_port = $port;
    }
    public function query()
    {
        $args    = func_get_args();
        $method  = array_shift($args);
        $request = new IXR_Request($method, $args);
        $length  = $request->getLength();
        $xml     = $request->getXml();
        $r       = "\r\n";
        $request = "POST {$this->path} HTTP/1.0$r";
        $request .= "Host: {$this->server}$r";
        $request .= "Content-Type: text/xml$r";
        $request .= "User-Agent: {$this->useragent}$r";
        $request .= "Content-length: {$length}$r$r";
        $request .= $xml;
        // Now send the request
        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($request) . "\n</pre>\n\n";
        }

        if ($this->proxy_host && $this->proxy_port) {
            $host = $this->proxy_host;
            $port = $this->proxy_port;
        } else {
            $host = $this->server;
            $port = $this->port;
        }

        $fp = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        if (!$fp) {
            $this->error = new IXR_Error(-32300, 'transport error - could not open socket');

            return false;
        }
        socket_set_timeout($fp, $this->timeout);
        fputs($fp, $request);
        $contents       = '';
        $gotFirstLine   = false;
        $gettingHeaders = true;
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if (!$gotFirstLine) {
                // Check line for '200'
                if (strstr($line, '200') === false) {
                    $this->error = new IXR_Error(-32300, 'transport error - HTTP status code was not 200');

                    return false;
                }
                $gotFirstLine = true;
            }
            if (trim($line) == '') {
                $gettingHeaders = false;
            }
            if (!$gettingHeaders) {
                $contents .= trim($line) . "\n";
            }
        }
        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($contents) . "\n</pre>\n\n";
        }
        // Now parse what we've got back
        $this->message = new IXR_Message($contents);
        if (!$this->message->parse()) {
            // XML error
            $this->error = new IXR_Error(-32700, 'parse error. not well formed');

            return false;
        }
        // Is the message a fault?
        if ($this->message->messageType == 'fault') {
            $this->error = new IXR_Error($this->message->faultCode, $this->message->faultString);

            return false;
        }
        // Message must be OK
        return true;
    }
    public function getResponse()
    {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }
    public function isError()
    {
        return (is_object($this->error));
    }
    public function getErrorCode()
    {
        return $this->error->code;
    }
    public function getErrorMessage()
    {
        return $this->error->message;
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Xml\Incutio\IXR_Request', 'IXR_Request');
