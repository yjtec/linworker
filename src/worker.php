#!/usr/bin/env php
<?php
/**
 * 主入口
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/yjtec
 * @version 1.0.0
 */
// 只允许在cli下面运行  
if (php_sapi_name() != "cli") {
    die("仅支持命令行模式\n");
}
$INTERVAL = getenv('INTV'); //worker循环间隔
$APP = getenv('APP'); //用户APP

require_once './Autoload.php';
spl_autoload_register('\Yjtec\Linworker\Autoload::autoload');

$worker = new \Yjtec\Linworker\Worker\Master($APP ? $APP : "Yjtec\\Linworker\\App\\Ax,Yjtec\\Linworker\\App\\Az", $INTERVAL > 0 ? $INTERVAL : 5, 0); //主进程
$worker->startWork();
