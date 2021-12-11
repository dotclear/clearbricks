<?php
/**
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

define('CLEARBRICKS_VERSION', '1.2');

# By default Keep compatibility with clearbricks < 2.0
if (!defined('CLEARBRICKS_LEGACY')) {
    define('CLEARBRICKS_LEGACY', true);
}

# Define CLEARBRICKS_AUTOLOAD as false to use your own autoloader
if (!defined('CLEARBRICKS_AUTOLOAD')) {
    define('CLEARBRICKS_AUTOLOAD', true);
}
//*
# Legacy common class
if (CLEARBRICKS_LEGACY === true) {
    $__autoload = [
        'l10n'              => dirname(__FILE__) . '/L10n.php',
        'crypt'             => dirname(__FILE__) . '/Crypt.php',
        'dt'                => dirname(__FILE__) . '/Dt.php',
        'files'             => dirname(__FILE__) . '/Files.php',
        'path'              => dirname(__FILE__) . '/Path.php',
        'form'              => dirname(__FILE__) . '/Form.php',
        'formSelectOption'  => dirname(__FILE__) . '/FormSelectOption.php',
        'forms'             => dirname(__FILE__) . '/Forms.php',
        'formsSelectOption' => dirname(__FILE__) . '/FormsSelectOption.php',
        'html'              => dirname(__FILE__) . '/Html.php',
        'http'              => dirname(__FILE__) . '/Http.php',
        'text'              => dirname(__FILE__) . '/Text.php',
    ];
}
//*/
# Instanciate Clearbricks PSR-4 autoloader
if (CLEARBRICKS_AUTOLOAD === true) {
    require_once dirname(__FILE__) . '/Autoloader.php';

    $clearbricks_autoloader = new Clearbricks\Common\Autoloader();
    $clearbricks_autoloader->addNamespace('Clearbricks', dirname(__FILE__) . '/../');
}

# We only need l10n __() function
require_once dirname(__FILE__) . '/L10n.php';

# We set default timezone to avoid warning
Clearbricks\Common\Dt::setTZ('UTC');

# Legacy autoloader stack
if (!isset($__autoload) || !is_array($__autoload)) {
    $__autoload = [];
}

# We always offer Legacy autoloader
function cb_autoload($name)
{
    global $__autoload;

    if (isset($__autoload[$name])) {
        require_once $__autoload[$name];
    }
}
spl_autoload_register('cb_autoload');
