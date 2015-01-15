<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:31
 */

return array(
    "V2Book/CreatePublicChapter"=>array(
        "name"=>"创建公共章节文件",
        "processNum"=>1,
        "parse"=>"Gearman",
        "task"=>array(
            "servers"=>"127.0.0.1:4730",
            "function"=>array(
                "createPublicChapter",
                "checkPublicChapter",
            )
        )
    ),
);