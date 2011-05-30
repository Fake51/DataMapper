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
 * pulls definition data out of a mysql database
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapperMysql implements DataMapperDatabase {

    /**
     * database connection
     *
     * @var resource
     */
    protected $connection;

    /**
     * instance of the DataMapper class
     * mainly used here for debugging/output
     * purposes
     *
     * @var DataMapper
     */
    protected $datamapper;

    /**
     * constructor
     *
     * @param DataMapper $datamapper
     * @param string     $host
     * @param string     $database
     * @param string     $username
     * @param string     $password
     *
     * @throws DataMapperException
     * @access public
     * @return void
     */
    public function __construct(DataMapper $datamapper, $host, $database, $username, $password = null) {
        $this->datamapper = $datamapper;
        $this->connection = mysqli_init();
        if ($password) {
            if (!$this->connection->real_connect($host, $username, $password, $database)) {
                throw new DataMapperException();
            }
        } else {
            if (!$this->connection->real_connect($host, $username, '', $database)) {
                throw new DataMapperException();
            }
        }
    }

    /**
     * fetches the schema for the database and parses
     * it into arrays, then returns that
     *
     * @access public
     * @return array
     */
    public function getTableDefinitions() {
        $this->datamapper->debug("Fetching overall table information", 2);
        $result = $this->connection->query("SHOW TABLES");
        $info = array();
        while ($row = $result->fetch_row()) {
            $tablename = $row[0];
            $this->datamapper->debug("Fetching table information for table: {$tablename}", 2);
            $describe_result = $this->connection->query("DESCRIBE `{$tablename}`");
            $info[$tablename] = $this->parseTableDescription($describe_result);
            $this->datamapper->debug("Parsed information for table: {$tablename}", 3);
        }
        return $info;
    }

    /**
     * parses a DESCRIBE query from mysql
     *
     * @param MySQLi_Result $result
     *
     * @access protected
     * @return array
     */
    protected function parseTableDescription(MySQLi_Result $result) {
        $columns = array();
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = array(
                'type'     => strtolower($row['Type']),
                'null'     => $row['Null'],
                'key'      => $row['Key'],
                'default'  => $row['Default'],
                'extra'    => $row['Extra'],
            );
        }
        return $columns;
    }
}
