<?php

/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-27
 * Time: 下午3:46
 */
class Squire_LoadConfig
{
    static public $config_file;
    static protected $ori_config;
    static protected $config = array();

    /**
     * 返回格式化好的任务配置
     * @return array
     */
    static public function get_config()
    {
        if (empty(self::$config)) {
            self::load_config();
            self::$config = self::parse_config();
        }

        return self::$config;
    }

    static public function reload_config()
    {
        self::load_config();
        self::$config = self::parse_config();
    }

    static public function send_config($workers)
    {
        foreach($workers as $id=>$worker)
        {
            self::$ori_config[$id] = $worker;
        }
        self::save_config();
    }

    /**
     * 从配置文件载入配置
     */
    static protected function load_config()
    {
        if (is_dir(self::$config_file)) {
            self::$ori_config = self::load_by_path(self::$config_file);
        } elseif (is_file(self::$config_file)) {
            self::$ori_config = include(self::$config_file);
        }
    }

    static protected function load_by_path($path)
    {
        $config = array();
        $files = glob($path . "*.php");
        if (empty($files)) {
            return array();
        }
        foreach ($files as $filename) {
            $conf = include($filename);
            $config = array_merge($config, $conf);
        }
        return $config;
    }

    /**
     * 格式化配置文件中的配置
     * @return array
     */
    static protected function parse_config()
    {
        $config = array();
        foreach (self::$ori_config as $id => $worker) {
            for ($i = 1; $i <= $worker["processNum"]; $i++) {
                $config[$i . "_" . $id] = array("parse" => $worker["parse"], "data" => $worker["task"]);
            }
        }
        return $config;
    }

    static public function del_config($task)
    {
        unset(self::$ori_config[$task]);
        self::save_config();
        self::parse_config();
    }

    static public function get_ori_config()
    {
        if(empty(self::$ori_config)){
            self::load_config();
        }
        return self::$ori_config;
    }

    static protected function save_config()
    {
        ob_start();
        var_export(self::$ori_config);
        $config = ob_get_clean();
        if (is_file(self::$config_file)) {
            $path = self::$config_file;
        } else if (is_dir(self::$config_file)) {
            $path = self::$config_file . "worker.php";
        }
        file_put_contents($path, "<?php \n return " . $config . ";");
    }
}