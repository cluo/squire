<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:08
 */
class Squire_Slaver{

    private $process_name_prefix = "lzm_gearman_";
    public $worker;
    public $name;
    public $data;



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

    static protected function autoload($class)
    {
        static $_load = array();
        if(isset($_load[$class])) return true;
        include(ROOT_PATH . "app" . DS . "Squire_Common.class.php");
        $file = ROOT_PATH . "app" . DS . $class . ".class.php";
        if (file_exists($file)) {
            include($file);
        } else {
            Main::log_write("处理类不存在");
        }
        $_load[$class] = true;
        return true;
    }

    public function run($worker)
    {
        $GLOBALS["worker"] = $worker;
        $this->worker = $worker;
        $pid = $this->worker->read();
        $this->worker->name($this->process_name_prefix.$this->name."_".$pid);
        $this->register_signal();
        $this->add_event();
        $class = "Squire_".$this->data["parse"];
        self::autoload($class);
        (new $class)->run($worker,array_merge($this->data["data"],array("name"=>$this->name)));
    }
}