<?php
/**
 * @class mysqlQuery
 *
 * @package Clearbricks
 * @subpackage DBQuery
 *
 * @copyright Franck Paul & Association Dotclear
 * @copyright GPL-2.0-only
 */

/** @cond ONCE */
if (class_exists('dbQuery')) {
/** @endcond */

    class mysqlQuery extends dbQuery implements dbQueryStatement
    {
        public function surround($identifier)
        {
            return "`$identifier`";
        }
    }

/** @cond ONCE */
}
/** @endcond */