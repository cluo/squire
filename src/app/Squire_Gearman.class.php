<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-29
 * Time: 下午3:44
 */

class Squire_Gearman    implements Squire_Common
{
    static public $worker;
    public function run($worker,$task)
    {
        self::$worker = $worker;
        $_SERVER["argv"][1] = preg_replace("/\d{1,}\_/","",$task["name"]);
        sleep(10);
    }
    static public function _exit()
    {
        self::$worker->exit(1);
    }
}