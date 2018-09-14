<?php
/**
 * @class sessionDB
 * @brief Database Session Handler
 *
 * This class allows you to handle session data in database.
 *
 * @package Clearbricks
 * @subpackage Session
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class sessionDB
{
    private $con;
    private $table;
    private $cookie_name;
    private $cookie_path;
    private $ttl       = '-120 minutes';
    private $transient = false;

    /**
     * Constructor
     *
     * This method creates an instance of sessionDB class.
     *
     * @param dbLayer    &$con            dbLayer inherited database instance
     * @param string        $table            Table name
     * @param string        $cookie_name    Session cookie name
     * @param string        $cookie_path    Session cookie path
     * @param string        $cookie_domain    Session cookie domaine
     * @param boolean    $cookie_secure    Session cookie is available only through SSL if true
     * @param string     $ttl             TTL (default -120 minutes)
     * @param boolean    $transient        Transient session : no db optimize on session destruction if true
     */
    public function __construct($con, $table, $cookie_name,
        $cookie_path = null, $cookie_domain = null, $cookie_secure = false, $ttl = null, $transient = false) {
        $this->con           = &$con;
        $this->table         = $table;
        $this->cookie_name   = $cookie_name;
        $this->cookie_path   = is_null($cookie_path) ? '/' : $cookie_path;
        $this->cookie_domain = $cookie_domain;
        $this->cookie_secure = $cookie_secure;
        if (!is_null($ttl)) {
            $this->ttl = $ttl;
        }
        $this->transient = $transient;

        if (function_exists('ini_set')) {
            @ini_set('session.use_cookies', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('url_rewriter.tags', '');
            @ini_set('session.use_trans_sid', '0');
            @ini_set('session.cookie_path', $this->cookie_path);
            @ini_set('session.cookie_domain', $this->cookie_domain);
            @ini_set('session.cookie_secure', $this->cookie_secure);
        }
    }

    /**
     * Destructor
     *
     * This method calls session_write_close PHP function.
     */
    public function __destruct()
    {
        if (isset($_SESSION)) {
            session_write_close();
        }
    }

    /**
     * Session Start
     */
    public function start()
    {
        session_set_save_handler(
            [&$this, '_open'],
            [&$this, '_close'],
            [&$this, '_read'],
            [&$this, '_write'],
            [&$this, '_destroy'],
            [&$this, '_gc']
        );

        if (isset($_SESSION) && session_name() != $this->cookie_name) {
            $this->destroy();
        }

        if (!isset($_COOKIE[$this->cookie_name])) {
            session_id(sha1(uniqid(rand(), true)));
        }

        session_name($this->cookie_name);
        session_start();
    }

    /**
     * Session Destroy
     *
     * This method destroies all session data and removes cookie.
     */
    public function destroy()
    {
        $_SESSION = [];
        session_unset();
        session_destroy();
        call_user_func_array('setcookie', $this->getCookieParameters(false, -600));
    }

    /**
     * Session Transient
     *
     * This method set the transient flag of the session
     *
     * @param boolean     $transient     Session transient flag
     */
    public function setTransientSession($transient = false)
    {
        $this->transient = $transient;
    }

    /**
     * Session Cookie
     *
     * This method returns an array of all session cookie parameters.
     *
     * @param mixed        $value        Cookie value
     * @param integer    $expire        Cookie expiration timestamp
     */
    public function getCookieParameters($value = null, $expire = 0)
    {
        return [
            session_name(),
            $value,
            $expire,
            $this->cookie_path,
            $this->cookie_domain,
            $this->cookie_secure
        ];
    }

    public function _open($path, $name)
    {
        return true;
    }

    public function _close()
    {
        $this->_gc();
        return true;
    }

    public function _read($ses_id)
    {
        $strReq = 'SELECT ses_value FROM ' . $this->table . ' ' .
        'WHERE ses_id = \'' . $this->checkID($ses_id) . '\' ';

        $rs = $this->con->select($strReq);

        if ($rs->isEmpty()) {
            return '';
        } else {
            return $rs->f('ses_value');
        }
    }

    public function _write($ses_id, $data)
    {
        $strReq = 'SELECT ses_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE ses_id = '" . $this->checkID($ses_id) . "' ";

        $rs = $this->con->select($strReq);

        $cur            = $this->con->openCursor($this->table);
        $cur->ses_time  = (string) time();
        $cur->ses_value = (string) $data;

        if (!$rs->isEmpty()) {
            $cur->update("WHERE ses_id = '" . $this->checkID($ses_id) . "' ");
        } else {
            $cur->ses_id    = (string) $this->checkID($ses_id);
            $cur->ses_start = (string) time();

            $cur->insert();
        }

        return true;
    }

    public function _destroy($ses_id)
    {
        $strReq = 'DELETE FROM ' . $this->table . ' ' .
        'WHERE ses_id = \'' . $this->checkID($ses_id) . '\' ';

        $this->con->execute($strReq);

        if (!$this->transient) {
            $this->_optimize();
        }
        return true;
    }

    public function _gc()
    {
        $ses_life = strtotime($this->ttl);

        $strReq = 'DELETE FROM ' . $this->table . ' ' .
            'WHERE ses_time < ' . $ses_life . ' ';

        $this->con->execute($strReq);

        if ($this->con->changes() > 0) {
            $this->_optimize();
        }
        return true;
    }

    private function _optimize()
    {
        $this->con->vacuum($this->table);
    }

    private function checkID($id)
    {
        if (!preg_match('/^([0-9a-f]{40})$/i', $id)) {
            return;
        }
        return $id;
    }
}
