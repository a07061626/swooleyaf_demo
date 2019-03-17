<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 10:18
 */
namespace SyServer;

use Constant\Server;

class HttpServer extends BaseServer {
    public function __construct(int $port){
        parent::__construct($port);

//        $projectLength = strlen(SY_PROJECT);
//        $serverType = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.modules.' . substr(SY_MODULE, $projectLength) . '.type');
//        if(!in_array($serverType, [Server::SERVER_TYPE_API_GATE, Server::SERVER_TYPE_FRONT_GATE])){
//            exit('服务端类型不支持' . PHP_EOL);
//        }

        define('SY_SERVER_TYPE', Server::SERVER_TYPE_FRONT_GATE);
    }

    public function start(){
        $this->_server = new \swoole_websocket_server($this->_host, $this->_port);
        $this->_server->set($this->_configs['swoole']);
        $this->_server->on('message', function (\swoole_websocket_server $server, $frame) {
            $server->push($frame->fd, "this is server");
        });
        $this->_server->on('request', function ($request, $response) {
            $response->end("<h1>Hello Websocket Swoole. #" . random_int(1000, 9999) . "</h1>");
        });
        $this->_server->on('close', function ($ser, $fd) {
        });
        $this->_server->start();
    }

    private function __clone() {
    }
}