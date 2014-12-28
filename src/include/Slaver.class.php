<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:08
 */
class Slaver{

    private $process_name_prefix = "lzm_gearman_";
    public $worker;
    public $name;



    private function register_signal()
    {
        swoole_process::signal(SIGTERM, function ($signal_num) {
            $this->worker->write($this->name.":signal call = $signal_num");
            $this->worker->exit();
        }
        );
    }

    private function add_event()
    {
        swoole_event_add($this->worker->pipe, function ($pipe) {
            //TODO 这里可以写主进程通知子进程的逻辑
            $recv = $this->worker->read();
        }
        );
    }

    public function run($worker)
    {
        $GLOBALS["worker"] = $worker;
        $this->worker = $worker;

        $recv = $this->worker->read();
        $recv = explode("#",$recv);
        $name = $recv[0];
        $pid = $recv[1];

        $_SERVER["argv"][1] = preg_replace("/\d{1,}\_/","",$name);
        $this->name = $this->process_name_prefix.$name."_".$pid;
        $this->worker->name($this->name);
        $this->register_signal();
        $this->add_event();
//        require(APP_PATH . '..' . DS . 'ThinkPHP' . DS . 'ThinkPHP.php');

    }
}