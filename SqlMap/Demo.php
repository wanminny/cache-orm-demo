<?php
/**
 * Created by PhpStorm.
 * User: wanmin
 * Date: 17/1/10
 * Time: 上午10:49
 */

namespace SqlMap;


class Demo
{
    const SELECTALL = "select * from cloud_domain";

    const CHECKAPPVALID = "SELECT COUNT(*) FROM app_info WHERE app_id = :app_id AND state = 1";

    const INSERTAPPINFO = "INSERT INTO app_info(#columns#) VALUES (#values#)";

}

