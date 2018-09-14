<?php
/**
 * @class mysqlimb4Connection
 * @brief MySQLi utf8-mb4 Database Driver
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

    class mysqlimb4Connection extends dbLayer implements i_dbLayer
    {
        public static $weak_locks = true; ///< boolean: Enables weak locks if true

        protected $__driver = 'mysqlimb4';
        protected $__syntax = 'mysql';

        public function db_connect($host, $user, $password, $database)
        {
            if (!function_exists('mysqli_connect')) {
                throw new Exception('PHP MySQLi functions are not available');
            }

            $port   = ini_get("mysqli.default_port");
            $socket = null;
            if (strpos($host, ':') !== false) {
                // Port or socket given
                $bits   = explode(':', $host);
                $host   = array_shift($bits);
                $socket = array_shift($bits);
                if (abs((integer) $socket) > 0) {
                    // TCP/IP connection on given port
                    $port   = abs((integer) $socket);
                    $socket = null;
                } else {
                    // Socket connection
                    $port = null;
                }
            }
            if (($link = @mysqli_connect($host, $user, $password, $database, $port, $socket)) === false) {
                throw new Exception('Unable to connect to database');
            }

            $this->db_post_connect($link, $database);

            return $link;
        }

        public function db_pconnect($host, $user, $password, $database)
        {
            // No pconnect wtih mysqli, below code is for comatibility
            return $this->db_connect($host, $user, $password, $database);
        }

        private function db_post_connect($link, $database)
        {
            if (version_compare($this->db_version($link), '5.7.7', '>=')) {
                $this->db_query($link, 'SET NAMES utf8mb4');
                $this->db_query($link, 'SET CHARACTER SET utf8mb4');
                $this->db_query($link, "SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
                $this->db_query($link, "SET COLLATION_SERVER = 'utf8mb4_unicode_ci'");
                $this->db_query($link, "SET CHARACTER_SET_SERVER = 'utf8mb4'");
                $this->db_query($link, "SET CHARACTER_SET_DATABASE = 'utf8mb4'");
                $link->set_charset("utf8mb4");
            } else {
                throw new Exception('Unable to connect to an utf8mb4 database');
            }
        }

        public function db_close($handle)
        {
            if ($handle instanceof MySQLi) {
                mysqli_close($handle);
            }
        }

        public function db_version($handle)
        {
            if ($handle instanceof MySQLi) {
                return mysqli_get_server_info($handle);
            }
            return;
        }

        public function db_query($handle, $query)
        {
            if ($handle instanceof MySQLi) {

                $res = @mysqli_query($handle, $query);
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
            if ($res instanceof MySQLi_Result) {
                //return mysql_num_fields($res);
                return $res->field_count;
            }
            return 0;
        }

        public function db_num_rows($res)
        {
            if ($res instanceof MySQLi_Result) {
                return $res->num_rows;
            }
            return 0;
        }

        public function db_field_name($res, $position)
        {
            if ($res instanceof MySQLi_Result) {
                $res->field_seek($position);
                $finfo = $res->fetch_field();
                return $finfo->name;
            }
        }

        public function db_field_type($res, $position)
        {
            if ($res instanceof MySQLi_Result) {
                $res->field_seek($position);
                $finfo = $res->fetch_field();
                return $this->_convert_types($finfo->type);
            }
        }

        public function db_fetch_assoc($res)
        {
            if ($res instanceof MySQLi_Result) {
                $v = $res->fetch_assoc();
                return ($v === null) ? false : $v;
            }
        }

        public function db_result_seek($res, $row)
        {
            if ($res instanceof MySQLi_Result) {
                return $res->data_seek($row);
            }
        }

        public function db_changes($handle, $res)
        {
            if ($handle instanceof MySQLi) {
                return mysqli_affected_rows($handle);
            }
        }

        public function db_last_error($handle)
        {
            if ($handle instanceof MySQLi) {
                $e = mysqli_error($handle);
                if ($e) {
                    return $e . ' (' . mysqli_errno($handle) . ')';
                }
            }
            return false;
        }

        public function db_escape_string($str, $handle = null)
        {
            if ($handle instanceof MySQLi) {

                return mysqli_real_escape_string($handle, $str);
            }
            return addslashes($str);
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
                    $res[]      = $v['field'] . ($v['collate'] ? ' COLLATE utf8mb4_unicode_ci' : '') . ' ' . $v['order'];
                }
            }
            return empty($res) ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
        }

        public function lexFields()
        {
            $fmt = '%s COLLATE utf8mb4_unicode_ci';
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

        protected function _convert_types($id)
        {
            $id2type = [
                '1'   => 'int',
                '2'   => 'int',
                '3'   => 'int',
                '8'   => 'int',
                '9'   => 'int',

                '16'  => 'int', //BIT type recognized as unknown with mysql adapter

                '4'   => 'real',
                '5'   => 'real',
                '246' => 'real',

                '253' => 'string',
                '254' => 'string',

                '10'  => 'date',
                '11'  => 'time',
                '12'  => 'datetime',
                '13'  => 'year',

                '7'   => 'timestamp',

                '252' => 'blob'

            ];
            $type = 'unknown';

            if (isset($id2type[$id])) {
                $type = $id2type[$id];
            }

            return $type;
        }
    }

/** @cond ONCE */
}
/** @endcond */
