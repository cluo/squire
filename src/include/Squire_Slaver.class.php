<?php

/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:08
 */
class Squire_Slaver
{

    public $worker;
    public $name;
    public $pid;
    public $data;

    public function run($worker)
    {
        $this->worker = $worker;
        $this->pid = $this->worker->read();
        $class = "Squire_" . $this->data["parse"];
        $this->exec($class);
    }

    /**
     * 执行命令
     * @param $class
     * @return bool
     */
    public function exec($class)
    {
        $file = ROOT_PATH . "app" . DS . $class . ".class.php";
        if (!file_exists($file)) {
            return false;
        }
        $this->worker->exec($_SERVER["_"], array($file, $this->name, $this->pid, Squire_Master::$config_file));
    }
}