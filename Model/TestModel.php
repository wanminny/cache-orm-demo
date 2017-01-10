<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:55
 */


namespace Model;
use SqlMap\Demo;

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

        //select
        $key = self::$_key_system.'fix.app_info_';
        $data =  $this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->fetchAll(Demo::SELECTALL);

        var_dump($this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->getSQL(Demo::SELECTALL));


        //select parameter.
        $id = 1;
        $data1 =  $this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->fetchAll(Demo::CHECKAPPVALID,["id"=>$id]);

        var_dump($this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->getSQL(Demo::CHECKAPPVALID,["id"=>$id]));
        var_dump($data1);


        // insert
        $domain ="www.yihaojf.com";
        $app_id = 10001;
        $app_secret = "aadsfdaf";
        $status = 1;
        $type = 5;
        $remark = "fuck remark!";
        $default = 2;
        //compact   extract
        // 默认关键字要有`` eg: `default`  此处不要用关键字命名!!
//        http://www.tuicool.com/articles/Brauq2e
//        $columnsArr = array_filter(compact('domain','app_id', 'app_secret','add_ip','status','type','remark','default'));
        $columnsArr = array_filter(compact('domain','app_id', 'app_secret','add_ip','status','type','remark'));

        $columnsArr['add_time'] = time();
        $replaces = static::compileInsertColumns($columnsArr);
//        var_dump($columnsArr,$replaces);die;

        $data2 =  $this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->insert(Demo::INSERTAPPINFO,$columnsArr,$replaces);
        var_dump($this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->getSQL(Demo::INSERTAPPINFO,$columnsArr,$replaces));


        //update:
        $remark = "fuck 发反反复复方法反反复复!";
        $app_secret = "ssdddd";
        $columnsArr = array_filter(compact('remark','app_secret'));
        $columnsArr['add_time'] = time();

        /// 要替换的字符串  #replace#
        $columns['columns'] = static::compileUpdateColumns($columnsArr);
        /// 变量绑定 :status
        $columnsArr['status'] = 1;


        $data3 =  $this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->update(Demo::UPDATEAPPINFO,$columnsArr, $columns);

        var_dump($this->dao()->tag(self::$_tag_system)->key($key)
            ->expire(self::EXPIRE)->getSQL(Demo::UPDATEAPPINFO,$columnsArr, $columns));
//        return $data;
    }
}