<?php

/**
 * Created by PhpStorm.
 * User: vic
 * Date: 15-1-8
 * Time: 下午9:31
 */
class Squire_Manager
{

    /**
     * 获取任务列表
     * @param $params
     * @return array
     */
    function getworker_cron($params)
    {
        return $this->output(Squire_LoadConfig::get_ori_config());
    }

    /**
     * 重新加载配置文件
     * @param $params
     * @return array
     */
    function reloadworker_cron($params)
    {
        Squire_Master::reload();
        return $this->output("ok");
    }

    /**
     * 添加或替换
     * @param $params
     * @return array
     */
    function addworker_cron($params)
    {
        $workers = isset($params["post"]["workers"]) ? $params["post"]["workers"] : "";
        $workers = json_decode($workers, true);
        if (empty($workers)) {
            return $this->output("参数有误", false);
        }
        foreach ($workers as $id => $worker) {
            if (empty($worker["name"]) || empty($worker["processNum"]) || empty($worker["parse"]) || empty($worker["task"])) {
                return $this->output("参数有误", false);
            }
        }
        Squire_LoadConfig::send_config($workers);
        $config = Squire_LoadConfig::parse_config($workers);
        Squire_Master::reload($config);
        return $this->output("ok");
    }

    /**
     * 删除
     * @param $params
     * @return array
     */
    function delworker_cron($params)
    {
        $workerid = isset($params["get"]["workerid"])?$params["get"]["workerid"]:"";
        if (empty($workerid)) {
            return $this->output("参数有误", false);
        }
        $config = Squire_LoadConfig::del_config($workerid);
        if(empty($config)){
            return $this->output("workerid不存在", false);
        }
        Squire_Master::exitprocess($config);
        return $this->output("ok");
    }

    /**
     * 日志
     * @param $params
     */
    function loglist_http($request)
    {
        $date = isset($request->get["date"]) ? $request->get["date"] : "";
        if ($date) {
            $filename = ROOT_PATH . "logs/log_" . $date . ".log";
            $data = file_get_contents($filename);
            $data = $this->output($data);
        } else {
            $data = $this->output("参数有误", false);
        }
        return $data;
    }

    /**
     * 导入任务配置数据，会清空存在的数据
     * @param $request
     * @param $response
     */
    function importconf_http($request)
    {
        $workers = isset($request->post["workers"])?$request->post["workers"]:"";
        $workers = json_decode($workers, true);

        if (empty($workers)) {
            return $this->output("参数有误", false);
        }
        foreach ($workers as $id => $worker) {
            if (empty($worker["name"]) || empty($worker["processNum"]) || empty($worker["parse"]) || empty($worker["task"])) {
               return $this->output("参数有误", false);
            }
        }
        ob_start();
        var_export($workers);
        $config = ob_get_clean();
        file_put_contents(Http::$conf_file, "<?php \n return " . $config . ";");
        fwrite(Http::$fp, "reloadworker#@#" . json_encode(array()));
        return $this->output("ok");
    }

    /**
     * 统一输出
     * @param $data
     * @param bool $status
     * @return array
     */
    public function output($data, $status = true)
    {
        return array("status" => $status, "data" => $data);
    }
}