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
        if (file_put_contents($this->path . $this->getClassFilename(), $definition) === false) {
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
            . $this->createFooterCrud();
    }

    /**
     * creates the top part of the class
     *
     * @access protected
     * @return string
     */
    protected function createHeaderCrud() {
        return <<<PHP
<?php
/**
 * Mapper file created automatically
 * with DataMapper
 */

class {$this->getClassName(true)} extends DataMapper {
    /**
     * instance of model
     *
     * @var {$this->getModelClassName()}
     */
    protected \$model;

    /**
     * table name
     *
     * @var string
     */
    protected \$table_name = '{$this->info['tablename']}';

    /**
     * data storage
     *
     * @var array
     */
    protected \$data;

    /**
     * fields of associated table
     *
     * @var array
     */
    protected \$table_fields = array({$this->getTableFieldsString()});

    /**
     * primary key(s) for associated table
     *
     * @var array
     */
    protected \$primary_keys = array({$this->getPrimaryKeysString()});


PHP;
    }
    /**

     * read access to table definition
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
     * returns a string with all the names
     * of the fields of the matching db table
     *
     * @access protected
     * @return string
     */
    protected function getTableFieldsString() {
        return "'" . implode("', '", array_keys($this->info['columns'])) . "'";
    }

    /**
     * returns a string with all the names
     * of the fields used in the primary key
     *
     * @access protected
     * @return string
     */
    protected function getPrimaryKeysString() {
        return "'" . implode("', '", $this->getPrimaryKeys()) . "'";
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
}
