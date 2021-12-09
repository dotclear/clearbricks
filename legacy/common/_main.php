<?php
/**
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

# Autoload
$__autoload = [
    'l10n'              => dirname(__FILE__) . '/lib.l10n.php',
    'crypt'             => dirname(__FILE__) . '/lib.crypt.php',
    'dt'                => dirname(__FILE__) . '/lib.date.php',
    'files'             => dirname(__FILE__) . '/lib.files.php',
    'path'              => dirname(__FILE__) . '/lib.files.php',
    'form'              => dirname(__FILE__) . '/lib.form.php',
    'formSelectOption'  => dirname(__FILE__) . '/lib.form.php',
    'forms'             => dirname(__FILE__) . '/lib.forms.php',
    'formsSelectOption' => dirname(__FILE__) . '/lib.forms.php',
    'html'              => dirname(__FILE__) . '/lib.html.php',
    'http'              => dirname(__FILE__) . '/lib.http.php',
    'text'              => dirname(__FILE__) . '/lib.text.php'
];

# autoload for clearbricks
function cb_autoload($name)
{
    global $__autoload;

    if (isset($__autoload[$name])) {
        require_once $__autoload[$name];
    }
}
spl_autoload_register('cb_autoload');
