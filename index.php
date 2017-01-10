<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:45
 */

require_once("./autoloader.php");

Autoloader::loader();

define("APP_PATH",dirname(__FILE__));

$c = new \Controller\TestController();
$data = [];
for($i = 0;$i<1;$i++) {
    $data = $c->orm();

}

var_dump($data);
