<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:54
 */
namespace Controller;
use Model\Facade\Test\TestModel;

class TestController
{
    public function orm()
    {
        $model = new TestModel();
        $data = $model->getData();
        return $data;
    }
}