<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.text.php';

use atoum;
use Faker;

/**
 * Test the form class
 */
class text extends atoum
{
    public function testIsEmail()
    {
        $faker = Faker\Factory::create();
        $text  = $faker->email();

        $this
            ->boolean(\text::isEmail($text))
            ->isTrue();

        $this
            ->boolean(\text::isEmail('@dotclear.org'))
            ->isFalse();
    }

    /**
     * @dataProvider testIsEmailDataProvider
     */
    protected function testIsEmailAllDataProvider()
    {
        require_once __DIR__ . '/../fixtures/data/lib.text.php';
        return array_values($emailTest);
    }

    public function testIsEmailAll($payload, $expected)
    {
        $this
            ->boolean(\text::isEmail($payload))
            ->isEqualTo($expected);
    }

    public function testDeaccent()
    {
        $this
            ->string(\text::deaccent('ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß éè'))
            ->isEqualTo('AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss ee');
    }

    public function teststr2URL()
    {
        $this
            ->string(\text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html'))
            ->isEqualTo('https://domaincom/AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss/eehtml');

        $this
            ->string(\text::str2URL('https://domain.com/ÀÅÆÇÐÈËÌÏÑÒÖØŒŠÙÜÝŽàåæçðèëìïñòöøœšùüýÿžß/éè.html', false))
            ->isEqualTo('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml');
    }

    public function testTidyURL()
    {
        // Keep /, no spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !'))
            ->isEqualTo('Étrange-et-curieux/À-vous-!');

        // Keep /, keep spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !', true, true))
            ->isEqualTo('Étrange et curieux/À vous !');

        // No /, keep spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !', false, true))
            ->isEqualTo('Étrange et curieux-À vous !');

        // No /, no spaces
        $this
            ->string(\text::tidyURL('Étrange et curieux/=À vous !', false, false))
            ->isEqualTo('Étrange-et-curieux-À-vous-!');
    }

    public function testcutString()
    {
        $faker = Faker\Factory::create();
        $text  = $faker->realText(400);
        $this
            ->string(\text::cutString($text, 200))
            ->hasLengthLessThan(201);

        $this
            ->string(\text::cutString('https:-domaincom-AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss-eehtml', 20))
            ->isIdenticalTo('https:-domaincom-AAA');


        $this
            ->string(\text::cutString('https domaincom AAAECDEEIINOOOOESUUYZaaaecdeeiinooooesuuyyzss eehtml', 20))
            ->isIdenticalTo('https domaincom');
    }
}
