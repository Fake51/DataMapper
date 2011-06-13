<?php

class DataMapperBase {

    /**
     * table schema
     *
     * @var array
     */
    protected $info;

    /**
     * instance of the DataMapper class
     * mainly used here for debugging/output
     * purposes
     *
     * @var DataMapperUtil
     */
    protected $datamapper;

    /**
     * name of the class being generated
     *
     * @var string
     */
    protected $classname;

    /**
     * whether to overwrite model files
     *
     * @var bool
     */
    protected $overwrite;

    /**
     * path to create files in
     *
     * @var string
     */
    protected $path;

    /**
     * constructor
     *
     * @param DataMapper $datamapper
     * @param array      $info
     * @param string     $path
     * @param bool       $overwrite
     *
     * @access public
     * @return void
     */
    public function __construct(DataMapperUtil $datamapper, array $info, $path, $overwrite) {
        $this->datamapper = $datamapper;
        $this->info       = $info;
        $this->path       = $path;
        $this->overwrite  = !!$overwrite;
    }

    /**
     * returns all keys used for the primary key
     * of the associated database table
     *
     * @access protected
     * @return array
     */
    protected function getPrimaryKeys() {
        $keys = array();
        foreach ($this->info['columns'] as $field => $info) {
            if (strtolower($info['key']) == 'pri') {
                $keys[] = $field;
            }
        }
        return $keys;
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
    public function getFieldType($field) {
        if (!isset($this->info['columns'][$field])) {
            throw new Exception("No such field in table");
        }
        $type = $this->info['columns'][$field]['type'];

        // number types
        if (preg_match('/^(tiny|medimum|long)?int/', $type)) {
            return 'int';
        }
        if (preg_match('/^(float|real|decimal|double)/', $type)) {
            return 'float';
        }

        // date/time types
        if (preg_match('/^(time|date)/', $type)) {
            return 'string';
        }
    }
}
