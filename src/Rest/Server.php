<?php
/**
 * @class Server
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
namespace Clearbricks\Rest;

class Server
{
    public $rsp;                 ///< XmlTag Server response
    public $functions = []; ///< array Server's functions

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rsp = new XmlTag('rsp');
    }

    /**
     * Add Function
     *
     * This adds a new function to the server. <var>$callback</var> should be
     * a valid PHP callback. Callback function takes two arguments: GET and
     * POST values.
     *
     * @param string    $name        Function name
     * @param callable  $callback        Callback function
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
        $get  = $_GET ?: [];
        $post = $_POST ?: [];

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

/** Backwards compatibility */
class_alias('Clearbricks\Rest\Server', 'restServer');
