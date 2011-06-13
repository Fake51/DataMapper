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
 * creates templates for model classes
 *
 * @package DataMapper
 * @author  Peter Lind <peter.e.lind@gmail.com>
 */
class DataMapperModel extends DataMapperBase {

    /**
     * instance of the data mapper for
     * creating the model-mapper pair
     *
     * @var DataMapperMapper
     */
    protected $mapper;

    /**
     * creates the model file
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
     * sets the model the mapper is for
     *
     * @param DataMapperModel $model
     *
     * @access public
     * @return $this
     */
    public function setMapper(DataMapperMapper $mapper) {
        $this->mapper = $mapper;
        return $this;
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
 * Model file created automatically
 * with DataMapper
 */

class {$this->getClassName(true)}{$extends} {
    /**
     * instance of datamapper for class
     *
     * @var {$this->mapper->getClassName()}
     */
    protected \$mapper;


PHP;
    }

    /**
     * creates the construct part of the
     * model class
     *
     * @access protected
     * @return string
     */
    protected function createConstructor() {
        $type_length = 0;
        $params = array(
            '$database_connection' => 'resource',
            '$mapper'              => $this->mapper->getClassName(),
        );
        foreach ($params as $type) {
            $type_length = strlen($type) > $type_length ? strlen($type) : $type_length;
        }
        $primary_keys = $this->getPrimaryKeys(); 
        $primary_params = array();
        foreach ($primary_keys as $key) {
            $field_type = $this->getFieldType($key);
            $primary_params["\$$key"] = $field_type;
            $type_length = strlen($field_type) > $type_length ? strlen($field_type) : $type_length;
        }

        $txt = <<<TXT
    /**
     * constructor
     *
TXT;
        foreach (array_merge($params, $primary_params) as $name => $type) {
            $txt .= "
     * @param " . str_pad($type, $type_length, ' ') . " $name";
        }

        $param_def = implode(', ', array_keys($params)) . ', ' . implode(' = null, ', array_keys($primary_params)) . ' = null';
        $parent_construct = empty($this->info['extends']) ? '' : "parent::__construct(" . implode(', ', array_keys(array_merge($params, $primary_params))) . ");" . PHP_EOL;
        $txt .= <<<TXT

     *
     * @access public
     * @return void
     */
    public function __construct({$param_def}) {
        \$this->db     = \$database_connection;
        \$this->mapper = \$mapper;
        {$parent_construct}
TXT;

        if (count($primary_params)) {
            $key_check = '$' . implode(' && $', $primary_keys);

            $txt .= <<<TXT
        if ({$key_check}) {

TXT;
            if (count($primary_params == 1) && isset ($this->info['columns']['id'])) {
                // dealing with normal entity with an id
                $txt .= <<<TXT
            if (!\$this->load(\${$primary_keys[0]})) {
                throw new Exception("Could not load {$this->getClassName()} with ID \${$primary_keys[0]}");
            }

TXT;
            } else {
                $find_data = array();
                foreach ($primary_keys as $key) {
                    $find_data[] = "'{$key} = ?' => \${$key}";
                }
                $find_string = implode(', ', $find_data);

                $txt .= <<<TXT
            if (!\$this->mapper->load(array({$find_string}))) {
                throw new Exception("Could not load {$this->getClassName()} with provided parameters");
            }

TXT;
            }

            $txt .= <<<TXT
        }

TXT;
        }

        $txt .= <<<TXT
    }


TXT;

        return $txt;
    }

    /**
     * creates load and save definitions for the
     * model class
     *
     * @access protected
     * @return string
     */
    protected function createIOMethods() {
        $text = <<<TXT
    /**
     * wrapper for call to the datamappers
     * save method
     *
     * @throws Exception
     * @access public
     * @return \$this
     */
    public function save() {
        \$this->mapper->save(\$this);
        return \$this;
    }

    /**
     * wrapper for call to the datamappers
     * load method - will take arguments
     * to the effect of a primary key
     *
     * @throws Exception
     * @access public
     * @return \$this
     */
    public function load(/* args */) {
        \$args = get_func_args();
        array_unshift(\$args, \$this);
        call_user_func_array(array(\$this->mapper, 'load'), \$args);
        return \$this;
    }

TXT;

        return $text;
    }

    /**
     * returns crud for the end of the
     * model class
     *
     * @access protected
     * @return string
     */
    protected function createFooterCrud() {
        $txt = <<<TXT
}

TXT;
        return $txt;
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
                $classname = str_replace($match[1] . '_' . $match[2], strtoupper($match[2]), $classname);
            }
            $this->classname = ucfirst($classname);
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
     * creates the constructor function for the class
     *
     * @access private
     * @return string
     */
    private function _makeConstructor()
    {
        $zend_config = $this->_config->getZendConfig();

        $pkeys = $this->_fetchPrimaryKeys();
        $txt = <<<TXT
    /**
     * public constructor
     *

TXT;
        $params = array();
        foreach ($pkeys as $key)
        {
            $params[] = "\$$key = null";
            $txt .= <<<TXT
     * @param {$this->getFieldType($key)} \$key

TXT;
        }

        $param_def = implode(', ', $params);
        $txt .= <<<TXT
     *
     * @access public
     * @return void
     */
    public function __construct({$param_def})
    {
        parent::__construct();

TXT;

        $zend_config = $this->_config->getZendConfig();
        if ($zend_config->use_dbtables)
        {
            $db_table = new MM_DbTable($this, $this->_config);
            $txt .= <<<TXT
        \$this->_db_table = new {$db_table->getClassName()};

TXT;
        }

        if (count($pkeys))
        {
            $key_check = '$' . implode(' && $', $pkeys);

            $txt .= <<<TXT
        if ({$key_check})
        {

TXT;
            if (count($pkeys == 1) && isset ($this->_column_info['id']))
            {
                // dealing with normal entity with an id
                $txt .= <<<TXT
            if (!\$this->find(\${$pkeys[0]}))
            {
                throw new Exception("Could not load {$this->getClassName()} with ID \${$pkeys[0]}");
            }

TXT;
            }
            else
            {
                foreach ($pkeys as $key)
                {
                    $find_data[] = "'{$key} = ?' => \${$key}";
                }
                $find_string = implode(', ', $find_data);

                $txt .= <<<TXT
            if (!\$this->findBy(array({$find_string})))
            {
                throw new Exception("Could not load {$this->getClassName()} with provided parameters");
            }

TXT;
            }

            $txt .= <<<TXT
        }

TXT;
        }

        $txt .= <<<TXT
    }


TXT;

        return $txt;
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
