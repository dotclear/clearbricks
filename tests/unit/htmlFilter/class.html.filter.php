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

require_once __DIR__ . '/../../../html.filter/class.html.filter.php';
require_once __DIR__ . '/../../../common/lib.html.php';

use atoum;

/**
 * html.filter test.
 */
class htmlFilter extends atoum
{
    public function testTidySimple()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<p>test</I>'))
            ->isIdenticalTo('<p>test</p>' . "\n");
    }

    public function testTidyComplex()
    {
        $filter = new \htmlFilter();
        $str    = <<<EODTIDY
<p>Hello</p>
<div aria-role="navigation">
 <p data-customattribute="will be an error">bla</p>
 <p>bla</p>
</div>
<div>
 <p>Hi there!</p>
 <div>
  <p>Opps, a mistake</px>
 </div>
</div>
EODTIDY;
        $validStr = <<<EODTIDYV
<p>Hello</p>
<div>
<p>bla</p>
<p>bla</p>
</div>
<div>
<p>Hi there!</p>
<div>
<p>Opps, a mistake</p>
</div>
</div>
EODTIDYV;
        $this->string($filter->apply($str))
            ->isIdenticalTo($validStr . "\n");
    }

    public function testTidyOnerror()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<p onerror="alert(document.domain)">test</I>'))
            ->isIdenticalTo('<p>test</p>' . "\n");
    }

    public function testSimple()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<p>test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testComplex()
    {
        $filter = new \htmlFilter();
        $str    = <<<EOD
<p>Hello</p>
<div aria-role="navigation">
 <p data-customattribute="will be an error">bla</p>
 <p>bla</p>
</div>
<div>
 <p>Hi there!</p>
 <div>
  <p>Opps, a mistake</px>
 </div>
</div>
EOD;
        $validStr = <<<EODV
<p>Hello</p>
<div>
 <p>bla</p>
 <p>bla</p>
</div>
<div>
 <p>Hi there!</p>
 <div>
  <p>Opps, a mistake
EODV;
        $this->string($filter->apply($str, false))
            ->isIdenticalTo($validStr);
    }

    public function testOnerror()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<p onerror="alert(document.domain)">test</I>', false))
            ->isIdenticalTo('<p>test');
    }

    public function testAccesskey()
    {
        $filter = new \htmlFilter();

        $this->string($filter->apply('<a accesskey="x">test</a>', false))
            ->isIdenticalTo('<a accesskey="x">test</a>');
    }
}
