<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Clearbricks.
#
# Copyright (c) Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

/**
 * @package Clearbricks
 * @subpackage Common
 */

define('CLEARBRICKS_VERSION', '1.0');

# Autoload
$__autoload = array(
    'crypt'            => dirname(__FILE__) . '/lib.crypt.php',
    'dt'               => dirname(__FILE__) . '/lib.date.php',
    'files'            => dirname(__FILE__) . '/lib.files.php',
    'path'             => dirname(__FILE__) . '/lib.files.php',
    'form'             => dirname(__FILE__) . '/lib.form.php',
    'formSelectOption' => dirname(__FILE__) . '/lib.form.php',
    'html'             => dirname(__FILE__) . '/lib.html.php',
    'http'             => dirname(__FILE__) . '/lib.http.php',
    'text'             => dirname(__FILE__) . '/lib.text.php'
);

# autoload for clearbricks
function cb_autoload($name)
{
    global $__autoload;

    if (isset($__autoload[$name])) {
        require_once $__autoload[$name];
    }
}
spl_autoload_register("cb_autoload");

# We only need l10n __() function
require_once dirname(__FILE__) . '/lib.l10n.php';

# We set default timezone to avoid warning
dt::setTZ('UTC');

# JSON functions for PHP < 5.2 or PHP > 5.2 compiling without json
require_once dirname(__FILE__) . '/lib.json.php';
