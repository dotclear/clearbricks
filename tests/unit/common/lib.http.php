<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.http.php';

use atoum;

/**
 * Test the form class
 */
class http extends atoum
{
    /** Test getHost
     * In CLI mode superglobal variable $_SERVER is not set correctly
     */
    public function testGetHost()
    {
        // Normal
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $this
            ->string(\http::getHost())
            ->isEqualTo('http://localhost');

        // On a different port
        $_SERVER['SERVER_PORT'] = 8080;
        $this
            ->string(\http::getHost())
            ->isEqualTo('http://localhost:8080');

        // On secure port without enforcing TLS
        $_SERVER['SERVER_PORT'] = 443;
        $this
            ->string(\http::getHost())
            ->isEqualTo('http://localhost:443');

        // On sercure port with enforcing TLS
        $_SERVER['SERVER_PORT'] = 443;
        \http::$https_scheme_on_443 = true;
        $this
            ->string(\http::getHost())
            ->isEqualTo('https://localhost');
    }

    public function testGetHostFromURL()
    {
        $this
            ->string(\http::getHostFromURL('http://www.dotclear.org/is-good-for-you/'))
            ->isEqualTo('http://www.dotclear.org');

        // Note: An empty string might be confuse
        $this
            ->string(\http::getHostFromURL('http:/www.dotclear.org/is-good-for-you/'))
            ->isEqualTo('');
    }

    public function testGetSelfURI()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['REQUEST_URI'] = '/test.html';
        $this
            ->string(\http::getSelfURI())
            ->isEqualTo('http://localhost/test.html');

        // It's usually unlikly, but unlikly is not impossible.
        $_SERVER['REQUEST_URI'] = 'test.html';
        $this
            ->string(\http::getSelfURI())
            ->isEqualTo('http://localhost/test.html');
    }

    public function testRedirect()
    {

    }

    public function testConcatURL()
    {

    }

    public function testRealIP()
    {

    }

    public function testBrowserUID()
    {

    }

    public function testGetAcceptLanguage()
    {

    }

    public function testGetAcceptLanguages()
    {

    }

    public function testCache()
    {

    }

    public function testEtag()
    {

    }

    public function testHead()
    {

    }

    public function testTrimRequest()
    {

    }

    public function testUnsetGlobals()
    {

    }
}
