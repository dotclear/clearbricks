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

require_once CLEARBRICKS_PATH . '/common/lib.html.php';

use atoum;

/**
 * Test the form class
 */
class html extends atoum
{
    /** Simple test. Don't need to test PHP functions
     */
    public function testEscapeHTML()
    {
        $str = "\"<>&";
        $this
            ->string(\html::escapeHTML($str))
            ->isEqualTo('&quot;&lt;&gt;&amp;');
    }

    public function testDecodeEntities()
    {
        $this
            ->string(\html::decodeEntities('&lt;body&gt;', true))
            ->isEqualTo('&lt;body&gt;');
        $this
            ->string(\html::decodeEntities('&lt;body&gt;'))
            ->isEqualTo('<body>');

    }

    /**
     * html::clean is a wrapper of a PHP native function
     * Simple test
     */
    public function testClean()
    {
        $this
            ->string(\html::clean('<b>test</b>'))
            ->isEqualTo('test');
    }

    public function testEscapeJS()
    {
        $this
            ->string(\html::escapeJS('<script>alert("Hello world");</script>'))
            ->isEqualTo('&lt;script&gt;alert(\"Hello world\");&lt;/script&gt;');
    }

    /**
     * html::escapeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testEscapeURL()
    {
        $this
            ->string(\html::escapeURL('http://www.dotclear.org/?q=test&test=1'))
            ->isEqualTo('http://www.dotclear.org/?q=test&amp;test=1');
    }

    /**
     * html::sanitizeURL is a wrapper of a PHP native function
     * Simple test
     */
    public function testSanitizeURL()
    {
        $this
            ->string(\html::sanitizeURL('http://www.dotclear.org/'))
            ->isEqualTo('http%3A//www.dotclear.org/');
    }

    /**
     * Test removing host prefix
     */
    public function testStripHostURL()
    {
        $this
            ->string(\html::stripHostURL('http://www.dotclear.org/best-blog-engine/'))
            ->isEqualTo('/best-blog-engine/');

        $this
            ->string(\html::stripHostURL('dummy:/not-well-formed-url.d'))
            ->isEqualTo('dummy:/not-well-formed-url.d');
    }

    public function testAbsoluteURLs()
    {
        $this
            ->string(\html::absoluteURLs('<a href="/best-blog-engine-ever/">Clickme</a>', 'http://dotclear.org/'))
            ->isEqualTo('<a href="http://dotclear.org/best-blog-engine-ever/">Clickme</a>');

        $this
            ->string(\html::absoluteURLs('<a href="best-blog-engine-ever/">Clickme</a>', 'http://dotclear.org/'))
            ->isEqualTo('<a href="http://dotclear.org/best-blog-engine-ever/">Clickme</a>');

    }
}
