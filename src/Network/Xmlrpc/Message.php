<?php
/**
 * @class Message
 * @brief XML-RPC Message
 *
 * @package Clearbricks
 * @subpackage XML-RPC
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
namespace Clearbricks\Network\Xmlrpc;

use Clearbricks\Common\Exception;

class Message
{
    protected $brutxml; ///< string Brut XML message
    protected $message; ///< string XML message

    public $messageType;      ///< string Type of message - methodCall / methodResponse / fault
    public $faultCode;        ///< string Fault code
    public $faultString;      ///< string Fault string
    public $methodName;       ///< string Method name
    public $params = []; ///< array Method parameters

    # Currentstring variable stacks
    protected $_arraystructs      = []; ///< The stack used to keep track of the current array/struct
    protected $_arraystructstypes = []; ///< Stack keeping track of if things are structs or array
    protected $_currentStructName = []; ///< A stack as well
    protected $_param;
    protected $_value;
    protected $_currentTag;
    protected $_currentTagContents;
    protected $_parser; ///< The XML parser

    /**
     * Constructor
     *
     * @param string        $message        XML Message
     */
    public function __construct($message)
    {
        $this->brutxml = $this->message = $message;
    }

    /**
     * Message parser
     */
    public function parse()
    {
        // first remove the XML declaration
        $this->message = preg_replace('/<\?xml(.*)?\?' . '>/', '', (string) $this->message);
        if (trim($this->message) == '') {
            throw new Exception('XML Parser Error. Empty message');
        }

        // Strip DTD.
        $header = preg_replace('/^<!DOCTYPE[^>]*+>/i', '', substr($this->message, 0, 200), 1);
        $xml    = trim(substr_replace($this->message, $header, 0, 200));
        if ($xml == '') {
            throw new Exception('XML Parser Error.');
        }
        // Confirm the XML now starts with a valid root tag. A root tag can end in [> \t\r\n]
        $root_tag = substr($xml, 0, strcspn(substr($xml, 0, 20), "> \t\r\n"));
        // Reject a second DTD.
        if (strtoupper($root_tag) == '<!DOCTYPE') {
            throw new Exception('XML Parser Error.');
        }
        if (!in_array($root_tag, ['<methodCall', '<methodResponse', '<fault'])) {
            throw new Exception('XML Parser Error.');
        }

        try {
            $dom = new \DOMDocument();
            @$dom->loadXML($xml);
            if ($dom->getElementsByTagName('*')->length > 30000) {
                throw new Exception('XML Parser Error.');
            }
        } catch (Exception $e) {
            throw new Exception('XML Parser Error.');
        }
        $this->_parser = xml_parser_create();

        # Set XML parser to take the case of tags in to account
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

        # Set XML parser callback functions
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, [$this, 'tag_open'], [$this, 'tag_close']);
        xml_set_character_data_handler($this->_parser, [$this, 'cdata']);

        if (!xml_parse($this->_parser, $this->message)) {
            $c = xml_get_error_code($this->_parser);
            $e = xml_error_string($c);
            $e .= ' on line ' . xml_get_current_line_number($this->_parser);

            throw new Exception('XML Parser Error. ' . $e, $c);
        }

        xml_parser_free($this->_parser);

        # Grab the error messages, if any
        if ($this->messageType == 'fault') {
            $this->faultCode   = $this->params[0]['faultCode'];
            $this->faultString = $this->params[0]['faultString'];
        }

        return true;
    }

    protected function tag_open($parser, $tag, $attr)
    {
        $this->_currentTag = $tag;

        switch ($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault':
                $this->messageType = $tag;

                break;
            # Deal with stacks of arrays and structs
            case 'data': # data is to all intents and puposes more interesting than array
                $this->_arraystructstypes[] = 'array';
                $this->_arraystructs[]      = [];

                break;
            case 'struct':
                $this->_arraystructstypes[] = 'struct';
                $this->_arraystructs[]      = [];

                break;
        }
    }

    protected function cdata($parser, $cdata)
    {
        $this->_currentTagContents .= $cdata;
    }

    protected function tag_close($parser, $tag)
    {
        $valueFlag = false;
        $value     = null;

        switch ($tag) {
            case 'int':
            case 'i4':
                $value                     = (int) trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'double':
                $value                     = (float) trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'string':
                $value                     = (string) trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'dateTime.iso8601':
                $value = new Date(trim($this->_currentTagContents));
                # $value = $iso->getTimestamp();
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'value':
                # "If no type is indicated, the type is string."
                if (trim($this->_currentTagContents) != '') {
                    $value                     = (string) $this->_currentTagContents;
                    $this->_currentTagContents = '';
                    $valueFlag                 = true;
                }

                break;
            case 'boolean':
                $value                     = (bool) trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            case 'base64':
                $value                     = base64_decode($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;

                break;
            # Deal with stacks of arrays and structs
            case 'data':
            case 'struct':
                $value = array_pop($this->_arraystructs);
                array_pop($this->_arraystructstypes);
                $valueFlag = true;

                break;
            case 'member':
                array_pop($this->_currentStructName);

                break;
            case 'name':
                $this->_currentStructName[] = trim($this->_currentTagContents);
                $this->_currentTagContents  = '';

                break;
            case 'methodName':
                $this->methodName          = trim($this->_currentTagContents);
                $this->_currentTagContents = '';

                break;
        }

        if ($valueFlag) {
            if (count($this->_arraystructs) > 0) {
                # Add value to struct or array
                if ($this->_arraystructstypes[count($this->_arraystructstypes) - 1] == 'struct') {
                    # Add to struct
                    $this->_arraystructs[count($this->_arraystructs) - 1][$this->_currentStructName[count($this->_currentStructName) - 1]] = $value;
                } else {
                    # Add to array
                    $this->_arraystructs[count($this->_arraystructs) - 1][] = $value;
                }
            } else {
                # Just add as a paramater
                $this->params[] = $value;
            }
        }
    }
}

/** Backwards compatibility */
class_alias('Clearbricks\Network\Xmlrpc\Message', 'xmlrpcMessage');
