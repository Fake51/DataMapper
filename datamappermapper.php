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
 * creates templates for mapper classes
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapperMapper extends DataMapperBase {

    /**
     * creates the mapper file
     *
     * @access public
     * @return void
     */
    public function create() {
        if ($this->createFileCheck()) {
            $this->writeToFile($this->tableInfoToString());
        }
    }

    /**
     * writes the class definition to file
     *
     * @access protected
     * @return void
     */
    protected function writeToFile($definition) {
        if (file_put_contents($this->getClassFilename(), $definition) === false) {
            throw new DataMapperException("Failed to write class to file");
        }
    }

    /**
     * converts the table info to a class
     *
     * @access public
     * @return string
     */
    public function tableInfoToString() {
        return $this->createHeaderCrud()
            . $this->createConstructor()
            . $this->createIOMethods()
            . $this->createFooterCrud();
    }

    /**
     * creates the top part of the class
     *
     * @access protected
     * @return string
     */
    protected function createHeaderCrud() {
        $extends = empty($this->info['extends']) ? '' : ' extends ' . $this->info['extends'];
        return <<<PHP
<?php
/**
 * Mapper file created automatically
 * with DataMapper
 */

class {$this->getClassName(true)}{$extends} {
    /**
     * instance of model
     *
     * @var {$this->getModelClassName()}
     */
    protected \$model;


PHP;
    }

    /**
     * fetches the class name for
     * the associated model
     *
     * @throws DataMapperException
     * @access protected
     * @return string
     */
    protected function getModelClassName() {
        if (empty($this->model)) {
            throw new DataMapperException("No model set for DataMapperMapper");
        }
        return $this->model->getClassName();
    }

    /**
     * creates the construct part of the
     * mapper class
     *
     * @access protected
     * @return string
     */
    protected function createConstructor() {
        $model_name = $this->model->getClassName();
        if (strlen($model_name) > 8) {
            $resource = str_pad('resource', strlen($model_name), ' ');
        } else {
            $resource = 'resource';
            $model_name = str_pad($model_name, 8, ' ');
        }


        $txt = <<<TXT
    /**
     * constructor
     *
     * @param {$resource} \$database_connection
     * @param {$model_name} \$model

TXT;
        $params = array('$database_connection', '$model');

        $param_def = implode(', ', $params);
        $parent_construct = empty($this->info['extends']) ? '' : "parent::__construct();" . PHP_EOL;
        $txt .= <<<TXT
     *
     * @access public
     * @return void
     */
    public function __construct({$param_def}) {
        \$this->db = \$database_connection;
        \$this->model = \$model;
        {$parent_construct}
    }


TXT;

        return $txt;
    }

    protected function createIOMethods() {
    }

    protected function createFooterCrud() {
    }

    /**
     * checks if a file should be created
     *
     * @access public
     * @return bool
     */
    public function createFileCheck() {
        if (file_exists($this->path . $this->getClassFilename())) {
            return $this->overwrite;
        }
        return true;
    }

    /**
     * sets the model the mapper is for
     *
     * @param DataMapperModel $model
     *
     * @access public
     * @return $this
     */
    public function setModel(DataMapperModel $model) {
        $this->model = $model;
        return $this;
    }

    /**
     * returns the name of the class
     *
     * @param bool $prefix - return name with prefix
     *
     * @access public
     * @return string
     */
    public function getClassName($prefix = false) {
        if (empty($this->classname)) {
            $classname = preg_replace(array('/(es|s)$/', '/ies$/'), array('', 'y'), $this->info['tablename']);
            preg_match_all('/(s?)_([a-z])/', $classname, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $classname = str_replace($match[1] . $match[2], strtoupper($match[2]), $classname);
            }
            $this->classname = ucfirst($classname) . 'Mapper';
        }
        return $prefix ? $this->info['prefix'] . $this->classname : $this->classname;
    }

    /**
     * returns the filename of the class
     *
     * @access public
     * @return string
     */
    public function getClassFilename() {
        return $this->info['translation'] ? $this->getClassName() . '.php' : strtolower($this->getClassName()) . '.php';
    }

    /**
     * returns a string with interface statements
     * which interfaces are used are based on table
     * characteristics such as composite primary keys
     *
     * @access private
     * @return string
     */
    private function _getInterfaceDeclaration()
    {
        $interfaces = array();
        if (count($this->_fetchPrimaryKeys()) > 1)
        {
            $interfaces[] = 'Abstracts_ComplexPK'; 
        }
        return empty($interfaces) ? '' : ' implements ' . implode(', ', $interfaces);
    }

    /**
     * creates the header part of a class
     * class statement and properties
     *
     * @access private
     * @return string
     */
    private function _makeHeader()
    {
        $class_def = <<<TXT
<?php
/**
 * File automatically generated by ModelMaker
 * from MySQL table {$this->_tablename}
 */

class {$this->getClassName(true)}{$this->_getModelExtend()}{$this->_getInterfaceDeclaration()}
{

TXT;

        $mapper_config = $this->_config->getMapperConfig();
        if (!$mapper_config->use)
        {
            $class_def .= <<<TXT
    /**
     * holds all field data for the object
     *
     * @var array
     */
    protected \$_data = array();

    /**
     * used for checking which fields have been changed
     *
     * @var array
     */
    protected \$_dirty_fields = array();

TXT;
        }
        else
        {
            $mapper = new MM_Mapper($this, $this->_config);
            $class_def .= <<<TXT
    /**
     * Data mapper object
     *
     * @var {$mapper->getClassName()}
     */
    protected \$_mapper;

    /**
     * Validations used by data mapper object when saving
     * the object
     *
     * @var array
     */
    protected \$_mapper_validations = array();


TXT;
        }

        $zend_config = $this->_config->getZendConfig();
        if ($zend_config->use_dbtables)
        {
            $dbtable = new MM_DbTable($this, $this->_config);
            $class_def .= <<<TXT
    /**
     * DbTable object
     *
     * @var {$dbtable->getClassName()}
     */
    protected \$_db_table;


TXT;
        }
        return $class_def;
    }

    /** 
     * creates the end of the class definition
     *
     * @access private
     * @return string
     */
    private function _makeFooter()
    {
        return <<<TXT
}
TXT;
    }

    /**
     * do actual processing of tables
     * to create a class file
     *
     * @access public
     * @return void
     */
    public function process()
    {
        $class_def = $this->_makeHeader();
        $class_def .= $this->_makeConstructor();
        $class_def .= $this->_makeLoader();
        $class_def .= $this->_makeSaver();
        $class_def .= $this->_processFields();
        $class_def .= $this->_makeFooter();

        $zend_config = $this->_config->getZendConfig();
        if ($zend_config->use_dbtables)
        {
            $db_table = new MM_DbTable($this, $this->_config);
            $db_table->process();
        }

        $mapper_config = $this->_config->getMapperConfig();
        if ($mapper_config->use)
        {
            $mapper = new MM_Mapper($this, $this->_config);
            $mapper->process();
        }

        $this->_class_def = $class_def;
        $this->_storeClass();
    }

    /**
     * creates the load() function in a class
     *
     * @access private
     * @return string
     */
    private function _makeLoader()
    {
        $mapper_config = $this->_config->getMapperConfig();
        if ($mapper_config->use)
        {
            $mapper = new MM_Mapper($this, $this->_config);
            $load_func = <<<TXT
    /**
     * wrapper for data mapper class
     * loads the object with data
     *
     * @param Zend_Db_Table_Row \$row
     *
     * @access public
     * @return \$this
     */
    public function load(Zend_Db_Table_Row \$row)
    {
        \$this->_mapper = new {$mapper->getClassName()}(\$this);
        \$this->_mapper->load(\$row);

TXT;
        }
        else
        {
            $load_func = <<<TXT
    /**
     * loads the object with data from the DB
     *
     * @param Zend_Db_Table_Row \$row
     *
     * @access public
     * @return \$this
     */
    public function load(Zend_Db_Table_Row \$row)
    {

TXT;

            foreach (array_keys($this->_column_info) as $field)
            {
                $load_func .= <<<TXT
        \$this->_data['{$field}'] = \$row->$field;

TXT;
            }

        }

        $model_config = $this->_config->getModelConfig();
        if (!empty($model_config->extends))
        {
            $load_func .= <<<TXT
        parent::init();
        return \$this;
    }


TXT;
        }
        return $load_func;
    }

    /**
     * creates code for dealing with a tables fields
     *
     * @access private
     * @return string
     */
    private function _processFields()
    {
        $def = '';
        $mapper_config = $this->_config->getMapperConfig();
        if ($mapper_config->use)
        {

            $def .= <<<TXT
    /**
     * returns validations for use by the data mapper
     *
     * @access public
     * @return array
     */
    public function getMapperValidations()
    {
        return \$this->_mapper_validations;
    }


TXT;

        }
        else
        {

            foreach ($this->_column_info as $field => $info)
            {
                $fieldname = ucfirst($field);
                preg_match_all('/_([a-z])/', $fieldname, $m, PREG_SET_ORDER);
                foreach ($m as $match)
                {
                    $fieldname = str_replace('_' . $match[1], strtoupper($match[1]), $fieldname);
                }

                if ($fieldname == 'Id')
                {
                    $def .= <<<TXT

    /**
     * protected setter for {$field}
     *
     * @param int \${$field}
     *
     * @access protected
     * @return \$this
     */
    protected function set{$fieldname}(\${$field})
    {
        \$this->_data['{$field}'] = \${$field};
        return \$this;
    }

    /**
     * public getter for {$field}
     *
     * @access public
     * @return int
     */
    public function get{$fieldname}()
    {
        if (!isset(\$this->_data['{$field}']))
        {
            return null;
        }
        return \$this->_data['{$field}'];
    }

TXT;

                }
                elseif (substr($fieldname, -2) == 'Id')
                {

                    $public_field = substr($fieldname, 0, -2);
                    $protected_field = substr($field, 0, -3);

                    $model_config = $this->_config->getModelConfig();
                    $type_hint = $model_config->prefix . $public_field;

                    $def .= <<<TXT

    /**
     * public setter for {$public_field}
     *
     * @param {$type_hint} \${$public_field}
     *
     * @access public
     * @return \$this
     */
    public function set{$public_field}({$type_hint} \${$public_field})
    {
        \$this->_data['{$field}'] = \${$public_field}->getId();
        \$this->{$protected_field} = \${$public_field};
        \$this->_dirty_fields['{$field}'] = true;
        return \$this;
    }

    /**
     * protected setter for {$field}
     *
     * @param int \${$field}
     *
     * @access protected
     * @return \$this
     */
    protected function set{$fieldname}(\${$field})
    {
        \$this->_data['{$field}'] = \${$field};
        return \$this;
    }

    /**
     * public getter for {$public_field}
     *
     * @access public
     * @return {$type_hint}
     */
    public function get{$public_field}()
    {
        if (empty(\$this->{$protected_field}))
        {
            if (empty(\$this->_data['{$field}']))
            {
                return null;
            }
            \$this->{$protected_field} = new {$type_hint}(\$this->_data['{$field}']);
        }
        return \$this->{$protected_field};
    }

TXT;

                }
                else
                {
                    $type = $this->getFieldType($field);

                    $def .= <<<TXT

    /**
     * public setter for {$field}
     *
     * @param {$type} \${$field}
     *
     * @access public
     * @return \$this
     */
    public function set{$fieldname}(\${$field})
    {
        \$this->_data['{$field}'] = \${$field};
        \$this->_dirty_fields['{$field}'] = true;
        return \$this;
    }

    /**
     * public getter for {$field}
     *
     * @access public
     * @return {$type}
     */
    public function get{$fieldname}()
    {
        if (!isset(\$this->_data['{$field}']))
        {
            return null;
        }
        return \$this->_data['{$field}'];
    }

TXT;
                }
            }
        }
        return $def;
    }
}
