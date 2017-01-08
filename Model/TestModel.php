<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:55
 */


namespace Model;

class TestModel extends ModelsDao
{

    public static $_key_system = "key";
    public static $_tag_system = 'tag';
    const  EXPIRE = 800;

    public function __construct()
    {
        $this->database = 'ship2pv5';
    }

    public  function getData()
    {
        $key = self::$_key_system.'fix.app_info_';
        $data =  $this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->fetchAll("select * from cloud_domain");
//        $data = $this->dao()->tag();

        return $data;
//        var_dump($data);
    }
}