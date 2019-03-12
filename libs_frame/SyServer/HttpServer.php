<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:14
 */
namespace SyServer;

use Constant\Server;

class HttpServer extends BaseServer {
    public function __construct(int $port){
        parent::__construct($port);
    }

    public function start(){
        $this->_server = new \swoole_websocket_server($this->_host, $this->_port);
        $this->_server->set($this->_configs['swoole']);
        $this->_server->on('request', function (\swoole_http_request $request,\swoole_http_response $response) {
            $response->end("<h1>Hello Swoole Websocket Server. #" . random_int(1000, 9999) . "</h1>");
        });
        $this->_server->on('message', function (\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
            $server->push($frame->fd, "this is websocket server");
        });
        $this->_server->on('close', function (\swoole_server $server,int $fd,int $reactorId) {
        });
        $this->_server->on('start', function (\swoole_server $server) {
            @cli_set_process_title(Server::PROCESS_TYPE_MAIN . SY_MODULE . $this->_port);
        });
        $this->_server->on('workStart', function (\swoole_server $server, $workerId) {
            if($workerId >= $server->setting['worker_num']){
                @cli_set_process_title(Server::PROCESS_TYPE_TASK . SY_MODULE . $this->_port);
            } else {
                @cli_set_process_title(Server::PROCESS_TYPE_WORKER . SY_MODULE . $this->_port);
            }
        });
        $this->_server->on('managerStart', function (\swoole_server $server) {
            @cli_set_process_title(Server::PROCESS_TYPE_MANAGER . SY_MODULE . $this->_port);
        });

        $this->_server->start();
    }
}