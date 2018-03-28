<?php
/**
 * @class mysqliQuery
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

    class mysqliQuery extends dbQuery implements dbQueryStatement
    {
        public function surround($identifier)
        {
            return "`$identifier`";
        }
    }

/** @cond ONCE */
}
/** @endcond */
