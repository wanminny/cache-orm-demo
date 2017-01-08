<?php



namespace Model;

use Orm\MySQL;


abstract class ModelsDao
{

    protected $database;
    protected $dbDriver = '';
    protected static $dao = array();

    protected $cacheSwitch = 1;

    /**
     * @param string $database
     * @return \Orm\MySQL\Quick
     */
    protected function dao($database = '')
    {
    	if(!empty($this->dbDriver)) return $this->dbDriver;
        $_database = empty($database) ? $this->database : $database;

        //添加缓存开关判断
        //return DB::Quick($_database);
        if (empty(self::$dao[$_database])){
//            $_appConfig = Registry::get('appConfig');
            $switch = $this->cacheSwitch;
            if (empty($switch)){
                self::$dao[$_database] = DB::Connection($_database);
            }else{
                self::$dao[$_database] = DB::Quick($_database);
            }
        }
        return self::$dao[$_database];
        
	}

} 