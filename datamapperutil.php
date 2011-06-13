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

require __DIR__ . DIRECTORY_SEPARATOR . 'datamappertable.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'datamapperbase.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'datamappermodel.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'datamappermapper.php';

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
class DataMapperUtil {

    /**
     * whether or not to overwrite models
     * when running runProcess
     *
     * defaults to false
     *
     * @var bool
     */
    protected $overwrite_models = false;

    /**
     * whether or not to overwrite mappers
     * when running runProcess
     *
     * defaults to true
     *
     * @var bool
     */
    protected $overwrite_mappers = true;

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
                include __DIR__ . DIRECTORY_SEPARATOR . 'datamappermysql.php';
                $this->db = new DataMapperMysql($this, $host, $database, $username, $password);
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
        if (!is_string($directory)) {
            throw new DataMapperException("Path provided to DataMapper::setModelDirectory is not a string, but of type: " . gettype($directory));
        }
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new DataMapperException("Path provided to DataMapper::setModelDirectory does not exist or is not writable");
        }
        $this->model_directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
     * returns the path to store the mapper classes
     * in - defaults to the path of the model classes
     *
     * @access public
     * @return string
     */
    public function getMapperDirectory() {
        if (isset($this->mapper_directory)) {
            return $this->mapper_directory;
        }
        return $this->model_directory;
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
     * default is false
     *
     * @param bool $overwrite_models
     *
     * @access public
     * @return $this
     */
    public function overwriteModels($overwrite_models = false) {
        $this->overwrite_models = !!$overwrite_models;
        return $this;
    }

    /**
     * if set to true, will overwrite mappes
     * otherwise, mappers won't be created again
     * default is true
     *
     * @param bool $overwrite_models
     *
     * @access public
     * @return $this
     */
    public function overwriteMappers($overwrite_mappers = true) {
        $this->overwrite_mappers = !!$overwrite_mappers;
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
        return $this;
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
        return $this;
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
        $this->debug("Data mapper creation process started", 1);
        $this->model_extend_class = isset($this->model_extend_class) ? $this->model_extend_class : '';
        $this->debug("Setting Model class extension to: " . $this->model_extend_class, 3);
        $this->model_prefix = isset($this->model_prefix) ? $this->model_prefix : '';
        $this->debug("Setting Model prefix to: " . $this->model_prefix, 3);
        $this->translation = isset($this->translation) ? $this->translation : true;
        $this->debug("Setting translation mode to: " . ($this->translation ? 'true' : 'false'), 3);
        foreach ($this->db->getTableDefinitions() as $tablename => $definition) {
            $table = new DataMapperTable($this, $tablename, $definition);
            $table->setModelExtend($this->model_extend_class);
            $table->setModelPrefix($this->model_prefix);
            $table->setTranslationMode($this->translation);
            $this->debug("Creating data pair for table: {$tablename}", 2);
            $table->createDataPair($this->model_directory, $this->getMapperDirectory(), $this->overwrite_models, $this->overwrite_mappers);
            $this->debug("Data pair created", 2);
        }
        if ($this->overwrite_mappers) {
            $this->copyMapperBase();
        }
    }

    protected function copyMapperBase() {
        if (!copy(__DIR__ . $this->db->getMapperBaseTemplatePath(), $this->getMapperDirectory() . 'datamapper.php')) {
            throw new DataMapperException("Could not copy datamapper base file to mapper directory");
        }
    }

    /**
     * outputs debug messages based on verbosity level
     * if provided level is equal to or lower than
     * the set verbosity level, message is output
     *
     * @param string $message
     * @param int    $level
     *
     * @access public
     * @return void
     */
    public function debug($message, $level) {
        if (intval($level) <= $this->verbosity_level && $this->verbosity_level !== 0) {
            echo $message . PHP_EOL;
        }
    }
}
