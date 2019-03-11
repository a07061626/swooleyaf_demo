<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:14
 */
namespace SyServer;

class HttpServer extends BaseServer {
    public function __construct(){
        parent::__construct();
    }

    public function start(){
        $this->_server = new \swoole_websocket_server('172.18.134.124', 7100);
        $this->_server->on('request', function (\swoole_http_request $request,\swoole_http_response $response) {
            $response->end("<h1>Hello Swoole Websocket Server. #" . random_int(1000, 9999) . "</h1>");
        });
        $this->_server->on('message', function (\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
            $server->push($frame->fd, "this is websocket server");
        });
        $this->_server->on('close', function (\swoole_server $server,int $fd,int $reactorId) {
        });

        $this->_server->start();
    }
}