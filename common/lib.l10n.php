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
* Localization tools
*
* @package Clearbricks
* @subpackage Common
*/

if (!function_exists('__')) {

	/**
	 * Translated string
	 *
	 * @see l10n::trans()
	 *
	 * @param string $singular Singular form of the string
	 * @param string $pural Plural form of the string (optionnal)
	 * @param integer $count Context number for plural form (optionnal)
	 * @return string translated string
	 */
	function __($singular, $plural=null, $count=null)
	{
		return l10n::trans($singular, $plural, $count);
	}
}

/**
* Localization utilities
*/
class l10n
{
	/// @name Languages properties
	//@{
	protected static $languages_definitions         = array();
	protected static $languages_name                = null;
	protected static $languages_textdirection       = null;
	protected static $languages_pluralsnumber       = null;
	protected static $languages_pluralexpression    = null;
	//@}

	/// @name Current language properties
	//@{
	protected static $language_code                 = null;
	protected static $language_name                 = null;
	protected static $language_textdirection        = null;
	protected static $language_pluralsnumber        = null;
	protected static $language_pluralexpression     = null;
	protected static $language_pluralfunction       = null;
	//@}

	/** @ignore @deprecated */
	public static $text_direction;

	/** @ignore @deprecated */
	protected static $langs = array();

	/**
	 * L10N initialization
	 *
	 * Create global arrays for L10N stuff. Should be called before any work
	 * with other methods. For plural-forms, __l10n values can now be array.
	 *
	 * @param string $code Language code to work with
	 */
	public static function init($code='en')
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
	public static function lang($code=null)
	{
		if ($code !== null && self::$language_code != $code && self::isCode($code)) {
			self::$language_code                = $code;
			self::$language_name                = self::getLanguageName($code);
			self::$language_textdirection       = self::getLanguageTextDirection($code);
			self::$language_pluralsnumber       = self::getLanguagePluralsNumber($code);
			self::$language_pluralexpression    = self::getLanguagePluralExpression($code);

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
	public static function trans($singular, $plural=null, $count=null)
	{
		// If no $plural form or if current language has no plural form return $singular translation
		if ($plural === null || $count === null || self::$language_pluralsnumber == 1) {
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
			&& array_key_exists($i,$GLOBALS['__l10n'][$singular]) 
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
	 * @param string	$file		Filename (without extension)
	 * @return boolean True on success
	 */
	public static function set($file)
	{
		$lang_file = $file.'.lang';
		$po_file = $file.'.po';
		$php_file = $file.'.lang.php';

		if (file_exists($php_file)) {
			require $php_file;

		} elseif (($tmp = self::getPoFile($po_file)) !== false) {
			$GLOBALS['__l10n_files'][] = $po_file;
			$GLOBALS['__l10n'] = array_merge($GLOBALS['__l10n'],$tmp);

		} elseif (($tmp = self::getLangFile($lang_file)) !== false) {
			$GLOBALS['__l10n_files'][] = $lang_file;
			$GLOBALS['__l10n'] = array_merge($GLOBALS['__l10n'],$tmp);

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
	 * @param string	$dir		Directory
	 * @param string	$file	File
	 * @param string	$lang	Language
	 * @return string|false		File path or false
	 */
	public static function getFilePath($dir, $file, $lang)
	{
		$f = $dir.'/'.$lang.'/'.$file;
		if (!file_exists($f)) {
			$f = $dir.'/en/'.$file;
		}

		return file_exists($f) ? $f : false;
	}

	/** @ignore @deprecated */
	public static function getLangFile($file)
	{
		if (!file_exists($file)) {
			return false;
		}

		$fp = @fopen($file,'r');
		if ($fp === false) {
			return false;
		}

		$res = array();
		while ($l = fgets($fp))
		{
			$l = trim($l);
			# Comment
			if (substr($l,0,1) == '#') {
				continue;
			}

			# Original text
			if (substr($l,0,1) == ';' && ($t = fgets($fp)) !== false && trim($t) != '') {
				$res[$l] = trim($t);
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
	 * @param string	$file		Filename
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
		foreach($m[1] as $v) {
			$r[$v['msgid']] = $v['msgstr'];
		}

		return $r;
	}

	/**
	* Parse Po File
	*
	* @param string $file File path
	* @return array Parsed file
	*/
	public static function parsePoFile($file)
	{
		if (!file_exists($file)) {
			return false;
		}

		if (false === ($lines = file($file, FILE_IGNORE_NEW_LINES))) {
			return false;
		}

		// parsing headers; stop at the first empty line
		$headers = array(
			'Project-Id-Version'            => '',
			'Report-Msgid-Bugs-To'          => '',
			'POT-Creation-Date'             => '',
			'PO-Revision-Date'              => '',
			'Last-Translator'               => '',
			'Language-Team'                 => '',
			'Content-Type'                  => '',
			'Content-Transfer-Encoding'     => '',
			'Plural-Forms'                  => ''
		);

		// po file MUST start with empty msgid and msgstr with headers
		$i = 2;
		while ($line = $lines[$i++]) {
			$line = self::cleanPoString($line);
			$colon_index = strpos($line, ':');
			if ($colon_index === false) {
				continue;
			}
			
			$header_name = substr($line, 0, $colon_index);
			if (!isset($headers[$header_name])) {
				continue;
			}
			
			// clean white space and end line
			$headers[$header_name] = substr($line, $colon_index + 1, -2);
		}

		$entries = $entry = array();
		for ($n = count($lines); $i < $n; $i++) {
			$line = $lines[$i];
			if ($line === '') {
				$entries[] = $entry;
				$entry = array();
				continue;
			}
			
			// comments
			if ($line[0] == '#') {
				$comment = trim(substr($line, 2));
				switch ($line[1]) {

					// translator comments
					case ' ':
						if (!isset($entry['translator-comments'])) {
							$entry['translator-comments'] = $comment;
						} else {
							$entry['translator-comments'] .= "\n" . $comment;
						}
						break;

					// extracted comments
					case '.':
						if (!isset($entry['extracted-comments'])) {
							$entry['extracted-comments'] = $comment;
						} else {
							$entry['extracted-comments'] .= "\n" . $comment;
						}
						break;

					// reference
					case ':':
						if (!isset($entry['references'])) {
							$entry['references'] = array();
						}
						$entry['references'][] = $comment;
						break;

					// flag
					case ',':
						if (!isset($entry['flags'])) {
							$entry['flags'] = array();
						}
						$entry['flags'][] = $comment;
						break;

					// previous msgid, msgctxt
					case '|':
						// msgid
						if ($comment[4] == 'd') {
							$entry['previous-msgid'] = self::cleanPoString(substr($comment, 6));
						// msgcxt
						} else {
							$entry['previous-msgctxt'] = self::cleanPoString(substr($comment, 8));
						}
						break;
				}
			// msgid
			} elseif (substr($line, 0, 5) == 'msgid') {
				// msgid_plural
				if ($line[5] == "_") {
					$entry['msgid_plural'] = self::cleanPoString(substr($line, 13));
				} else {
					$entry['msgid'] = self::cleanPoString(substr($line, 6));
				}
			// msgstr
			} elseif (strpos($line, 'msgstr') === 0) {
				// no plural forms
				if ($line[6] === ' ') {
				$entry['msgstr'] = self::cleanPoString(substr($line, 7));
				// plural forms
				} else {
					if (!isset($entry['msgstr'])) {
						$entry['msgstr'] = array();
					}
					$entry['msgstr'][] = self::cleanPoString(substr($line, strpos($line, ' ') + 1));
				}
			// multiline
			} elseif ($line[0] === '"' && isset($entry['msgstr'])) {
				$line = "\n" . preg_replace('/([^\\\\])\\\\n$/', "\$1\n", self::cleanPoString($line));
				if (!is_array($entry['msgstr'])) {
					$entry['msgstr'] .= $line;
				} else {
					$entry['msgstr'][count($entry['msgstr']) - 1] .= $line;
				}
			}
		}

		return array($headers, $entries);
	}

	/* @ignore */
	protected static function cleanPoString($_)
	{
		return substr(trim($_), 1, -1);
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
		return create_function('$n', '$i = (integer) ('.str_replace('n', '$n', $expression).'); return ($i < '.$nplurals.') ? $i : '.$nplurals.' - 1;');
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
					$l= 0;
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
		return array_key_exists($code,self::getLanguagesName());
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

		return array_key_exists($code,$_) ? $code : self::$language_code;
	}

	/**
	 * ISO Codes
	 *
	 * @param boolean	$flip			Flip resulting array
	 * @param boolean	$name_with_code	Prefix (code) to names
	 * @return array
	 */
	public static function getISOcodes($flip=false,$name_with_code=false)
	{
		$langs = self::getLanguagesName();
		if ($name_with_code) {
			foreach ($langs as $k => &$v) {
				$v = $k.' - '.$v;
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

		return array_key_exists($code,$_) ? $_[$code] : self::$language_name;
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

		return array_key_exists($code,$_) ? $_[$code] : self::$language_textdirection;
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
	 * @param string	$lang	Language code
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

		return array_key_exists($code,$_) ? $_[$code] : self::$language_pluralsnumber;
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

		return array_key_exists($code,$_) ? $_[$code] : self::$language_pluralexpression;
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
	 * Countries codes and names from
	 * {@link http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes}
	 * {@link http://www.gnu.org/software/gettext/manual/gettext.html#Language-Codes}
	 * {@link http://www.loc.gov/standards/iso639-2/php/English_list.php}
	 *
	 * Text direction from
	 * {@link http://translate.sourceforge.net/wiki/l10n/displaysettings}
	 * {@link http://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code}
	 *
	 * Plural-forms taken from
	 * {@link http://translate.sourceforge.net/wiki/l10n/pluralforms}
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
	protected static function getLanguagesDefinitions($type, $default='')
	{
		if ($type < 0 || $type > 6) {

			return array();
		}

		if (empty(self::$languages_definitions)) {
			self::$languages_definitions =  array(
				array('aa', 'aar', 'Afar', 'Afaraf', 'ltr', null, null),
				array('ab', 'abk', 'Abkhazian', 'ÐÒ§ÑÑƒÐ°', 'ltr', null, null),
				array('ae', 'ave', 'Avestan', 'Avesta', 'ltr', null, null),
				array('af', 'afr', 'Afrikaans', 'Afrikaans', 'ltr', 2, 'n != 1'),
				array('ak', 'aka', 'Akan', 'Akan', 'ltr', 2, 'n > 1)'),
				array('am', 'amh', 'Amharic', 'áŠ áˆ›áˆ­áŠ›', 'ltr', 2, 'n > 1'),
				array('an', 'arg', 'Aragonese', 'AragonÃ©s', 'ltr', 2, 'n != 1'),
				array('ar', 'ara', 'Arabic', 'â€«Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©', 'rtl', 6, 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5'),
				array('as', 'asm', 'Assamese', 'à¦…à¦¸à¦®à§€à¦¯à¦¼à¦¾', 'ltr', null, null),
				array('av', 'ava', 'Avaric', 'Ð°Ð²Ð°Ñ€ Ð¼Ð°Ñ†Ó€', 'ltr', null, null),
				array('ay', 'aym', 'Aymara', 'Aymar aru', 'ltr', 1, '0'),
				array('az', 'aze', 'Azerbaijani', 'AzÉ™rbaycan dili', 'ltr', 2, 'n != 1'),

				array('ba', 'bak', 'Bashkir', 'Ð±Ð°ÑˆÒ¡Ð¾Ñ€Ñ‚ Ñ‚ÐµÐ»Ðµ', 'ltr', null, null),
				array('be', 'bel', 'Belarusian', 'Ð‘ÐµÐ»Ð°Ñ€ÑƒÑÐºÐ°Ñ', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
				array('bg', 'bul', 'Bulgarian', 'Ð±ÑŠÐ»Ð³Ð°Ñ€ÑÐºÐ¸ ÐµÐ·Ð¸Ðº', 'ltr', 2, 'n != 1'),
				array('bh', 'bih', 'Bihari languages', 'à¤­à¥‹à¤œà¤ªà¥à¤°à¥€', 'ltr', null, null),
				array('bi', 'bis', 'Bislama', 'Bislama', 'ltr', null, null),
				array('bm', 'bam', 'Bambara', 'Bamanankan', 'ltr', null, null),
				array('bn', 'ben', 'Bengali', 'à¦¬à¦¾à¦‚à¦²à¦¾', 'ltr', 2, 'n != 1'),
				array('bo', 'tib', 'Tibetan', 'à½–à½¼à½‘à¼‹à½¡à½²à½‚', 'ltr', 1, '0'),
				array('br', 'bre', 'Breton', 'Brezhoneg', 'ltr', 2, 'n > 1'),
				array('bs', 'bos', 'Bosnian', 'Bosanski jezik', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),

				array('ca', 'cat', 'Catalan', 'CatalÃ ', 'ltr', 2, 'n != 1'),
				array('ce', 'che', 'Chechen', 'Ð½Ð¾Ñ…Ñ‡Ð¸Ð¹Ð½ Ð¼Ð¾Ñ‚Ñ‚', 'ltr', null, null),
				array('ch', 'cha', 'Chamorro', 'Chamoru', 'ltr', 3, 'n==1 ? 0 : (n>=2 && n<=4) ? 1 : 2'),
				array('co', 'cos', 'Corsican', 'Corsu', 'ltr', null, null),
				array('cr', 'cre', 'Cree', 'á“€á¦áƒá”­ááá£', 'ltr', null, null),
				array('cs', 'cze', 'Czech', 'ÄŒesky', 'ltr', null, null),
				array('cu', 'chu', 'Church Slavonic', 'Ñ©Ð·Ñ‹ÐºÑŠ Ð¡Ð»Ð¾Ð²Ñ£Ð½ÑŒÑÐºÑŠ', 'ltr', null, null),
				array('cv', 'chv', 'Chuvash', 'Ñ‡Ó‘Ð²Ð°Ñˆ Ñ‡Ó—Ð»Ñ…Ð¸', 'ltr', null, null),
				array('cy', 'wel', 'Welsh', 'Cymraeg', 'ltr', 4, 'n==1 ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3'),

				array('da', 'dan', 'Danish', 'Dansk', 'ltr', 2, 'n != 1'),
				array('de', 'ger', 'German', 'Deutsch', 'ltr', 2, 'n != 1'),
				array('dv', 'div', 'Maldivian', 'â€«Þ‹Þ¨ÞˆÞ¬Þ€Þ¨', 'rtl', null, null),
				array('dz', 'dzo', 'Dzongkha', 'à½¢à¾«à½¼à½„à¼‹à½', 'ltr', 1, '0'),

				array('ee', 'ewe', 'Ewe', 'ÆÊ‹É›gbÉ›', 'ltr', null, null),
				array('el', 'gre', 'Greek', 'Î•Î»Î»Î·Î½Î¹ÎºÎ¬', 'ltr', 2, 'n != 1'),
				array('en', 'eng', 'English', 'English', 'ltr', 2, 'n != 1'),
				array('eo', 'epo', 'Esperanto', 'Esperanto', 'ltr', 2, 'n != 1'),
				array('es', 'spa', 'Spanish', 'espaÃ±ol', 'ltr', 2, 'n != 1'),
				array('es-ar', null, 'Argentinean Spanish', 'Argentinean Spanish', 'ltr', 2, 'n != 1'),
				array('et', 'est', 'Estonian', 'Eesti keel', 'ltr', 2, 'n != 1'),
				array('eu', 'baq', 'Basque', 'Euskara', 'ltr', 2, 'n != 1'),

				array('fa', 'per', 'Persian', 'â€«ÙØ§Ø±Ø³ÛŒ', 'rtl', 1, '0'),
				array('ff', 'ful', 'Fulah', 'Fulfulde', 'ltr', 2, 'n != 1'),
				array('fi', 'fin', 'Finnish', 'Suomen kieli', 'ltr', 2, 'n != 1'),
				array('fj', 'fij', 'Fijian', 'Vosa Vakaviti', 'ltr', null, null),
				array('fo', 'fao', 'Faroese', 'FÃ¸royskt', 'ltr', 2, 'n != 1'),
				array('fr', 'fre', 'French', 'FranÃ§ais', 'ltr', 2, 'n > 1'),
				array('fy', 'fry', 'Western Frisian', 'Frysk', 'ltr', 2, 'n != 1'),

				array('ga', 'gle', 'Irish', 'Gaeilge', 'ltr', 5, 'n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4'),
				array('gd', 'gla', 'Gaelic', 'GÃ idhlig', 'ltr', 4, '(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3'),
				array('gl', 'glg', 'Galician', 'Galego', 'ltr', 2, 'n != 1'),
				array('gn', 'grn', 'Guarani', "AvaÃ±e'áº½", 'ltr', null, null),
				array('gu', 'guj', 'Gujarati', 'àª—à«àªœàª°àª¾àª¤à«€', 'ltr', 2, 'n != 1'),
				array('gv', 'glv', 'Manx', 'Ghaelg', 'ltr', null, null),

				array('ha', 'hau', 'Hausa', 'â€«Ù‡ÙŽÙˆÙØ³ÙŽ', 'rtl', 2, 'n != 1'),
				array('he', 'heb', 'Hebrew', 'â€«×¢×‘×¨×™×ª', 'rtl', 2, 'n != 1'),
				array('hi', 'hin', 'Hindi', 'à¤¹à¤¿à¤¨à¥à¤¦à¥€', 'ltr', 2, 'n != 1'),
				array('ho', 'hmo', 'Hiri Motu', 'Hiri Motu', 'ltr', null, null),
				array('hr', 'hrv', 'Croatian', 'Hrvatski', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
				array('ht', 'hat', 'Haitian', 'KreyÃ²l ayisyen', 'ltr', null, null),
				array('hu', 'hun', 'Hungarian', 'Magyar', 'ltr', 2, 'n != 1'),
				array('hy', 'arm', 'Armenian', 'Õ€Õ¡ÕµÕ¥Ö€Õ¥Õ¶', 'ltr', 2, 'n != 1'),
				array('hz', 'her', 'Herero', 'Otjiherero', 'ltr', null, null),

				array('ia', 'ina', 'Interlingua', 'Interlingua', 'ltr', 2, 'n != 1'),
				array('id', 'ind', 'Indonesian', 'Bahasa Indonesia', 'ltr', 1, '0'),
				array('ie', 'ile', 'Interlingue', 'Interlingue', 'ltr', null, null),
				array('ig', 'ibo', 'Igbo', 'Igbo', 'ltr', null, null),
				array('ii', 'iii', 'Sichuan Yi', 'ê†‡ê‰™', 'ltr', null, null),
				array('ik', 'ipk', 'Inupiaq', 'IÃ±upiaq', 'ltr', null, null),
				array('io', 'ido', 'Ido', 'Ido', 'ltr', null, null),
				array('is', 'ice', 'Icelandic', 'Ãslenska', 'ltr', 2, '(n%10!=1 || n%100==11) ? 1 : 0'),
				array('it', 'ita', 'Italian', 'Italiano', 'ltr', 2, 'n != 1'),
				array('iu', 'iku', 'Inuktitut', 'áƒá“„á’ƒá‘Žá‘á‘¦', 'ltr', null, null),

				array('ja', 'jpn', 'Japanese', 'æ—¥æœ¬èªž', 'ltr', 1, '0'),
				array('jv', 'jav', 'Javanese', 'Basa Jawa', 'ltr', 2, 'n != 0'),

				array('ka', 'geo', 'Georgian', 'áƒ¥áƒáƒ áƒ—áƒ£áƒšáƒ˜', 'ltr', 1, '0'),
				array('kg', 'kon', 'Kongo', 'KiKongo', 'ltr', null, null),
				array('ki', 'kik', 'Kikuyu', 'GÄ©kÅ©yÅ©', 'ltr', null, null),
				array('kj', 'kua', 'Kuanyama', 'Kuanyama', 'ltr', null, null),
				array('kk', 'kaz', 'Kazakh', 'ÒšÐ°Ð·Ð°Ò› Ñ‚Ñ–Ð»Ñ–', 'ltr', 1, '0'),
				array('kl', 'kal', 'Greenlandic', 'Kalaallisut', 'ltr', null, null),
				array('km', 'khm', 'Central Khmer', 'áž—áž¶ážŸáž¶ážáŸ’áž˜áŸ‚ážš', 'ltr', 1, '0'),
				array('kn', 'kan', 'Kannada', 'à²•à²¨à³à²¨à²¡', 'ltr', 2, 'n != 1'),
				array('ko', 'kor', 'Korean', 'í•œêµ­ì–´', 'ltr', 1, '0'),
				array('kr', 'kau', 'Kanuri', 'Kanuri', 'ltr', null, null),
				array('ks', 'kas', 'Kashmiri', 'à¤•à¤¶à¥à¤®à¥€à¤°à¥€', 'rtl', null, null),
				array('ku', 'kur', 'Kurdish', 'KurdÃ®', 'ltr', 2, 'n!= 1'),
				array('kv', 'kom', 'Komi', 'ÐºÐ¾Ð¼Ð¸ ÐºÑ‹Ð²', 'ltr', null, null),
				array('kw', 'cor', 'Cornish', 'Kernewek', 'ltr', 4, 'n==1 ? 0 : (n==2) ? 1 : (n == 3) ? 2 : 3'),
				array('ky', 'kir', 'Kirghiz', 'ÐºÑ‹Ñ€Ð³Ñ‹Ð· Ñ‚Ð¸Ð»Ð¸', 'ltr', 1, '0'),

				array('la', 'lat', 'Latin', 'Latine', 'ltr', null, null),
				array('lb', 'ltz', 'Luxembourgish', 'LÃ«tzebuergesch', 'ltr', 2, 'n != 1'),
				array('lg', 'lug', 'Ganda', 'Luganda', 'ltr', null, null),
				array('li', 'lim', 'Limburgan', 'Limburgs', 'ltr', null, null),
				array('ln', 'lin', 'Lingala', 'LingÃ¡la', 'ltr', 2, 'n>1'),
				array('lo', 'lao', 'Lao', 'àºžàº²àºªàº²àº¥àº²àº§', 'ltr', 1, '0'),
				array('lt', 'lit', 'Lithuanian', 'LietuviÅ³ kalba', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 or n%100>=20) ? 1 : 2'),
				array('lu', 'lub', 'Luba-Katanga', 'Luba-Katanga	', 'ltr', null, null),
				array('lv', 'lav', 'Latvian', 'LatvieÅ¡u valoda', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2'),

				array('mg', 'mlg', 'Malagasy', 'Malagasy fiteny', 'ltr', 2, 'n > 1'),
				array('mh', 'mah', 'Marshallese', 'Kajin MÌ§ajeÄ¼', 'ltr', null, null),
				array('mi', 'mao', 'Maori', 'Te reo MÄori', 'ltr', 2, 'n > 1'),
				array('mk', 'mac', 'Macedonian', 'Ð¼Ð°ÐºÐµÐ´Ð¾Ð½ÑÐºÐ¸ Ñ˜Ð°Ð·Ð¸Ðº', 'ltr', 2, 'n==1 || n%10==1 ? 0 : 1'),
				array('ml', 'mal', 'Malayalam', 'à´®à´²à´¯à´¾à´³à´‚', 'ltr', 2, 'n != 1'),
				array('mn', 'mon', 'Mongolian', 'ÐœÐ¾Ð½Ð³Ð¾Ð»', 'ltr', 2, 'n != 1'),
				array('mo', null, 'Moldavian', 'Limba moldoveneascÄƒ', 'ltr', 3, 'n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2'), //cf: ro
				array('mr', 'mar', 'Marathi', 'à¤®à¤°à¤¾à¤ à¥€', 'ltr', 2, 'n != 1'),
				array('ms', 'may', 'Malay', 'Bahasa Melayu', 'ltr', 1, '0'),
				array('mt', 'mlt', 'Maltese', 'Malti', 'ltr', 4, 'n==1 ? 0 : n==0 || ( n%100>1 && n%100<11) ? 1 : (n%100>10 && n%100<20 ) ? 2 : 3'),
				array('my', 'bur', 'Burmese', 'á€—á€™á€¬á€…á€¬', 'ltr', 1, '0'),

				array('na', 'nau', 'Nauru', 'EkakairÅ© Naoero', 'ltr', null, null),
				array('nb', 'nob', 'Norwegian Bokmål', 'Norsk bokmÃ¥l', 'ltr', 2, 'n != 1'),
				array('nd', 'nde', 'North Ndebele', 'isiNdebele', 'ltr', null, null),
				array('ne', 'nep', 'Nepali', 'à¤¨à¥‡à¤ªà¤¾à¤²à¥€', 'ltr', 2, 'n != 1'),
				array('ng', 'ndo', 'Ndonga', 'Owambo', 'ltr', null, null),
				array('nl', 'dut', 'Flemish', 'Nederlands', 'ltr', 2, 'n != 1'),
				array('nl-be', null, 'Flemish', 'Nederlands (Belgium)', 'ltr', 2, 'n != 1'),
				array('nn', 'nno', 'Norwegian Nynorsk', 'Norsk nynorsk', 'ltr', 2, 'n != 1'),
				array('no', 'nor', 'Norwegian', 'Norsk', 'ltr', 2, 'n != 1'),
				array('nr', 'nbl', 'South Ndebele', 'NdÃ©bÃ©lÃ©', 'ltr', null, null),
				array('nv', 'nav', 'Navajo', 'DinÃ© bizaad', 'ltr', null, null),
				array('ny', 'nya', 'Chichewa', 'ChiCheÅµa', 'ltr', null, null),

				array('oc', 'oci', 'Occitan', 'Occitan', 'ltr', 2, 'n > 1'),
				array('oj', 'oji', 'Ojibwa', 'áŠá“‚á”‘á“ˆá¯á’§áŽá“', 'ltr', null, null),
				array('om', 'orm', 'Oromo', 'Afaan Oromoo', 'ltr', null, null),
				array('or', 'ori', 'Oriya', 'à¬“à¬¡à¬¼à¬¿à¬†', 'ltr', 2, 'n != 1'),
				array('os', 'oss', 'Ossetian', 'Ð˜Ñ€Ð¾Ð½ Ã¦Ð²Ð·Ð°Ð³', 'ltr', null, null),

				array('pa', 'pan', 'Panjabi', 'à¨ªà©°à¨œà¨¾à¨¬à©€', 'ltr', 2, 'n != 1'),
				array('pi', 'pli', 'Pali', 'à¤ªà¤¾à¤´à¤¿', 'ltr', null, null),
				array('pl', 'pol', 'Polish', 'Polski', 'ltr', 3, 'n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
				array('ps', 'pus', 'Pushto', 'â€«Ù¾ÚšØªÙˆ', 'rtl', 2, 'n != 1'),
				array('pt', 'por', 'Portuguese', 'PortuguÃªs', 'ltr', 2, 'n != 1'),
				array('pt-br', null, 'Brazilian Portuguese', 'PortuguÃªs do Brasil', 'ltr', 2, 'n > 1'),

				array('qu', 'que', 'Quechua', 'Runa Simi', 'ltr', null, null),

				array('rm', 'roh', 'Romansh', 'Rumantsch grischun', 'ltr', 2, 'n != 1'),
				array('rn', 'run', 'Rundi', 'kiRundi', 'ltr', null, null),
				array('ro', 'rum', 'Romanian', 'RomÃ¢nÄƒ', 'ltr', 3, 'n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2'),
				array('ru', 'rus', 'Russian', 'Ð ÑƒÑÑÐºÐ¸Ð¹', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
				array('rw', 'kin', 'Kinyarwanda', 'IKinyarwanda', 'ltr', 2, 'n != 1'),

				array('sa', 'san', 'Sanskrit', 'à¤¸à¤‚à¤¸à¥à¤•à¥ƒà¤¤à¤®à¥', 'ltr', null, null),
				array('sc', 'srd', 'Sardinian', 'sardu', 'ltr', null, null),
				array('sd', 'snd', 'Sindhi', 'à¤¸à¤¿à¤¨à¥à¤§à¥€', 'ltr', 2, 'n != 1'),
				array('se', 'sme', 'Northern Sami', 'DavvisÃ¡megiella', 'ltr', null, null),
				array('sg', 'sag', 'Sango', 'YÃ¢ngÃ¢ tÃ® sÃ¤ngÃ¶', 'ltr', null, null),
				array('sh', null, null, 'SrpskoHrvatski', 'ltr', null, null), //!
				array('si', 'sin', 'Sinhalese', 'à·ƒà·’à¶‚à·„à¶½', 'ltr', 2, 'n != 1'),
				array('sk', 'slo', 'Slovak', 'SlovenÄina', 'ltr', 3, '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'),
				array('sl', 'slv', 'Slovenian', 'SlovenÅ¡Äina', 'ltr', 4, 'n%100==1 ? 1 : n%100==2 ? 2 : n%100==3 || n%100==4 ? 3 : 0'),
				array('sm', 'smo', 'Samoan', "Gagana fa'a Samoa", 'ltr', null, null),
				array('sn', 'sna', 'Shona', 'chiShona', 'ltr', null, null),
				array('so', 'som', 'Somali', 'Soomaaliga', 'ltr', 2, 'n != 1'),
				array('sq', 'alb', 'Albanian', 'Shqip', 'ltr', 2, 'n != 1'),
				array('sr', 'srp', 'Serbian', 'ÑÑ€Ð¿ÑÐºÐ¸ Ñ˜ÐµÐ·Ð¸Ðº', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
				array('ss', 'ssw', 'Swati', 'SiSwati', 'ltr', null, null),
				array('st', 'sot', 'Southern Sotho', 'seSotho', 'ltr', null, null),
				array('su', 'sun', 'Sundanese', 'Basa Sunda', 'ltr', 1, '0'),
				array('sv', 'swe', 'Swedish', 'Svenska', 'ltr', 2, 'n != 1'),
				array('sw', 'swa', 'Swahili', 'Kiswahili', 'ltr', 2, 'n != 1'),

				array('ta', 'tam', 'Tamil', 'à®¤à®®à®¿à®´à¯', 'ltr', 2, 'n != 1'),
				array('te', 'tel', 'Telugu', 'à°¤à±†à°²à±à°—à±', 'ltr', 2, 'n != 1'),
				array('tg', 'tgk', 'Tajik', 'Ñ‚Ð¾Ò·Ð¸ÐºÓ£', 'ltr', 2, 'n > 1'),
				array('th', 'tha', 'Thai', 'à¹„à¸—à¸¢', 'ltr', 1, '0'),
				array('ti', 'tir', 'Tigrinya', 'á‰µáŒáˆ­áŠ›', 'ltr', 2, 'n > 1'),
				array('tk', 'tuk', 'Turkmen', 'TÃ¼rkmen', 'ltr', 2, 'n != 1'),
				array('tl', 'tlg', 'Tagalog', 'Tagalog', 'ltr', null, null),
				array('tn', 'tsn', 'Tswana', 'seTswana', 'ltr', null, null),
				array('to', 'ton', 'Tonga', 'faka Tonga', 'ltr', null, null),
				array('tr', 'tur', 'Turkish', 'TÃ¼rkÃ§e', 'ltr', 2, 'n > 1'),
				array('ts', 'tso', 'Tsonga', 'xiTsonga', 'ltr', null, null),
				array('tt', 'tat', 'Tatar', 'Ñ‚Ð°Ñ‚Ð°Ñ€Ñ‡Ð°', 'ltr', 1, '0'),
				array('tw', 'twi', 'Twi', 'Twi', 'ltr', null, null),
				array('ty', 'tah', 'Tahitian', 'Reo MÄ`ohi', 'ltr', null, null),

				array('ug', 'uig', 'Uighur', 'UyÆ£urqÉ™', 'ltr', 1, '0'),
				array('uk', 'ukr', 'Ukrainian', 'Ð£ÐºÑ€Ð°Ñ—Ð½ÑÑŒÐºÐ°', 'ltr', 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
				array('ur', 'urd', 'Urdu', 'â€«Ø§Ø±Ø¯Ùˆ', 'rtl', 2, 'n != 1'),
				array('uz', 'uzb', 'Uzbek', "O'zbek", 'ltr', 2, 'n > 1'),

				array('ve', 'ven', 'Venda', 'tshiVená¸“a', 'ltr', null, null),
				array('vi', 'vie', 'Vietnamese', 'Tiáº¿ng Viá»‡t', 'ltr', 1, '0'),
				array('vo', 'vol', 'Volapük', 'VolapÃ¼k', 'ltr', null, null),

				array('wa', 'wln', 'Walloon', 'Walon', 'ltr', 2, 'n > 1'),
				array('wo', 'wol', 'Wolof', 'Wollof', 'ltr', 1, '0'),

				array('xh', 'xho', 'Xhosa', 'isiXhosa', 'ltr', null, null),

				array('yi', 'yid', 'Yiddish', 'â€«×™×™Ö´×“×™×©', 'rtl', null, null),
				array('yo', 'yor', 'Yoruba', 'YorÃ¹bÃ¡', 'ltr', 2, 'n != 1'),

				array('za', 'zha', 'Chuang', 'SaÉ¯ cueÅ‹Æ…', 'ltr', null, null),
				array('zh', 'zhi', 'Chinese', 'ä¸­æ–‡', 'ltr', 1, '0'),
				array('zh-hk', null, 'Honk Kong Chinese', 'ä¸­æ–‡ (é¦™æ¸¯)', 'ltr', 1, '0'),
				array('zh-tw', null, 'Taiwan Chinese', 'ä¸­æ–‡ (è‡ºç£)', 'ltr', 1, '0'),
				array('zu', 'zul', 'Zulu', 'isiZulu', 'ltr', null, null),
			);
		}

		$r = array();
		foreach(self::$languages_definitions as $_) {
			$r[$_[0]] = empty($_[$type]) ? $default : $_[$type];
		}

		return $r;
	}
	//@}
}
?>