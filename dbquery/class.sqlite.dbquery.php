<?php
/**
 * @class sqliteQuery
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

    class sqliteQuery extends dbQuery
    {
        public function surround($identifier)
        {
            return "\"$identifier\"";
        }
    }

/** @cond ONCE */
}
/** @endcond */
