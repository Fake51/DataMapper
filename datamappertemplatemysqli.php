<?php
/**
 * Copyright 2011 Peter Lind
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5.3
 *
 * @package    DataMapper
 * @author     Peter Lind <peter.e.lind@gmail.com>
 * @copyright  2011 Peter Lind
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @link       http://www.github.com/Fake51/DataMapper
 */

/**
 * DataMapper mysqli template
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapper {
    /**
     * database connection
     *
     * @var resource
     */
    protected $db;

    /**
     * constructor
     *
     * @param resource $database_connection
     *
     * @access public
     * @return void
     */
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    /**
     * return all fields used for
     * corresponding table
     *
     * @access public
     * @return array
     */
    public function getTableFields() {
        return $this->table_fields;
    }

    /**
     * return all fields used
     * in primary key
     *
     * @access public
     * @return array
     */
    public function getPrimaryKeys() {
        return $this->primary_keys;
    }

    /**
     * populate model with data
     *
     * @param mixed
     *
     * @throws Exception
     * @access public
     * @return $this
     */
    public function load(/* args */) {
        $args = func_get_args();
        if (count($args) < 2) {
            throw new Exception("Lacking data to load object with");
        }
        $model = array_shift($args);
        if (count($args) > 1 || is_scalar($args[0])) {
            // rest of the arguments should be values for
            // primary keys to load
            $this->processLoadArgs($args);
        } elseif (is_array($args[0])) {
            if ($this->isNumericArgs($args[0])) {
                $this->processLoadArgs($args[0]);
            } else {
                $this->processLoadArgsArray($args[0]);
            }
        } elseif ($args[0] instanceof MySQLi_Result) {
            $this->processLoadArgsResult($args[0]);
        } elseif (is_object($args[0])) {
            $this->processLoadArgsObject($args[0]);
        } else {
            throw new Exception("Could not make sense of arguments for DataMapper::load()");
        }
    }

    protected function isNumericArgs(array $args) {
        foreach ($args as $key => $value) {
            if (!(intval($key) || $key === 0)) {
                return false;
            }
        }
        return true;
    }

    /**
     * process load arguments based
     * on array
     *
     * @param array $args
     *
     * @access protected
     * @return void
     */
    protected function processLoadArgs(array $args) {
        $args_count = count($args);
        if ($args_count === count($this->table_fields)) {
            $this->fillData($args);
        } elseif ($args_count === count($this->primary_keys)) {
            $primary_args = array();
            for ($i = 0; $i < $args_count; ++$i) {
                $primary_args[$this->primary_keys[$i]] = $args[$i];
            }
            $this->loadFromDB($this->makeLoadSelect($primary_args));
        } else {
            throw new Exception("Load args do not match for loading by primary keys or filling with data");
        }
    }

    /**
     * process load arguments based
     * on a mysqli result
     *
     * @param MySQLi_Result $result
     *
     * @access protected
     * @return void
     */
    protected function processLoadArgsResult(MySQLi_Result $result) {
        if (!$result->num_rows()) {
            throw new Exception("Resource contained no data for loading object");
        }
        $this->processLoadArgsArray($result->fetch_assoc());
    }

    /**
     * process load arguments based
     * on an associative array
     *
     * @param resource $resource
     *
     * @access protected
     * @return void
     */
    protected function processLoadArgsArray(array $args) {
        $fill = true;
        foreach ($this->table_fields as $key) {
            if (!isset($args[$key])) {
                $fill = false;
            }
        }
        if ($fill) {
            $this->fillData($args);
            return;
        }
        $primary = true;
        foreach ($this->primary_keys as $key) {
            if (!isset($args[$key])) {
                $primary = false;
            }
        }
        if ($primary) {
            $this->loadFromDB($this->makeLoadSelect($args));
            return;
        }
        throw new Exception("Load args do not match for loading by primary keys or filling with data");
    }

    /**
     * process load arguments based
     * on an object
     *
     * @param resource $resource
     *
     * @access protected
     * @return void
     */
    protected function processLoadArgsObject($arg_object) {
        $fill = true;
        $fill_args = array();
        foreach ($this->table_fields as $key) {
            if (!isset($args->$key)) {
                $fill = false;
            }
            $fill_args[$key] = $args->$key;
        }
        if ($fill) {
            $this->fillData($fill_args);
            return;
        }
        $primary = true;
        $primary_args = array();
        foreach ($this->primary_keys as $key) {
            if (!isset($args->$key)) {
                $primary = false;
            }
            $primary_args[$key] = $args->$key;
        }
        if ($primary) {
            $this->loadFromDB($this->makeLoadSelect($primary_args));
            return;
        }
        throw new Exception("Load args do not match for loading by primary keys or filling with data");
    }

    /**
     * creates an sql select query to load data
     * with
     *
     * @param array $args
     *
     * @throws Exception
     * @access protected
     * @return string
     */
    protected function makeLoadSelect(array $args) {
        $keys = array();
        foreach ($this->primary_keys as $key) {
            if (!isset($args[$key])) {
                throw new Exception("Lacking value for field $key");
            }
            $keys[] = "`$key` = '{$this->db->real_escape_string($args[$key])}'";
        }
        return "
SELECT `" . implode('`, `', $this->table_fields) . "` FROM `\$this->tablename` WHERE " . implode(' AND ', $keys);
    }

    public function loadFromDB($query) {
        if (($result = $this->db->query($query)) && $result->num_rows()) {
            $this->fillData($result->fetch_assoc());
        } else {
            throw new Exception("Could not load data from query");
        }
    }

    public function save(/* args */) {
    }
}
