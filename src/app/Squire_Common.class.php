<?php
/**
 * Created by PhpStorm.
 * User: vic
 * Date: 14-12-29
 * Time: 下午2:49
 */

interface Squire_Common {

     public function run($worker,$task);

     static public function _exit();
}