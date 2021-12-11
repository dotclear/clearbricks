<?php
/**
 * @package Clearbricks
 *
 * Tiny library including:
 * - Database abstraction layer (MySQL/MariadDB, postgreSQL and SQLite)
 * - File manager
 * - Feed reader
 * - HTML filter/validator
 * - Images manipulation tools
 * - Mail utilities
 * - HTML pager
 * - REST Server
 * - Database driven session handler
 * - Simple Template Systeme
 * - URL Handler
 * - Wiki to XHTML Converter
 * - HTTP/NNTP clients
 * - XML-RPC Client and Server
 * - Zip tools
 * - Diff tools
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 * @version 1.0
 */

namespace Clearbricks;

define('CLEARBRICKS_VERSION', '1.2');

// Define CLEARBRICKS_NO_AUTOLOAD as True to use your own autoloader
if (!defined('CLEARBRICKS_NO_AUTOLOAD') || CLEARBRICKS_NO_AUTOLOAD !== true) {
    require dirname(__FILE__) . '/src/Common/Autoloader.php';

    $clearbricks_autoloader = new Common\Autoloader();
    $clearbricks_autoloader->addNamespace('Clearbricks', dirname(__FILE__) . '/src');
}

// By default Keep compatibility with clearbricks < 2.0
if (!defined('CLEARBRICKS_NO_COMPAT') || CLEARBRICKS_NO_COMPAT !== true) {
    require dirname(__FILE__) . '/_legacy.php';
}

# We only need l10n __() function
require_once dirname(__FILE__) . '/src/Common/L10n.php';

# We set default timezone to avoid warning
Common\Dt::setTZ('UTC');
