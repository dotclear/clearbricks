<?php
/**
 * @class mysqlConnection
 * @brief MySQL Database Driver
 *
 * See the {@link dbLayer} documentation for common methods.
 *
 * @package Clearbricks
 * @subpackage DBLayer
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

/** @cond ONCE */
if (class_exists('dbLayer')) {
/** @endcond */

    class mysqlConnection extends dbLayer implements i_dbLayer
    {
        public static $weak_locks = false; ///< boolean: Enables weak locks if true

        protected $__driver = 'mysql';
        protected $__syntax = 'mysql';

        public function db_connect($host, $user, $password, $database)
        {
            if (!function_exists('mysql_connect')) {
                throw new Exception('PHP MySQL functions are not available');
            }

            if (($link = @mysql_connect($host, $user, $password, true)) === false) {
                throw new Exception('Unable to connect to database');
            }

            $this->db_post_connect($link, $database);

            return $link;
        }

        public function db_pconnect($host, $user, $password, $database)
        {
            if (!function_exists('mysql_pconnect')) {
                throw new Exception('PHP MySQL functions are not available');
            }

            if (($link = @mysql_pconnect($host, $user, $password)) === false) {
                throw new Exception('Unable to connect to database');
            }

            $this->db_post_connect($link, $database);

            return $link;
        }

        private function db_post_connect($link, $database)
        {
            if (@mysql_select_db($database, $link) === false) {
                throw new Exception('Unable to use database ' . $database);
            }

            if (version_compare($this->db_version($link), '4.1', '>=')) {
                $this->db_query($link, 'SET NAMES utf8');
                $this->db_query($link, 'SET CHARACTER SET utf8');
                $this->db_query($link, "SET COLLATION_CONNECTION = 'utf8_general_ci'");
                $this->db_query($link, "SET COLLATION_SERVER = 'utf8_general_ci'");
                $this->db_query($link, "SET CHARACTER_SET_SERVER = 'utf8'");
                $this->db_query($link, "SET CHARACTER_SET_DATABASE = 'utf8'");
            }
        }

        public function db_close($handle)
        {
            if (is_resource($handle)) {
                mysql_close($handle);
            }
        }

        public function db_version($handle)
        {
            if (is_resource($handle)) {
                return mysql_get_server_info();
            }
            return;
        }

        public function db_query($handle, $query)
        {
            if (is_resource($handle)) {
                $res = @mysql_query($query, $handle);
                if ($res === false) {
                    $e      = new Exception($this->db_last_error($handle));
                    $e->sql = $query;
                    throw $e;
                }
                return $res;
            }
        }

        public function db_exec($handle, $query)
        {
            return $this->db_query($handle, $query);
        }

        public function db_num_fields($res)
        {
            if (is_resource($res)) {
                return mysql_num_fields($res);
            }
            return 0;
        }

        public function db_num_rows($res)
        {
            if (is_resource($res)) {
                return mysql_num_rows($res);
            }
            return 0;
        }

        public function db_field_name($res, $position)
        {
            if (is_resource($res)) {
                return mysql_field_name($res, $position);
            }
        }

        public function db_field_type($res, $position)
        {
            if (is_resource($res)) {
                return mysql_field_type($res, $position);
            }
        }

        public function db_fetch_assoc($res)
        {
            if (is_resource($res)) {
                return mysql_fetch_assoc($res);
            }
        }

        public function db_result_seek($res, $row)
        {
            if (is_resource($res)) {
                return mysql_data_seek($res, $row);
            }
        }

        public function db_changes($handle, $res)
        {
            if (is_resource($handle)) {
                return mysql_affected_rows($handle);
            }
        }

        public function db_last_error($handle)
        {
            if (is_resource($handle)) {
                $e = mysql_error($handle);
                if ($e) {
                    return $e . ' (' . mysql_errno($handle) . ')';
                }
            }
            return false;
        }

        public function db_escape_string($str, $handle = null)
        {
            if (is_resource($handle)) {
                return mysql_real_escape_string($str, $handle);
            } else {
                return mysql_escape_string($str);
            }
        }

        public function db_write_lock($table)
        {
            try {
                $this->execute('LOCK TABLES ' . $this->escapeSystem($table) . ' WRITE');
            } catch (Exception $e) {
                # As lock is a privilege in MySQL, we can avoid errors with weak_locks static var
                if (!self::$weak_locks) {
                    throw $e;
                }
            }
        }

        public function db_unlock()
        {
            try {
                $this->execute('UNLOCK TABLES');
            } catch (Exception $e) {
                if (!self::$weak_locks) {
                    throw $e;
                }
            }
        }

        public function vacuum($table)
        {
            $this->execute('OPTIMIZE TABLE ' . $this->escapeSystem($table));
        }

        public function dateFormat($field, $pattern)
        {
            $pattern = str_replace('%M', '%i', $pattern);

            return 'DATE_FORMAT(' . $field . ',' . "'" . $this->escape($pattern) . "') ";
        }

        public function orderBy()
        {
            $default = [
                'order'   => '',
                'collate' => false
            ];
            foreach (func_get_args() as $v) {
                if (is_string($v)) {
                    $res[] = $v;
                } elseif (is_array($v) && !empty($v['field'])) {
                    $v          = array_merge($default, $v);
                    $v['order'] = (strtoupper($v['order']) == 'DESC' ? 'DESC' : '');
                    $res[]      = $v['field'] . ($v['collate'] ? ' COLLATE utf8_unicode_ci' : '') . ' ' . $v['order'];
                }
            }
            return empty($res) ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
        }

        public function lexFields()
        {
            $fmt = '%s COLLATE utf8_unicode_ci';
            foreach (func_get_args() as $v) {
                if (is_string($v)) {
                    $res[] = sprintf($fmt, $v);
                } elseif (is_array($v)) {
                    $res = array_map(function ($i) use ($fmt) {return sprintf($fmt, $i);}, $v);
                }
            }
            return empty($res) ? '' : implode(',', $res);
        }

        public function concat()
        {
            $args = func_get_args();
            return 'CONCAT(' . implode(',', $args) . ')';
        }

        public function escapeSystem($str)
        {
            return '`' . $str . '`';
        }
    }

/** @cond ONCE */
}
/** @endcond */
