<?php

namespace Yjtec\Linworker\Lib;

/**
 * 显示命令行
 * 记录日志
 * 
 * @author Linko
 * @email 18716463@qq.com
 * @link https://github.com/yjtec
 * @version 1.0.0
 */
class ProcLine
{

    private $logFile;
    private $initDisplay = array();

    public function __construct($logFile = null)
    {
        $this->logFile = $logFile;
    }

    /**
     * 显示UI方框.
     *
     * @return void
     */
    public function displayUI()
    {
        $this->EchoAndLog("┌───────────────────────────── LineWorker ─────────────────────────────┐" . PHP_EOL);
        $this->EchoAndLog("├──────────────────────────────────────────── LineWorker Version:1.0.0 ┤" . PHP_EOL);
        $this->EchoAndLog("│感谢您选择LineWorker                                                  │" . PHP_EOL);
        $this->EchoAndLog("│LineWorker是一款基于PHP的简单守护进程程序                                 │" . PHP_EOL);
        $this->EchoAndLog("│需要更多帮助,请访问https://blog.biuio.com                 │" . PHP_EOL);
        $this->showInitDisplay();
        $this->EchoAndLog("├──────────────────────────────────────────────── PHPVersion:" . PHP_VERSION . " ┤" . PHP_EOL);
        $this->EchoAndLog("└───────────────────────────────────────────────────────────────────┘" . PHP_EOL);
        $this->initDisplay = null;
    }

    private function showInitDisplay()
    {
        foreach ($this->initDisplay as $string) {
            $lenth = strlen($string);
            for ($i = 0; $i < 67 - $lenth; $i++) { //结尾字符串补充这么多空格
                $string .= ' ';
            }
            $this->EchoAndLog("│" . $string . "│" . PHP_EOL);
        }
    }

    public function initDisplay($string)
    {
        $this->initDisplay[] = $string;
    }

    /**
     * Safe Echo.
     *
     * @param $msg
     */
    public function EchoAndLog($msg)
    {
        echo '[' . date('Y/m/d H:i:s') . ']linworker ' . $msg;
        $this->log($msg);
    }

    /**
     * Log.
     *
     * @param string $msg
     * @return void
     */
    public function log($msg)
    {
        if ($this->logFile) {
            file_put_contents((string) $this->logFile, '[' . date('Y/m/d H:i:s') . ']linworker ' . ' ' . $msg, FILE_APPEND | LOCK_EX);
        }
    }
}
