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
     * constructor
     *
     * @param string $tablename
     * @param array  $definition
     *
     * @access public
     * @return void
     */
    public function __construct($tablename, array $definition) {
        $this->tablename  = $tablename;
        $this->definition = $definition;
    }

    /**
     * returns the name of the table
     *
     * @access public
     * @return string
     */
    public function getTableName() {
        return $this->_tablename;
    }

    /**
     * returns the collected table information
     *
     * @access public
     * @return array
     */
    public function getColumnInfo()
    {
        return $this->_column_info;
    }

    /**
     * queries the database for info about the table
     *
     * @access private
     * @return void
     */
    private function _gatherTableInformation()
    {
        $res = mysql_query("DESCRIBE `{$this->_tablename}`;");
        if (empty($res)) throw new Exception("Could not query database for table information for {$this->_tablename}");
        while ($row = mysql_fetch_assoc($res))
        {
            $this->_column_info[$row['Field']] = array(
                'type'     => strtolower($row['Type']),
                'null'     => $row['Null'],
                'key'      => $row['Key'],
                'default'  => $row['Default'],
                'extra'    => $row['Extra'],
            );
        }
    }

    /**
     * returns the name of the class
     *
     * @param bool $prefix - return name with prefix
     *
     * @access public
     * @return string
     */
    public function getClassName($prefix = false)
    {
        if (empty($this->_classname))
        {
            $classname = preg_replace(array('/(es|s)$/', '/ies$/'), array('', 'y'), $this->_tablename);
            preg_match_all('/(s?)_([a-z])/', $classname, $matches, PREG_SET_ORDER);
            foreach ($matches as $match)
            {
                $classname = str_replace($match[1] . '_' . $match[2], strtoupper($match[2]), $classname);
            }
            $this->_classname = ucfirst($classname);
        }
        $model_config = $this->_config->getModelConfig();
        return $prefix ? $model_config->prefix . $this->_classname : $this->_classname;
    }

    /**
     * returns the type of a given field
     *
     * @param string $field
     *
     * @throws Exception
     * @access public
     * @return string
     */
    public function getFieldType($field)
    {
        if (!isset($this->_column_info[$field]))
        {
            throw new Exception("No such field in table");
        }
        $type = $this->_column_info[$field]['type'];

        // number types
        if (preg_match('/^(tiny|medimum|long)?int/', $type))
        {
            return 'int';
        }
        if (preg_match('/^(float|real|decimal|double)/', $type))
        {
            return 'float';
        }

        // date/time types
        if (strpos($type, 'datetime') === 0)
        {
            return 'string';
        }
    }

    /**
     * returns the filename of the class
     *
     * @access public
     * @return string
     */
    public function getClassFilename()
    {
        $gen_config = $this->_config->getGeneralConfig();
        return $gen_config->uppercase_class_files ? $this->getClassName() . '.php' : strtolower($this->getClassName()) . '.php';
    }

    /**
     * returns a string describing what the model extends
     * if anything
     *
     * @access private
     * @return string
     */
    private function _getModelExtend()
    {
        $model_config = $this->_config->getModelConfig();
        return $model_config->extends == '' ? '' : ' extends ' . $model_config->extends;
    }

    /**
     * returns all keys used for the primary key
     * of the associated database table
     *
     * @access private
     * @return array
     */
    private function _fetchPrimaryKeys()
    {
        $keys = array();
        foreach ($this->_column_info as $field => $info)
        {
            if (strtolower($info['key']) == 'pri')
            {
                $keys[] = $field;
            }
        }
        return $keys;
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

    /**
     * stores the generated class
     * tries to be intelligent about naming/overwriting
     * based on config
     *
     * @access private
     * @return void
     */
    private function _storeClass()
    {
        $model_config = $this->_config->getModelConfig();
        $file = new MM_File($model_config->path, $this->getClassFilename());
        $file->store($this->_class_def, $model_config->overwrite);
    }
}
