<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:31
 */

return array(
    [
        "name"=>"V2Book/CreatePublicChapter",
        "processNum"=>1,
        "parse"=>"Gearman",
        "task"=>[
            "servers"=>"127.0.0.1:4730",
            "function"=>[
                "createPublicChapter",
                "checkPublicChapter",
            ]
        ]
    ],
    [
        "name"=>"SyncV2BookDB/CheckSync",
        "processNum"=>1,
        "parse"=>"Gearman",
        "task"=>[
            "servers"=>"127.0.0.1:4730",
            "function"=>[
                "checkSync",
                "syncAll",
                "syncNoBid",
                "novel2bid",
                "syncBidFormRedis",
                "sync4file",
                "syncAll4Novel",
            ]
        ]
    ],
    [
        "name"=>"SyncV2BookDB/Syncbook",
        "processNum"=>1,
        "parse"=>"Gearman",
        "task"=>[
            "servers"=>"127.0.0.1:4730",
            "function"=>[
                "syncBook",
                "syncDB2Redis",
            ]
        ]
    ],
);