<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../../../net/class.net.socket.php';
require_once __DIR__ . '/../../../net.http/class.net.http.php';
require_once __DIR__ . '/../../../html.validator/class.html.validator.php';

use atoum;

/**
 * html.validator test.
 */
class htmlValidator extends atoum
{
    public function testGetDocument()
    {
        $validator = new \htmlValidator();
        $str    = <<<EODTIDY
<p>Hello</p>
EODTIDY;
        $doc = <<<EODTIDYV
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>validation</title>
</head>
<body>
<p>Hello</p>
</body>
</html>
EODTIDYV;

        $this
            ->string($validator->getDocument($str))
            ->isIdenticalTo($doc);
    }

    public function testGetErrors()
    {
        $validator = new \htmlValidator();
        $str    = <<<EODTIDYE
<p>Hello</b>
EODTIDYE;
        $err    = <<<EODTIDYF
<ul>
<li>Line 7, character 12:
<pre>&lt;p&gt;Hello&lt;/b&gt;
           ^</pre>
Error: end tag for element  b which is not open; try removing the end tag or check for  improper nesting of elements</li>
<li>Line 8, character 7:
<pre>&lt;/body&gt;
      ^</pre>
Error: end tag for  p omitted; end tags are required in  XML for  non-empty elements;  empty elements require an end tag or the start tag must end with /&gt;</li>
<li>Line 7, character 1:
<pre>&lt;p&gt;Hello&lt;/b&gt;
^</pre>
 start tag was here</li>
</ul>
EODTIDYF;

        $this
            ->array($validator->getErrors())
            ->isEmpty();

        $this
            ->variable($validator->perform($validator->getDocument($str)))
            ->isEqualTo(false);

        $this
            ->string($validator->getErrors())
            ->isIdenticalTo($err);
    }

    public function testValidate()
    {
        $this
            ->variable(\htmlValidator::validate('<p>Hello</p>'))
            ->isEqualTo(true);

        $this
            ->array(\htmlValidator::validate('<p>Hello</b>'))
            ->hasSize(2)
            ->boolean['valid']->isEqualTo(false);
    }
}
