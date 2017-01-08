<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/8
 * Time: 下午5:23
 */

namespace Model;

use Orm\MySQL;

class DB
{
    /**
     * 原生不带缓存
     * @param $database
     * @return MySQL\Connection
     */
    static function Connection($database)
    {
        return new MySQL\Connection($database);
    }

    /**
     * 快速数据存取(带缓存的DB)
     * @param $database
     * @return MySQL\Quick
     */
    static function Quick($database)
    {
        return new MySQL\Quick($database);
    }
}