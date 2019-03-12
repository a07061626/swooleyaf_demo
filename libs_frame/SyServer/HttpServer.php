<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:14
 */
namespace SyServer;

use Log\Log;

class HttpServer extends BaseServer {
    public function __construct(int $port){
        parent::__construct($port);
    }

    public function onRequest(\swoole_http_request $request,\swoole_http_response $response) {
        $response->end("<h1>Hello Swoole Websocket Server. #" . random_int(1000, 9999) . "</h1>");
    }

    public function onMessage(\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
        Log::log('fjalkfjkl');
        try{
            throw new \Exception('fdfas', 999);
        }catch(\Exception $e){
            Log::error($e->getMessage(), $e->getCode(), $e->getTraceAsString());
        }
        $server->push($frame->fd, "this is websocket server");
    }

    public function start(){
        $this->_server = new \swoole_websocket_server($this->_host, $this->_port);
        $this->_server->set($this->_configs['swoole']);
        $this->_server->on('request', [$this, 'onRequest']);
        $this->_server->on('message', [$this, 'onMessage']);
        $this->_server->on('close', [$this, 'onClose']);
        $this->_server->on('start', [$this, 'onStart']);
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('managerStart', [$this, 'onManagerStart']);

        file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;31m \t[fail] \e[0m');
        $this->_server->start();
    }
}