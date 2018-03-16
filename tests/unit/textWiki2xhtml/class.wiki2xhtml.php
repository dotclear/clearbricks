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

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/text.wiki2xhtml/class.wiki2xhtml.php';

class wiki2xhtml extends atoum
{
    public function testHelp()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $this
            ->string($wiki2xhtml->help())
            ->isNotEmpty();
    }

    public function testAntispam()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $faker = Faker\Factory::create();
        $email = 'contact@dotclear.org';

        $this
            ->string($wiki2xhtml->transform('Email: [Email|mailto:' . $email . '].'))
            ->isIdenticalTo('<p>Email: <a href="mailto:%63%6f%6e%74%61%63%74%40%64%6f%74%63%6c%65%61%72%2e%6f%72%67">Email</a>.</p>');

        $wiki2xhtml->setOpt('active_antispam', 0);
        $this
            ->string($wiki2xhtml->transform('Email: [Email|mailto:' . $email . '].'))
            ->isIdenticalTo('<p>Email: <a href="mailto:' . $email . '">Email</a>.</p>');
    }

    public function testOpt()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $url = 'https://dotclear.org/';

        $wiki2xhtml->setOpt('first_title_level', 5);
        $this
            ->string($wiki2xhtml->transform('!!!H5'))
            ->isIdenticalTo('<h4>H5</h4>');

        $wiki2xhtml->setOpt('active_setext_title', 1);
        $this
            ->string($wiki2xhtml->transform('Title' . "\n" . '=====' . "\n" . 'Subtitle' . "\n" . '-----'))
            ->isIdenticalTo('<h4>Title</h4>' . "\n\n" . '<h5>Subtitle</h5>');

        $wiki2xhtml->setOpt('active_auto_urls', 1);
        $this
            ->string($wiki2xhtml->transform('URL: ' . $url))
            ->isIdenticalTo('<p>URL: <a href="' . $url . '" title="' . $url . '">' . $url . '</a></p>');

        $wiki2xhtml->setOpt('active_urls', 0);
        $this
            ->string($wiki2xhtml->transform('URL: ' . $url))
            ->isIdenticalTo('<p>URL: <a href="' . $url . '" title="' . $url . '">' . $url . '</a></p>');

        $wiki2xhtml->setOpt('active_hr', 0);
        $this
            ->string($wiki2xhtml->transform('----'))
            ->isIdenticalTo('<p><del></del></p>');

        $wiki2xhtml->setOpt('active_hr', 1);
        $this
            ->string($wiki2xhtml->transform('----'))
            ->isIdenticalTo('<hr />');

        $wiki2xhtml->setOpts(array(
            'active_urls'        => 0,
            'active_auto_urls'   => 0,
            'active_img'         => 0,
            'active_anchor'      => 0,
            'active_em'          => 0,
            'active_strong'      => 0,
            'active_q'           => 0,
            'active_code'        => 0,
            'active_acronym'     => 0,
            'active_ins'         => 0,
            'active_del'         => 0,
            'active_inline_html' => 0,
            'active_footnotes'   => 0,
            'active_wikiwords'   => 0,
            'active_mark'        => 0,
            'active_sup'         => 0,
            'active_empty'       => 0,
            'active_title'       => 0,
            'active_hr'          => 0,
            'active_quote'       => 0,
            'active_lists'       => 0,
            'active_defl'        => 0,
            'active_pre'         => 0,
            'active_aside'       => 0
        ));
        $wiki = <<<EOW

URL: https://dotclear.org/
((/public/image.jpg))

With an ~anchor~ here

Some __strong__ and ''em'' texts with {{citation}} and @@code@@ plus an ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and with some ``<span class="focus">focus</span>`` and a footnote\$\$Footnote content\$\$

Another ""mark""

!!!Top level title

!!Second level title

!Third level title

----

> Big quote
> on several lines

* List item 1
* List item 2

 Pre code
 Another code line

= term
: definition

) And finally an aside paragraph with a square^2 inside
)
) End

EOW;
        $html = <<<EOH
<p>URL: https://dotclear.org/
((/public/image.jpg))</p>


<p>With an ~anchor~ here</p>


<p>Some __strong__ and ''em'' texts with {{citation}} and @@code@@ plus an ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and with some ``&lt;span class="focus"&gt;focus&lt;/span&gt;`` and a footnote\$\$Footnote content\$\$</p>


<p>Another ""mark""</p>


<p>!!!Top level title</p>


<p>!!Second level title</p>


<p>!Third level title</p>


<p>----</p>


<p>&gt; Big quote
&gt; on several lines</p>


<p>* List item 1
* List item 2</p>


<p>Pre code
Another code line</p>


<p>= term
: definition</p>


<p>) And finally an aside paragraph with a square^2 inside
)
) End</p>
EOH;
        $this
            ->string($wiki2xhtml->transform($wiki))
            ->isIdenticalTo($html);
    }

    public function testOpts()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $wiki2xhtml->setOpts('fake');

        $wiki2xhtml->setOpts(array(
            'active_hr' => 0,
            'active_br' => 0));
        $this
            ->string($wiki2xhtml->transform('----' . "\n" . 'Line%%%'))
            ->isIdenticalTo('<p><del></del>' . "\n" . 'Line%%%</p>');

        $wiki2xhtml->setOpts(array(
            'active_hr' => 1,
            'active_br' => 1));
        $this
            ->string($wiki2xhtml->transform('----' . "\n" . 'Line%%%'))
            ->isIdenticalTo('<hr />' . "\n\n" . '<p>Line<br /></p>');
    }

    public function testTagTransform($tag, $delimiters)
    {
        $wiki2xhtml = new \wiki2xhtml();

        $faker  = Faker\Factory::create();
        $phrase = $faker->text(20);
        $url    = $faker->url();

        $this
            ->string($wiki2xhtml->transform(sprintf('Before %s%s%s After', $delimiters[0], $phrase, $delimiters[1])))
            ->isIdenticalTo(sprintf('<p>Before <%1$s>%2$s</%1$s> After</p>', $tag, $phrase));

        $this
            ->string($wiki2xhtml->transform(sprintf('%s%s%s', $delimiters[0], $phrase, $delimiters[1])))
            ->isIdenticalTo(sprintf('<p><%1$s>%2$s</%1$s></p>', $tag, $phrase));
    }

    public function testLinks()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $faker = Faker\Factory::create();

        $lang  = $faker->languageCode();
        $title = $faker->text(10);
        $label = $faker->text(20);
        $url   = $faker->url();

        $this
            ->string($wiki2xhtml->transform(sprintf('[%s|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">%2$s</a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[%s|%s|%s]', $label, $url, $lang)))
            ->isIdenticalTo(sprintf('<p><a href="%s" hreflang="%s">%s</a></p>', $url, $lang, $label))

            ->string($wiki2xhtml->transform(sprintf('[%s|%s|%s|%s]', $label, $url, $lang, $title)))
            ->isIdenticalTo(sprintf('<p><a href="%s" hreflang="%s" title="%s">%s</a></p>', $url, $lang, $title, $label))

            ->string($wiki2xhtml->transform(sprintf('[\'\'%s\'\'|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s"><em>%2$s</em></a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[\'\'%s\'\' (em first)|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s"><em>%2$s</em> (em first)</a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[(em last) \'\'%s\'\'|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">(em last) <em>%2$s</em></a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[(not first) \'\'%s\'\' (not last)|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">(not first) <em>%2$s</em> (not last)</a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[__%s__|%s]', $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s"><strong>%2$s</strong></a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[em: \'\'%s\'\' and strong: __%s__|%s]', $label, $label, $url)))
            ->isIdenticalTo(sprintf('<p><a href="%1$s">em: <em>%2$s</em> and strong: <strong>%2$s</strong></a></p>', $url, $label))

            ->string($wiki2xhtml->transform(sprintf('[%s|%s]', $label, 'javascript:alert(1);')))
            ->isIdenticalTo(sprintf('<p><a href="#">%s</a></p>', $label))
        ;
    }

    public function testImages()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $faker = Faker\Factory::create();

        $title  = $faker->text(10);
        $alt    = $faker->text(20);
        $url    = $faker->url();
        $legend = $faker->text(30);

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

            ->string($wiki2xhtml->transform(sprintf('((%s|%s|R|%s|%s))', $url, $alt, $title, $legend)))
            ->isIdenticalTo(sprintf('<p><figure style="float:right; margin: 0 0 1em 1em;"><img src="%s" alt="%s" title="%s" /><figcaption>%s</figcaption></figure></p>', $url, $alt, $title, $legend))

            ->string($wiki2xhtml->transform(sprintf('((%s|%s|G|%s|%s))', $url, $alt, $title, $legend)))
            ->isIdenticalTo(sprintf('<p><figure style="float:left; margin: 0 1em 1em 0;"><img src="%s" alt="%s" title="%s" /><figcaption>%s</figcaption></figure></p>', $url, $alt, $title, $legend))

            ->string($wiki2xhtml->transform(sprintf('((%s|%s|C|%s|%s))', $url, $alt, $title, $legend)))
            ->isIdenticalTo(sprintf('<p><figure style="display:block; margin:0 auto;"><img src="%s" alt="%s" title="%s" /><figcaption>%s</figcaption></figure></p>', $url, $alt, $title, $legend))
        ;
    }

    public function testBlocks($in, $out, $count)
    {
        $wiki2xhtml = new \wiki2xhtml();

        $faker = Faker\Factory::create();

        $url  = $faker->url();
        $word = $faker->word();
        $lang = $faker->languageCode();

        $search  = array('%url%', '%lang%', '%word%');
        $replace = array($url, $lang, $word);

        $in  = str_replace($search, $replace, $in);
        $out = str_replace($search, $replace, $out);

        if (strpos($in, '%s') !== false) {
            for ($n = 1; $n <= $count; $n++) {
                $phrase[$n] = $faker->text(20);
            }

            $in  = vsprintf($in, $phrase);
            $out = vsprintf($out, $phrase);
        }
        $this
            ->string($this->removeSpace($wiki2xhtml->transform($in)))
            ->isIdenticalTo($out);
    }

    public function testAutoBR()
    {
        $wiki2xhtml = new \wiki2xhtml();
        $faker      = Faker\Factory::create();

        $text = $faker->paragraphs(3);

        $this
            ->string($wiki2xhtml->transform(implode("\n", $text)))
            ->isIdenticalTo('<p>' . implode("\n", $text) . '</p>')

            ->if($wiki2xhtml->setOpt('active_auto_br', 1))
            ->then()
            ->string($wiki2xhtml->transform(implode("\n", $text)))
            ->isIdenticalTo('<p>' . nl2br(implode("\n", $text)) . '</p>')
        ;
    }

    public function testMacro()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $macro_name = 'php';

        $in_html  = "///html\n<p>some text</p>\n<p><strong>un</strong> autre</p>\n///";
        $out_html = "<p>some text</p>\n<p><strong>un</strong> autre</p>\n";

        $in                = "///dummy-macro\n<?php\necho 'Hello World!';\n?>\n///";
        $out_without_macro = "<pre>dummy-macro\n&lt;?php\necho 'Hello World!';\n?&gt;\n</pre>";
        $out               = "[[<?php\necho 'Hello World!';\n?>\n]]";

        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html)

            ->string($wiki2xhtml->transform($in))
            ->isIdenticalTo($out_without_macro);

        $this
            ->if($wiki2xhtml->registerFunction('macro:dummy-macro', function ($s) {return "[[$s]]";}))
            ->object($wiki2xhtml->functions['macro:dummy-macro'])
            ->isCallable()
            ->string($wiki2xhtml->transform($in))
            ->isIdenticalTo($out);
    }

    public function testAcronyms()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $in_html           = "Some __strong__ and ''em'' ??dc?? texts with {{citation}} and @@code@@ plus an ??cb?? ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and with some ``<span class=\"focus\">focus</span>`` on specific part";
        $out_html          = "<p>Some <strong>strong</strong> and <em>em</em> <abbr>dc</abbr> texts with <q>citation</q> and <code>code</code> plus an <abbr>cb</abbr> <abbr title=\"american company manufacturing everything\">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class=\"focus\">focus</span> on specific part</p>";
        $out_html_acronyms = "<p>Some <strong>strong</strong> and <em>em</em> <abbr title=\"dotclear\">dc</abbr> texts with <q>citation</q> and <code>code</code> plus an <abbr title=\"clearbicks\">cb</abbr> <abbr title=\"american company manufacturing everything\">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class=\"focus\">focus</span> on specific part</p>";

        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki2xhtml->setOpt('acronyms_file', __DIR__ . '/../fixtures/data/acronyms.txt');

        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html_acronyms);
    }

    public function testWikiWords()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $in_html           = "Some __strong__ and ''em'' texts with {{citation}} and @@code@@ plus an ??ACME|american company manufacturing everything?? where we can ++insert++ and --delete-- texts, and with some ``<span class=\"focus\">focus</span>`` on specific WikiWord part";
        $out_html          = "<p>Some <strong>strong</strong> and <em>em</em> texts with <q>citation</q> and <code>code</code> plus an <abbr title=\"american company manufacturing everything\">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class=\"focus\">focus</span> on specific WikiWord part</p>";
        $out_html_acronyms = "<p>Some <strong>strong</strong> and <em>em</em> texts with <q>citation</q> and <code>code</code> plus an <abbr title=\"american company manufacturing everything\">ACME</abbr> where we can <ins>insert</ins> and <del>delete</del> texts, and with some <span class=\"focus\">focus</span> on specific wikiword part</p>";

        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki2xhtml->setOpt('active_wikiwords', 1);
        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki2xhtml->registerFunction('wikiword', function ($str) {return strtolower($str);});
        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html_acronyms);
    }

    public function testSpecialURLs()
    {
        $wiki2xhtml = new \wiki2xhtml();

        $in_html          = "Test with an [Wiki first link|wiki:first_link] !";
        $out_html         = "<p>Test with an <a href=\"wiki:first_link\">Wiki first link</a>&nbsp;!</p>";
        $out_html_special = "<p>Test with an <a href=\"https://example.org/wiki/first_link\" title=\"Wiki\">Wiki first link</a>&nbsp;!</p>";

        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html);

        $wiki2xhtml->registerFunction('url:wiki', function ($url, $content) {
            return array('url' => 'https://example.org/wiki/' . substr($url, 5), 'content' => $content, 'title' => 'Wiki');
        });
        $this
            ->string($wiki2xhtml->transform($in_html))
            ->isIdenticalTo($out_html_special);
    }

    /*
     * DataProviders
     **/

    protected function testTagTransformDataProvider()
    {
        return array(
            array('em', array("''", "''")),
            array('strong', array('__', '__')),
            array('abbr', array('??', '??')),
            array('q', array('{{', '}}')),
            array('code', array('@@', '@@')),
            array('del', array('--', '--')),
            array('ins', array('++', '++')),
            array('mark', array('""', '""')),
            array('sup', array('^', '^'))
        );
    }

    protected function testBlocksDataProvider()
    {
        return array(
            array('\[not a link | not a title label\]',
                '<p>[not a link | not a title label]</p>', 0),
            array('``<strong>%s</strong>%s</p><ul><li>%s</li><li>%s</li></ul>``',
                '<p><strong>%s</strong>%s</p><ul><li>%s</li><li>%s</li></ul></p>', 4),
            array("* item 1\n** item 1.1\n** item 1.2\n* item 2\n* item 3\n*# item 3.1",
                '<ul><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li></ul>', 1),
            array("# item 1\n#* item 1.1\n#* item 1.2\n# item 2\n# item 3\n## item 3.1\n# item 4",
                '<ol><li>item 1<ul><li>item 1.1</li><li>item 1.2</li></ul></li>' .
                '<li>item 2</li><li>item 3<ol><li>item 3.1</li></ol></li><li>item 4</li></ol>', 1),

            array('{{%s}}', '<p><q>%s</q></p>', 1),
            array('{{%s|%lang%}}', '<p><q lang="%lang%">%s</q></p>', 1),
            array('{{%s|%lang%|%url%}}', '<p><q lang="%lang%" cite="%url%">%s</q></p>', 1),

            array(" %s\n %s\n %s", '<pre>%s%s%s</pre>', 3),
            array('??%1$s|%2$s??', '<p><abbr title="%2$s">%1$s</abbr></p>', 2),
            array(">%s\n>%s", '<blockquote><p>%s%s</p></blockquote>', 2),

            array('----', '<hr />', 0),
            array(' %s', '<pre>%s</pre>', 1),
            array(') %s', '<aside><p>%s</p></aside>', 1),
            array(") %s\n)\n) %s", '<aside><p>%s</p><p>%s</p></aside>', 2),
            array('!!!!%s', '<h2>%s</h2>', 1),
            array('!!!%s', '<h3>%s</h3>', 1),
            array('!!%s', '<h4>%s</h4>', 1),
            array('!%s', '<h5>%s</h5>', 1),
            array('~%word%~', '<p><a id="%word%"></a></p>', 1),

            array('@@%s@@', '<p><code>%s</code></p>', 1),

            array('%s$$%s$$', '<p>%s<sup>[<a href="#wiki-footnote-1" id="rev-wiki-footnote-1">1</a>]</sup></p>' .
                '<div class="footnotes"><h4>Note</h4><p>[<a href="#rev-wiki-footnote-1" id="wiki-footnote-1">1</a>] ' .
                '%s</p></div>', 2),
            array('%s$$%s$$', '<p>%s<sup>[<a href="#wiki-footnote-1" id="rev-wiki-footnote-1">1</a>]</sup></p>' .
                '<div class="footnotes"><h4>Note</h4><p>[<a href="#rev-wiki-footnote-1" id="wiki-footnote-1">1</a>] ' .
                '%s</p></div>', 2),

            array("* %s\n///\n%s\n///\n", "<ul><li>%s</li></ul><pre>%s</pre>", 2),
            array("# %s\n///\n%s\n///\n", "<ol><li>%s</li></ol><pre>%s</pre>", 2),

            array("= term", "<dl><dt>term</dt></dl>", 0),
            array(": definition", "<dl><dd>definition</dd></dl>", 0),
            array("= %s", "<dl><dt>%s</dt></dl>", 1),
            array(": %s", "<dl><dd>%s</dd></dl>", 1),
            array("= %s\n: %s", "<dl><dt>%s</dt><dd>%s</dd></dl>", 2),
            array("= %s\n= %s\n: %s\n: %s", "<dl><dt>%s</dt><dt>%s</dt><dd>%s</dd><dd>%s</dd></dl>", 4)
        );
    }

    /*
     **/

    private function removeSpace($s)
    {
        return str_replace(array("\r\n", "\n"), array('', ''), $s);
    }
}
