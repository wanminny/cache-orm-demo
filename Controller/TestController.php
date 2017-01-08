<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:54
 */
namespace Controller;
use Model\TestModel;

class TestController
{
    public function orm()
    {
//        echo __FILE__;
//        return __FILE__;
        $model = new TestModel();
        $data = $model->getData();
//        var_dump($data);
    }
}