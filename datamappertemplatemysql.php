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
 * DataMapper mysql template
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
     * data storages
     *
     * @var array
     */
    protected $data;

    /** 
     * model the datamapper is for
     *
     * @var object
     */
    protected $model;

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
        $this->model = array_shift($args);
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
        } elseif (is_object($args[0])) {
            $this->processLoadArgsObject($args[0]);
        } elseif (is_resource($args[0])) {
            $this->processLoadArgsResource($args[0]);
        } else {
            throw new Exception("Could not make sense of arguments for DataMapper::load()");
        }
    }

    /**
     * checks if the array provided is a
     * numeric or associative array
     *
     * @param array $args
     *
     * @access protected
     * @return bool
     */
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
     * on a mysql resource
     *
     * @param resource $resource
     *
     * @access protected
     * @return void
     */
    protected function processLoadArgsResource($resource) {
        if (!mysql_num_rows($resource)) {
            throw new Exception("Resource contained no data for loading object");
        }
        $this->processLoadArgsArray(mysql_fetch_assoc($resource));
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
            $keys[] = "`$key` = '" . mysql_real_escape_string($args[$key], $this->db) . "'";
        }
        return "
SELECT `" . implode('`, `', $this->table_fields) . "` FROM `{$this->table_name}` WHERE " . implode(' AND ', $keys);
    }

    /**
     * load data from database using given query
     *
     * @param string $query
     *
     * @throws Exception
     * @access public
     * @return void
     */
    public function loadFromDB($query) {
        if (($result = mysql_query($query, $this->db)) && mysql_num_rows($result)) {
            $this->fillData(mysql_fetch_assoc($result));
        } else {
            throw new Exception("Could not load data from query");
        }
    }

    /**
     * loads data from provided array and sets it
     * locally and in the model as well
     *
     * @param array
     *
     * @throws Exception
     * @access protected
     * @return void
     */
    protected function fillData(array $data) {
        if (empty($this->model)) {
            throw new Exception("Model not set");
        }
        foreach ($this->getTableFields() as $field) {
            if (!isset($data[$field])) {
                throw new Exception("{$field} is not set in data provided to DataMapper::fillData");
            }
            $this->model->$field = $this->data[$field] = $data[$field];
        }
    }

    /**
     * saves the data for the model, either as an
     * insert or as an update, depending upon whether
     * primary key data was set from the start
     *
     * @param object $model
     *
     * @throws Exception
     * @access public
     * @return void
     */
    public function save($model) {
        $insert = false;
        if (empty($this->model)) {
            $this->model = $model;
            $insert = true;
        }
        // todo: handle many-to-many tables
        foreach ($this->getPrimaryKeys() as $key) {
            if (!isset($this->data[$key])) {
                $insert = true;
            }
        }
        if (!$insert) {
            // update: set all fields but primary keys
            $this->doUpdate();
        } else {
            // insert: set all fields but primary keys if autocreate, otherwise also set primary keys
            $this->doInsert();
        }
    }

    /**
     * creates and runs an update query
     *
     * @throws Exception
     * @access protected
     * @return void
     */
    protected function doUpdate() {
        $pks = $this->getPrimaryKeys();
        $updates = array();
        foreach ($this->getTableFields() as $field) {
            if (in_array($field, $pks) || (!isset($this->model->$field) && !isset($this->data[$field]))) {
                // field is a primary key, or isn't set in model and mapper
                continue;
            }
            if ((!isset($this->model->$field) || is_null($this->model->$field)) && isset($this->data[$field])) {
                // field has been set to null after loading
                $updates[] = "`{$field}` = NULL";
                $this->data[$field] = null;
            } elseif (isset($this->model->$field) && (!isset($this->data[$field]) || $this->model->$field != $this->data[$field])) {
                // field was updated after loading
                $updates[] = "`{$field}` = '" . mysql_real_escape_string($this->model->$field, $this->db) . "'";
                $this->data[$field] = $this->model->$field;
            }
        }
        $query = "UPDATE `{$this->table_name}` SET " . implode(", ", $updates) . " WHERE {$this->generatePrimaryKeyClause()}";
        if (!mysql_query($query, $this->db)) {
            throw new Exception("Failed to update table: {$this->table_name}");
        }
    }

    /**
     * creates and runs an insert query
     *
     * @throws Exception
     * @access protected
     * @return void
     */
    protected function doInsert() {
        $pks = array_flip($this->getPrimaryKeys());
        $inserts = array();
        foreach ($this->getTableFields() as $field) {
            if (isset($pks[$field]) && $this->auto_primary_key) {
                continue;
            }
            if (isset($pks[$field]) && !$this->auto_primary_key) {
                if (!isset($this->model->$field)) {
                    throw new Exception("Primary key is not auto created for model, but no data for primary key is set in model");
                }
                $inserts[] = "`{$field}` = '" . mysql_real_escape_string($this->model->$field, $this->db) . "'";
                $this->data[$field] = $this->model->$field;
            } elseif (!isset($this->model->$field)) {
                $inserts[] = "`{$field}` = NULL";
                $this->data[$field] = null;
            } else {
                $inserts[] = "`{$field}` = '" . mysql_real_escape_string($this->model->$field, $this->db) . "'";
                $this->data[$field] = $this->model->$field;
            }
        }
        $query = "INSERT INTO `{$this->table_name}` SET " . implode(", ", $inserts);
        if (!mysql_query($query, $this->db)) {
            throw new Exception("Failed to insert data into table: {$this->table_name}");
        }
        // todo: improve to handle multiple rows
        if ($this->auto_primary_key) {
            foreach ($this->getPrimaryKeys() as $key) {
                $this->model->$key = $this->data[$key] = mysql_insert_id($this->db);
            }
        }
    }

    /**
     * deletes data for a model
     *
     * @param object $model
     *
     * @throws Exception
     * @access public
     * @return void
     */
    public function delete($model) {
        $query = "DELETE FROM `{$this->table_name}` WHERE {$this->generatePrimaryKeyClause()} LIMIT 1";
        if (!mysql_query($query, $this->db)) {
            throw new Exception("Failed to delete row in table: {$this->table_name}");
        }
        foreach ($this->getTableFields as $field) {
            $model->$field = null;
        }
        $this->data = array();
    }

    /**
     * generates a WHERE clause with the primary
     * keys of the model
     *
     * @throws Exception
     * @access public
     * @return string
     */
    public function generatePrimaryKeyClause() {
        $keys = array();
        foreach ($this->getPrimaryKeys() as $key) {
            if (!isset($this->data[$key])) {
                throw new Exception("Model was not loaded prior to deletion");
            }
            $keys[] = "`{$key}` = '" . mysql_real_escape_string($this->data[$key], $this->db) . "'";
        }
        return implode(" AND ", $keys);
    }
}
