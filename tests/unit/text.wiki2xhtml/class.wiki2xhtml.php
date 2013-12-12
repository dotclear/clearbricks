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

class wiki2xhtml extends atoum
{
  public function testTagTransform($tag, $delimiters) {
    $wiki2xhtml = new \wiki2xhtml();

    $faker = Faker\Factory::create();
    $phrase = $faker->text(20);

    $this
      ->string($wiki2xhtml->Transform(sprintf('%s%s%s', $delimiters[0], $phrase, $delimiters[1])))
      ->isIdenticalTo(sprintf('<p><%1$s>%2$s</%1$s></p>', $tag, $phrase));
  }

  public function testLinks() {
    $wiki2xhtml = new \wiki2xhtml();

    $faker = Faker\Factory::create();

    $lang = $faker->languageCode();
    $title = $faker->text(10);
    $label = $faker->text(20);
    $url = $faker->url();

    $this
      ->string($wiki2xhtml->transform(sprintf('[%s|%s]', $label, $url)))
      ->isIdenticalTo(sprintf('<p><a href="%1$s">%2$s</a></p>', $url, $label))

      ->string($wiki2xhtml->transform(sprintf('[%s|%s|%s]', $label, $url, $lang)))
      ->isIdenticalTo(sprintf('<p><a href="%s" hreflang="%s">%s</a></p>', $url, $lang, $label))

      ->string($wiki2xhtml->transform(sprintf('[%s|%s|%s|%s]', $label, $url, $lang, $title)))
      ->isIdenticalTo(sprintf('<p><a href="%s" hreflang="%s" title="%s">%s</a></p>', $url, $lang, $title, $label))
      ;
  }

  public function testImages() {
    $wiki2xhtml = new \wiki2xhtml();

    $faker = Faker\Factory::create();

    $title = $faker->text(10);
    $alt = $faker->text(20);
    $url = $faker->url();

    $this
      ->string($wiki2xhtml->transform(sprintf('((%s|%s))', $url, $alt)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" /></p>', $url, $alt))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s||%s))', $url, $alt, $title)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" title="%s" /></p>', $url, $alt, $title))

      ->string($wiki2xhtml->transform(sprintf('((%s|))', $url)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="" /></p>', $url))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|L))', $url, $alt)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" /></p>', $url, $alt))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|L|%s))', $url, $alt, $title)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" title="%s" /></p>', $url, $alt, $title))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|G))', $url, $alt)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" /></p>', $url, $alt))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|G|%s))', $url, $alt, $title)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:left; margin: 0 1em 1em 0;" title="%s" /></p>', $url, $alt, $title))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|D))', $url, $alt)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" /></p>', $url, $alt))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|D|%s))', $url, $alt, $title)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" title="%s" /></p>', $url, $alt, $title))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|R))', $url, $alt)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" /></p>', $url, $alt))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|R|%s))', $url, $alt, $title)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="float:right; margin: 0 0 1em 1em;" title="%s" /></p>', $url, $alt, $title))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|C))', $url, $alt)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="display:block; margin:0 auto;" /></p>', $url, $alt))

      ->string($wiki2xhtml->transform(sprintf('((%s|%s|C|%s))', $url, $alt, $title)))
      ->isIdenticalTo(sprintf('<p><img src="%s" alt="%s" style="display:block; margin:0 auto;" title="%s" /></p>', $url, $alt, $title))
      ;
  }

  public function testBlocks($in, $out, $count) {
    $wiki2xhtml = new \wiki2xhtml();

    $faker = Faker\Factory::create();

    $url = $faker->url();
    $word = $faker->word();
    $lang = $faker->languageCode();

    $search = array('%url%', '%lang%', '%word%');
    $replace = array($url, $lang, $word);

    $in = str_replace($search, $replace, $in);
    $out = str_replace($search, $replace, $out);

    /* echo "$in\n"; */
    /* echo "$out\n"; */

    if (strpos($in, '%s')!==false) {
      for ($n=1;$n<=$count;$n++) {
	$phrase[$n] = $faker->text(20);
      }

      $in = vsprintf($in, $phrase);
      $out = vsprintf($out, $phrase);
    }
    $this
      ->string($this->removeSpace($wiki2xhtml->transform($in)))
      ->isIdenticalTo($out);
  }

  public function testAutoBR() {
    $wiki2xhtml = new \wiki2xhtml();
    $faker = Faker\Factory::create();

    $text = $faker->paragraphs(3);

    $this
      ->string($wiki2xhtml->transform(implode("\n", $text)))
      ->isIdenticalTo('<p>'.implode("\n", $text).'</p>')

      ->if($wiki2xhtml->setOpt('active_auto_br',1))
      ->then()
	  ->string($wiki2xhtml->transform(implode("\n", $text)))
	  ->isIdenticalTo('<p>'.nl2br(implode("\n", $text)).'</p>')
	  ;
  }

  public function testMacro() {
    $wiki2xhtml = new \wiki2xhtml();

    $macro_name = 'php';

    $in_html = "///html\n<p>some text</p>\n<p><strong>un</strong> autre</p>\n///";
    $out_html = "<p>some text</p>\n<p><strong>un</strong> autre</p>\n";

    $in = "///dummy-macro\n<?php\necho 'Hello World!';\n?>\n///";
    $out_without_macro = "<pre>dummy-macro\n&lt;?php\necho 'Hello World!';\n?&gt;\n</pre>";
    $out = "[[<?php\necho 'Hello World!';\n?>\n]]";

    $this
      ->string($wiki2xhtml->transform($in_html))
      ->isIdenticalTo($out_html)

      ->string($wiki2xhtml->transform($in))
      ->isIdenticalTo($out_without_macro);

    $this
      ->if($wiki2xhtml->registerFunction('macro:dummy-macro', function($s){return "[[$s]]";}))
      ->object($wiki2xhtml->functions['macro:dummy-macro'])
	  ->isCallable()
	  ->string($wiki2xhtml->transform($in))
	  ->isIdenticalTo($out);
  }

  /*
   * DataProviders
   **/

  protected function testTagTransformDataProvider() {
    return array(
		 array('em', array("''","''")),
		 array('strong', array('__','__')),
		 array('abbr', array('??','??')),
		 array('q', array('{{','}}')),
		 array('code', array('@@','@@')),
		 array('ins', array('++','++')),
		 array('del', array('--','--')),
		 //		 array('word', array('¶¶¶','¶¶¶')),
		 );
  }

  protected function testBlocksDataProvider() {
    return array(
		 array('\[not a link | not a title label\]',
		       '<p>[not a link | not a title label]</p>',0),
		 array('``<strong>%s</strong>%s</p><ul><li>%s</li><li>%s</li></ul>``',
		       '<p><strong>%s</strong>%s</p><ul><li>%s</li><li>%s</li></ul></p>',4),
		 array("* item 1\n** item 1.1\n** item 1.2\n* item 2\n* item 3\n*# item 3.1",
		       '<ul><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>'.
		       '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1),

		 array('{{%s}}', '<p><q>%s</q></p>', 1),
		 array('{{%s|%lang%}}', '<p><q lang="%lang%">%s</q></p>', 1),
		 array('{{%s|%lang%|%url%}}', '<p><q lang="%lang%" cite="%url%">%s</q></p>', 1),

		 array(" %s\n %s\n %s", '<pre>%s%s%s</pre>', 3),
		 array('??%1$s|%2$s??', '<p><abbr title="%2$s">%1$s</abbr></p>', 2),
		 array(">%s\n>%s", '<blockquote><p>%s%s</p></blockquote>', 2),

		 array('----', '<hr />', 0),
		 array(' %s', '<pre>%s</pre>', 1),
		 array('!!!!%s', '<h2>%s</h2>', 1),
		 array('!!!%s', '<h3>%s</h3>', 1),
		 array('!!%s', '<h4>%s</h4>', 1),
		 array('!%s', '<h5>%s</h5>', 1),
		 array('~%word%~', '<p><a name="%word%"></a></p>', 1),

		 array('@@%s@@', '<p><code>%s</code></p>', 1),

		 array('%s$$%s$$', '<p>%s<sup>[<a href="#wiki-footnote-1" id="rev-wiki-footnote-1">1</a>]</sup></p>'.
		       '<div class="footnotes"><h4>Note</h4><p>[<a href="#rev-wiki-footnote-1" id="wiki-footnote-1">1</a>] '.
		       '%s</p></div>', 2),
     array('%s$$%s$$', '<p>%s<sup>[<a href="#wiki-footnote-1" id="rev-wiki-footnote-1">1</a>]</sup></p>'.
           '<div class="footnotes"><h4>Note</h4><p>[<a href="#rev-wiki-footnote-1" id="wiki-footnote-1">1</a>] '.
           '%s</p></div>', 2),
     array("* %s\n///\n%s\n///\n","<ul><li>%s</li></ul><pre>%s</pre>", 2),
     array("# %s\n///\n%s\n///\n","<ol><li>%s</li></ol><pre>%s</pre>", 2)

		 );
  }

  /*
  **/

  private function removeSpace($s) {
    return str_replace(array("\r\n","\n"), array('',''), $s);
  }
}
