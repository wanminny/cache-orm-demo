<?php



namespace Model;

use Orm\MySQL;


abstract class ModelsDao
{

    protected $database;
    protected $dbDriver = '';
    protected static $dao = array();

    ///可以在参数中进行配置;
    protected $cacheSwitch = 1;

    /**
     * @param string $database
     * @return \Orm\MySQL\Quick
     */
    protected function dao($database = '')
    {
    	if(!empty($this->dbDriver)) return $this->dbDriver;
        $_database = empty($database) ? $this->database : $database;

        if (empty(self::$dao[$_database])){
            $switch = $this->cacheSwitch;
            if (empty($switch)){
                self::$dao[$_database] = DB::Connection($_database);
            }else{
                self::$dao[$_database] = DB::Quick($_database);
            }
        }
        return self::$dao[$_database];
        
	}


    /**
     * 处理查询条件参数
     * @param  array  $parameters
     * @return array
     */
    protected static function compileSelectConditions(array $parameters)
    {
        $orderby = 'ORDER BY ';
        $orderby .= isset($parameters['sortby']) ? $parameters['sortby'] : 'userid';
        $orderby .= ' ';
        $orderby .= isset($parameters['order']) ? $parameters['order'] : 'ASC';

        $limit = '';
        (isset($parameters['offset']) && isset($parameters['limit'])) && $limit .= 'LIMIT '.$parameters['offset'].','.$parameters['limit'];

        return array(
            'limit' => $limit,
            'orderby' => $orderby
        );
    }

    /**
     * 组装INSERT sql语句列部分
     * @param  array $arr
     * @return string
     */
    protected static function compileInsertColumns(array $values, $multiple = FALSE)
    {
        // 强制置每条插入都为批量插入
        if ( ! is_array(reset($values)))
        {
            $values = array($values);
        }

        $keys = array_keys(reset($values));
        // 获取需要插入的列
        $columns = implode(', ', $keys);

        // 获取需要插入列对应的值
        $parameters = implode(', ', array_map(function($value){return ':'.$value;}, $keys));

        $fill_value = "$parameters";
        if($multiple)
        {
            $fill_value = "($parameters)";
        }
        $value = array_fill(0, count($values), $fill_value);

        $parameters = implode(', ', $value);


        return array('columns'=>$columns, 'values'=>$parameters);
    }

    /**
     * 组装UPDATE sql语句列部分
     * @param  array $arr
     * @return string
     */
    protected static function compileUpdateColumns($arr)
    {
        $columns = array();

        foreach ($arr as $key => $value)
        {
            $columns[] = $key.' = :'.$key;
        }

        return implode(', ', $columns);
    }

    public function _array_null($arr)
    {
        foreach($arr as $key => $val)
        {
            if(is_array($val))
            {
                $arr[$key] = $this->_array_null($val);
            }
            if(is_null($val))
            {
                $arr[$key] = '';
            }
        }
        return $arr;
    }


    /**
     * dulicate sql 组成
     * @param array $params
     * @param array $insert_columns_array
     * @param array $update_columns_array
     * @return array
     */
    public static function compileDuplicate($params,$insert_columns_array,$update_columns_array)
    {
        $insert_params_array = $update_params_array = array();
        foreach($params as $key => $val)
        {
            if(in_array($key, $insert_columns_array))
            {
                $insert_params_array[$key] = $val;
            }
            if(in_array($key, $update_columns_array))
            {
                $update_params_array[$key] = $val;
            }
        }
        $insert_columns = self::compileInsertColumns($insert_params_array);
        $update_columns = self::compileUpdateColumns($update_params_array);
        $colunms = array(
            'insert_columns' => $insert_columns['columns'],
            'insert_values' => $insert_columns['values'],
            'update_columns' => $update_columns
        );
        return $colunms;
    }


} 