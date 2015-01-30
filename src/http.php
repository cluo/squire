<?php
/**
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 15-1-13
 * Time: 下午9:44
 */

define('ROOT_PATH', realpath(dirname(__FILE__)) . "/");

require ROOT_PATH . "include/Squire_Manager.class.php";

class Http
{
    static public $route = array(
        array("/conf", "getworker", 'get', true),
        array("/conf", "addworker", 'post', true),
        array("/conf", "delworker", 'delete', true),
        array("/reload", "reloadworker", 'get', true),
        array("/logs", "loglist", 'get', false),
        array("/import", "importconf", 'post', false),
    );
    static public $host = "127.0.0.1";
    static public $port = 9502;
    static public $name = "lzm_Squire_Http";

    static public $http;
    static public $manager;
    static public $fp;
    static public $conf_file;

    static public function http_server()
    {
        self::$http = new swoole_http_server(self::$host, self::$port, SWOOLE_BASE);
    }

    static public function start()
    {
        self::$manager = new Squire_Manager();
        self::$http->on('request', function ($request, $response) {
            if (!self::route($request, $response)) {
                $response->status(404);
                $response->end("404 not found");
            }
        });
        self::$http->start();
    }

    static public function route($request, $response)
    {
        $method = $request->server[strtolower("REQUEST_METHOD")];
        $path = $request->server[strtolower("PATH_INFO")];
        foreach (self::$route as $rte) {
            $pattern = str_replace("/", '\/', $rte[0]);
            preg_match("/$pattern/", $path, $matches);
            if (!empty($matches)) {
                if (strtolower($rte[2]) == strtolower($method)) {
                    if ($rte[3]) {
                        $data = array(
                            "get" => isset($request->get) ? $request->get : "",
                            "post" => isset($request->post) ? $request->post : ""
                        );
                        fwrite(self::$fp, $rte[1] . "#@#" . json_encode($data));
                        $return = fread(self::$fp, 4096);
                        $response->end($return);
                        return true;
                    } else {
                        $response->end(json_encode(call_user_func_array(array(new Squire_Manager(), $rte[1] . "_http"), array("request" => $request))));
                        return true;
                    }
                }
            }
        }
        return false;
    }

    static public function run($fd)
    {
        self::$fp = fopen("php://fd/" . $fd, "a");
        swoole_set_process_name(self::$name);
        self::http_server();
        self::start();
    }
}

if (!empty($argv[2]))
    Http::$conf_file = $argv[2];
if (!empty($argv[3]))
    Http::$host = $argv[3];
if (!empty($argv[4]))
    Http::$port = $argv[4];

Http::run($argv[1]);