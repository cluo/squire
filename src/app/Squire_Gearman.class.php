<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-29
 * Time: 下午3:44
 */

class Squire_Gearman
{
    static public $conf_file;
    static $process_name_prefix = "lzm_squire";
    static $pid;


    static public function register_signal()
    {
        swoole_process::signal(SIGUSR1, function ($signal_num) {
            echo "收到结束信号，结束进程";
            exit();
        }
        );
    }
    static public function set_process_name($name)
    {
        swoole_set_process_name(self::$process_name_prefix.$name."_".self::$pid);
    }
    static public function run($name)
    {
        self::set_process_name($name);
        self::register_signal();
        $_SERVER["argv"][1] = preg_replace("/\d{1,}\_/","",$name);
        echo $_SERVER["argv"][1]."\n";
        sleep(100);
    }
    static public function _exit()
    {
        self::$worker->exit(1);
    }
}


if(!empty($argv[2]))
    Squire_Gearman::$pid = $argv[2];
if(!empty($argv[3]))
    Squire_Gearman::$conf_file = $argv[3];

Squire_Gearman::run($argv[1]);