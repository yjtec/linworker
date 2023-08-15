<?php

namespace Yjtec\Linworker\Worker;

use Yjtec\Linworker\Config\Conf;
use Yjtec\Linworker\Lib\ProcLine;

/**
 * 主进程,用以保护实际执行job的子进程
 * 
 * @author Linko
 * @email 18716463@qq.com
 * @version 1.0.0
 */
class Master {

    private $interval; //主进程和子进程的循环间隔
    private $daemonize;
    private $logPath="";
    private $masterPid = 0; //主进程pid
    private $procLine; //日志处理类
    private $system; //系统版本
    public $count; //子进程数量
    public $slave;

    public function __construct($classes = 1, $interval = 5, $daemonize = 0,$logPath="") {
        $this->logPath=$logPath?$logPath:dirname(__FILE__).'/../linworker.log';
        $this->slave = strpos($classes, ',') === false ? array($classes) : explode(',', $classes);
        foreach ($this->slave as &$class) {
            $class = array('app' => $class, 'pid' => 0);
        }
        $this->procLine = new ProcLine($this->logPath);
        $this->interval = $interval ? $interval : 5;
        $this->daemonize = $daemonize;
        $this->system = Conf::getSystemPlatform();
    }

    /**
     * 主进程开始
     */
    public function startWork() {
        if ($this->system == 'linux') {
            $this->masterPid = posix_getpid();
            $this->displayUI(); //显示方框
            $procTitle = 'linworker ' . implode(' ', $_SERVER['argv']);
            cli_set_process_title($procTitle . '(master)');
            while (1) {
                foreach ($this->slave as &$slave) {
                    if (!$slave['pid']) {
                        $pid = pcntl_fork();
                        if ($pid == 0) {
                            cli_set_process_title($procTitle . '(slave)');
                            return $this->slaverMonitor($slave['app']); //子进程
                        } elseif ($pid > 0) {
                            $slave['pid'] = $pid;
                            $this->procLine->EchoAndLog("创建子进程成功Pid=" . $pid . PHP_EOL);
                        } else {
                            $this->procLine->EchoAndLog('创建子进程出错,请检查PHP配置' . PHP_EOL);
                            exit(0);
                        }
                    }
                }
                $this->masterMonitor(); //主进程,循环/等待,此处不能return
                sleep($this->interval);
            }
        } else {
            $this->masterPid = getmygid();
            $this->displayUI(); //显示方框
            return $this->slaverMonitor($this->slave[0]['app']); //子进程
        }
    }

    /**
     * 主进程监控
     * 等待子进程,发现意外退出,重启子进程
     */
    public function masterMonitor() {
        $status = 0;
        $exitPid = pcntl_wait($status);
        if ($exitPid > 0) {//$pid退出的子进程的编号
            $this->procLine->log('子进程意外退出Pid=' . $exitPid . '退出信号' . $status . PHP_EOL);
            foreach ($this->slave as &$slave) {
                if ($slave['pid'] == $exitPid) {
                    $slave['pid'] = 0;
                }
            }
        } elseif ($exitPid == 0) {//没有子进程退出
        }
    }

    /**
     * 子进程逻辑
     * @return boolean
     */
    public function slaverMonitor($app) {
        usleep(10000); //稍微等几毫秒
        $SlaveWorker = new Worker($app, $this->interval);
        $SlaveWorker->masterPid = $this->masterPid;
        return $SlaveWorker->startWork();
    }

    /**
     * 界面上显示个方框
     */
    public function displayUI() {
        $this->procLine->initDisplay("─进程信息──────────────────────────────────────────────────────────");
        if ($this->masterPid) {
            $this->procLine->initDisplay('MasterPid:' . $this->masterPid);
        }
        $this->procLine->initDisplay("─运行参数──────────────────────────────────────────────────────────");
        $slaveStr = array();
        foreach ($this->slave as &$slave) {
            $slaveStr[] = $slave['app'];
        }
        $this->procLine->initDisplay("Worker:" . implode(',', $slaveStr));
        $this->procLine->initDisplay("Interval:" . $this->interval . 's');
        $this->procLine->initDisplay("LogPath:" . $this->logPath);
        $this->procLine->initDisplay("─配置────────────────────────────────────────────────────────");
        $this->procLine->displayUI();
    }

/////////////////////////////乱七八糟的方法
//    /**
//     * 注册信号
//     */
//    private function registerSigHandlers() {
////        if (!function_exists('pcntl_signal')) {
////            return;
////        }
//        // 停止
//        pcntl_signal(SIGINT, array($this, 'signalHandler'), false);
//        // 用户信号,可用于重载
//        pcntl_signal(SIGUSR1, array($this, 'signalHandler'), false);
//        // 用户信号
//        pcntl_signal(SIGUSR2, array($this, 'signalHandler'), false);
//        // connection status
//        pcntl_signal(SIGIO, array($this, 'signalHandler'), false);
//        // 忽略
//        pcntl_signal(SIGPIPE, SIG_IGN, false);
//    }
//
//    /**
//     * 信号处理函数
//     * @param type $signo
//     * @return boolean
//     */
//    public function signalHandler($signo) {
//        $this->procLine->log('主进程收到信号:' . $signo . PHP_EOL);
//        switch ($signo) {
//            case SIGIO: //
//                echo "SIGIO" . PHP_EOL;
//                break;
//            case SIGINT: //
//                echo "SIGINT" . PHP_EOL;
//                break;
//            case SIGUSR1: //用户自定义信号
//                echo "SIGUSR1" . PHP_EOL;
//                break;
//            case SIGUSR2: //用户自定义信号
//                echo "SIGUSR2" . PHP_EOL;
//                break;
//            default:
//                return false;
//        }
//    }
/////////////////////////////////多进程,暂不可用
//    /**
//     * 多进程模式,开启多个进程
//     * @return boolean
//     */
//    public function forkWorkers() {
//        for ($i = 0; $i < $this->procCount; $i++) {
//            $pid = pcntl_fork();
//            if ($pid == -1) {
//                exit("fork进程出错,请检查PHP配置");
//            }
//            if ($pid == 0) {
//                usleep(10000);
//                return true;
//            } elseif ($pid > 0) {
//                $this->$procPid[] = $pid;
//            }
//        }
//        $this->displayUI();
//    }
///////////////////////////脱离控制台,暂不好用,请使用终端命令+&切换后台
//    /**
//     * 使进程脱离控制台控制
//     */
//    public function doDan() {
//        if (!$this->daemonize) {
//            return;
//        }
//        umask(0);
//        $pid = pcntl_fork(); //从此处分成两个进程执行
//        if ($pid > 0) {//这里是父进程要执行的,但上一行代码获取的pid是子进程的pid,由父进程获取
//            exit(0); //父进程退出,子进程变成孤儿进程被1号进程收养,进程已经脱离终端控制
//        } elseif ($pid == 0) {//子进程拿到的值是0,想要获取自己的进程号有其他方法,posix_getpid()
//            posix_setsid(); // 最重要的一步，让该进程脱离之前的会话，终端，进程组的控制
//            //通过上一步，我们创建了一个新的会话组长，进程组长，且脱离了终端，但是会话组长可以申请重新打开一个终端，为了避免
//            //这种情况，我们再次创建一个子进程，并退出当前进程，这样运行的进程就不再是会话组长。
//            $pid = pcntl_fork();
//            chdir(APP ? APP : '/'); // 修改当前进程的工作目录，由于子进程会继承父进程的工作目录，修改工作目录以释放对父进程工作目录的占用。
//            if ($pid > 0) {//父进程
//                exit(0);
//            } elseif ($pid == 0) {//子进程
////                @fclose(STDIN); // 由于守护进程用不到标准输入输出，关闭标准输入，输出，错误输出描述符
//                @fclose(STDOUT);
//                @fclose(STDERR);
//                return true;
//            }
//        } else {
//            $this->procLine->safeEcho('创建子进程出错,请检查PHP配置' . PHP_EOL);
//            exit(0);
//        }
//    }
//
//    /**
//     * Run as deamon mode.
//     *
//     * @throws Exception
//     */
//    protected function daemonize() {
//        if (!$this->daemonize) {
//            return;
//        }
//        umask(0);
//        $pid = pcntl_fork();
//        if (-1 === $pid) {
//            throw new Exception('fork fail');
//        } elseif ($pid > 0) {
//            exit(0);
//        }
//        if (-1 === posix_setsid()) {
//            throw new Exception("setsid fail");
//        }
//        // Fork again avoid SVR4 system regain the control of terminal.
//        $pid = pcntl_fork();
//        if (-1 === $pid) {
//            throw new Exception("fork fail");
//        } elseif (0 !== $pid) {
//            exit(0);
//        }
//    }
//
//    /**
//     * Redirect standard input and output.
//     *
//     * @throws Exception
//     */
//    public static function resetStd() {
//        if (!$this->daemonize) {
//            return;
//        }
//        global $STDOUT, $STDERR;
//        $handle = fopen(self::$stdoutFile, "a");
//        if ($handle) {
//            unset($handle);
//            @fclose(STDOUT);
//            @fclose(STDERR);
//            $STDOUT = fopen(self::$stdoutFile, "a");
//            $STDERR = fopen(self::$stdoutFile, "a");
//        } else {
//            throw new Exception('can not open stdoutFile ' . self::$stdoutFile);
//        }
//    }
}
