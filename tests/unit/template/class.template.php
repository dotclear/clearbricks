<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# All rights reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****
namespace tests\unit;

use atoum;
use Faker;

require_once __DIR__.'/../bootstrap.php';

$f = str_replace('\\',  '/', __FILE__);
require_once(str_replace('tests/unit/',  '', $f));
require_once(__DIR__.'/../../../common/lib.files.php');
require_once(__DIR__.'/../../../template/class.tplnode.php');
require_once(__DIR__.'/../../../template/class.tplnodeblock.php');
require_once(__DIR__.'/../../../template/class.tplnodeblockdef.php');
require_once(__DIR__.'/../../../template/class.tplnodevalue.php');
require_once(__DIR__.'/../../../template/class.tplnodevalueparent.php');
require_once(__DIR__.'/../../../template/class.tplnodetext.php');

class template extends atoum
{
	protected $fixturesDir;
	/**
	 * @dataProvider getTestTemplates
	 */
	public function testTemplate($file) {
		echo "being tested with : ".$file."\n";
        \tplNodeBlockDefinition::reset();
		$t = $this->parse($file);
		$dir = sys_get_temp_dir().'/tpl';
        $cachedir = sys_get_temp_dir().'/cbtpl';
        @mkdir ($dir);
        @mkdir ($cachedir);

        $basetpl="";
        foreach ($t['templates'] as $name=>$content) {
            $targetdir = $dir."/".dirname($name);
            $targetfile = basename($name);
            if (!is_dir($targetdir)) {
                @mkdir ($targetdir,0777,true);
            }
            if ($basetpl=='') {
                $basetpl=$targetfile;
            }
            file_put_contents($targetdir.'/'.$targetfile,$content);
        }
        $GLOBALS['tpl'] = new \template($cachedir,'$tpl');
        $GLOBALS['tpl']->use_cache=false;
        if (empty($t['path'])) {
            $GLOBALS['tpl']->setPath($dir);
        } else {
            $path=array();
            foreach ($t['path'] as $p) {
                $path[] = $dir.'/'.trim($p);
            }
            $GLOBALS['tpl']->setPath($path);
        }
        testTpls::register($GLOBALS['tpl']);
        $result = $GLOBALS['tpl']->getData($basetpl);
        $this
            ->string(rtrim($result))
            ->isEqualTo(rtrim($t['outputs'][0][1]));
        foreach ($t['templates'] as $name=>$content) {
            unlink($dir.'/'.$name);
        }
        unset($GLOBALS['tpl']);

	}


	public function parse($file) {
		$test = file_get_contents($file);
		if (preg_match('/
                --TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)\s*(?:--DATA--\s*(.*))?\s*--EXCEPTION--\s*(.*)/sx', $test, $match)) {
            $message = $match[1];
            $condition = $match[2];
            $templates = $this->parseTemplates($match[3]);
            $exception = $match[5];
            $outputs = array(array(null, $match[4], null, ''));
        } elseif (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)(?:--PATH--\s*(.*))?--EXPECT--.*/s', $test, $match)) {
            $message = $match[1];
            $condition = $match[2];
            $templates = $this->parseTemplates($match[3]);
            $path = isset($match[4])?explode(';',$match[4]):array();
            $exception = false;
            preg_match_all('/--EXPECT--\s*(.*?)$/s', $test, $outputs, PREG_SET_ORDER);
        } else {
            throw new \Exception(sprintf('Test "%s" is not valid.', str_replace($this->fixturesDir.'/', '', $file)));
        }

        return array(
            "name"      => str_replace($this->fixturesDir.'/', '', $file),
            "msg"       => $message,
            "condition" => $condition,
            "templates" => $templates,
            "exception" => $exception,
            "path"      => $path,
            "outputs"   => $outputs
        );
	}
    protected static function parseTemplates($test)
    {
        $templates = array();
        preg_match_all('/--TEMPLATE(?:\((.*?)\))?--\s*(.*?)(?=\-\-TEMPLATE|$)/s', $test, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $templates[($match[1] ? $match[1] : 'index.twig')] = $match[2];
        }

        return $templates;
    }

	public function getTestTemplates() {
        $this->fixturesDir = __DIR__.'/../fixtures/templates';
        $tests = array();

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->fixturesDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (preg_match('/\.test$/', $file)) {
                $tests[] = $file->getRealpath();
            }
        }
		return $tests;
	}

}


class testTpls
{
    public static function register($tpl) {
        $tpl->addValue("echo",array('tests\\unit\\testTpls','tplecho'));
        $tpl->addBlock("loop",array('tests\\unit\\testTpls','tplloop'));
    }

    public static function tplecho($attr,$str) {
        $ret = '';
        $txt=array();
        foreach ($attr as $k=>$v) {
            $txt[] = '"'.$k.'":"'.$v.'"';
        }
        if (!empty($txt)) {
            $ret .= '{'.join(',',$txt)."}";
        }
        if (empty($attr)) {
            $ret .= '['.$str.']';
        }
        return $ret;
    }

    public static function tplloop($attr,$content){
        $ret='';
        if (isset($attr['times'])) {
            $times = (integer)$attr['times'];
            for ($i=0;$i<$times; $i++) {
                $ret .= $content;
            }
            unset($attr['times']);
        }
        if (!empty($attr)) {
            $ret = self::tplecho($attr).$ret;
        }
        return $ret;
    }
}
