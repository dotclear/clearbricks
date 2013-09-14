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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
#
# ***** END LICENSE BLOCK *****

namespace tests\unit;

use atoum;
use Faker;

require_once __DIR__.'/../bootstrap.php';
require_once(str_replace('tests/unit/',	 '', __FILE__));

class l10n extends atoum
{
	private $l10n_dir = '/../fixtures/l10n';

	public function testWithoutTranslation() {
		$faker	= Faker\Factory::create();
		$text = $faker->text(50);

		$this
			->string(__($text))
			->isEqualTo($text);
	}

	public function testSimpleSingular() {
		\l10n::init();
		\l10n::set(__DIR__.'/../fixtures/l10n/fr/core');

		$this
			->string(__('Dotclear has been upgraded.'))
			->isEqualTo('Dotclear a été mis à jour.');
	}

	public function testPhpFormatSingular() {
		$faker	= Faker\Factory::create();
		$text = $faker->text(20);

		\l10n::init();
		\l10n::set(__DIR__.'/../fixtures/l10n/fr/php-format');

		$this
			->string(sprintf(__('The e-mail was sent successfully to %s.'), $text))
			->isEqualTo(sprintf('Message envoyé avec succès à %s.', $text));
	}

	public function testPluralWithoutTranslation() {
		\l10n::init();
		\l10n::set(__DIR__.'/../fixtures/l10n/dummy');

		$this
			->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 1))
			->isEqualTo('The category has been successfully removed.');

		$this
			->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 2))
			->isEqualTo('The categories have been successfully removed.');
	}

	public function testSimplePlural() {
		\l10n::init();
		\l10n::set(__DIR__.'/../fixtures/l10n/fr/main');

		/*
		  msgid "The category has been successfully removed."
		  msgid_plural "The categories have been successfully removed."
		  msgstr[0] "Catégorie supprimée avec succès."
		  msgstr[1] "Catégories supprimées avec succès."
		*/

		$this
			->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 1))
			->isEqualTo('Catégorie supprimée avec succès.');

		$this
			->string(__('The category has been successfully removed.', 'The categories have been successfully removed.', 2))
			->isEqualTo('Catégories supprimées avec succès.');
	}

	public function testNotExistingPhpAndPoFiles() {
		\l10n::init();
		\l10n::set(__DIR__.'/../fixtures/l10n/dummy');

		$this
			->string(__('Dotclear has been upgraded.'))
			->isEqualTo('Dotclear has been upgraded.');
	}

	public function testGetFilePath() {
		\l10n::init();
		
		$this
			->string(\l10n::getFilePath(__DIR__.$this->l10n_dir, 'main.po', 'fr'))
			->isEqualTo(__DIR__.$this->l10n_dir.'/fr/main.po');

		$this
			->boolean(\l10n::getFilePath(__DIR__.$this->l10n_dir, 'dummy.po', 'fr'))
			->isEqualTo(false);
	}

	public function testMultiLineIdString() {
		\l10n::init();

		$en_str = 'Not a real long sentence';
		$content = 'msgid ""'."\n".'"';
		$content .= implode('"'."\n".'" ', explode(' ', $en_str));
		$content .= '"'."\n";
		$content .= 'msgstr "Pas vraiment une très longue phrase"'."\n";
		
		$tmp_file = $this->tempPoFile($content);
		\l10n::set(str_replace('.po', '', $tmp_file));

		$this
			->string(__($en_str))
			->isEqualTo("Pas vraiment une très longue phrase");

		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
	}

	public function testMultiLineValueString() {
		\l10n::init();

		$en_str = 'Not a real long sentence';
		$fr_str = "Pas vraiment une très longue phrase";
		$content = 'msgid "'.$en_str.'"'."\n";
		$content .= 'msgstr ""'."\n".'"';
		$content .= implode('"'."\n".'" ', explode(' ', $fr_str));
		$content .= '"'."\n";
		
		$tmp_file = $this->tempPoFile($content);
		\l10n::set(str_replace('.po', '', $tmp_file));

		$this
			->string(__($en_str))
			->isEqualTo($fr_str);

		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
	}

	public function testSimpleStringInPhpFile() {
		\l10n::init();

		$file = __DIR__.'/../fixtures/l10n/fr/simple';
		if (file_exists("$file.lang.php")) {
			unlink("$file.lang.php");
		}
		\l10n::generatePhpFileFromPo($file);
		\l10n::set($file);

		$this
			->array($GLOBALS['__l10n'])
			->isIdenticalTo(array('Dotclear has been upgraded.' => 'Dotclear a été mis à jour.'));
	}	 

	public function testPluralStringsInPhpFile() {
		\l10n::init();

		$file = __DIR__.'/../fixtures/l10n/fr/plurals';
		if (file_exists("$file.lang.php")) {
			unlink("$file.lang.php");
		}
		\l10n::generatePhpFileFromPo($file);
		\l10n::set($file);

		$this
			->array($GLOBALS['__l10n'])
			->isIdenticalTo(array('The category has been successfully removed.' => array('Catégorie supprimée avec succès.', 'Catégories supprimées avec succès.')));
	}

	/*
	**/
	protected function tempPoFile($content) {
		$filename = sys_get_temp_dir() . '/temp.po';

		file_put_contents($filename, $content);
		return $filename;
	}
}
