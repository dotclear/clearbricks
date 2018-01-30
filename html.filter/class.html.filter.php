<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Clearbricks.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

/**
 * HTML code filter
 *
 * This class removes all unwanted tags and attributes from an HTML string.
 *
 * This was inspired by Ulf Harnhammar's Kses:
 * {@link http://sourceforge.net/projects/kses}
 *
 * @package Clearbricks
 * @subpackage HTML
 */
class htmlFilter
{
    private $parser;
    public $content;

    private $tag;

    /**
     * Constructor
     *
     * Creates a new instance of the class.
     */
    public function __construct($keep_aria = false, $keep_data = false, $keep_js = false)
    {
        $this->parser = xml_parser_create('UTF-8');
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'tag_open', 'tag_close');
        xml_set_character_data_handler($this->parser, 'cdata');
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);

        $this->removeTags(
            'applet',
            'base',
            'basefont',
            'body',
            'center',
            'dir',
            'font',
            'frame',
            'frameset',
            'head',
            'html',
            'isindex',
            'link',
            'menu',
            'menuitem',
            'meta',
            'noframes',
            'script',
            'noscript',
            'style'
        );

        // Remove aria-* and data-* attributes if necessary (tidy extension does it, not ready for HTML5)
        if (!$keep_aria) {
            $this->removePatternAttributes('^aria-[\-\w]+$');
            $this->removeAttributes('role');
        }
        if (!$keep_data) {
            $this->removePatternAttributes('^data-[\-\w].*$');
        }

        if (!$keep_js) {
            // Remove events attributes
            $this->removeArrayAttributes($this->event_attrs);
            // Remove inline JS in URI
            $this->removeHosts('javascript');
        }
    }

    /**
     * Append hosts
     *
     * Appends hosts to remove from URI. Each method argument is a host. Example:
     *
     * <code>
     * <?php
     * $filter = new htmlFilter();
     * $filter->removeHosts('javascript');
     * ?>
     * </code>
     */
    public function removeHosts()
    {
        foreach ($this->argsArray(func_get_args()) as $host) {
            $this->removed_hosts[] = $host;
        }
    }

    /**
     * Append tags
     *
     * Appends tags to remove. Each method argument is a tag. Example:
     *
     * <code>
     * <?php
     * $filter = new htmlFilter();
     * $filter->removeTags('frame','script');
     * ?>
     * </code>
     */
    public function removeTags()
    {
        foreach ($this->argsArray(func_get_args()) as $tag) {
            $this->removed_tags[] = $tag;
        }
    }

    /**
     * Append attributes
     *
     * Appends attributes to remove. Each method argument is an attribute. Example:
     *
     * <code>
     * <?php
     * $filter = new htmlFilter();
     * $filter->removeAttributes('onclick','onunload');
     * ?>
     * </code>
     */
    public function removeAttributes()
    {
        foreach ($this->argsArray(func_get_args()) as $a) {
            $this->removed_attrs[] = $a;
        }
    }

    /**
     * Append array of attributes
     *
     * Appends attributes to remove. Example:
     *
     * <code>
     * <?php
     * $filter = new htmlFilter();
     * $filter->removeAttributes(array('onload','onerror'));
     * ?>
     * </code>
     */
    public function removeArrayAttributes($attrs)
    {
        foreach ($attrs as $a) {
            $this->removed_attrs[] = $a;
        }
    }

    /**
     * Append attribute patterns
     *
     * Appends attribute patterns to remove. Each method argument is an attribute pattern. Example:
     *
     * <code>
     * <?php
     * $filter = new htmlFilter();
     * $filter->removeAttributes('data-.*');
     * ?>
     * </code>
     */
    public function removePatternAttributes()
    {
        foreach ($this->argsArray(func_get_args()) as $a) {
            $this->removed_pattern_attrs[] = $a;
        }
    }

    /**
     * Append attributes for tags
     *
     * Appends attributes to remove from specific tags. Each method argument is
     * an array of tags with attributes. Example:
     *
     * <code>
     * <?php
     * $filter = new htmlFilter();
     * $filter->removeTagAttributes(array('a' => array('src','title')));
     * ?>
     * </code>
     */
    public function removeTagAttributes($tag)
    {
        $args = $this->argsArray(func_get_args());
        array_shift($args);

        foreach ($args as $a) {
            $this->removed_tag_attrs[$tag][] = $a;
        }
    }

    /**
     * Known tags
     *
     * Creates a list of known tags.
     *
     * @param array        $t        Tags array
     */
    public function setTags($t)
    {
        if (is_array($t)) {
            $this->tags = $t;
        }
    }

    /**
     * Apply filter
     *
     * This method applies filter on given <var>$str</var> string. It will first
     * try to use tidy extension if exists and then apply the filter.
     *
     * @param string    $str        String to filter
     * @param boolean   $tidy       Use tidy extension if present
     *
     * @return string               Filtered string
     */
    public function apply($str, $tidy = true)
    {
        if ($tidy && extension_loaded('tidy') && class_exists('tidy')) {
            $config = array(
                'doctype'                     => 'strict',
                'drop-proprietary-attributes' => true,
                'drop-font-tags'              => true,
                'escape-cdata'                => true,
                'indent'                      => false,
                'join-classes'                => false,
                'join-styles'                 => true,
                'lower-literals'              => true,
                'output-xhtml'                => true,
                'show-body-only'              => true,
                'wrap'                        => 80
            );

            $str = '<p>tt</p>' . $str; // Fixes a big issue

            $tidy = new tidy;
            $tidy->parseString($str, $config, 'utf8');
            $tidy->cleanRepair();

            $str = (string) $tidy;

            $str = preg_replace('#^<p>tt</p>\s?#', '', $str);
        } else {
            $str = $this->miniTidy($str);
        }

        # Removing open comments, open CDATA and processing instructions
        $str = preg_replace('%<!--.*?-->%msu', '', $str);
        $str = str_replace('<!--', '', $str);
        $str = preg_replace('%<!\[CDATA\[.*?\]\]>%msu', '', $str);
        $str = str_replace('<![CDATA[', '', $str);

        # Transform processing instructions
        $str = str_replace('<?', '&gt;?', $str);
        $str = str_replace('?>', '?&lt;', $str);

        $str = html::decodeEntities($str, true);

        $this->content = '';
        xml_parse($this->parser, '<all>' . $str . '</all>');
        return $this->content;
    }

    private function miniTidy($str)
    {
        $str = preg_replace_callback('%(<(?!(\s*?/|!)).*?>)%msu', array($this, 'miniTidyFixTag'), $str);
        return $str;
    }

    private function miniTidyFixTag($m)
    {
        # Non quoted attributes
        return preg_replace_callback('%(=")(.*?)(")%msu', array($this, 'miniTidyFixAttr'), $m[1]);
    }

    private function miniTidyFixAttr($m)
    {
        # Escape entities in attributes value
        return $m[1] . html::escapeHTML(html::decodeEntities($m[2])) . $m[3];
    }

    private function argsArray($args)
    {
        $A = array();
        foreach ($args as $v) {
            if (is_array($v)) {
                $A = array_merge($A, $v);
            } else {
                $A[] = (string) $v;
            }
        }
        return array_unique($A);
    }

    private function tag_open(&$parser, $tag, $attrs)
    {
        $this->tag = strtolower($tag);

        if ($this->tag == 'all') {
            return;
        }

        if ($this->allowedTag($this->tag)) {
            $this->content .= '<' . $tag . $this->getAttrs($tag, $attrs);

            if (in_array($this->tag, $this->single_tags)) {
                $this->content .= ' />';
            } else {
                $this->content .= '>';
            }
        }
    }

    private function tag_close(&$parser, $tag)
    {
        if (!in_array($tag, $this->single_tags) && $this->allowedTag($tag)) {
            $this->content .= '</' . $tag . '>';
        }
    }

    private function cdata($parser, $cdata)
    {
        $this->content .= html::escapeHTML($cdata);
    }

    private function getAttrs($tag, $attrs)
    {
        $res = '';
        foreach ($attrs as $n => $v) {
            if ($this->allowedAttr($tag, $n)) {
                $res .= $this->getAttr($n, $v);
            }
        }
        return $res;
    }

    private function getAttr($attr, $value)
    {
        $value = preg_replace('/\xad+/', '', $value);

        if (in_array($attr, $this->uri_attrs)) {
            $value = $this->getURI($value);
        }

        return ' ' . $attr . '="' . html::escapeHTML($value) . '"';
    }

    private function getURI($uri)
    {
        // Trim URI
        $uri = trim($uri);
        // Remove escaped Unicode characters
        $uri = preg_replace('/\\\u[a-fA-F0-9]{4}/', '', $uri);
        // Sanitize and parse URL
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        $u   = @parse_url($uri);

        if (is_array($u) && (empty($u['scheme']) || in_array($u['scheme'], $this->allowed_schemes))) {
            if (empty($u['host']) || (!in_array($u['host'], $this->removed_hosts))) {
                return $uri;
            }
        }

        return '#';
    }

    private function allowedTag($tag)
    {
        return
        !in_array($tag, $this->removed_tags)
        && isset($this->tags[$tag]);
    }

    private function allowedAttr($tag, $attr)
    {
        if (in_array($attr, $this->removed_attrs)) {
            return false;
        }

        if (isset($this->removed_tag_attrs[$tag]) && in_array($attr, $this->removed_tag_attrs[$tag])) {
            return false;
        }

        if (!isset($this->tags[$tag]) ||
            (!in_array($attr, $this->tags[$tag]) && // Not in tag allowed attributes
                !in_array($attr, $this->gen_attrs) &&   // Not in allowed generic attributes
                !in_array($attr, $this->event_attrs) && // Not in allowed event attributes
                !$this->allowedPatternAttr($attr)))     // Not in allowed grep attributes
        {
            return false;
        }
        return true;
    }

    private function allowedPatternAttr($attr)
    {
        foreach ($this->removed_pattern_attrs as $pattern) {
            if (preg_match('/' . $pattern . '/u', $attr)) {
                return false;
            }
        }
        foreach ($this->grep_attrs as $pattern) {
            if (preg_match('/' . $pattern . '/u', $attr)) {
                return true;
            }
        }
        return false;
    }

    /* Tags and attributes definitions
     * Source: https://developer.mozilla.org/fr/docs/Web/HTML/
    ------------------------------------------------------- */
    private $removed_tags          = array();
    private $removed_attrs         = array();
    private $removed_pattern_attrs = array();
    private $removed_tag_attrs     = array();
    private $removed_hosts         = array();

    private $allowed_schemes = array(
        'data',
        'http',
        'https',
        'ftp',
        'mailto',
        'news'
    );

    // List of attributes which allow URL value
    private $uri_attrs = array(
        'action',
        'background',
        'cite',
        'classid',
        'code',
        'codebase',
        'data',
        'download',
        'formaction',
        'href',
        'longdesc',
        'profile',
        'src',
        'usemap'
    );

    // List of generic attributes
    private $gen_attrs = array(
        'accesskey',
        'class',
        'contenteditable',
        'contextmenu',
        'dir',
        'draggable',
        'dropzone',
        'hidden',
        'id',
        'itemid',
        'itemprop',
        'itemref',
        'itemscope',
        'itemtype',
        'lang',
        'role',
        'slot',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'translate',
        'xml:base',
        'xml:lang');

    // List of events attributes
    private $event_attrs = array(
        'onabort',
        'onafterprint',
        'onautocomplete',
        'onautocompleteerror',
        'onbeforeprint',
        'onbeforeunload',
        'onblur',
        'oncancel',
        'oncanplay',
        'oncanplaythrough',
        'onchange',
        'onclick',
        'onclose',
        'oncontextmenu',
        'oncuechange',
        'ondblclick',
        'ondrag',
        'ondragend',
        'ondragenter',
        'ondragexit',
        'ondragleave',
        'ondragover',
        'ondragstart',
        'ondrop',
        'ondurationchange',
        'onemptied',
        'onended',
        'onerror',
        'onfocus',
        'onhashchange',
        'oninput',
        'oninvalid',
        'onkeydown',
        'onkeypress',
        'onkeyup',
        'onlanguagechange',
        'onload',
        'onloadeddata',
        'onloadedmetadata',
        'onloadstart',
        'onmessage',
        'onmousedown',
        'onmouseenter',
        'onmouseleave',
        'onmousemove',
        'onmouseout',
        'onmouseover',
        'onmouseup',
        'onmousewheel',
        'onoffline',
        'ononline',
        'onpause',
        'onplay',
        'onplaying',
        'onpopstate',
        'onprogress',
        'onratechange',
        'onredo',
        'onreset',
        'onresize',
        'onscroll',
        'onseeked',
        'onseeking',
        'onselect',
        'onshow',
        'onsort',
        'onstalled',
        'onstorage',
        'onsubmit',
        'onsuspend',
        'ontimeupdate',
        'ontoggle',
        'onundo',
        'onunload',
        'onvolumechange',
        'onwaiting'
    );

    // List of pattern'ed attributes
    private $grep_attrs = array(
        '^aria-[\-\w]+$',
        '^data-[\-\w].*$'
    );

    // List of single tags
    private $single_tags = array(
        'area',
        'base',
        'basefont',
        'br',
        'col',
        'embed',
        'frame',
        'hr',
        'img',
        'input',
        'isindex',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    );

    private $tags = array(
        // A
        'a'          => array('charset', 'coords', 'download', 'href', 'hreflang', 'name', 'ping', 'referrerpolicy',
            'rel', 'rev', 'shape', 'target', 'type'),
        'abbr'       => array(),
        'acronym'    => array(),
        'address'    => array(),
        'applet'     => array('align', 'alt', 'archive', 'code', 'codebase', 'datafld', 'datasrc', 'height', 'hspace',
            'mayscript', 'name', 'object', 'vspace', 'width'),
        'area'       => array('alt', 'coords', 'download', 'href', 'name', 'media', 'nohref', 'referrerpolicy', 'rel',
            'shape', 'target', 'type'),
        'article'    => array(),
        'aside'      => array(),
        'audio'      => array('autoplay', 'buffered', 'controls', 'loop', 'muted', 'played', 'preload', 'src', 'volume'),
        // B
        'b'          => array(),
        'base'       => array('href', 'target'),
        'basefont'   => array('color', 'face', 'size'),
        'bdi'        => array(),
        'bdo'        => array(),
        'big'        => array(),
        'blockquote' => array('cite'),
        'body'       => array('alink', 'background', 'bgcolor', 'bottommargin', 'leftmargin', 'link', 'text', 'rightmargin',
            'text', 'topmargin', 'vlink'),
        'br'         => array('clear'),
        'button'     => array('autofocus', 'autocomplete', 'disabled', 'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget', 'name', 'type', 'value'),
        // C
        'canvas'     => array('height', 'width'),
        'caption'    => array('align'),
        'center'     => array(),
        'cite'       => array(),
        'code'       => array(),
        'col'        => array('align', 'bgcolor', 'char', 'charoff', 'span', 'valign', 'width'),
        'colgroup'   => array('align', 'bgcolor', 'char', 'charoff', 'span', 'valign', 'width'),
        // D
        'data'       => array('value'),
        'datalist'   => array(),
        'dd'         => array('nowrap'),
        'del'        => array('cite', 'datetime'),
        'details'    => array('open'),
        'dfn'        => array(),
        'dialog'     => array('open'),
        'dir'        => array('compact'),
        'div'        => array('align'),
        'dl'         => array(),
        'dt'         => array(),
        // E
        'em'         => array(),
        'embed'      => array('height', 'src', 'type', 'width'),
        // F
        'fieldset'   => array('disabled', 'form', 'name'),
        'figcaption' => array(),
        'figure'     => array(),
        'font'       => array('color', 'face', 'size'),
        'footer'     => array(),
        'form'       => array('accept', 'accept-charset', 'action', 'autocapitalize', 'autocomplete', 'enctype', 'method',
            'name', 'novalidate', 'target'),
        'frame'      => array('frameborder', 'marginheight', 'marginwidth', 'name', 'noresize', 'scrolling', 'src'),
        'frameset'   => array('cols', 'rows'),
        // G
        // H
        'h1'         => array('align'),
        'h2'         => array('align'),
        'h3'         => array('align'),
        'h4'         => array('align'),
        'h5'         => array('align'),
        'h6'         => array('align'),
        'head'       => array('profile'),
        'hr'         => array('align', 'color', 'noshade', 'size', 'width'),
        'html'       => array('manifest', 'version', 'xmlns'),
        // I
        'i'          => array(),
        'iframe'     => array('align', 'allowfullscreen', 'allowpaymentrequest', 'frameborder', 'height', 'longdesc',
            'marginheight', 'marginwidth', 'name', 'referrerpolicy', 'sandbox', 'scrolling', 'src', 'srcdoc', 'width'),
        'img'        => array('align', 'alt', 'border', 'crossorigin', 'decoding', 'height', 'hspace', 'ismap', 'longdesc',
            'name', 'referrerpolicy', 'sizes', 'src', 'srcset', 'usemap', 'vspace', 'width'),
        'input'      => array('accept', 'alt', 'autocomplete', 'autofocus', 'capture', 'checked', 'disabled', 'form',
            'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget', 'height', 'inputmode', 'ismap',
            'list', 'max', 'maxlength', 'min', 'minlength', 'multiple', 'name', 'pattern', 'placeholder', 'readonly',
            'required', 'selectionDirection', 'selectionEnd', 'selectionStart', 'size', 'spellcheck', 'src', 'step', 'type',
            'usemap', 'value', 'width'),
        'ins'        => array('cite', 'datetime'),
        'isindex'    => array('action', 'prompt'),
        // J
        // K
        'kbd'        => array(),
        'keygen'     => array('autofocus', 'challenge', 'disabled', 'form', 'keytype', 'name'),
        // L
        'label'      => array('for', 'form'),
        'legend'     => array(),
        'li'         => array('type', 'value'),
        'link'       => array('as', 'crossorigin', 'charset', 'disabled', 'href', 'hreflang', 'integrity', 'media', 'methods', 'prefetch', 'referrerpolicy', 'rel', 'rev', 'sizes', 'target', 'type'),
        // M
        'main'       => array(),
        'map'        => array('name'),
        'mark'       => array(),
        'menu'       => array('label', 'type'),
        'menuitem'   => array('checked', 'command', 'default', 'disabled', 'icon', 'label', 'radiogroup', 'type'),
        'meta'       => array('charset', 'content', 'http-equiv', 'name', 'scheme'),
        'meter'      => array('form', 'high', 'low', 'max', 'min', 'optimum', 'value'),
        // N
        'nav'        => array(),
        'noframes'   => array(),
        'noscript'   => array(),
        // O
        'object'     => array('archive', 'border', 'classid', 'codebase', 'codetype', 'data', 'declare', 'form', 'height',
            'hspace', 'name', 'standby', 'type', 'typemustmatch', 'usemap', 'width'),
        'ol'         => array('compact', 'reversed', 'start', 'type'),
        'optgroup'   => array('disabled', 'label'),
        'option'     => array('disabled', 'label', 'selected', 'value'),
        'output'     => array('for', 'form', 'name'),
        // P
        'p'          => array('align'),
        'param'      => array('name', 'type', 'value', 'valuetype'),
        'picture'    => array(),
        'pre'        => array('cols', 'width', 'wrap'),
        'progress'   => array('max', 'value'),
        // Q
        'q'          => array('cite'),
        // R
        'rp'         => array(),
        'rt'         => array(),
        'rtc'        => array(),
        'ruby'       => array(),
        // S
        's'          => array(),
        'samp'       => array(),
        'script'     => array('async', 'charset', 'crossorigin', 'defer', 'integrity', 'language', 'nomodule', 'nonce',
            'src', 'type'),
        'section'    => array(),
        'select'     => array('autofocus', 'disabled', 'form', 'multiple', 'name', 'required', 'size'),
        'small'      => array(),
        'source'     => array('media', 'sizes', 'src', 'srcset', 'type'),
        'span'       => array(),
        'strike'     => array(),
        'strong'     => array(),
        'style'      => array('media', 'nonce', 'scoped', 'type'),
        'sub'        => array(),
        'summary'    => array(),
        'sup'        => array(),
        // T
        'table'      => array('align', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'frame', 'rules', 'summary', 'width'),
        'tbody'      => array('align', 'bgcolor', 'char', 'charoff', 'valign'),
        'td'         => array('abbr', 'align', 'axis', 'bgcolor', 'char', 'charoff', 'colspan', 'headers', 'nowrap',
            'rowspan', 'scope', 'valign', 'width'),
        'template'   => array(),
        'textarea'   => array('autocomplete', 'autofocus', 'cols', 'disabled', 'form', 'maxlength', 'minlength', 'name',
            'placeholder', 'readonly', 'rows', 'spellcheck', 'wrap'),
        'tfoot'      => array('align', 'bgcolor', 'char', 'charoff', 'valign'),
        'th'         => array('abbr', 'align', 'axis', 'bgcolor', 'char', 'charoff', 'colspan', 'headers', 'nowrap',
            'rowspan', 'scope', 'valign', 'width'),
        'thead'      => array('align', 'bgcolor', 'char', 'charoff', 'valign'),
        'time'       => array('datetime'),
        'title'      => array(),
        'tr'         => array('align', 'bgcolor', 'char', 'charoff', 'valign'),
        'track'      => array('default', 'kind', 'label', 'src', 'srclang'),
        'tt'         => array(),
        // U
        'u'          => array(),
        'ul'         => array('compact', 'type'),
        // V
        'var'        => array(),
        'video'      => array('autoplay', 'buffered', 'controls', 'crossorigin', 'height', 'loop', 'muted', 'played',
            'playsinline', 'preload', 'poster', 'src', 'width'),
        // W
        'wbr'        => array()
        // X
        // Y
        // Z
    );
}
