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

require __DIR__ . '/datamappertable.php';

/**
 * empty exception class to have own
 * datamapper exception
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapperException extends Exception {
}

/**
 * interface used by the database classes
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
interface DataMapperDatabase {
    public function getTableDefinitions();
}

/**
 * base class for DataMapper, a Facade pattern class
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapper {

    /**
     * host to connect to
     *
     * @var string
     */
    protected $host;

    /**
     * database to connect to
     *
     * @var string
     */
    protected $database;

    /**
     * username to connect with
     *
     * @var string
     */
    protected $username;

    /**
     * password to connect with
     *
     * @var string
     */
    protected $password;

    /**
     * place to put created models
     *
     * @var string
     */
    protected $model_directory;

    /**
     * place to put created mappers
     *
     * @var string
     */
    protected $mapper_directory;

    /**
     * class to have model extend
     *
     * @var string
     */
    protected $model_extend_class;

    /**
     * prefix used for created models
     *
     * @var string
     */
    protected $model_prefix;

    /**
     * whether or not to translate class to filename
     *
     * @var bool
     */
    protected $translation;

    /**
     * how verbose the library is
     *
     * @var int
     */
    protected $verbosity_level = 0;

    /**
     * db class
     *
     * @var DataMapperDatabase
     */
    protected $db;

    /**
     * constructor
     *
     * @param string $host
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $database_type
     *
     * @throws DataMapperException
     * @access public
     * @return void
     */
    public function __construct($host, $database, $username, $password = null, $database_type = "mysql") {
        switch (strtolower($database_type)) {
            case 'mysql':
                include __DIR__ . '/datamappermysql.php';
                $this->db = new DataMapperMysql($host, $database, $username, $password);
                break;
            default:
                throw new DataMapperException("Only mysql databases supported currently");
        }
    }

    /**
     * sets the directory to store created models in
     *
     * @param string $directory
     *
     * @access public
     * @return $this
     */
    public function setModelDirectory($directory) {
        // todo check for write permissions
        $this->model_directory = $directory;
        return $this;
    }

    /**
     * sets the directory to store created data mappers in
     *
     * @param string $directory
     *
     * @access public
     * @return $this
     */
    public function setMapperDirectory($directory) {
        // todo check for write permissions
        $this->mapper_directory = $directory;
        return $this;
    }

    /**
     * sets a class that created models will extend
     *
     * @param string $classname
     *
     * @access public
     * @return $this
     */
    public function setModelExtendClass($classname) {
        $this->model_extend_class = $classname;
        return $this;
    }

    /**
     * sets a prefix used for models
     *
     * @param string $prefix
     *
     * @access public
     * @return $this
     */
    public function setModelPrefix($prefix) {
        $this->model_prefix = $prefix;
        return $this;
    }

    /**
     * if set to true, will overwrite models
     * otherwise, models won't be created again
     *
     * @param bool $overwrite_models
     *
     * @access public
     * @return $this
     */
    public function overwriteModels($overwrite_models) {
        $this->overwrite_models = !!$bool;
        return $this;
    }

    /**
     * if set to true, filenames will not be translated
     * when stored - i.e. no lowercasing
     *
     * @param bool $translation
     *
     * @access public
     * @return $this
     */
    public function setTranslationMode($translation) {
        $this->translation = !!$translation;
    }

    /**
     * determines how much output to the user during
     * the process there is - default is no output
     *
     * @param int $level
     *
     * @access public
     * @return $this
     */
    public function setVerbosityLevel($level) {
        $this->verbosity_level = $level;
    }

    /**
     * kicks off the actual processing
     *
     * @throws DataMapperException
     * @access public
     * @return void
     */
    public function runProcess() {
        if (empty($this->model_directory)) {
            throw new DataMapperException("No path to save models to");
        }
        foreach ($this->db->getTableDefinitions() as $tablename => $definition) {
            $table = new DataMapperTable($tablename, $definition);
        }
    }
}
