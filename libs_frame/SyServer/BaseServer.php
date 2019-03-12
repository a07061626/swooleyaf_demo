<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:13
 */
namespace SyServer;

use Constant\Server;
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

    public function __construct(int $port){
        if(($port <= 1024) || ($port > 65535)){
            exit('端口不合法' . PHP_EOL);
        }
        $this->_configs = Tool::getConfig('syserver.' . SY_ENV . SY_MODULE);
        $this->_configs['server']['port'] = $port;
        $this->_host = $this->_configs['server']['host'];
        $this->_port = $this->_configs['server']['port'];
    }

    private function __clone(){
    }

    abstract public function start();

    public function onClose(\swoole_server $server,int $fd,int $reactorId) {
    }

    public function onWorkStart(\swoole_server $server, $workerId) {
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
    }
}