#!/usr/bin/env php
<?php
/**
 * 主入口
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/kk1987n/LineQue.git
 * @version 1.0.0
 */
define('LineQue', __DIR__); //LineQue目录绝对路径,autoload中用到了,要加载类
// 只允许在cli下面运行  
if (php_sapi_name() != "cli") {
    die("仅支持命令行模式\n");
}
define('LOGPATH', LineQue . '/LineQue.log'); //系统日志文件路径,默认放在框架目录下
$INTERVAL = getenv('INTV'); //worker循环间隔
$APP = getenv('APP'); //用户APP

require_once './Autoload.php';
spl_autoload_register('\Yjtec\Linworker\Autoload::autoload');

$worker = new \Yjtec\Linworker\Worker\Master($APP ? $APP : "Yjtec\\Linworker\\App\\Ax,Yjtec\\Linworker\\App\\Az", $INTERVAL > 0 ? $INTERVAL : 5, 0); //主进程
$worker->startWork();
