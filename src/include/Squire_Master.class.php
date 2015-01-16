<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:08
 */

class Squire_Master
{
    static public $process_name = "lzm_squire_Master";//进程名称
    static public $pid_file;                    //pid文件位置
    static public $log_path;                    //日志文件位置
    static public $config_file;                 //配置文件位置
    static public $daemon = false;              //运行模式
    static private $pid;                        //pid

    static public $task_list = array();
    static public $start = false;
    static public $stop = false;

    static private $process_list = array();
    static private $workers = array();

    static public function start()
    {
        if (file_exists(self::$pid_file)) {
            die("Pid文件已存在!\n");
        }
        self::daemon();
        self::set_process_name();
        self::run();
        Main::log_write("启动成功");
    }

    static public function stop()
    {
        $pid = @file_get_contents(self::$pid_file);
        if ($pid) {
            if (swoole_process::kill($pid, 0)) {
                swoole_process::kill($pid, SIGTERM);
                @unlink(self::$pid_file);
                Main::log_write("进程" . $pid . "已结束");
            } else {
                @unlink(self::$pid_file);
                Main::log_write("进程" . $pid . "不存在,删除pid文件");
            }
        } else {
           Main::log_write("进程未启动");
        }
    }

    static public function restart()
    {
        self::stop();
        sleep(1);
        self::start();
    }

    static private function run()
    {
        self::get_pid();
        self::write_pid();
        self::params_config();
        foreach (self::$task_list as $task => $data) {
            self::create_child_process($task,$data);
        }
        self::register_signal();
        self::$start = true;
    }

    /**
     * 是否后台运行
     */
    static private function daemon()
    {
        if (self::$daemon) {
            swoole_process::daemon();
        }
    }

    /**
     * 解析配置文件
     */
    static private function params_config($reload = false)
    {
        Squire_LoadConfig::$config_file = self::$config_file;
        if($reload) Squire_LoadConfig::reload_config();
        self::$task_list = Squire_LoadConfig::get_config();
    }

    /**
     * 创建执行任务的子进程
     * @param $task
     * @param $data
     */
    static private function create_child_process($task,$data)
    {
        if (empty(self::$process_list[$task])) {
            $slaver = new Squire_Slaver();
            $slaver->name = $task;
            $slaver->data = $data;
            $process = new swoole_process(array($slaver, "run"));

            self::$process_list[$task] = $process;
        } else {
            $process = self::$process_list[$task];
        }
        $pid = $process->start();
        self::$workers[$pid] = array("task" => $task, "process" => $process);
        $process->write(self::$pid);
    }

    /**
     * 注册信号
     */
    static private function register_signal()
    {
        //注册子进程退出信号逻辑
        swoole_process::signal(SIGCHLD, function ($signo) {
            while (($pid = pcntl_wait($status, WNOHANG)) > 0) {
                Main::log_write("收到子进程{$pid}退出信号");
                if (!isset(Squire_Master::$workers[$pid]["logout"])) {
                    $task = Squire_Master::$workers[$pid]["task"];
                    Squire_Master::create_child_process($task,Squire_Master::$task_list[$task]);
                }
                unset(Squire_Master::$workers[$pid]);
            };
        });

        //注册主进程退出逻辑
        swoole_process::signal(SIGTERM, function ($signo) {
            Main::log_write("收到主进程退出信号, 发送子进程退出信号:" . $signo);
            foreach (Squire_Master::$workers as $pid => $process) {
                Squire_Master::$workers[$pid]["logout"] = true;
                swoole_process::kill($pid, SIGUSR2);
            }
            if (!empty(Main::$http_server)) {
                swoole_process::kill(Main::$http_server->pid, SIGKILL);
            }
            Main::log_write("已发送子进程退出信号,主进程正在退出.....");
            swoole_timer_add(501,function(){
                if(count(Squire_Master::$workers) == 0){
                    Squire_Master::exit2p("主进程退出成功");
                }
            });
        });
        //注册重新载入配置信号
        swoole_process::signal(SIGUSR1, function ($signo) {
            Main::log_write("收到重新载入配置信号:" . $signo);
            Squire_Master::reload();
        });
    }

    /**
     * 重载进程
     */
    static public function reload()
    {
        foreach (self::$workers as $pid => $process) {
            Squire_Master::$workers[$pid]["logout"] = true;
            swoole_process::kill($pid, SIGUSR2);

        }
        Squire_Master::$process_list = array();
        Squire_Master::params_config(true);
        foreach (Squire_Master::$task_list as $task => $data) {
            Squire_Master::create_child_process($task,$data);
        }
    }

    /**
     * 设置进程名称
     */
    static private function set_process_name()
    {
        if (!function_exists("swoole_set_process_name")) {
            self::exit2p("Please install swoole extension.http://www.swoole.com/");
        }
        swoole_set_process_name(self::$process_name);
    }

    /**
     * 获取进程id
     */
    static private function get_pid()
    {
        if (!function_exists("posix_getpid")) {
            self::exit2p("Please install posix extension.");
        }
       self::$pid = posix_getpid();
    }

    /**
     * 写入pid文件
     */
    static private function write_pid()
    {
        file_put_contents(self::$pid_file, self::$pid);
    }

    /**
     * 退出主进程
     * @param $msg
     */
    static public function exit2p($msg)
    {
        @unlink(self::$pid_file);
        Main::log_write($msg . "\n");
        exit();
    }

}