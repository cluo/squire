<?php 
 return array (
  'V2Book/CreatePublicChapter' => 
  array (
    'name' => '创建公共章节文件',
    'processNum' => 1,
    'parse' => 'Gearman',
    'task' => 
    array (
      'servers' => '127.0.0.1:4730',
      'function' => 
      array (
        0 => 'createPublicChapter',
        1 => 'checkPublicChapter',
      ),
    ),
  ),
);