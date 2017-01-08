<?php
/**
 * Created by PhpStorm.
 * User: Zip
 * Date: 14/12/7
 * Time: 下午2:48
 */



namespace Orm\MySQL;
class DebugException extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            parent::__construct($message, (int)$code);
        } else {
            parent::__construct($message, (int)$code, $previous);
        }
    }
} 