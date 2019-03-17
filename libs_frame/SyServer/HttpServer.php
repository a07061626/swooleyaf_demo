<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 10:18
 */
namespace SyServer;

class HttpServer extends BaseServer {
    public function __construct(){
        parent::__construct();
    }

    public function start(){
        $this->_server = new \swoole_websocket_server('172.18.134.124', 7100);
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
}