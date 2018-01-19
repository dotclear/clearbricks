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

require_once str_replace('tests/unit/', '', __FILE__);

use atoum;

class formSelectOption extends atoum
{

	public function testOption()
	{
		$option = new \formSelectOption('un',1,'classme','data-test="This Is A Test"');

		$this
			->string($option->render(0))
			->match('/<option.*?<\/option>/')
			->match('/<option\svalue="1".*?>un<\/option>/');

		$this
			->string($option->render(1))
			->match('/<option.*?<\/option>/')
			->match('/<option.*?value="1".*?>un<\/option>/')
			->match('/<option.*?selected="selected".*?>un<\/option>/');
	}

	public function testOptionOpt()
	{
		$option = new \formSelectOption('deux',2);

		$this
			->string($option->render(0))
			->match('/<option.*?<\/option>/')
			->match('/<option\svalue="2".*?>deux<\/option>/');

		$this
			->string($option->render(2))
			->match('/<option.*?<\/option>/')
			->match('/<option.*?value="2".*?>deux<\/option>/')
			->match('/<option.*?selected="selected".*?>deux<\/option>/');
	}
}

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
			->string(\form::combo('testID', array(
				new \formSelectOption('Un',1),
				new \formSelectOption('Deux',2))))
			->match('/<option.*?<\/option>/')
			->match('/<option\svalue="2">Deux<\/option>/');

		$this
			->string(\form::combo(array('aName', 'anID'), array()))
			->contains('name="aName"')
			->contains('id="anID"');

		$this
			->string(\form::combo('testID', array('onetwo' => array('one' => 'one', 'two' => 'two'))))
			->match('#<optgroup\slabel="onetwo">#')
			->match('#<option\svalue="one">one<\/option>#')
			->contains('</optgroup');

		$this
			->string(\form::combo('testID', array(), array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
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

		$this
			->string(\form::radio('testID', 'testvalue', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
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

		$this
			->string(\form::checkbox('testID', 'testvalue', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	public function testField()
	{
		$this
			->string(\form::field('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="text"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="testvalue"')
			->contains('required');

		$this
			->string(\form::field(array('aName', 'testID'), 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::field('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::field('testID', 10, 20, array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	public function testPassword()
	{
		$this
			->string(\form::password('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="password"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="testvalue"')
			->contains('required');

		$this
			->string(\form::password(array('aName', 'testID'), 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::password('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::password('testID', 10, 20, array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create a color input field
	 */
	public function testColor()
	{
		$this
			->string(\form::color('testID', 10, 20, '#f369a3', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="color"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="#f369a3"')
			->contains('required');

		$this
			->string(\form::color(array('aName', 'testID'), 10, 20, '#f369a3', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::color('testID', 10, 20, '#f369a3', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::color('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('size="7"')
			->contains('maxlength="7"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create an email input field
	 */
	public function testEmail()
	{
		$this
			->string(\form::email('testID', 10, 20, 'me@example.com', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="email"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="me@example.com"')
			->contains('required');

		$this
			->string(\form::email(array('aName', 'testID'), 10, 20, 'me@example.com', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::email('testID', 10, 20, 'me@example.com', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::email('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create an URL input field
	 */
	public function testUrl()
	{
		$this
			->string(\form::url('testID', 10, 20, 'https://example.com/', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="url"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="https://example.com/"')
			->contains('required');

		$this
			->string(\form::url(array('aName', 'testID'), 10, 20, 'https://example.com/', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::url('testID', 10, 20, 'https://example.com/', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::url('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create a datetime (local) input field
	 */
	public function testDatetime()
	{
		$this
			->string(\form::datetime('testID', 10, 20, '1962-05-13T02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="datetime-local"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="1962-05-13T02:15"')
			->contains('required')
			->contains('pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}')
			->contains('placeholder="1962-05-13T02:15"');

		$this
			->string(\form::datetime(array('aName', 'testID'), 10, 20, '1962-05-13T02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::datetime('testID', 10, 20, '1962-05-13T02:15', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::datetime('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create a date input field
	 */
	public function testDate()
	{
		$this
			->string(\form::date('testID', 10, 20, '1962-05-13', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="date"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="1962-05-13"')
			->contains('required')
			->contains('pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}')
			->contains('placeholder="1962-05-13"');

		$this
			->string(\form::date(array('aName', 'testID'), 10, 20, '1962-05-13', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::date('testID', 10, 20, '1962-05-13', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::date('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create a datetime (local) input field
	 */
	public function testTime()
	{
		$this
			->string(\form::time('testID', 10, 20, '02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="time"')
			->contains('size="10"')
			->contains('maxlength="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="02:15"')
			->contains('required')
			->contains('pattern="[0-9]{2}:[0-9]{2}')
			->contains('placeholder="02:15"');

		$this
			->string(\form::time(array('aName', 'testID'), 10, 20, '02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::time('testID', 10, 20, '02:15', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::time('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	/**
	 * Create a file input field
	 */
	public function testFile()
	{
		$this
			->string(\form::file('testID', 'filename.ext', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="file"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="filename.ext"')
			->contains('required');

		$this
			->string(\form::file(array('aName', 'testID'), 'filename.ext', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::file('testID', 'filename.ext', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::file('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	public function testNumber()
	{
		$this
			->string(\form::number('testID', 0, 99, 13, 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('type="number"')
			->contains('min="0"')
			->contains('max="99"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('value="13"')
			->contains('required');

		$this
			->string(\form::number(array('aName', 'testID'), 0, 99, 13, 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::number('testID', 0, 99, 13, 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::number('testID', array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->notContains('min=')
			->notContains('max=')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
	}

	public function testTextArea()
	{
		$this
			->string(\form::textArea('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->match('#<textarea.*?testvalue.*?<\/textarea>#s')
			->contains('cols="10"')
			->contains('rows="20"')
			->contains('name="testID"')
			->contains('id="testID"')
			->contains('class="aclassname"')
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"')
			->contains('data-test="A test"')
			->contains('required');

		$this
			->string(\form::textArea(array('aName', 'testID'), 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true))
			->contains('name="aName"')
			->contains('id="testID"');

		$this
			->string(\form::textArea('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"', true))
			->notContains('disabled');

		$this
			->string(\form::textArea('testID', 10, 20, array(
				'tabindex' => 'atabindex',
				'disabled' => true,
			)))
			->contains('tabindex="atabindex"')
			->contains('disabled="disabled"');
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
