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

    static private function create_child_process($task,$data)
    {
        if (empty(self::$process_list[$task])) {
            $slaver = new Squire_Slaver();
            $slaver->name = $task;
            $slaver->data = $data;
            $process = new swoole_process(array($slaver, "run"));

            self::$process_list[$task] = $process;

            swoole_event_add($process->pipe, function ($pipe) use ($process) {
                $recv = $process->read();
                Main::log_write("From {$process->pid} :" . $recv);
            });
        } else {
            $process = self::$process_list[$task];
        }
        $pid = $process->start();
        self::$workers[$pid] = array("task" => $task, "process" => $process);
        $process->write(self::$pid);
    }

    static private function register_signal()
    {
        swoole_process::signal(SIGCHLD, function ($signo) {
            while (($pid = pcntl_wait($status, WNOHANG)) > 0) {
                if (!self::$stop) {
                    $task = self::$workers[$pid]["task"];
                    self::create_child_process($task,self::$task_list[$task]);
                }
                unset(self::$workers[$pid]);
            };
        });

        swoole_process::signal(SIGTERM, function ($signo) {
            Main::log_write("收到主进程退出信号, 发送子进程退出信号:" . $signo);
            self::$stop = true;
            foreach (self::$workers as $pid => $process) {
                swoole_process::kill($pid, $signo);
            }
            if (!empty(Main::$http_server)) {
                swoole_process::kill(Main::$http_server->pid, SIGKILL);
            }
            sleep(1);
            self::exit2p("已发送子进程退出信号,主进程退出");
        });
        //重新载入配置
        swoole_process::signal(SIGUSR1, function ($signo) {
            Main::log_write("收到重新载入配置信号:" . $signo);
            self::$stop = true;
            foreach (self::$workers as $pid => $process) {
                swoole_process::kill($pid, SIGTERM);
            }
            self::$stop = false;
            self::$workers = array();
            self::$process_list = array();
            self::params_config();
            foreach (self::$task_list as $task => $data) {
                self::create_child_process($task,$data);
            }
        });
    }

    static private function set_process_name()
    {
        if (!function_exists("swoole_set_process_name")) {
            self::exit2p("Please install swoole extension.http://www.swoole.com/");
        }
        swoole_set_process_name(self::$process_name);
    }

    static private function get_pid()
    {
        if (!function_exists("posix_getpid")) {
            self::exit2p("Please install posix extension.");
        }
       self::$pid = posix_getpid();
    }

    static private function write_pid()
    {
        file_put_contents(self::$pid_file, self::$pid);
    }

    static public function exit2p($msg)
    {
        @unlink(self::$pid_file);
        Main::log_write($msg . "\n");
        exit();
    }

}