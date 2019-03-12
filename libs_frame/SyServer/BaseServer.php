<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:13
 */
namespace SyServer;

use Constant\Project;
use Constant\Server;
use Log\Log;
use Tool\Tool;

abstract class BaseServer {
    /**
     * @var \swoole_server
     */
    protected $_server = null;
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
     * 配置数组
     * @var array
     */
    protected $_configs = [];
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
        if(($port <= 1024) || ($port > 65535)){
            exit('端口不合法' . PHP_EOL);
        }
        $this->_configs = Tool::getConfig('syserver.' . SY_ENV . SY_MODULE);
        $this->_configs['server']['port'] = $port;
        //关闭协程
        $this->_configs['swoole']['enable_coroutine'] = false;
        //日志
        $this->_configs['swoole']['log_level'] = SWOOLE_LOG_INFO;
        //开启TCP快速握手特性,可以提升TCP短连接的响应速度
        $this->_configs['swoole']['tcp_fastopen'] = true;
        //启用异步安全重启特性,Worker进程会等待异步事件完成后再退出
        $this->_configs['swoole']['reload_async'] = true;
        //进程最大等待时间,单位为秒
        $this->_configs['swoole']['max_wait_time'] = 60;
        //设置请求数据尺寸
        $this->_configs['swoole']['open_length_check'] = true;
        $this->_configs['swoole']['package_max_length'] = Project::SIZE_SERVER_PACKAGE_MAX;
        $this->_configs['swoole']['socket_buffer_size'] = Project::SIZE_CLIENT_SOCKET_BUFFER;
        $this->_configs['swoole']['buffer_output_size'] = Project::SIZE_CLIENT_BUFFER_OUTPUT;

        define('SY_SERVER_IP', $this->_configs['server']['host']);
        $this->_host = $this->_configs['server']['host'];
        $this->_port = $this->_configs['server']['port'];
        $this->_pidFile = SY_ROOT . '/pidfile/' . SY_MODULE . $this->_port . '.pid';
        $this->_tipFile = SY_ROOT . '/tipfile/' . SY_MODULE . $this->_port . '.txt';
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

        //设置日志目录
        Log::setPath(SY_LOG_PATH);
    }

    private function __clone(){
    }

    abstract public function start();

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

    public function onClose(\swoole_server $server,int $fd,int $reactorId) {
    }

    public function onWorkerStart(\swoole_server $server, $workerId) {
        if($workerId >= $server->setting['worker_num']){
            @cli_set_process_title(Server::PROCESS_TYPE_TASK . SY_MODULE . $this->_port);
        } else {
            @cli_set_process_title(Server::PROCESS_TYPE_WORKER . SY_MODULE . $this->_port);
        }
    }

    public function onManagerStart(\swoole_server $server) {
        @cli_set_process_title(Server::PROCESS_TYPE_MANAGER . SY_MODULE . $this->_port);
    }

    public function onStart(\swoole_server $server) {
        @cli_set_process_title(Server::PROCESS_TYPE_MAIN . SY_MODULE . $this->_port);

        file_put_contents($this->_pidFile, $server->master_pid);
        file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;32m \t[success] \e[0m');
    }
}