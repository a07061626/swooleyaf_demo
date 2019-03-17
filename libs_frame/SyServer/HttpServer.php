<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 10:18
 */
namespace SyServer;

use Constant\ErrorCode;
use Constant\Server;
use Response\Result;
use Response\SyResponseHttp;
use Tool\Tool;
use Traits\HttpServerTrait;
use Traits\Server\BasicHttpTrait;
use Yaf\Request\Http;

class HttpServer extends BaseServer {
    use BasicHttpTrait;
    use HttpServerTrait;

    public function __construct(int $port){
        parent::__construct($port);

//        $projectLength = strlen(SY_PROJECT);
//        $serverType = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.modules.' . substr(SY_MODULE, $projectLength) . '.type');
        $serverType = Server::SERVER_TYPE_API_GATE;
        if(!in_array($serverType, [Server::SERVER_TYPE_API_GATE, Server::SERVER_TYPE_FRONT_GATE])){
            exit('服务端类型不支持' . PHP_EOL);
        }

        define('SY_SERVER_TYPE', $serverType);

        $this->checkServerHttp();
    }

    public function start(){
        $this->initTableHttp();
        $this->_server = new \swoole_websocket_server($this->_host, $this->_port);
        $this->baseStart([
            'start' => 'onStart',
            'managerStart' => 'onManagerStart',
            'workerStart' => 'onWorkerStart',
            'workerStop' => 'onWorkerStop',
            'workerError' => 'onWorkerError',
            'shutdown' => 'onShutdown',
            'request' => 'onRequest',
            'message' => 'onMessage',
            'close' => 'onClose',
        ]);
    }

    private function __clone() {
    }

    public function onWorkerStart(\swoole_server $server, $workerId){
        $this->basicWorkStart($server, $workerId);
    }

    public function onWorkerStop(\swoole_server $server, int $workerId){
        $this->basicWorkStop($server, $workerId);
    }

    public function onWorkerError(\swoole_server $server, $workId, $workPid, $exitCode){
        $this->basicWorkError($server, $workId, $workPid, $exitCode);
    }

    /**
     * 接受socket消息
     * 消息格式：abcde
     * <pre>
     * 格式说明：
     *     a:消息头长度，值固定为16
     *     b:消息内容长度，无符号整数
     *     c:消息执行命令标识，4位字符串
     *     d:保留字段，值固定为0000
     *     e:消息内容，json格式
     * </pre>
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     */
    public function onMessage(\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
        $server->push($frame->fd, "this is server");
    }

    /**
     * 处理请求
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onRequest(\swoole_http_request $request,\swoole_http_response $response){
        $this->createReqId();
        $uri = Tool::getArrayVal($request->server, 'request_uri', '/');
        $uriCheckRes = $this->checkRequestUri($uri);
        if(strlen($uriCheckRes['error']) > 0){
            $error = new Result();
            $error->setCodeMsg(ErrorCode::COMMON_ROUTE_URI_FORMAT_ERROR, $uriCheckRes['error']);
            $result = $error->getJson();
            unset($error);
            return $result;
        }
        $uri = $uriCheckRes['uri'];

        $error = null;
        $result = '';
        $httpObj = new Http($uri);

        try {
            $result = $this->_app->bootstrap()->getDispatcher()->dispatch($httpObj)->getBody();
            if(strlen($result) == 0){
                $error = new Result();
                $error->setCodeMsg(ErrorCode::SWOOLE_SERVER_NO_RESPONSE_ERROR, '未设置响应数据');
            }
        } catch (\Exception $e){
            SyResponseHttp::header('Content-Type', 'application/json; charset=utf-8');
            if(SY_REQ_EXCEPTION_HANDLE_TYPE){
                $error = $this->handleReqExceptionByFrame($e);
            } else {
                $error = $this->handleReqExceptionByProject($e);
            }
        } finally {
            unset($httpObj);
            if(is_object($error)){
                $result = $error->getJson();
                unset($error);
            }
        }

        $response->end($result);
    }
}