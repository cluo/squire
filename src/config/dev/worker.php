<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-28
 * Time: 下午10:31
 */

return array(
    "GEARMAN_WORKERS"=>array(
        "V2Book"=>array(
            "CreatePublicChapter"=>array(
                "servers"=>"127.0.0.1:4730",
                "processNum"=>1,
                "task"   => array(
                    "createPublicChapter",
                    "checkPublicChapter",
                )

            )
        ),
        "SyncV2BookDB"=>array(
            "CheckSync"=>array(
                "servers"=>"127.0.0.1:4730",
                "processNum"=>1,
                "task"   => array(
                    "checkSync",
                    "syncAll",
                    "syncNoBid",
                    "novel2bid",
                    "syncBidFormRedis",
                    "sync4file",
                    "syncAll4Novel",
                )
            ),
            "Syncbook"=>array(
                "servers"=>"127.0.0.1:4730",
                "processNum"=>1,
                "task"   => array(
                    "syncBook",
                    "syncDB2Redis",
                )
            )
        ),

    )
);