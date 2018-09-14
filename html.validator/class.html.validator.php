<?php
/**
 * @class htmlValidator
 * @brief HTML Validator
 *
 * This class will perform an HTML validation upon WDG validator.
 *
 * @package Clearbricks
 * @subpackage HTML
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

/** @cond ONCE */
if (class_exists('netHttp')) {
/** @endcond */

    class htmlValidator extends netHttp
    {
        protected $host       = 'validator.w3.org';
        protected $path       = '/nu/';
        protected $use_ssl    = true;
        protected $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.3a) Gecko/20021207';
        protected $timeout    = 2;

        protected $html_errors = []; ///<    <b>array</b>        Validation errors list

        /**
         * Constructor, no parameters.
         */
        public function __construct()
        {
            parent::__construct($this->host, 443, $this->timeout);
        }

        /**
         * HTML Document
         *
         * Returns an HTML document from a <var>$fragment</var>.
         *
         * @param string    $fragment            HTML content
         * @return string
         */
        public function getDocument($fragment)
        {
            return
                '<!DOCTYPE html>' . "\n" .
                '<html>' . "\n" .
                '<head>' . "\n" .
                '<title>validation</title>' . "\n" .
                '</head>' . "\n" .
                '<body>' . "\n" .
                $fragment . "\n" .
                '</body>' . "\n" .
                '</html>';
        }

        /**
         * HTML validation
         *
         * Performs HTML validation of <var>$html</var>.
         *
         * @param string    $html            HTML document
         * @param string    $charset            Document charset
         * @return boolean
         */
        public function perform($html, $charset = 'UTF-8')
        {
            $this->setMoreHeader('Content-Type: text/html; charset=' . strtolower($charset));
            $this->post($this->path, $html);

            if ($this->getStatus() != 200) {
                throw new Exception('Status code line invalid.');
            }

            $result = $this->getContent();

            if (strpos($result, '<p class="success">The document validates according to the specified schema(s).</p>')) {
                return true;
            } else {
                if ($errors = preg_match('#(<ol>.*</ol>)<p class="failure">There were errors.</p>#msU', $result, $matches)) {
                    $this->html_errors = strip_tags($matches[1], '<ol><li><p><code><strong>');
                }
                return false;
            }
        }

        /**
         * Validation Errors
         *
         * @return array    HTML validation errors list
         */
        public function getErrors()
        {
            return $this->html_errors;
        }

        /**
         * Static HTML validation
         *
         * Static validation method of an HTML fragment. Returns an array with the
         * following parameters:
         *
         * - valid (boolean)
         * - errors (string)
         *
         * @param string    $fragment            HTML content
         * @param string    $charset            Document charset
         * @return array
         */
        public static function validate($fragment, $charset = 'UTF-8')
        {
            $o        = new self;
            $fragment = $o->getDocument($fragment, $charset);

            if ($o->perform($fragment, $charset)) {
                return ['valid' => true, 'errors' => null];
            } else {
                return ['valid' => false, 'errors' => $o->getErrors()];
            }
        }
    }

/** @cond ONCE */
}
/** @endcond */
