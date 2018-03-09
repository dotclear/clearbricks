<?php
/**
 * @class l10n
 * @brief Localization tools
 *
 * Localization utilities
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

/** @cond ONCE */
if (!function_exists('__')) {
/** @endcond */

    /**
     * Translated string
     *
     * @see l10n::trans()
     *
     * @param      string   $singular Singular form of the string
     * @param      string   $plural Plural form of the string (optionnal)
     * @param      integer  $count Context number for plural form (optionnal)
     * @return     string   translated string
     */
    function __($singular, $plural = null, $count = null)
    {
        return l10n::trans($singular, $plural, $count);
    }

/** @cond ONCE */
}
/** @endcond */

class l10n
{
    /// @name Languages properties
    //@{
    protected static $languages_definitions      = array();
    protected static $languages_name             = null;
    protected static $languages_textdirection    = null;
    protected static $languages_pluralsnumber    = null;
    protected static $languages_pluralexpression = null;
    //@}

    /// @name Current language properties
    //@{
    protected static $language_code             = null;
    protected static $language_name             = null;
    protected static $language_textdirection    = null;
    protected static $language_pluralsnumber    = null;
    protected static $language_pluralexpression = null;
    protected static $language_pluralfunction   = null;
    //@}

    /** @deprecated */
    public static $text_direction;

    /** @deprecated */
    protected static $langs = array();

    /**
     * L10N initialization
     *
     * Create global arrays for L10N stuff. Should be called before any work
     * with other methods. For plural-forms, __l10n values can now be array.
     *
     * @param string $code Language code to work with
     */
    public static function init($code = 'en')
    {
        $GLOBALS['__l10n'] = $GLOBALS['__l10n_files'] = array();

        self::lang($code);
    }

    /**
     * Set a language to work on or return current working language code
     *
     * This set up language properties to manage plurals form.
     * Change of language code not reset global array of L10N stuff.
     *
     * @param string $code Language code
     * @return string Current language code
     */
    public static function lang($code = null)
    {
        if ($code !== null && self::$language_code != $code && self::isCode($code)) {
            self::$language_code             = $code;
            self::$language_name             = self::getLanguageName($code);
            self::$language_textdirection    = self::getLanguageTextDirection($code);
            self::$language_pluralsnumber    = self::getLanguagePluralsNumber($code);
            self::$language_pluralexpression = self::getLanguagePluralExpression($code);

            self::$language_pluralfunction = self::createPluralFunction(
                self::$language_pluralsnumber,
                self::$language_pluralexpression
            );

            // Backwards compatibility
            self::$text_direction = self::$language_textdirection;
        }

        return self::$language_code;
    }

    /**
     * Translate a string
     *
     * Returns a translated string of $singular
     * or $plural according to a number if it is set.
     * If translation is not found, returns the string.
     *
     * @param string $singular Singular form of the string
     * @param string $plural Plural form of the string (optionnal)
     * @param integer $count Context number for plural form (optionnal)
     * @return string Translated string
     */
    public static function trans($singular, $plural = null, $count = null)
    {
        // If no string to translate, return no string
        if ($singular == '') {

            return '';

            // If no l10n translation loaded or exists
        } elseif ((!array_key_exists('__l10n', $GLOBALS) || empty($GLOBALS['__l10n'])
            || !array_key_exists($singular, $GLOBALS['__l10n'])) && is_null($count)) {

            return $singular;

            // If no $plural form or if current language has no plural form return $singular translation
        } elseif ($plural === null || $count === null || self::$language_pluralsnumber == 1) {
            $t = !empty($GLOBALS['__l10n'][$singular]) ? $GLOBALS['__l10n'][$singular] : $singular;

            return is_array($t) ? $t[0] : $t;

            // Else return translation according to $count
        } else {
            $i = self::index($count);

            // If it is a plural and translation exists in "singular" form
            if ($i > 0 && !empty($GLOBALS['__l10n'][$plural])) {
                $t = $GLOBALS['__l10n'][$plural];

                return is_array($t) ? $t[0] : $t;

                // If it is plural and index exists in plurals translations
            } elseif (!empty($GLOBALS['__l10n'][$singular])
                && is_array($GLOBALS['__l10n'][$singular])
                && array_key_exists($i, $GLOBALS['__l10n'][$singular])
                && !empty($GLOBALS['__l10n'][$singular][$i])) {

                return $GLOBALS['__l10n'][$singular][$i];

                // Else return input string according to "en" plural form
            } else {

                return $i > 0 ? $plural : $singular;
            }
        }
    }

    /**
     * Retrieve plural index from input number
     *
     * @param integer $count Number to take account
     * @return integer Index of plural form
     */
    public static function index($count)
    {
        return call_user_func(self::$language_pluralfunction, $count);
    }

    /**
     * Add a file
     *
     * Adds a l10n file in translation strings. $file should be given without
     * extension. This method will look for $file.lang.php and $file.po (in this
     * order) and retrieve the first one found.
     * We not care about language (and plurals forms) of the file.
     *
     * @param string    $file        Filename (without extension)
     * @return boolean True on success
     */
    public static function set($file)
    {
        $lang_file = $file . '.lang';
        $po_file   = $file . '.po';
        $php_file  = $file . '.lang.php';

        if (file_exists($php_file)) {
            require $php_file;

        } elseif (($tmp = self::getPoFile($po_file)) !== false) {
            $GLOBALS['__l10n_files'][] = $po_file;
            $GLOBALS['__l10n']         = $tmp + $GLOBALS['__l10n']; // "+" erase numeric keys unlike array_merge

        } elseif (($tmp = self::getLangFile($lang_file)) !== false) {
            $GLOBALS['__l10n_files'][] = $lang_file;
            $GLOBALS['__l10n']         = $tmp + $GLOBALS['__l10n']; // "+" erase numeric keys unlike array_merge

        } else {
            return false;
        }

        return true;
    }

    /**
     * L10N file
     *
     * Returns a file path for a file, a directory and a language.
     * If $dir/$lang/$file is not found, it will check if $dir/en/$file
     * exists and returns the result. Returns false if no file were found.
     *
     * @param string    $dir        Directory
     * @param string    $file    File
     * @param string    $lang    Language
     * @return string|false        File path or false
     */
    public static function getFilePath($dir, $file, $lang)
    {
        $f = $dir . '/' . $lang . '/' . $file;
        if (!file_exists($f)) {
            $f = $dir . '/en/' . $file;
        }

        return file_exists($f) ? $f : false;
    }

    /** @deprecated */
    public static function getLangFile($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        $fp = @fopen($file, 'r');
        if ($fp === false) {
            return false;
        }

        $res = array();
        while ($l = fgets($fp)) {
            $l = trim($l);
            # Comment
            if (substr($l, 0, 1) == '#') {
                continue;
            }

            # Original text
            if (substr($l, 0, 1) == ';' && ($t = fgets($fp)) !== false && trim($t) != '') {
                $res[substr($l, 1)] = trim($t);
            }
        }
        fclose($fp);

        return $res;
    }

    /// @name Gettext PO methods
    //@{
    /**
     * Load gettext file
     *
     * Returns an array of strings found in a given gettext (.po) file
     *
     * @param string    $file        Filename
     * @return array|false
     */
    public static function getPoFile($file)
    {
        if (($m = self::parsePoFile($file)) === false) {
            return false;
        }

        if (empty($m[1])) {
            return array();
        }

        // Keep singular id and translations, remove headers and comments
        $r = array();
        foreach ($m[1] as $v) {
            if (isset($v['msgid']) && isset($v['msgstr'])) {
                $r[$v['msgid']] = $v['msgstr'];
            }
        }

        return $r;
    }

    /**
     * Generates a PHP file from a po file
     *
     * Return a boolean depending on success or failure
     *
     * @param      string $file File
     * @param      string $license_block optional license block to add at the beginning
     * @return     boolean true on success
     */
    public static function generatePhpFileFromPo($file, $license_block = '')
    {
        $po_file  = $file . '.po';
        $php_file = $file . '.lang.php';

        $strings  = self::getPoFile($po_file);
        $fcontent =
            "<?php\n" .
            $license_block .
            "#\n#\n#\n" .
            "#        DOT NOT MODIFY THIS FILE !\n\n\n\n\n";

        foreach ($strings as $vo => $tr) {
            $vo = str_replace("'", "\\'", $vo);
            if (is_array($tr)) {
                foreach ($tr as $i => $t) {
                    $t = str_replace("'", "\\'", $t);
                    $fcontent .= '$GLOBALS[\'__l10n\'][\'' . $vo . '\'][' . $i . '] = \'' . $t . '\';' . "\n";
                }
            } else {
                $tr = str_replace("'", "\\'", $tr);
                $fcontent .= '$GLOBALS[\'__l10n\'][\'' . $vo . '\'] = \'' . $tr . '\';' . "\n";
            }
        }

        if (($fp = fopen($php_file, 'w')) !== false) {
            fwrite($fp, $fcontent, strlen($fcontent));
            fclose($fp);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parse Po File
     *
     * Return an array of po headers and translations from a po file
     *
     * @param string $file File path
     * @return array Parsed file
     */
    public static function parsePoFile($file)
    {
        // stop if file not exists
        if (!file_exists($file)) {
            return false;
        }

        // read file per line in array (without ending new line)
        if (false === ($lines = file($file, FILE_IGNORE_NEW_LINES))) {
            return false;
        }

        // prepare variables
        $headers = array(
            'Project-Id-Version'        => '',
            'Report-Msgid-Bugs-To'      => '',
            'POT-Creation-Date'         => '',
            'PO-Revision-Date'          => '',
            'Last-Translator'           => '',
            'Language-Team'             => '',
            'Content-Type'              => '',
            'Content-Transfer-Encoding' => '',
            'Plural-Forms'              => ''
            // there are more headers but these ones are default
        );
        $headers_searched = $headers_found = false;
        $h_line           = $h_val         = $h_key           = '';
        $entries          = $entry         = $desc            = array();
        $i                = 0;

        // read through lines
        for ($i = 0; $i < count($lines); $i++) {

            // some people like mirovinben add white space at the end of line
            $line = trim($lines[$i]);

            // jump to next line on blank one or empty comment (#)
            if (strlen($line) < 2) {
                continue;
            }

            // headers
            if (!$headers_searched && preg_match('/^msgid\s+""$/', trim($line))) {

                // headers start wih empty msgid and msgstr follow be multine
                if (!preg_match('/^msgstr\s+""$/', trim($lines[$i + 1]))
                    || !preg_match('/^"(.*)"$/', trim($lines[$i + 2]))) {
                    $headers_searched = true;
                } else {

                    $l = $i + 2;
                    while (false !== ($def = self::cleanPoLine('multi', $lines[$l++]))) {

                        $h_line = self::cleanPoString($def[1]);

                        // an header has key:val
                        if (false === ($h_index = strpos($h_line, ':'))) {

                            // multiline value
                            if (!empty($h_key) && !empty($headers[$h_key])) {
                                $headers[$h_key] = trim($headers[$h_key] . $h_line);
                                continue;

                                // your .po file is so bad
                            } else {
                                $headers_searched = true;
                                break;
                            }
                        }

                        // extract key and value
                        $h_key = substr($h_line, 0, $h_index);
                        $h_val = substr($h_line, $h_index + 1);

                        // unknow header
                        if (!isset($headers[$h_key])) {
                            //continue;
                        }

                        // ok it's an header, add it
                        $headers[$h_key] = trim($h_val);
                        $headers_found   = true;
                    }

                    // headers found so stop search and clean previous comments
                    if ($headers_found) {
                        $headers_searched = true;
                        $entry            = $desc            = array();
                        $i                = $l - 1;
                        continue;
                    }
                }
            }

            // comments
            if (false !== ($def = self::cleanPoLine('comment', $line))) {

                $str = self::cleanPoString($def[2]);

                switch ($def[1]) {

                    // translator comments
                    case ' ':
                        if (!isset($desc['translator-comments'])) {
                            $desc['translator-comments'] = $str;
                        } else {
                            $desc['translator-comments'] .= "\n" . $str;
                        }
                        break;

                    // extracted comments
                    case '.':
                        if (!isset($desc['extracted-comments'])) {
                            $desc['extracted-comments'] = $str;
                        } else {
                            $desc['extracted-comments'] .= "\n" . $str;
                        }
                        break;

                    // reference
                    case ':':
                        if (!isset($desc['references'])) {
                            $desc['references'] = array();
                        }
                        $desc['references'][] = $str;
                        break;

                    // flag
                    case ',':
                        if (!isset($desc['flags'])) {
                            $desc['flags'] = array();
                        }
                        $desc['flags'][] = $str;
                        break;

                    // previous msgid, msgctxt
                    case '|':
                        // msgid
                        if (strpos($def[2], 'msgid') === 0) {
                            $desc['previous-msgid'] = $str;
                            // msgcxt
                        } else {
                            $desc['previous-msgctxt'] = $str;
                        }
                        break;
                }
            }

            // msgid
            elseif (false !== ($def = self::cleanPoLine('msgid', $line))) {

                // add last translation and start new one
                if ((isset($entry['msgid']) || isset($entry['msgid_plural'])) && isset($entry['msgstr'])) {

                    // save last translation and start new one
                    $entries[] = $entry;
                    $entry     = array();

                    // add comments to new translation
                    if (!empty($desc)) {
                        $entry = array_merge($entry, $desc);
                        $desc  = array();
                    }

                    // stop searching headers
                    $headers_searched = true;
                }

                $str = self::cleanPoString($def[2]);

                // msgid_plural
                if (!empty($def[1])) {
                    $entry['msgid_plural'] = $str;
                } else {
                    $entry['msgid'] = $str;
                }
            }

            // msgstr
            elseif (false !== ($def = self::cleanPoLine('msgstr', $line))) {

                $str = self::cleanPoString($def[2]);

                // plural forms
                if (!empty($def[1])) {
                    if (!isset($entry['msgstr'])) {
                        $entry['msgstr'] = array();
                    }
                    $entry['msgstr'][] = $str;
                } else {
                    $entry['msgstr'] = $str;
                }
            }

            // multiline
            elseif (false !== ($def = self::cleanPoLine('multi', $line))) {

                $str = self::cleanPoString($def[1]);

                // msgid
                if (!isset($entry['msgstr'])) {
                    //msgid plural
                    if (isset($entry['msgid_plural'])) {
                        if (!is_array($entry['msgid_plural'])) {
                            $entry['msgid_plural'] .= $str;
                        } else {
                            $entry['msgid_plural'][count($entry['msgid_plural']) - 1] .= $str;
                        }
                    } else {
                        if (!is_array($entry['msgid'])) {
                            $entry['msgid'] .= $str;
                        } else {
                            $entry['msgid'][count($entry['msgid']) - 1] .= $str;
                        }
                    }

                    // msgstr
                } else {
                    if (!is_array($entry['msgstr'])) {
                        $entry['msgstr'] .= $str;
                    } else {
                        $entry['msgstr'][count($entry['msgstr']) - 1] .= $str;
                    }
                }
            }
        }

        // Add last translation
        if (!empty($entry)) {
            if (!empty($desc)) {
                $entry = array_merge($entry, $desc);
            }
            $entries[] = $entry;
        }

        return array($headers, $entries);
    }

    /* @ignore */
    protected static function cleanPoLine($type, $_)
    {
        $patterns = array(
            'msgid'   => 'msgid(_plural|)\s+"(.*)"',
            'msgstr'  => 'msgstr(\[.*?\]|)\s+"(.*)"',
            'multi'   => '"(.*)"',
            'comment' => '#\s*(\s|\.|:|\,|\|)\s*(.*)'
        );

        if (array_key_exists($type, $patterns)
            && preg_match('/^' . $patterns[$type] . '$/i', trim($_), $m)) {

            return $m;
        }

        return false;
    }

    /* @ignore */
    protected static function cleanPoString($_)
    {
        return stripslashes(str_replace(array('\n', '\r\n'), "\n", $_));
    }

    /**
     * Extract nplurals and plural from po expression
     *
     * @param string $expression Plural form as of gettext Plural-form param
     * @return array Number of plurals and cleaned plural expression
     */
    public static function parsePluralExpression($expression)
    {
        return preg_match('/^\s*nplurals\s*=\s*(\d+)\s*;\s+plural\s*=\s*(.+)$/', $expression, $m) ?
        array((integer) $m[1], trim(self::cleanPluralExpression($m[2]))) :
        array(self::$language_pluralsnumber, self::$language_pluralexpression);
    }

    /**
     * Create function to find plural msgstr index from gettext expression
     *
     * @param integer $nplurals Plurals number
     * @param string $expression Plural expression
     * @return function Function to extract right plural index
     */
    public static function createPluralFunction($nplurals, $expression)
    {
        return function ($n) use ($nplurals, $expression) {
            $i = eval('return (integer) (' . str_replace('n', $n, $expression) . ');');
            return ($i < $nplurals) ? $i : $nplurals - 1;
        };
    }

    /* @ignore */
    protected static function cleanPluralExpression($_)
    {
        $_ .= ';';
        $r = '';
        $l = 0;

        for ($i = 0; $i < strlen($_); ++$i) {
            switch ($_[$i]) {
                case '?':
                    $r .= ' ? (';
                    $l++;
                    break;

                case ':':
                    $r .= ') : (';
                    break;

                case ';':
                    $r .= str_repeat(')', $l) . ';';
                    $l = 0;
                    break;

                default:
                    $r .= $_[$i];
            }
        }

        return rtrim($r, ';');
    }
    //@}

    /// @name Languages definitions methods
    //@{
    /**
     * Check if a language code exists
     *
     * @param string $code Language code
     * @return boolean True if code exists
     */
    public static function isCode($code)
    {
        return array_key_exists($code, self::getLanguagesName());
    }

    /**
     * Get a language code according to a language name
     *
     * @param string $code Language name
     * @return string Language code
     */
    public static function getCode($code)
    {
        $_ = self::getLanguagesName();

        return (($index = array_search($code, $_)) !== false) ? $index : self::$language_code;
    }

    /**
     * ISO Codes
     *
     * @param boolean    $flip            Flip resulting array
     * @param boolean    $name_with_code    Prefix (code) to names
     * @return array
     */
    public static function getISOcodes($flip = false, $name_with_code = false)
    {
        $langs = self::getLanguagesName();
        if ($name_with_code) {
            foreach ($langs as $k => &$v) {
                $v = $k . ' - ' . $v;
            }
        }

        if ($flip) {
            return array_flip($langs);
        }

        return $langs;
    }

    /**
     * Get a language name according to a lang code
     *
     * @param string $code Language code
     * @return string Language name
     */
    public static function getLanguageName($code)
    {
        $_ = self::getLanguagesName();

        return array_key_exists($code, $_) ? $_[$code] : self::$language_name;
    }

    /**
     * Get languages names
     *
     * @return array List of languages names by languages codes
     */
    public static function getLanguagesName()
    {
        if (empty(self::$languages_name)) {
            self::$languages_name = self::getLanguagesDefinitions(3);

            // Backwards compatibility
            self::$langs = self::$languages_name;
        }

        return self::$languages_name;
    }

    /**
     * Get a text direction according to a language code
     *
     * @param string $code Language code
     * @return string Text direction (rtl or ltr)
     */
    public static function getLanguageTextDirection($code)
    {
        $_ = self::getLanguagesTextDirection();

        return array_key_exists($code, $_) ? $_[$code] : self::$language_textdirection;
    }

    /**
     * Get languages text directions
     *
     * @return array List of text directions by languages codes
     */
    public static function getLanguagesTextDirection()
    {
        if (empty(self::$languages_textdirection)) {
            self::$languages_textdirection = self::getLanguagesDefinitions(4);
        }

        return self::$languages_textdirection;
    }

    /**
     * Text direction
     *
     * @deprecated
     * @see l10n::getLanguageTextDirection()
     *
     * @param string    $lang    Language code
     * @return string ltr or rtl
     */
    public static function getTextDirection($lang)
    {
        return self::getLanguageTextDirection($lang);
    }

    /**
     * Get a number of plurals according to a language code
     *
     * @param string $code Language code
     * @return integer Number of plurals
     */
    public static function getLanguagePluralsNumber($code)
    {
        $_ = self::getLanguagesPluralsNumber();

        return !empty($_[$code]) ? $_[$code] : self::$language_pluralsnumber;
    }

    /**
     * Get languages numbers of plurals
     *
     * @return array List of numbers of plurals by languages codes
     */
    public static function getLanguagesPluralsNumber()
    {
        if (empty(self::$languages_pluralsnumber)) {
            self::$languages_pluralsnumber = self::getLanguagesDefinitions(5);
        }

        return self::$languages_pluralsnumber;
    }

    /**
     * Get a plural expression according to a language code
     *
     * @param string $code Language code
     * @return string Plural expression
     */
    public static function getLanguagePluralExpression($code)
    {
        $_ = self::getLanguagesPluralExpression();

        return !empty($_[$code]) ? $_[$code] : self::$language_pluralexpression;
    }

    /**
     * Get languages plural expressions
     *
     * @return array List of plural expressions by languages codes
     */
    public static function getLanguagesPluralExpression()
    {
        if (empty(self::$languages_pluralexpression)) {
            self::$languages_pluralexpression = self::getLanguagesDefinitions(6);
        }

        return self::$languages_pluralexpression;
    }

    /**
     * Get languages definitions of a given type
     *
     * The list follows ISO 639.1 norm with additionnal IETF codes as pt-br
     *
     * Countries codes and names from:
     * - http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     * - http://www.gnu.org/software/gettext/manual/gettext.html#Language-Codes
     * - http://www.loc.gov/standards/iso639-2/php/English_list.php
     *
     * Text direction from:
     * - http://translate.sourceforge.net/wiki/l10n/displaysettings
     * - http://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
     *
     * Plural-forms taken from:
     * - http://translate.sourceforge.net/wiki/l10n/pluralforms
     *
     * $languages_definitions types look like this:
     * 0 = code ISO 639.1 (2 digit) + IETF add
     * 1 = code ISO 639.2 (english 3 digit)
     * 2 = English name
     * 3 = natal name
     * 4 = text direction (ltr or rtl)
     * 5 = number of plurals (1 means no plural form)
     * 6 = plural expression (as of gettext .po plural form)
     *
     * null values represent missing values
     *
     * @param integer $type Type of definition
     * @param string $default Default value if definition is empty
     * @return array List of requested definition by languages codes
     */
    protected static function getLanguagesDefinitions($type, $default = '')
    {
        if ($type < 0 || $type > 6) {

            return array();
        }

        if (empty(self::$languages_definitions)) {
            self::$languages_definitions = array(
                array('aa', 'aar', 'Afar', 'Afaraf', 'ltr', null, null),
                array('ab', 'abk', 'Abkhazian', 'Аҧсуа', 'ltr', null, null),
                array('ae', 'ave', 'Avestan', 'Avesta', 'ltr', null, null),
                array('af', 'afr', 'Afrikaans', 'Afrikaans', 'ltr', 2, 'n != 1'),
                array('ak', 'aka', 'Akan', 'Akan', 'ltr', 2, 'n > 1)'),
                array('am', 'amh', 'Amharic', 'አማርኛ', 'ltr', 2, 'n > 1'),
                array('an', 'arg', 'Aragonese', 'Aragonés', 'ltr', 2, 'n != 1'),
                array('ar', 'ara', 'Arabic', '‫العربية', 'rtl', 6, 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5'),
                array('as', 'asm', 'Assamese', 'অসমীয়া', 'ltr', null, null),
                array('av', 'ava', 'Avaric', 'авар мацӀ', 'ltr', null, null),
                array('ay', 'aym', 'Aymara', 'Aymar aru', 'ltr', 1, '0'),
                array('az', 'aze', 'Azerbaijani', 'Azərbaycan dili', 'ltr', 2, 'n != 1'),

                array('ba', 'bak', 'Bashkir', 'башҡорт теле', 'ltr', null, null),
                array('be', 'bel', 'Belarusian', 'Беларуская', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
                array('bg', 'bul', 'Bulgarian', 'български език', 'ltr', 2, 'n != 1'),
                array('bh', 'bih', 'Bihari languages', 'भोजपुरी', 'ltr', null, null),
                array('bi', 'bis', 'Bislama', 'Bislama', 'ltr', null, null),
                array('bm', 'bam', 'Bambara', 'Bamanankan', 'ltr', null, null),
                array('bn', 'ben', 'Bengali', 'বাংলা', 'ltr', 2, 'n != 1'),
                array('bo', 'tib', 'Tibetan', 'བོད་ཡིག', 'ltr', 1, '0'),
                array('br', 'bre', 'Breton', 'Brezhoneg', 'ltr', 2, 'n > 1'),
                array('bs', 'bos', 'Bosnian', 'Bosanski jezik', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),

                array('ca', 'cat', 'Catalan', 'Català', 'ltr', 2, 'n != 1'),
                array('ce', 'che', 'Chechen', 'нохчийн мотт', 'ltr', null, null),
                array('ch', 'cha', 'Chamorro', 'Chamoru', 'ltr', 3, 'n==1 ? 0 : (n>=2 && n<=4) ? 1 : 2'),
                array('co', 'cos', 'Corsican', 'Corsu', 'ltr', null, null),
                array('cr', 'cre', 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ', 'ltr', null, null),
                array('cs', 'cze', 'Czech', 'Česky', 'ltr', null, null),
                array('cu', 'chu', 'Church Slavonic', 'ѩзыкъ Словѣньскъ', 'ltr', null, null),
                array('cv', 'chv', 'Chuvash', 'чӑваш чӗлхи', 'ltr', null, null),
                array('cy', 'wel', 'Welsh', 'Cymraeg', 'ltr', 4, 'n==1 ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3'),

                array('da', 'dan', 'Danish', 'Dansk', 'ltr', 2, 'n != 1'),
                array('de', 'ger', 'German', 'Deutsch', 'ltr', 2, 'n != 1'),
                array('dv', 'div', 'Maldivian', 'ދިވެހި', 'rtl', null, null),
                array('dz', 'dzo', 'Dzongkha', 'རྫོང་ཁ', 'ltr', 1, '0'),

                array('ee', 'ewe', 'Ewe', 'Ɛʋɛgbɛ', 'ltr', null, null),
                array('el', 'gre', 'Greek', 'Ελληνικά', 'ltr', 2, 'n != 1'),
                array('en', 'eng', 'English', 'English', 'ltr', 2, 'n != 1'),
                array('eo', 'epo', 'Esperanto', 'Esperanto', 'ltr', 2, 'n != 1'),
                array('es', 'spa', 'Spanish', 'español', 'ltr', 2, 'n != 1'),
                array('es-ar', null, 'Argentinean Spanish', 'Argentinean Spanish', 'ltr', 2, 'n != 1'),
                array('et', 'est', 'Estonian', 'Eesti keel', 'ltr', 2, 'n != 1'),
                array('eu', 'baq', 'Basque', 'Euskara', 'ltr', 2, 'n != 1'),

                array('fa', 'per', 'Persian', '‫فارسی', 'rtl', 1, '0'),
                array('ff', 'ful', 'Fulah', 'Fulfulde', 'ltr', 2, 'n != 1'),
                array('fi', 'fin', 'Finnish', 'Suomen kieli', 'ltr', 2, 'n != 1'),
                array('fj', 'fij', 'Fijian', 'Vosa Vakaviti', 'ltr', null, null),
                array('fo', 'fao', 'Faroese', 'Føroyskt', 'ltr', 2, 'n != 1'),
                array('fr', 'fre', 'French', 'Français', 'ltr', 2, 'n > 1'),
                array('fy', 'fry', 'Western Frisian', 'Frysk', 'ltr', 2, 'n != 1'),

                array('ga', 'gle', 'Irish', 'Gaeilge', 'ltr', 5, 'n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4'),
                array('gd', 'gla', 'Gaelic', 'Gàidhlig', 'ltr', 4, '(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3'),
                array('gl', 'glg', 'Galician', 'Galego', 'ltr', 2, 'n != 1'),
                array('gn', 'grn', 'Guarani', "Avañe'ẽ", 'ltr', null, null),
                array('gu', 'guj', 'Gujarati', 'ગુજરાતી', 'ltr', 2, 'n != 1'),
                array('gv', 'glv', 'Manx', 'Ghaelg', 'ltr', null, null),

                array('ha', 'hau', 'Hausa', '‫هَوُسَ', 'rtl', 2, 'n != 1'),
                array('he', 'heb', 'Hebrew', '‫עברית', 'rtl', 2, 'n != 1'),
                array('hi', 'hin', 'Hindi', 'हिन्दी', 'ltr', 2, 'n != 1'),
                array('ho', 'hmo', 'Hiri Motu', 'Hiri Motu', 'ltr', null, null),
                array('hr', 'hrv', 'Croatian', 'Hrvatski', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
                array('ht', 'hat', 'Haitian', 'Kreyòl ayisyen', 'ltr', null, null),
                array('hu', 'hun', 'Hungarian', 'Magyar', 'ltr', 2, 'n != 1'),
                array('hy', 'arm', 'Armenian', 'Հայերեն', 'ltr', 2, 'n != 1'),
                array('hz', 'her', 'Herero', 'Otjiherero', 'ltr', null, null),

                array('ia', 'ina', 'Interlingua', 'Interlingua', 'ltr', 2, 'n != 1'),
                array('id', 'ind', 'Indonesian', 'Bahasa Indonesia', 'ltr', 1, '0'),
                array('ie', 'ile', 'Interlingue', 'Interlingue', 'ltr', null, null),
                array('ig', 'ibo', 'Igbo', 'Igbo', 'ltr', null, null),
                array('ii', 'iii', 'Sichuan Yi', 'ꆇꉙ', 'ltr', null, null),
                array('ik', 'ipk', 'Inupiaq', 'Iñupiaq', 'ltr', null, null),
                array('io', 'ido', 'Ido', 'Ido', 'ltr', null, null),
                array('is', 'ice', 'Icelandic', 'Íslenska', 'ltr', 2, '(n%10!=1 || n%100==11) ? 1 : 0'),
                array('it', 'ita', 'Italian', 'Italiano', 'ltr', 2, 'n != 1'),
                array('iu', 'iku', 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ', 'ltr', null, null),

                array('ja', 'jpn', 'Japanese', '日本語', 'ltr', 1, '0'),
                array('jv', 'jav', 'Javanese', 'Basa Jawa', 'ltr', 2, 'n != 0'),

                array('ka', 'geo', 'Georgian', 'ქართული', 'ltr', 1, '0'),
                array('kg', 'kon', 'Kongo', 'KiKongo', 'ltr', null, null),
                array('ki', 'kik', 'Kikuyu', 'Gĩkũyũ', 'ltr', null, null),
                array('kj', 'kua', 'Kuanyama', 'Kuanyama', 'ltr', null, null),
                array('kk', 'kaz', 'Kazakh', 'Қазақ тілі', 'ltr', 1, '0'),
                array('kl', 'kal', 'Greenlandic', 'Kalaallisut', 'ltr', null, null),
                array('km', 'khm', 'Central Khmer', 'ភាសាខ្មែរ', 'ltr', 1, '0'),
                array('kn', 'kan', 'Kannada', 'ಕನ್ನಡ', 'ltr', 2, 'n != 1'),
                array('ko', 'kor', 'Korean', '한국어', 'ltr', 1, '0'),
                array('kr', 'kau', 'Kanuri', 'Kanuri', 'ltr', null, null),
                array('ks', 'kas', 'Kashmiri', 'कश्मीरी', 'rtl', null, null),
                array('ku', 'kur', 'Kurdish', 'Kurdî', 'ltr', 2, 'n!= 1'),
                array('kv', 'kom', 'Komi', 'коми кыв', 'ltr', null, null),
                array('kw', 'cor', 'Cornish', 'Kernewek', 'ltr', 4, 'n==1 ? 0 : (n==2) ? 1 : (n == 3) ? 2 : 3'),
                array('ky', 'kir', 'Kirghiz', 'кыргыз тили', 'ltr', 1, '0'),

                array('la', 'lat', 'Latin', 'Latine', 'ltr', null, null),
                array('lb', 'ltz', 'Luxembourgish', 'Lëtzebuergesch', 'ltr', 2, 'n != 1'),
                array('lg', 'lug', 'Ganda', 'Luganda', 'ltr', null, null),
                array('li', 'lim', 'Limburgan', 'Limburgs', 'ltr', null, null),
                array('ln', 'lin', 'Lingala', 'Lingála', 'ltr', 2, 'n>1'),
                array('lo', 'lao', 'Lao', 'ພາສາລາວ', 'ltr', 1, '0'),
                array('lt', 'lit', 'Lithuanian', 'Lietuvių kalba', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 or n%100>=20) ? 1 : 2'),
                array('lu', 'lub', 'Luba-Katanga', 'Luba-Katanga', 'ltr', null, null),
                array('lv', 'lav', 'Latvian', 'Latviešu valoda', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2'),

                array('mg', 'mlg', 'Malagasy', 'Malagasy fiteny', 'ltr', 2, 'n > 1'),
                array('mh', 'mah', 'Marshallese', 'Kajin M̧ajeļ', 'ltr', null, null),
                array('mi', 'mao', 'Maori', 'Te reo Māori', 'ltr', 2, 'n > 1'),
                array('mk', 'mac', 'Macedonian', 'македонски јазик', 'ltr', 2, 'n==1 || n%10==1 ? 0 : 1'),
                array('ml', 'mal', 'Malayalam', 'മലയാളം', 'ltr', 2, 'n != 1'),
                array('mn', 'mon', 'Mongolian', 'Монгол', 'ltr', 2, 'n != 1'),
                array('mo', null, 'Moldavian', 'Limba moldovenească', 'ltr', 3, 'n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2'), //cf: ro
                array('mr', 'mar', 'Marathi', 'मराठी', 'ltr', 2, 'n != 1'),
                array('ms', 'may', 'Malay', 'Bahasa Melayu', 'ltr', 1, '0'),
                array('mt', 'mlt', 'Maltese', 'Malti', 'ltr', 4, 'n==1 ? 0 : n==0 || ( n%100>1 && n%100<11) ? 1 : (n%100>10 && n%100<20 ) ? 2 : 3'),
                array('my', 'bur', 'Burmese', 'ဗမာစာ', 'ltr', 1, '0'),

                array('na', 'nau', 'Nauru', 'Ekakairũ Naoero', 'ltr', null, null),
                array('nb', 'nob', 'Norwegian Bokmål', 'Norsk bokmål', 'ltr', 2, 'n != 1'),
                array('nd', 'nde', 'North Ndebele', 'isiNdebele', 'ltr', null, null),
                array('ne', 'nep', 'Nepali', 'नेपाली', 'ltr', 2, 'n != 1'),
                array('ng', 'ndo', 'Ndonga', 'Owambo', 'ltr', null, null),
                array('nl', 'dut', 'Flemish', 'Nederlands', 'ltr', 2, 'n != 1'),
                array('nl-be', null, 'Flemish', 'Nederlands (Belgium)', 'ltr', 2, 'n != 1'),
                array('nn', 'nno', 'Norwegian Nynorsk', 'Norsk nynorsk', 'ltr', 2, 'n != 1'),
                array('no', 'nor', 'Norwegian', 'Norsk', 'ltr', 2, 'n != 1'),
                array('nr', 'nbl', 'South Ndebele', 'Ndébélé', 'ltr', null, null),
                array('nv', 'nav', 'Navajo', 'Diné bizaad', 'ltr', null, null),
                array('ny', 'nya', 'Chichewa', 'ChiCheŵa', 'ltr', null, null),

                array('oc', 'oci', 'Occitan', 'Occitan', 'ltr', 2, 'n > 1'),
                array('oj', 'oji', 'Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ', 'ltr', null, null),
                array('om', 'orm', 'Oromo', 'Afaan Oromoo', 'ltr', null, null),
                array('or', 'ori', 'Oriya', 'ଓଡ଼ିଆ', 'ltr', 2, 'n != 1'),
                array('os', 'oss', 'Ossetian', 'Ирон æвзаг', 'ltr', null, null),

                array('pa', 'pan', 'Panjabi', 'ਪੰਜਾਬੀ', 'ltr', 2, 'n != 1'),
                array('pi', 'pli', 'Pali', 'पाऴि', 'ltr', null, null),
                array('pl', 'pol', 'Polish', 'Polski', 'ltr', 3, 'n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
                array('ps', 'pus', 'Pushto', '‫پښتو', 'rtl', 2, 'n != 1'),
                array('pt', 'por', 'Portuguese', 'Português', 'ltr', 2, 'n != 1'),
                array('pt-br', null, 'Brazilian Portuguese', 'Português do Brasil', 'ltr', 2, 'n > 1'),

                array('qu', 'que', 'Quechua', 'Runa Simi', 'ltr', null, null),

                array('rm', 'roh', 'Romansh', 'Rumantsch grischun', 'ltr', 2, 'n != 1'),
                array('rn', 'run', 'Rundi', 'kiRundi', 'ltr', null, null),
                array('ro', 'rum', 'Romanian', 'Română', 'ltr', 3, 'n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2'),
                array('ru', 'rus', 'Russian', 'Русский', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
                array('rw', 'kin', 'Kinyarwanda', 'IKinyarwanda', 'ltr', 2, 'n != 1'),

                array('sa', 'san', 'Sanskrit', 'संस्कृतम्', 'ltr', null, null),
                array('sc', 'srd', 'Sardinian', 'sardu', 'ltr', null, null),
                array('sd', 'snd', 'Sindhi', 'सिन्धी', 'ltr', 2, 'n != 1'),
                array('se', 'sme', 'Northern Sami', 'Davvisámegiella', 'ltr', null, null),
                array('sg', 'sag', 'Sango', 'Yângâ tî sängö', 'ltr', null, null),
                array('sh', null, null, 'SrpskoHrvatski', 'ltr', null, null), //!
                array('si', 'sin', 'Sinhalese', 'සිංහල', 'ltr', 2, 'n != 1'),
                array('sk', 'slo', 'Slovak', 'Slovenčina', 'ltr', 3, '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'),
                array('sl', 'slv', 'Slovenian', 'Slovenščina', 'ltr', 4, 'n%100==1 ? 1 : n%100==2 ? 2 : n%100==3 || n%100==4 ? 3 : 0'),
                array('sm', 'smo', 'Samoan', "Gagana fa'a Samoa", 'ltr', null, null),
                array('sn', 'sna', 'Shona', 'chiShona', 'ltr', null, null),
                array('so', 'som', 'Somali', 'Soomaaliga', 'ltr', 2, 'n != 1'),
                array('sq', 'alb', 'Albanian', 'Shqip', 'ltr', 2, 'n != 1'),
                array('sr', 'srp', 'Serbian', 'српски језик', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
                array('ss', 'ssw', 'Swati', 'SiSwati', 'ltr', null, null),
                array('st', 'sot', 'Southern Sotho', 'seSotho', 'ltr', null, null),
                array('su', 'sun', 'Sundanese', 'Basa Sunda', 'ltr', 1, '0'),
                array('sv', 'swe', 'Swedish', 'Svenska', 'ltr', 2, 'n != 1'),
                array('sw', 'swa', 'Swahili', 'Kiswahili', 'ltr', 2, 'n != 1'),

                array('ta', 'tam', 'Tamil', 'தமிழ்', 'ltr', 2, 'n != 1'),
                array('te', 'tel', 'Telugu', 'తెలుగు', 'ltr', 2, 'n != 1'),
                array('tg', 'tgk', 'Tajik', 'тоҷикӣ', 'ltr', 2, 'n > 1'),
                array('th', 'tha', 'Thai', 'ไทย', 'ltr', 1, '0'),
                array('ti', 'tir', 'Tigrinya', 'ትግርኛ', 'ltr', 2, 'n > 1'),
                array('tk', 'tuk', 'Turkmen', 'Türkmen', 'ltr', 2, 'n != 1'),
                array('tl', 'tlg', 'Tagalog', 'Tagalog', 'ltr', null, null),
                array('tn', 'tsn', 'Tswana', 'seTswana', 'ltr', null, null),
                array('to', 'ton', 'Tonga', 'faka Tonga', 'ltr', null, null),
                array('tr', 'tur', 'Turkish', 'Türkçe', 'ltr', 2, 'n > 1'),
                array('ts', 'tso', 'Tsonga', 'xiTsonga', 'ltr', null, null),
                array('tt', 'tat', 'Tatar', 'татарча', 'ltr', 1, '0'),
                array('tw', 'twi', 'Twi', 'Twi', 'ltr', null, null),
                array('ty', 'tah', 'Tahitian', 'Reo Mā`ohi', 'ltr', null, null),

                array('ug', 'uig', 'Uighur', 'Uyƣurqə', 'ltr', 1, '0'),
                array('uk', 'ukr', 'Ukrainian', 'Українська', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
                array('ur', 'urd', 'Urdu', '‫اردو', 'rtl', 2, 'n != 1'),
                array('uz', 'uzb', 'Uzbek', "O'zbek", 'ltr', 2, 'n > 1'),

                array('ve', 'ven', 'Venda', 'tshiVenḓa', 'ltr', null, null),
                array('vi', 'vie', 'Vietnamese', 'Tiếng Việt', 'ltr', 1, '0'),
                array('vo', 'vol', 'Volapük', 'Volapük', 'ltr', null, null),

                array('wa', 'wln', 'Walloon', 'Walon', 'ltr', 2, 'n > 1'),
                array('wo', 'wol', 'Wolof', 'Wollof', 'ltr', 1, '0'),

                array('xh', 'xho', 'Xhosa', 'isiXhosa', 'ltr', null, null),

                array('yi', 'yid', 'Yiddish', '‫ייִדיש', 'rtl', null, null),
                array('yo', 'yor', 'Yoruba', 'Yorùbá', 'ltr', 2, 'n != 1'),

                array('za', 'zha', 'Chuang', 'Saɯ cueŋƅ', 'ltr', null, null),
                array('zh', 'zhi', 'Chinese', '中文', 'ltr', 1, '0'),
                array('zh-hk', null, 'Honk Kong Chinese', '中文 (香港)', 'ltr', 1, '0'),
                array('zh-tw', null, 'Taiwan Chinese', '中文 (臺灣)', 'ltr', 1, '0'),
                array('zu', 'zul', 'Zulu', 'isiZulu', 'ltr', null, null)
            );
        }

        $r = array();
        foreach (self::$languages_definitions as $_) {
            $r[$_[0]] = empty($_[$type]) ? $default : $_[$type];
        }

        return $r;
    }
    //@}
}
