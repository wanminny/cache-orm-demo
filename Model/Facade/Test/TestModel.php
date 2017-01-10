<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:55
 */


namespace Model\Facade\Test;

use Model\Service\Test\ServiceTest;

class TestModel
{

    public static $_service = null;

    public static function service()
    {
        if(self::$_service === null)
        {
            self::$_service = new ServiceTest();

        }
        return self::$_service;
    }

    public  function getData()
    {
        $result = self::service()->getData();
        return $result;
    }

}