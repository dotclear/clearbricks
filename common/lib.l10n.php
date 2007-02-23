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
		$res = array(
		'aa' => 'Afar',
		'ab' => 'Abkhazian',
		'af' => 'Afrikaans',
		'am' => 'Amharic',
		'ar' => 'Arabic',
		'as' => 'Assamese',
		'ay' => 'Aymara',
		'az' => 'Azerbaijani',
		'ba' => 'Bashkir',
		'be' => 'Byelorussian',
		'bg' => 'Bulgarian',
		'bh' => 'Bihari',
		'bi' => 'Bislama',
		'bn' => 'Bengali',
		'bo' => 'Tibetan',
		'br' => 'Breton',
		'ca' => 'Catalan',
		'co' => 'Corsican',
		'cs' => 'Czech',
		'cy' => 'Welsh',
		'da' => 'Danish',
		'de' => 'German',
		'dz' => 'Bhutani',
		'el' => 'Greek',
		'en' => 'English',
		'eo' => 'Esperanto',
		'es' => 'Spanish',
		'et' => 'Estonian',
		'eu' => 'Basque',
		'fa' => 'Persian',
		'fi' => 'Finnish',
		'fj' => 'Fiji',
		'fo' => 'Faeroese',
		'fr' => 'French',
		'fy' => 'Frisian',
		'ga' => 'Irish',
		'gd' => 'Gaelic',
		'gl' => 'Galician',
		'gn' => 'Guarani',
		'gu' => 'Gujarati',
		'ha' => 'Hausa',
		'hi' => 'Hindi',
		'hr' => 'Croatian',
		'hu' => 'Hungarian',
		'hy' => 'Armenian',
		'ia' => 'Interlingua',
		'ie' => 'Interlingue',
		'ik' => 'Inupiak',
		'in' => 'Indonesian',
		'is' => 'Icelandic',
		'it' => 'Italian',
		'iw' => 'Hebrew',
		'ja' => 'Japanese',
		'ji' => 'Yiddish',
		'jw' => 'Javanese',
		'ka' => 'Georgian',
		'kk' => 'Kazakh',
		'kl' => 'Greenlandic',
		'km' => 'Cambodian',
		'kn' => 'Kannada',
		'ko' => 'Korean',
		'ks' => 'Kashmiri',
		'ku' => 'Kurdish',
		'ky' => 'Kirghiz',
		'la' => 'Latin',
		'ln' => 'Lingala',
		'lo' => 'Laothian',
		'lt' => 'Lithuanian',
		'lv' => 'Latvian',
		'mg' => 'Malagasy',
		'mi' => 'Maori',
		'mk' => 'Macedonian',
		'ml' => 'Malayalam',
		'mn' => 'Mongolian',
		'mo' => 'Moldavian',
		'mr' => 'Marathi',
		'ms' => 'Malay',
		'mt' => 'Maltese',
		'my' => 'Burmese',
		'na' => 'Nauru',
		'ne' => 'Nepali',
		'nl' => 'Dutch',
		'no' => 'Norwegian',
		'oc' => 'Occitan',
		'om' => 'Oromo',
		'or' => 'Oriya',
		'pa' => 'Punjabi',
		'pl' => 'Polish',
		'ps' => 'Pashto',
		'pt' => 'Portuguese',
		'qu' => 'Quechua',
		'rm' => 'Rhaeto-Romance',
		'rn' => 'Kirundi',
		'ro' => 'Romanian',
		'ru' => 'Russian',
		'rw' => 'Kinyarwanda',
		'sa' => 'Sanskrit',
		'sd' => 'Sindhi',
		'sg' => 'Sangro',
		'sh' => 'Serbo-Croatian',
		'si' => 'Singhalese',
		'sk' => 'Slovak',
		'sl' => 'Slovenian',
		'sm' => 'Samoan',
		'sn' => 'Shona',
		'so' => 'Somali',
		'sq' => 'Albanian',
		'sr' => 'Serbian',
		'ss' => 'Siswati',
		'st' => 'Sesotho',
		'su' => 'Sudanese',
		'sv' => 'Swedish',
		'sw' => 'Swahili',
		'ta' => 'Tamil',
		'te' => 'Tegulu',
		'tg' => 'Tajik',
		'th' => 'Thai',
		'ti' => 'Tigrinya',
		'tk' => 'Turkmen',
		'tl' => 'Tagalog',
		'tn' => 'Setswana',
		'to' => 'Tonga',
		'tr' => 'Turkish',
		'ts' => 'Tsonga',
		'tt' => 'Tatar',
		'tw' => 'Twi',
		'uk' => 'Ukrainian',
		'ur' => 'Urdu',
		'uz' => 'Uzbek',
		'vi' => 'Vietnamese',
		'vo' => 'Volapuk',
		'wo' => 'Wolof',
		'xh' => 'Xhosa',
		'yo' => 'Yoruba',
		'zh' => 'Chinese',
		'zu' => 'Zulu'
		);
		
		if ($flip) {
			$res = array_flip($res);
			ksort($res);
		}
		
		return $res;
	}
}
?>