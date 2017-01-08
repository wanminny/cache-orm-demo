<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午4:53
 */

class Autoloader
{
    public function __construct()
    {
        spl_autoload_register(array($this, 'register'));
    }
    private function register($className)
    {
        if (stristr($className, '\\') === FALSE) {
            $classNamePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        } else {
            $parts = explode('\\', $className);
            $classNamePath = implode(DIRECTORY_SEPARATOR, $parts) . '.php';
        }
        $includePath = explode(':', get_include_path());
        foreach ($includePath as $path) {
            $classPath = $path . DIRECTORY_SEPARATOR . $classNamePath;
            if (file_exists($classPath)) {
                require_once($classPath);
                return;
            }
        }
    }
    public static function loader()
    {
        new Autoloader();
    }
}