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
    //select
    const SELECTALL = "select * from cloud_domain";

    const CHECKAPPVALID = "SELECT COUNT(*) FROM cloud_domain WHERE id = :id AND status = 1";

    //insert
    const INSERTAPPINFO = "INSERT INTO cloud_domain(#columns#) VALUES (#values#)";

    //update
    const UPDATEAPPINFO = "UPDATE cloud_domain SET #columns# WHERE status = :status";

}

