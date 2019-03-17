<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 10:17
 */
namespace SyServer;

use Constant\Server;
use Log\Log;
use Tool\Dir;
use Tool\Tool;

abstract class BaseServer {
    /**
     * 请求服务对象
     * @var \swoole_websocket_server|\swoole_server
     */
    protected $_server = null;
    /**
     * 配置数组
     * @var array
     */
    protected $_configs = [];
    /**
     * 请求域名
     * @var string
     */
    protected $_host = '';
    /**
     * 请求端口
     * @var int
     */
    protected $_port = 0;
    /**
     * pid文件
     * @var string
     */
    protected $_pidFile = '';
    /**
     * 提示文件
     * @var string
     */
    protected $_tipFile = '';

    public function __construct(int $port){
        if (($port <= 1024) || ($port > 65535)) {
            exit('端口不合法' . PHP_EOL);
        }
        $this->_configs = Tool::getConfig('syserver.' . SY_ENV . SY_MODULE);
        $this->_configs['server']['port'] = $port;

        define('SY_SERVER_IP', $this->_configs['server']['host']);

        $this->_host = $this->_configs['server']['host'];
        $this->_port = $this->_configs['server']['port'];
        $this->_pidFile = SY_ROOT . '/pidfile/' . SY_MODULE . $this->_port . '.pid';
        $this->_tipFile = SY_ROOT . '/tipfile/' . SY_MODULE . $this->_port . '.txt';
        Dir::create(SY_ROOT . '/tipfile/');
        if(is_dir($this->_tipFile)){
            exit('提示文件不能是文件夹' . PHP_EOL);
        } else if(!file_exists($this->_tipFile)){
            $tipFileObj = fopen($this->_tipFile, 'wb');
            if(is_bool($tipFileObj)){
                exit('创建或打开提示文件失败' . PHP_EOL);
            }
            fwrite($tipFileObj, '');
            fclose($tipFileObj);
        }
    }

    private function __clone() {
    }

    protected function basicWorkStart(\swoole_server $server, $workerId){
        if($workerId >= $server->setting['worker_num']){
            @cli_set_process_title(Server::PROCESS_TYPE_TASK . SY_MODULE . $this->_port);
        } else {
            @cli_set_process_title(Server::PROCESS_TYPE_WORKER . SY_MODULE . $this->_port);
        }
    }

    protected function basicWorkStop(\swoole_server $server,int $workId) {
        $errCode = $server->getLastError();
        if($errCode > 0){
            Log::error('swoole work stop,workId=' . $workId . ',errorCode=' . $errCode . ',errorMsg=' . print_r(error_get_last(), true));
        }
    }

    protected function basicWorkError(\swoole_server $server, $workId, $workPid, $exitCode){
        if($exitCode > 0){
            $msg = 'swoole work error. work_id=' . $workId . '|work_pid=' . $workPid . '|exit_code=' . $exitCode . '|err_msg=' . $server->getLastError();
            Log::error($msg);
        }
    }

    /**
     * 基础启动服务
     * @param array $registerMap
     */
    protected function baseStart(array $registerMap) {
        $this->_server->set($this->_configs['swoole']);
        //绑定注册方法
        foreach ($registerMap as $eventName => $funcName) {
            $this->_server->on($eventName, [$this, $funcName]);
        }

        file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;31m \t[fail] \e[0m');
        //启动服务
        $this->_server->start();
    }

    /**
     * 开启服务
     */
    abstract public function start();

    /**
     * 关闭服务
     */
    public function stop(){
        if(is_file($this->_pidFile) && is_readable($this->_pidFile)){
            $pid = (int)file_get_contents($this->_pidFile);
        } else {
            $pid = 0;
        }

        $msg = ' \e[1;31m \t[fail]';
        if($pid > 0){
            if(\swoole_process::kill($pid)){
                $msg = ' \e[1;32m \t[success]';
            }
            file_put_contents($this->_pidFile, '');
        }
        system('echo -e "\e[1;36m stop ' . SY_MODULE . ': \e[0m' . $msg . ' \e[0m"');
        exit();
    }

    /**
     * 获取服务启动状态
     */
    public function getStartStatus(){
        $fileContent = file_get_contents($this->_tipFile);
        $command = 'echo -e "\e[1;31m ' . SY_MODULE . ' start status fail \e[0m"';
        if(is_string($fileContent)){
            if(strlen($fileContent) > 0){
                $command = 'echo -e "' . $fileContent . '"';
            }
            file_put_contents($this->_tipFile, '');
        }
        system($command);
        exit();
    }

    /**
     * 清理僵尸进程
     */
    public function killZombies(){
        //清除僵尸进程
        $commandZombies = 'ps -A -o pid,ppid,stat,cmd|grep ' . SY_MODULE . '|awk \'{if(($3 == "Z") || ($3 == "z")) print $1}\'';
        $execRes = Tool::execSystemCommand($commandZombies);
        if(($execRes['code'] == 0) && !empty($execRes['data'])){
            system('kill -9 ' . implode(' ', $execRes['data']));
        }

        //清除worker中断进程
        $commandWorkers = 'ps -A -o pid,ppid,stat,cmd|grep ' . Server::PROCESS_TYPE_WORKER . SY_MODULE . '|awk \'{if($2 == "1") print $1}\'';
        $execRes = Tool::execSystemCommand($commandWorkers);
        if(($execRes['code'] == 0) && !empty($execRes['data'])){
            system('kill -9 ' . implode(' ', $execRes['data']));
        }

        //清除task中断进程
        $commandTasks = 'ps -A -o pid,ppid,stat,cmd|grep ' . Server::PROCESS_TYPE_TASK . SY_MODULE . '|awk \'{if($2 == "1") print $1}\'';
        $execRes = Tool::execSystemCommand($commandTasks);
        if(($execRes['code'] == 0) && !empty($execRes['data'])){
            system('kill -9 ' . implode(' ', $execRes['data']));
        }

        $commandTip = 'echo -e "\e[1;36m kill ' . SY_MODULE . ' zombies: \e[0m \e[1;32m \t[success] \e[0m"';
        system($commandTip);
    }

    /**
     * 帮助信息
     */
    public function help(){
        print_r('帮助信息' . PHP_EOL);
        print_r('-s 操作类型: restart-重启 stop-关闭 start-启动 kz-清理僵尸进程 startstatus-启动状态' . PHP_EOL);
        print_r('-n 项目名' . PHP_EOL);
        print_r('-module 模块名' . PHP_EOL);
        print_r('-port 端口,取值范围为1025-65535' . PHP_EOL);
    }

    /**
     * 启动主进程服务
     * @param \swoole_server $server
     * @throws \Exception\Swoole\ServerException
     */
    public function onStart(\swoole_server $server) {
        @cli_set_process_title(Server::PROCESS_TYPE_MAIN . SY_MODULE . $this->_port);

        Dir::create(SY_ROOT . '/pidfile/');
        if (file_put_contents($this->_pidFile, $server->master_pid) === false) {
            Log::error('write ' . SY_MODULE . ' pid file error');
        }

        file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;32m \t[success] \e[0m');
    }

    /**
     * 关闭服务
     * @param \swoole_server $server
     */
    public function onShutdown(\swoole_server $server){
    }

    /**
     * 启动管理进程
     * @param \swoole_server $server
     */
    public function onManagerStart(\swoole_server $server){
        @cli_set_process_title(Server::PROCESS_TYPE_MANAGER . SY_MODULE . $this->_port);
    }

    /**
     * 关闭连接
     * @param \swoole_server $server
     * @param int $fd 连接的文件描述符
     * @param int $reactorId reactor线程ID,$reactorId<0:服务器端关闭 $reactorId>0:客户端关闭
     */
    public function onClose(\swoole_server $server,int $fd,int $reactorId) {
    }

    /**
     * 启动工作进程
     * @param \swoole_server $server
     * @param int $workerId 进程编号
     */
    abstract public function onWorkerStart(\swoole_server $server, $workerId);
    /**
     * 退出工作进程
     * @param \swoole_server $server
     * @param int $workerId
     * @return mixed
     */
    abstract public function onWorkerStop(\swoole_server $server, int $workerId);
    /**
     * 工作进程错误处理
     * @param \swoole_server $server
     * @param int $workId 进程编号
     * @param int $workPid 进程ID
     * @param int $exitCode 退出状态码
     */
    abstract public function onWorkerError(\swoole_server $server, $workId, $workPid, $exitCode);
}