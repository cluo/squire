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
     * @param $params
     * @return array
     */
    function getworker_cron($params)
    {
        return $this->output(Squire_LoadConfig::get_config());
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

    function addworker_cron($params)
    {
        $workers = isset($params["post"]["workers"])?$params["post"]["workers"]:"";
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
        Squire_Master::reload();
        return $this->output("ok");
    }

    function delworker_cron($params)
    {
        $workerid = $params["get"]["workerid"];
        if (!is_string($workerid)) {
            return $this->output("参数有误", false);
        }
        Squire_LoadConfig::del_config($workerid);
        Squire_Master::reload();
        return $this->output("ok");
    }

    /**
     * @param $params
     */
    function loglist_http($request, $response)
    {
        $date = isset($request->get["date"])?$request->get["date"]:"";
        if ($date) {
            $filename = ROOT_PATH . "logs/log_" . $date . ".log";
            $data = file_get_contents($filename);
            $data = $this->output($data);
        } else {
            $data = $this->output("参数有误", false);
        }
        $response->end(json_encode($data));
    }

    /**
     * 导入任务配置数据，会清空存在的数据
     * @param $request
     * @param $response
     */
    function importconf_http($request, $response)
    {
        $workers = $request->post["workers"];
        $workers = json_decode($workers, true);
        if (empty($workers)) {
            $response->end(json_encode($this->output("参数有误", false)));
        }
        foreach ($workers as $id => $task) {
            if (empty($task["name"]) || empty($task["time"]) || empty($task["parse"]) || empty($task["task"])) {
                $response->end(json_encode($this->output("参数有误", false)));
            }
        }
        ob_start();
        var_export($workers);
        $config = ob_get_clean();
        file_put_contents(Http::$conf_file, "<?php \n return " . $config . ";");
        fwrite(Http::$fp, "reloadworker#@#" . json_encode(array()));

        $response->end(json_encode($this->output("ok")));
    }

    public function output($data, $status = true)
    {
        return array("status" => $status, "data" => $data);
    }
}