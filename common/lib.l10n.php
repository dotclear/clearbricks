<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2006 Olivier Meunier and contributors. All rights
# reserved.
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


function __($str)
{
	return (!empty($GLOBALS['__l10n'][$str])) ? $GLOBALS['__l10n'][$str] : $str;
}

class l10n
{
	protected static $langs = array();
	
	public static function init()
	{
		$GLOBALS['__l10n'] = array();
		$GLOBALS['__l10n_files'] = array();
	}
	
	public static function set($file)
	{
		$lang_file = $file.'.lang';
		$po_file = $file.'.po';
		$php_file = $file.'.lang.php';
		
		if (file_exists($php_file))
		{
			require $php_file;
		}
		elseif (($tmp = self::getPoFile($po_file)) !== false)
		{
			$GLOBALS['__l10n_files'][] = $po_file;
			$GLOBALS['__l10n'] = array_merge($GLOBALS['__l10n'],$tmp);
		}
		elseif (($tmp = self::getLangFile($lang_file)) !== false)
		{
			$GLOBALS['__l10n_files'][] = $lang_file;
			$GLOBALS['__l10n'] = array_merge($GLOBALS['__l10n'],$tmp);
		}
		else
		{
			return false;
		}
	}
	
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
	
	public static function getPoFile($file)
	{
		if (!file_exists($file)) {
			return false;
		}
		
		$fc = implode('',file($file));
		
		$res = array();
		
		$matched = preg_match_all('/(msgid\s+("([^"]|\\\\")*?"\s*)+)\s+'.
		'(msgstr\s+("([^"]|\\\\")*?(?<!\\\)"\s*)+)/',
		$fc, $matches);
		
		if (!$matched) {
			return false;
		}
		
		for ($i=0; $i<$matched; $i++)
		{
			$msgid = preg_replace('/\s*msgid\s*"(.*)"\s*/s','\\1',$matches[1][$i]);
			$msgstr= preg_replace('/\s*msgstr\s*"(.*)"\s*/s','\\1',$matches[4][$i]);
			
			$msgstr = self::poString($msgstr);
			
			if ($msgstr) {
				$res[self::poString($msgid)] = $msgstr;
			}
		}
		
		if (!empty($res[''])) {
			$meta = $res[''];
			unset($res['']);
		}
		
		return $res;
	}
	
	private static function poString($string,$reverse=false)
	{
		if ($reverse) {
			$smap = array('"', "\n", "\t", "\r");
			$rmap = array('\\"', '\\n"' . "\n" . '"', '\\t', '\\r');
			return trim((string) str_replace($smap, $rmap, $string));
		} else {
			$smap = array('/"\s+"/', '/\\\\n/', '/\\\\r/', '/\\\\t/', '/\\\"/');
			$rmap = array('', "\n", "\r", "\t", '"');
			return trim((string) preg_replace($smap, $rmap, $string));
		}
	}
	
	public static function getFilePath($dir,$file,$lang)
	{
		$f = $dir.'/'.$lang.'/'.$file;
		if (!file_exists($f)) {
			$f = $dir.'/en/'.$file;
		}
		
		return file_exists($f) ? $f : false;
	}
	
	public static function getISOcodes($flip=false)
	{
		if (empty(self::$lang))
		{
			self::$langs = array(
			'aa' => __('Afar'),
			'ab' => __('Abkhazian'),
			'af' => __('Afrikaans'),
			'am' => __('Amharic'),
			'ar' => __('Arabic'),
			'as' => __('Assamese'),
			'ay' => __('Aymara'),
			'az' => __('Azerbaijani'),
			'ba' => __('Bashkir'),
			'be' => __('Byelorussian'),
			'bg' => __('Bulgarian'),
			'bh' => __('Bihari'),
			'bi' => __('Bislama'),
			'bn' => __('Bengali'),
			'bo' => __('Tibetan'),
			'br' => __('Breton'),
			'ca' => __('Catalan'),
			'co' => __('Corsican'),
			'cs' => __('Czech'),
			'cy' => __('Welsh'),
			'da' => __('Danish'),
			'de' => __('German'),
			'dz' => __('Bhutani'),
			'el' => __('Greek'),
			'en' => __('English'),
			'eo' => __('Esperanto'),
			'es' => __('Spanish'),
			'et' => __('Estonian'),
			'eu' => __('Basque'),
			'fa' => __('Persian'),
			'fi' => __('Finnish'),
			'fj' => __('Fiji'),
			'fo' => __('Faeroese'),
			'fr' => __('French'),
			'fy' => __('Frisian'),
			'ga' => __('Irish'),
			'gd' => __('Gaelic'),
			'gl' => __('Galician'),
			'gn' => __('Guarani'),
			'gu' => __('Gujarati'),
			'ha' => __('Hausa'),
			'hi' => __('Hindi'),
			'hr' => __('Croatian'),
			'hu' => __('Hungarian'),
			'hy' => __('Armenian'),
			'ia' => __('Interlingua'),
			'ie' => __('Interlingue'),
			'ik' => __('Inupiak'),
			'in' => __('Indonesian'),
			'is' => __('Icelandic'),
			'it' => __('Italian'),
			'iw' => __('Hebrew'),
			'ja' => __('Japanese'),
			'ji' => __('Yiddish'),
			'jw' => __('Javanese'),
			'ka' => __('Georgian'),
			'kk' => __('Kazakh'),
			'kl' => __('Greenlandic'),
			'km' => __('Cambodian'),
			'kn' => __('Kannada'),
			'ko' => __('Korean'),
			'ks' => __('Kashmiri'),
			'ku' => __('Kurdish'),
			'ky' => __('Kirghiz'),
			'la' => __('Latin'),
			'ln' => __('Lingala'),
			'lo' => __('Laothian'),
			'lt' => __('Lithuanian'),
			'lv' => __('Latvian'),
			'mg' => __('Malagasy'),
			'mi' => __('Maori'),
			'mk' => __('Macedonian'),
			'ml' => __('Malayalam'),
			'mn' => __('Mongolian'),
			'mo' => __('Moldavian'),
			'mr' => __('Marathi'),
			'ms' => __('Malay'),
			'mt' => __('Maltese'),
			'my' => __('Burmese'),
			'na' => __('Nauru'),
			'ne' => __('Nepali'),
			'nl' => __('Dutch'),
			'no' => __('Norwegian'),
			'oc' => __('Occitan'),
			'om' => __('Oromo'),
			'or' => __('Oriya'),
			'pa' => __('Punjabi'),
			'pl' => __('Polish'),
			'ps' => __('Pashto'),
			'pt' => __('Portuguese'),
			'qu' => __('Quechua'),
			'rm' => __('Rhaeto-Romance'),
			'rn' => __('Kirundi'),
			'ro' => __('Romanian'),
			'ru' => __('Russian'),
			'rw' => __('Kinyarwanda'),
			'sa' => __('Sanskrit'),
			'sd' => __('Sindhi'),
			'sg' => __('Sangro'),
			'sh' => __('Serbo-Croatian'),
			'si' => __('Singhalese'),
			'sk' => __('Slovak'),
			'sl' => __('Slovenian'),
			'sm' => __('Samoan'),
			'sn' => __('Shona'),
			'so' => __('Somali'),
			'sq' => __('Albanian'),
			'sr' => __('Serbian'),
			'ss' => __('Siswati'),
			'st' => __('Sesotho'),
			'su' => __('Sudanese'),
			'sv' => __('Swedish'),
			'sw' => __('Swahili'),
			'ta' => __('Tamil'),
			'te' => __('Tegulu'),
			'tg' => __('Tajik'),
			'th' => __('Thai'),
			'ti' => __('Tigrinya'),
			'tk' => __('Turkmen'),
			'tl' => __('Tagalog'),
			'tn' => __('Setswana'),
			'to' => __('Tonga'),
			'tr' => __('Turkish'),
			'ts' => __('Tsonga'),
			'tt' => __('Tatar'),
			'tw' => __('Twi'),
			'uk' => __('Ukrainian'),
			'ur' => __('Urdu'),
			'uz' => __('Uzbek'),
			'vi' => __('Vietnamese'),
			'vo' => __('Volapuk'),
			'wo' => __('Wolof'),
			'xh' => __('Xhosa'),
			'yo' => __('Yoruba'),
			'zh' => __('Chinese'),
			'zu' => __('Zulu')
			);
		}
		
		if ($flip) {
			$res = array_flip(self::$langs);
			ksort($res);
			return $res;
		}
		
		return self::$langs;
	}
}
?>