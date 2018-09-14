<?php
/**
 * @class restServer
 * @brief REST Server
 *
 * A very simple REST server implementation
 *
 * @package Clearbricks
 * @subpackage Rest
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class restServer
{
    public $rsp;                 ///< xmlTag Server response
    public $functions = []; ///< array Server's functions

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rsp = new xmlTag('rsp');
    }

    /**
     * Add Function
     *
     * This adds a new function to the server. <var>$callback</var> should be
     * a valid PHP callback. Callback function takes two arguments: GET and
     * POST values.
     *
     * @param string    $name        Function name
     * @param callback    $callback        Callback function
     */
    public function addFunction($name, $callback)
    {
        if (is_callable($callback)) {
            $this->functions[$name] = $callback;
        }
    }

    /**
     * Call Function
     *
     * This method calls callback named <var>$name</var>.
     *
     * @param string    $name        Function name
     * @param array        $get            GET values
     * @param array        $post        POST values
     * @return mixed
     */
    protected function callFunction($name, $get, $post)
    {
        if (isset($this->functions[$name])) {
            return call_user_func($this->functions[$name], $get, $post);
        }
    }

    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param string    $encoding        Server charset
     */
    public function serve($encoding = 'UTF-8')
    {
        $get = [];
        if (isset($_GET)) {
            $get = $_GET;
        }

        $post = [];
        if (isset($_POST)) {
            $post = $_POST;
        }

        if (!isset($_REQUEST['f'])) {
            $this->rsp->status = 'failed';
            $this->rsp->message('No function given');
            $this->getXML($encoding);
            return false;
        }

        if (!isset($this->functions[$_REQUEST['f']])) {
            $this->rsp->status = 'failed';
            $this->rsp->message('Function does not exist');
            $this->getXML($encoding);
            return false;
        }

        try {
            $res = $this->callFunction($_REQUEST['f'], $get, $post);
        } catch (Exception $e) {
            $this->rsp->status = 'failed';
            $this->rsp->message($e->getMessage());
            $this->getXML($encoding);
            return false;
        }

        $this->rsp->status = 'ok';

        $this->rsp->insertNode($res);

        $this->getXML($encoding);
        return true;
    }

    private function getXML($encoding = 'UTF-8')
    {
        header('Content-Type: text/xml; charset=' . $encoding);
        echo $this->rsp->toXML(1, $encoding);
    }
}

/**
 * @class xmlTag
 *
 * XML Tree
 *
 * @package Clearbricks
 * @subpackage XML
 */
class xmlTag
{
    private $_name;
    private $_attr  = [];
    private $_nodes = [];

    /**
     * Constructor
     *
     * Creates the root XML tag named <var>$name</var>. If content is given,
     * it will be appended to root tag with {@link insertNode()}
     *
     * @param string    $name        Tag name
     * @param mixed        $content        Tag content
     */
    public function __construct($name = null, $content = null)
    {
        $this->_name = $name;

        if ($content !== null) {
            $this->insertNode($content);
        }
    }

    /**
     * Add Attribute
     *
     * Magic __set method to add an attribute.
     *
     * @param string    $name        Attribute name
     * @param string    $value        Attribute value
     * @see insertAttr()
     */
    public function __set($name, $value)
    {
        $this->insertAttr($name, $value);
    }

    /**
     * Add a tag
     *
     * This magic __call method appends a tag to XML tree.
     *
     * @param string    $name        Tag name
     * @param array        $args        Function arguments, the first one would be tag content
     */
    public function __call($name, $args)
    {
        if (!preg_match('#^[a-z_]#', $name)) {
            return false;
        }

        if (!isset($args[0])) {
            $args[0] = null;
        }

        $this->insertNode(new self($name, $args[0]));
    }

    /**
     * Add CDTA
     *
     * Appends CDATA to current tag.
     *
     * @param string    $value        Tag CDATA content
     */
    public function CDATA($value)
    {
        $this->insertNode($value);
    }

    /**
     * Add Attribute
     *
     * This method adds an attribute to current tag.
     *
     * @param string    $name        Attribute name
     * @param string    $value        Attribute value
     * @see insertAttr()
     */
    public function insertAttr($name, $value)
    {
        $this->_attr[$name] = $value;
    }

    /**
     * Insert Node
     *
     * This method adds a new XML node. Node could be a instance of xmlTag, an
     * array of valid values, a boolean or a string.
     *
     * @param xmlTag|array|boolean|string    $node    Node value
     */
    public function insertNode($node = null)
    {
        if ($node instanceof self) {
            $this->_nodes[] = $node;
        } elseif (is_array($node)) {
            $child = new self(null);
            foreach ($node as $tag => $n) {
                $child->insertNode(new self($tag, $n));
            }
            $this->_nodes[] = $child;
        } elseif (is_bool($node)) {
            $this->_nodes[] = $node ? '1' : '0';
        } else {
            $this->_nodes[] = (string) $node;
        }
    }

    /**
     * XML Result
     *
     * Returns a string with XML content.
     *
     * @param boolean    $prolog        Append prolog to result
     * @param string    $encoding        Result charset
     * @return string
     */
    public function toXML($prolog = false, $encoding = 'UTF-8')
    {
        if ($this->_name && count($this->_nodes) > 0) {
            $p = '<%1$s%2$s>%3$s</%1$s>';
        } elseif ($this->_name && count($this->_nodes) == 0) {
            $p = '<%1$s%2$s/>';
        } else {
            $p = '%3$s';
        }

        $res = $attr = $content = '';

        foreach ($this->_attr as $k => $v) {
            $attr .= ' ' . $k . '="' . htmlspecialchars($v, ENT_QUOTES, $encoding) . '"';
        }

        foreach ($this->_nodes as $node) {
            if ($node instanceof self) {
                $content .= $node->toXML();
            } else {
                $content .= htmlspecialchars($node, ENT_QUOTES, $encoding);
            }
        }

        $res = sprintf($p, $this->_name, $attr, $content);

        if ($prolog && $this->_name) {
            $res = '<?xml version="1.0" encoding="' . $encoding . '" ?>' . "\n" . $res;
        }

        return $res;
    }
}
