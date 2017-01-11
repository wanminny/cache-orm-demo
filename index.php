<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:45
 */

require_once("./autoloader.php");

Autoloader::loader();

//设置环境
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'developer');
//
//if(APPLICATION_ENV == "developer"  || APPLICATION_ENV == "testing")
//{
//    ini_set('display_errros',1);
//    error_reporting(E_ALL);
//}
//else{
//    error_reporting(E_ERROR);
//}

switch (APPLICATION_ENV)
{
    case 'developer':
    case 'testing':
        error_reporting(-1);
        ini_set('display_errors', 1);
        break;

    case 'production':
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>='))
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
        else
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1); // EXIT_ERROR
}


define("APP_PATH",dirname(__FILE__));

$c = new \Controller\TestController();
$data = [];
for($i = 0;$i<1;$i++) {
    $data = $c->orm();

}

var_dump($data);
