<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.form.php';
require_once CLEARBRICKS_PATH . '/common/lib.forms.php';

use atoum;

class formsSelectOption extends atoum
{
    public function testOption()
    {
        $option = new \formsSelectOption([
            'name'  => 'un',
            'value' => 1,
            'class' => 'classme',
            'extra' => 'data-test="This Is A Test"'
        ]);

        $this
            ->string($option->render(0))
            ->match('/<option.*?<\/option>/')
            ->match('/<option\svalue="1".*?>un<\/option>/');

        $this
            ->string($option->render(1))
            ->match('/<option.*?<\/option>/')
            ->match('/<option.*?value="1".*?>un<\/option>/')
            ->match('/<option.*?selected.*?>un<\/option>/');
    }

    public function testOptionOpt()
    {
        $option = new \formsSelectOption([
            'name'  => 'deux',
            'value' => 2
        ]);

        $this
            ->string($option->render(0))
            ->match('/<option.*?<\/option>/')
            ->match('/<option\svalue="2".*?>deux<\/option>/');

        $this
            ->string($option->render(2))
            ->match('/<option.*?<\/option>/')
            ->match('/<option.*?value="2".*?>deux<\/option>/')
            ->match('/<option.*?selected.*?>deux<\/option>/');
    }
}

/**
 * Test the form class.
 * formSelectOptions is implicitly tested with testCombo
 */
class forms extends atoum
{
    /**
     * Create a combo (select)
     */
    public function testCombo()
    {
        $this
            ->string(\forms::combo([]))
            ->isEqualTo('');

        $this
            ->string(\forms::combo(
                [
                    'name'     => 'testID',
                    'items'    => [],
                    'tabindex' => 1,
                    'class'    => 'classme',
                    'disabled' => true,
                    'data'     => ['test' => 'This Is A Test']
                ]
            ))
            ->contains('<select')
            ->contains('</select>')
            ->contains('class="classme"')
            ->contains('id="testID"')
            ->contains('name="testID"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="This Is A Test"');

        $this
            ->string(\forms::combo([
                'name'     => 'testID',
                'items'    => [],
                'tabindex' => 1,
                'class'    => 'classme',
                'data'     => ['test' => 'This Is A Test']
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::combo([
                'name'    => 'testID',
                'items'   => ['one', 'two', 'three'],
                'default' => 'one',
                'class'   => 'classme',
                'data'    => ['test' => 'This Is A Test']
            ]))
            ->match('/<option.*?<\/option>/')
            ->match('/<option\svalue="one"\sselected.*?<\/option>/');

        $this
            ->string(\forms::combo([
                'name'  => 'testID',
                'items' => [
                    new \formSelectOption('Un', 1),
                    new \formSelectOption('Deux', 2)],
                'default' => 'one',
                'class'   => 'classme',
                'data'    => ['test' => 'This Is A Test']
            ]))
            ->match('/<option.*?<\/option>/')
            ->match('/<option\svalue="2">Deux<\/option>/');

        $this
            ->string(\forms::combo([
                'name'  => 'testID',
                'items' => [
                    new \formsSelectOption([
                        'name'  => 'Un',
                        'value' => 1
                    ]),
                    new \formsSelectOption([
                        'name'  => 'Deux',
                        'value' => 2
                    ])
                ],
                'default' => 'one',
                'class'   => 'classme',
                'data'    => ['test' => 'This Is A Test']
            ]))
            ->match('/<option.*?<\/option>/')
            ->match('/<option\svalue="2">Deux<\/option>/');

        $this
            ->string(\forms::combo([
                'name' => 'aName',
                'id'   => 'anID'
            ]))
            ->contains('name="aName"')
            ->contains('id="anID"');

        $this
            ->string(\forms::combo([
                'id' => 'anID'
            ]))
            ->contains('name="anID"')
            ->contains('id="anID"');

        $this
            ->string(\forms::combo([
                'name'  => 'testID',
                'id'    => 'testID',
                'items' => ['onetwo' => ['one' => 'one', 'two' => 'two']]
            ]))
            ->match('#<optgroup\slabel="onetwo">#')
            ->match('#<option\svalue="one">one<\/option>#')
            ->contains('</optgroup');

        $this
            ->string(\forms::combo([
                'name'     => 'testID',
                'id'       => 'testID',
                'items'    => [],
                'tabindex' => 'atabindex',
                'disabled' => true
            ]))
            ->contains('tabindex="0"')
            ->contains('disabled');
    }

    /** Test for <input type="radio"
     */
    public function testRadio()
    {
        $this
            ->string(\forms::radio([]))
            ->isEqualTo('');

        $this
            ->string(\forms::radio([
                'name'     => 'testID',
                'value'    => 'testvalue',
                'checked'  => true,
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => true,
                'data'     => ['test' => 'A test']
            ]))
            ->contains('type="radio"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('value="testvalue"')
            ->contains('checked')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"');

        $this
            ->string(\forms::radio([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::radio([
                'name'     => 'testID',
                'value'    => 'testvalue',
                'checked'  => true,
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => false,
                'data'     => ['test' => 'A test']
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::radio([
                'name'     => 'testID',
                'value'    => 'testvalue',
                'checked'  => false,
                'class'    => 'aclassname',
                'tabindex' => null,
                'disabled' => true,
                'data'     => ['test' => 'A test']
            ]))
            ->notContains('checked')
            ->contains('tabindex="0"')
            ->contains('disabled');
    }

    /** Test for <input type="checkbox"
     */
    public function testCheckbox()
    {
        $this
            ->string(\forms::checkbox([]))
            ->isEqualTo('');

        $this
            ->string(\forms::checkbox([
                'name'     => 'testID',
                'value'    => 'testvalue',
                'checked'  => true,
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => true,
                'data'     => ['test' => 'A test']
            ]))
            ->contains('type="checkbox"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('checked')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"');

        $this
            ->string(\forms::checkbox([
                'name'     => 'aName',
                'id'       => 'testID',
                'value'    => 'testvalue',
                'checked'  => true,
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => false,
                'data'     => ['test' => 'A test']
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::checkbox([
                'name'     => 'aName',
                'id'       => 'testID',
                'value'    => 'testvalue',
                'checked'  => true,
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => false,
                'data'     => ['test' => 'A test']
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::checkbox([
                'name'     => 'aName',
                'id'       => 'testID',
                'value'    => 'testvalue',
                'checked'  => true,
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => true,
                'data'     => ['test' => 'A test']
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    public function testField()
    {
        $this
            ->string(\forms::field([]))
            ->isEqualTo('');

        $this
            ->string(\forms::field([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="text"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="testvalue"')
            ->contains('required');

        $this
            ->string(\forms::field([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="text"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="testvalue"')
            ->contains('required');

        $this
            ->string(\forms::field([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'default'   => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test1' => 'A test', 'test2' => 2, 'test3' => true],
                'required'  => true
            ]))
            ->contains('type="text"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test1="A test"')
            ->contains('data-test2="2"')
            ->contains('data-test3="1"')
            ->contains('value="testvalue"')
            ->contains('required');

        $this
            ->string(\forms::field([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::field([
                'name'     => 'aName',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::field([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'tabindex'  => '1',
                'disabled'  => true,
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    public function testPassword()
    {
        $this
            ->string(\forms::password([]))
            ->isEqualTo('');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="password"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="testvalue"')
            ->contains('required');

        $this
            ->string(\forms::password([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => false,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => 0,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => null,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('disabled');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => 1,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('disabled');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => -1,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('disabled');

        $this
            ->string(\forms::password([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'testvalue',
                'class'     => 'aclassname',
                'tabindex'  => '1',
                'disabled'  => 2,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('disabled');

        $this
            ->string(\forms::password([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'tabindex'  => 1,
                'disabled'  => true,
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create a color input field
     */
    public function testColor()
    {
        $this
            ->string(\forms::color([]))
            ->isEqualTo('');

        $this
            ->string(\forms::color([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => '#f369a3',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="color"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="#f369a3"')
            ->contains('required');

        $this
            ->string(\forms::color([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::color([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => '#f369a3',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => false,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::color([
                'id'       => 'testID',
                'tabindex' => 1,
                'disabled' => true
            ]))
            ->contains('size="7"')
            ->contains('maxlength="7"')
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create an email input field
     */
    public function testEmail()
    {
        $this
            ->string(\forms::email([]))
            ->isEqualTo('');

        $this
            ->string(\forms::email([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'me@example.com',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="email"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="me@example.com"')
            ->contains('required');

        $this
            ->string(\forms::email([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::email([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'me@example.com',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => false,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::email([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'me@example.com',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create an URL input field
     */
    public function testUrl()
    {
        $this
            ->string(\forms::url([]))
            ->isEqualTo('');

        $this
            ->string(\forms::url([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'https://example.com/',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="url"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="https://example.com/"')
            ->contains('required');

        $this
            ->string(\forms::url([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::url([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => 'https://example.com/',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => false,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::url([
                'id'       => 'testID',
                'tabindex' => 1,
                'disabled' => true,
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create a datetime (local) input field
     */
    public function testDatetime()
    {
        $this
            ->string(\forms::datetime([]))
            ->isEqualTo('');

        $this
            ->string(\forms::datetime([
                'name'      => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => '1962-05-13T02:15',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="datetime-local"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="1962-05-13T02:15"')
            ->contains('required')
            ->contains('pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}')
            ->contains('placeholder="1962-05-13T14:45"');

        $this
            ->string(\forms::datetime([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"')
            ->contains('size="16"')
            ->contains('maxlength="16"');

        $this
            ->string(\forms::datetime([
                'id'       => 'testID',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::datetime([
                'id' => 'testID'
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::datetime([
                'id'       => 'testID',
                'tabindex' => 1,
                'disabled' => true,
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create a date input field
     */
    public function testDate()
    {
        $this
            ->string(\forms::date([]))
            ->isEqualTo('');

        $this
            ->string(\forms::date([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => '1962-05-13',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="date"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="1962-05-13"')
            ->contains('required')
            ->contains('pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}')
            ->contains('placeholder="1962-05-13"');

        $this
            ->string(\forms::date([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"')
            ->contains('size="10"')
            ->contains('maxlength="10"');

        $this
            ->string(\forms::date([
                'id'       => 'testID',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::date([
                'id'       => 'testID',
                'disabled' => 0
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::date([
                'id'       => 'testID',
                'disabled' => null
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::date([
                'id' => 'testID'
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::date([
                'id'       => 'testID',
                'tabindex' => 1,
                'disabled' => true
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create a datetime (local) input field
     */
    public function testTime()
    {
        $this
            ->string(\forms::time([]))
            ->isEqualTo('');

        $this
            ->string(\forms::time([
                'id'        => 'testID',
                'size'      => 10,
                'maxlength' => 20,
                'value'     => '02:15',
                'class'     => 'aclassname',
                'tabindex'  => 1,
                'disabled'  => true,
                'data'      => ['test' => 'A test'],
                'required'  => true
            ]))
            ->contains('type="time"')
            ->contains('size="10"')
            ->contains('maxlength="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="02:15"')
            ->contains('required')
            ->contains('pattern="[0-9]{2}:[0-9]{2}')
            ->contains('placeholder="14:45"');

        $this
            ->string(\forms::time([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"')
            ->contains('size="5"')
            ->contains('maxlength="5"');

        $this
            ->string(\forms::time([
                'id'       => 'testID',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::time([
                'id'       => 'testID',
                'tabindex' => '1',
                'disabled' => true
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    /**
     * Create a file input field
     */
    public function testFile()
    {
        $this
            ->string(\forms::file([]))
            ->isEqualTo('');

        $this
            ->string(\forms::file([
                'name'     => 'testID',
                'value'    => 'filename.ext',
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => true,
                'data'     => ['test' => 'A test'],
                'required' => true
            ]))
            ->contains('type="file"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="filename.ext"')
            ->contains('required');

        $this
            ->string(\forms::file([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::file([
                'id'       => 'testID',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::file([
                'id'       => 'testID',
                'tabindex' => 0,
                'disabled' => true,
            ]))
            ->contains('tabindex="0"')
            ->contains('disabled');
    }

    public function testNumber()
    {
        $this
            ->string(\forms::number([]))
            ->isEqualTo('');

        $this
            ->string(\forms::number([
                'id'       => 'testID',
                'min'      => 0,
                'max'      => 99,
                'value'    => 13,
                'class'    => 'aclassname',
                'tabindex' => 'atabindex',
                'disabled' => true,
                'data'     => ['test' => 'A test'],
                'required' => true
            ]))
            ->contains('type="number"')
            ->contains('min="0"')
            ->contains('max="99"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="0"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('value="13"')
            ->contains('required');

        $this
            ->string(\forms::number([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::number([
                'id'       => 'testID',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::number([
                'id'       => 'testID',
                'tabindex' => 1,
                'disabled' => true,
            ]))
            ->notContains('min=')
            ->notContains('max=')
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    public function testTextArea()
    {
        $this
            ->string(\forms::textArea([]))
            ->isEqualTo('');

        $this
            ->string(\forms::textArea([
                'id'       => 'testID',
                'cols'     => 10,
                'rows'     => 20,
                'value'    => 'testvalue',
                'class'    => 'aclassname',
                'tabindex' => 1,
                'disabled' => true,
                'data'     => ['test' => 'A test'],
                'required' => true
            ]))
            ->match('#<textarea.*?testvalue.*?<\/textarea>#s')
            ->contains('cols="10"')
            ->contains('rows="20"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('class="aclassname"')
            ->contains('tabindex="1"')
            ->contains('disabled')
            ->contains('data-test="A test"')
            ->contains('required');

        $this
            ->string(\forms::textArea([
                'name' => 'aName',
                'id'   => 'testID'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');

        $this
            ->string(\forms::textArea([
                'id'       => 'testID',
                'disabled' => false
            ]))
            ->notContains('disabled');

        $this
            ->string(\forms::textArea([
                'id'       => 'testID',
                'tabindex' => true,
                'disabled' => true,
            ]))
            ->contains('tabindex="1"')
            ->contains('disabled');
    }

    public function testHidden()
    {
        $this
            ->string(\forms::hidden([]))
            ->isEqualTo('');

        $this
            ->string(\forms::hidden([
                'id'    => 'testID',
                'value' => 'testvalue'
            ]))
            ->contains('type="hidden"')
            ->contains('name="testID"')
            ->contains('id="testID"')
            ->contains('value="testvalue"');

        $this
            ->string(\forms::hidden([
                'name'  => 'aName',
                'id'    => 'testID',
                'value' => 'testvalue'
            ]))
            ->contains('name="aName"')
            ->contains('id="testID"');
    }
}
