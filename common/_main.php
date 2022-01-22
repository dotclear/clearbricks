<?php
/**
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('CLEARBRICKS_VERSION', '1.2');

# Autoload
$__autoload = [
    'crypt'             => __DIR__ . '/lib.crypt.php',
    'dt'                => __DIR__ . '/lib.date.php',
    'files'             => __DIR__ . '/lib.files.php',
    'path'              => __DIR__ . '/lib.files.php',
    'form'              => __DIR__ . '/lib.form.php',
    'formSelectOption'  => __DIR__ . '/lib.form.php',
    'forms'             => __DIR__ . '/lib.forms.php',
    'formsSelectOption' => __DIR__ . '/lib.forms.php',
    'html'              => __DIR__ . '/lib.html.php',
    'http'              => __DIR__ . '/lib.http.php',
    'text'              => __DIR__ . '/lib.text.php',
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

# We only need l10n __() function
require_once __DIR__ . '/lib.l10n.php';

# We set default timezone to avoid warning
dt::setTZ('UTC');
