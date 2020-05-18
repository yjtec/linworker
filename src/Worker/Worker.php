<?php

namespace Yjtec\Linworker\Worker;

use Exception;
use Yjtec\Linworker\Config\Conf;
use Yjtec\Linworker\Lib\ProcLine;
use const LOGPATH;

class Worker {

    private $interval; //循环时间间隔
    private $appInstance;
    private $app;
    private $procLine = null; //日志记录
    private $system;
	public $masterPid;

    public function __construct($app, $interval = 5) {
        $this->app = $app;
        $this->interval = $interval;
        $this->procLine = new ProcLine(LOGPATH);
        $this->system = Conf::getSystemPlatform();
    }

    public function startWork() {
        $this->procLine->EchoAndLog('子进程开始循环PID=' . $this->getMyPid() . PHP_EOL);
        while (1) {
			if($this->isParentDead()){
				return true;
			}
            try {
                $this->appStart(); //执行
            } catch (Exception $ex) {
                $this->procLine->EchoAndLog('执行发生异常PID=' . $this->getMyPid() . ':' . json_encode($ex) . PHP_EOL);
            }
            usleep($this->interval * 1000000); //休眠多少秒
        }
    }

    public function appStart() {
        $instance = $this->getAppInstance();
        if (!$instance) {
            return false;
        }
        if ($instance && is_callable(array($instance, 'before'))) {
            $instance->before(); //执行用户的before方法
        }
        if ($instance && is_callable(array($instance, 'run'))) {
            $instance->run(); //执行用户的run方法
        }
        if ($instance && is_callable(array($instance, 'after'))) {
            $instance->after(); //执行用户的after方法
        }
        return true;
    }

    /**
     * 获取用户指定的类,并初始化其参数
     * @param type $job
     * @return type
     * @throws Exception
     * @throws Exception
     */
    public function getAppInstance() {
        if ($this->appInstance) {
            return $this->appInstance;
        }
        if (!class_exists($this->app)) {
            $this->procLine->EchoAndLog('找不到用户APP:' . $this->app . PHP_EOL);
            return false;
        }
        if (!method_exists($this->app, 'run')) {
            $this->procLine->EchoAndLog('用户APP找不到run方法:' . $this->app . PHP_EOL);
            return false;
        }
        $this->appInstance = new $this->app();
        $this->procLine->EchoAndLog('用户APP实例化成功：' . $this->app . PHP_EOL);
        return $this->appInstance; //实例化job
    }

    public function getMyPid() {
        return $this->system == 'linux' ? posix_getpid() : getmypid();
    }
	
	public function isParentDead(){
		if($this->system == 'linux'&&is_callable("exec")&&$this->masterPid){
			exec("ps p ".$this->masterPid."|awk '{if(NR>1)print}'",$str,$re);
			if($re==0){
				if(!$str){
					$this->procLine->EchoAndLog('未检测到父进程，子进程将退出:' . $this->getMyPid() . PHP_EOL);
					return true;
				}
				$arg=explode(" ",trim(preg_replace("/\s(?=\s)/","\\1", $str[0])));
				unset($arg[0],$arg[1],$arg[2],$arg[3],$arg[4]);
				$arg=array_values($arg);
				if($arg==$_SERVER['argv']){
					return false;
				}
				$this->procLine->EchoAndLog('检测到一个奇怪的父进程，子进程将退出:' . $this->getMyPid()."，进程参数：".json_encode($str) . PHP_EOL);
				return true;
			}
		}
		return false;
	}

}
