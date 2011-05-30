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
 * parses info for a table
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapperTable {

    /**
     * whether or not to translate class to filename
     *
     * @var bool
     */
    protected $translation;

    /**
     * stores the name of the table
     *
     * @var string
     */
    protected $tablename;

    /**
     * holds info on table columns
     *
     * @var array
     */
    protected $definition;

    /**
     * stores classname for the table
     *
     * @var string
     */
    protected $classname;

    /**
     * stores class to extend for model
     *
     * @var string
     */
    protected $model_extends;

    /**
     * stores prefix for the table
     *
     * @var string
     */
    protected $model_prefix;

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
     * @param string $tablename
     * @param array  $definition
     *
     * @access public
     * @return void
     */
    public function __construct(DataMapper $datamapper, $tablename, array $definition) {
        $this->datamapper = $datamapper;
        $this->tablename  = $tablename;
        $this->definition = $definition;
    }

    /**
     * handles creating datamapper classes
     *
     * @param string $path
     * @param bool   $overwrite_mappers
     *
     * @access public
     * @return void
     */
    public function createDataMappers($path, $overwrite_mappers) {
    }

    /**
     * handles creating data model classes
     *
     * @param string $path
     * @param bool   $overwrite_mappers
     *
     * @access public
     * @return void
     */
    public function createDataModels($path, $overwrite_models) {
        $info = array(
            'columns'      => $this->definition,
            'extends'      => empty($this->model_extends) ? '' : $this->model_extends,
            'prefix'       => empty($this->model_prefix) ? '' : $this->model_prefix,
            'translation'  => empty($this->translation) ? false : true,
            'tablename'    => $this->tablename,
        );
        $model = new DataMapperModel($this->datamapper, $info, $path, $overwrite_models);
        $model->create();
    }

    /**
     * returns the name of the table
     *
     * @access public
     * @return string
     */
    public function getTableName() {
        return $this->tablename;
    }

    /**
     * returns the collected table information
     *
     * @access public
     * @return array
     */
    public function getColumnInfo()
    {
        return $this->definition;
    }

    /**
     * sets the model which the created data models will extend
     *
     * @param string $classname
     *
     * @access public
     * @return $this
     */
    public function setModelExtend($classname) {
        $this->model_extends = $classname;
        return $this;
    }

    /**
     * sets a prefix which will be used for all the model class names
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
     * if true, class names will be lowercased
     *
     * @param bool $mode
     *
     * @access public
     * @return $this
     */
    public function setTranslationMode($mode) {
        $this->translation = !!$mode;
        return $this;
    }
}
