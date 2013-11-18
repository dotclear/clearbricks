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

require_once CLEARBRICKS_PATH . '/common/lib.form.php';

use atoum;

/**
 * Test the form class.
 * formSelectOptions is implicitly tested with testCombo
 */
class form extends atoum
{
    /**
     * Create a combo (select)
     */
    public function testCombo()
    {
        $this
            ->string(\form::combo('testID', array(), '', 'classme', 'atabindex', true, 'data-test="This Is A Test"'))
            ->contains('<select')
            ->contains('</select>')
            ->contains('class="classme"')
            ->contains('id="testID"')
            ->contains('name="testID"')
            ->contains('tabindex="atabindex"')
            ->contains('disabled="disabled"')
            ->contains('data-test="This Is A Test"');

        $this
            ->string(\form::combo('testID', array(), '', 'classme', 'atabindex', false, 'data-test="This Is A Test"'))
            ->notContains('disabled');

        $this
            ->string(\form::combo('testID', array('one', 'two', 'three'), 'one'))
            ->match('/<option.*?<\/option>/')
            ->match('/<option\svalue="one"\sselected="selected".*?<\/option>/');

        $this
            ->string(\form::combo(array('aName','anID'), array()))
            ->contains('name="aName"')
            ->contains('id="anID"');

        $this
            ->string(\form::combo('testID', array('onetwo' => array('one' => 'one', 'two' => 'two'))))
            ->match('#<optgroup\slabel="onetwo">#')
            ->match('#<option\svalue="one">one<\/option>#')
            ->contains('</optgroup');
    }

    /** Test for <input type="radio"
     */
    public function testRadio()
    {
        $this
            ->string(\form::radio('testID', 'testvalue', true, 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('type="radio"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('checked="checked"')
            ->contains('class="aclassname"')
            ->contains('tabindex="atabindex"')
            ->contains('disabled="disabled"')
            ->contains('data-test="A test"');

        $this
            ->string(\form::radio(array('aName', 'testID'), 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\form::radio('testID', 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->notContains('disabled');
    }

    /** Test for <input type="checkbox"
     */
    public function testCheckbox()
    {
        $this
            ->string(\form::checkbox('testID', 'testvalue', true, 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('type="checkbox"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('checked="checked"')
            ->contains('class="aclassname"')
            ->contains('tabindex="atabindex"')
            ->contains('disabled="disabled"')
            ->contains('data-test="A test"');

        $this
            ->string(\form::checkbox(array('aName', 'testID'), 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\form::checkbox('testID', 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->notContains('disabled');
    }

    public function testField()
    {
        $this
            ->string(\form::field('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('type="text"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="atabindex"')
            ->contains('disabled="disabled"')
            ->contains('data-test="A test"')
            ->contains('value="testvalue"');

        $this
            ->string(\form::field(array('aName', 'testID'), 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\form::field('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->notContains('disabled');
    }

    public function testPassword()
    {
        $this
            ->string(\form::password('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('type="password"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="atabindex"')
            ->contains('disabled="disabled"')
            ->contains('data-test="A test"')
            ->contains('value="testvalue"');

        $this
            ->string(\form::password(array('aName', 'testID'), 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\form::password('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->notContains('disabled');
    }

    public function testTextArea()
    {
        $this
            ->string(\form::textArea('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->match('#<textarea.*?testvalue.*?<\/textarea>#')
            ->contains('cols="10"')
            ->contains('rows="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="atabindex"')
            ->contains('disabled="disabled"')
            ->contains('data-test="A test"');

        $this
            ->string(\form::textArea(array('aName', 'testID'), 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"'))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\form::textArea('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"'))
            ->notContains('disabled');
    }

    public function testHidden()
    {
        $this
            ->string(\form::hidden('testID', 'testvalue'))
            ->contains('type="hidden"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('value="testvalue"');

        $this
            ->string(\form::hidden(array('aName', 'testID'), 'testvalue'))
            ->contains('name="aName"')
            ->contains('id="testID"');
    }
}

