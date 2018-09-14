<?php
/**
 * @class sqliteConnection
 * @brief SQLite Database Driver
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

    class sqliteConnection extends dbLayer implements i_dbLayer
    {
        protected $__driver        = 'sqlite';
        protected $__syntax        = 'sqlite';
        protected $utf8_unicode_ci = null;

        public function db_connect($host, $user, $password, $database)
        {
            if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
                throw new Exception('PDO SQLite class is not available');
            }

            $link = new PDO('sqlite:' . $database);
            $this->db_post_connect($link, $database);

            return $link;
        }

        public function db_pconnect($host, $user, $password, $database)
        {
            if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
                throw new Exception('PDO SQLite class is not available');
            }

            $link = new PDO('sqlite:' . $database, null, null, [PDO::ATTR_PERSISTENT => true]);
            $this->db_post_connect($link, $database);

            return $link;
        }

        private function db_post_connect($handle, $database)
        {
            if ($handle instanceof PDO) {
                $this->db_exec($handle, 'PRAGMA short_column_names = 1');
                $this->db_exec($handle, 'PRAGMA encoding = "UTF-8"');
                $handle->sqliteCreateFunction('now', [$this, 'now'], 0);
                if (class_exists('Collator') && method_exists($handle, 'sqliteCreateCollation')) {
                    $this->utf8_unicode_ci = new Collator('root');
                    if (!$handle->sqliteCreateCollation('utf8_unicode_ci', [$this->utf8_unicode_ci, 'compare'])) {
                        $this->utf8_unicode_ci = null;
                    }
                }
            }
        }

        public function db_close($handle)
        {
            if ($handle instanceof PDO) {
                $handle       = null;
                $this->__link = null;
            }
        }

        public function db_version($handle)
        {
            if ($handle instanceof PDO) {
                return $handle->getAttribute(PDO::ATTR_SERVER_VERSION);
            }
        }

        # There is no other way than get all selected data in a staticRecord
        public function select($sql)
        {
            $result              = $this->db_query($this->__link, $sql);
            $this->__last_result = &$result;

            $info         = [];
            $info['con']  = &$this;
            $info['cols'] = $this->db_num_fields($result);
            $info['info'] = [];

            for ($i = 0; $i < $info['cols']; $i++) {
                $info['info']['name'][] = $this->db_field_name($result, $i);
                $info['info']['type'][] = $this->db_field_type($result, $i);
            }

            $data = [];
            while ($r = $result->fetch(PDO::FETCH_ASSOC)) {
                $R = [];
                foreach ($r as $k => $v) {
                    $k     = preg_replace('/^(.*)\./', '', $k);
                    $R[$k] = $v;
                    $R[]   = &$R[$k];
                }
                $data[] = $R;
            }

            $info['rows'] = count($data);
            $result->closeCursor();

            return new staticRecord($data, $info);
        }

        public function db_query($handle, $query)
        {
            if ($handle instanceof PDO) {
                $res = $handle->query($query);
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
            if ($res instanceof PDOStatement) {
                return $res->columnCount();
            }
            return 0;
        }

        public function db_num_rows($res)
        {
        }

        public function db_field_name($res, $position)
        {
            if ($res instanceof PDOStatement) {
                $m = $res->getColumnMeta($position);
                return preg_replace('/^.+\./', '', $m['name']); # we said short_column_names = 1
            }
        }

        public function db_field_type($res, $position)
        {
            if ($res instanceof PDOStatement) {
                $m = $res->getColumnMeta($position);
                switch ($m['pdo_type']) {
                    case PDO::PARAM_BOOL:
                        return 'boolean';
                    case PDO::PARAM_NULL:
                        return 'null';
                    case PDO::PARAM_INT:
                        return 'integer';
                    default:
                        return 'varchar';
                }
            }
        }

        public function db_fetch_assoc($res)
        {
        }

        public function db_result_seek($res, $row)
        {
        }

        public function db_changes($handle, $res)
        {
            if ($res instanceof PDOStatement) {
                return $res->rowCount();
            }
        }

        public function db_last_error($handle)
        {
            if ($handle instanceof PDO) {
                $err = $handle->errorInfo();
                return $err[2] . ' (' . $err[1] . ')';
            }
            return false;
        }

        public function db_escape_string($str, $handle = null)
        {
            if ($handle instanceof PDO) {
                return trim($handle->quote($str), "'");
            }
            return $str;
        }

        public function escapeSystem($str)
        {
            return "'" . $this->escape($str) . "'";
        }

        public function begin()
        {
            if ($this->__link instanceof PDO) {
                $this->__link->beginTransaction();
            }
        }

        public function commit()
        {
            if ($this->__link instanceof PDO) {
                $this->__link->commit();
            }
        }

        public function rollback()
        {
            if ($this->__link instanceof PDO) {
                $this->__link->rollBack();
            }
        }

        public function db_write_lock($table)
        {
            $this->execute('BEGIN EXCLUSIVE TRANSACTION');
        }

        public function db_unlock()
        {
            $this->execute('END');
        }

        public function vacuum($table)
        {
            $this->execute('VACUUM ' . $this->escapeSystem($table));
        }

        public function dateFormat($field, $pattern)
        {
            return "strftime('" . $this->escape($pattern) . "'," . $field . ') ';
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
                    if ($v['collate']) {
                        if ($this->utf8_unicode_ci instanceof Collator) {
                            $res[] = $v['field'] . ' COLLATE utf8_unicode_ci ' . $v['order'];
                        } else {
                            $res[] = 'LOWER(' . $v['field'] . ') ' . $v['order'];
                        }
                    } else {
                        $res[] = $v['field'] . ' ' . $v['order'];
                    }
                }
            }
            return empty($res) ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
        }

        public function lexFields()
        {
            $fmt = $this->utf8_unicode_ci instanceof Collator ? '%s COLLATE utf8_unicode_ci' : 'LOWER(%s)';
            foreach (func_get_args() as $v) {
                if (is_string($v)) {
                    $res[] = sprintf($fmt, $v);
                } elseif (is_array($v)) {
                    $res = array_map(function ($i) use ($fmt) {return sprintf($fmt, $i);}, $v);
                }
            }
            return empty($res) ? '' : implode(',', $res);
        }

        # Internal SQLite function that adds NOW() SQL function.
        public function now()
        {
            return date('Y-m-d H:i:s');
        }
    }

/** @cond ONCE */
}
/** @endcond */
