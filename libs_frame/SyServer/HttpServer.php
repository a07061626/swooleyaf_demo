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
use Yaf\Registry;
use Yaf\Request\Http;

class HttpServer extends BaseServer {
    use BasicHttpTrait;
    use HttpServerTrait;

    const RESPONSE_RESULT_TYPE_FORBIDDEN = 0; //响应结果类型-拒绝请求
    const RESPONSE_RESULT_TYPE_ACCEPT = 1; //响应结果类型-允许请求执行业务
    const RESPONSE_RESULT_TYPE_ALLOW = 2; //响应结果类型-不执行业务，直接返回响应

    /**
     * 跨域共享资源数组
     * @var array
     */
    protected $_cors = [];
    /**
     * swoole请求cookie域名数组
     * @var array
     */
    private $_reqCookieDomains = [];
    /**
     * HTTP响应
     * @var \swoole_http_response
     */
    private static $_response = null;
    /**
     * 响应消息
     * @var string
     */
    private static $_rspMsg = '';
    /**
     * swoole请求头信息数组
     * @var array
     */
    private static $_reqHeaders = [];
    /**
     * swoole服务器信息数组
     * @var array
     */
    private static $_reqServers = [];
    /**
     * swoole task请求数据
     * @var string
     */
    private static $_reqTask = null;

    public function __construct(int $port){
        parent::__construct($port);

//        $projectLength = strlen(SY_PROJECT);
//        $serverType = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.modules.' . substr(SY_MODULE, $projectLength) . '.type');
        $serverType = Server::SERVER_TYPE_API_GATE;
        if(!in_array($serverType, [Server::SERVER_TYPE_API_GATE, Server::SERVER_TYPE_FRONT_GATE])){
            exit('服务端类型不支持' . PHP_EOL);
        }

        define('SY_SERVER_TYPE', $serverType);

        if ($serverType == Server::SERVER_TYPE_API_GATE) {
            $this->_configs['server']['cachenum']['sign'] = (int)Tool::getArrayVal($this->_configs, 'server.cachenum.sign', 0, true);
        } else {
            $this->_configs['server']['cachenum']['sign'] = 1;
        }
        $this->_cors = Tool::getConfig('cors.' . SY_ENV . SY_PROJECT);
        $this->_cors['allow']['headerStr'] = isset($this->_cors['allow']['headers']) ? implode(', ', $this->_cors['allow']['headers']) : '';
        $this->_cors['allow']['methodStr'] = isset($this->_cors['allow']['methods']) ? implode(', ', $this->_cors['allow']['methods']) : '';
        $this->_reqCookieDomains = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.domain.cookie');

        $this->checkServerHttp();
    }

    private function __clone() {
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

    /**
     * 初始化公共数据
     * @param \swoole_http_request $request
     */
    private function initReceive(\swoole_http_request $request) {
        Registry::del(Server::REGISTRY_NAME_SERVICE_ERROR);
        $_POST = $request->post ?? [];
        $_SESSION = [];
        self::$_reqHeaders = $request->header ?? [];
        self::$_reqServers = $request->server ?? [];
        self::$_rspMsg = '';
        $this->createReqId();

        $taskData = $_POST[Server::SERVER_DATA_KEY_TASK] ?? '';
        self::$_reqTask = is_string($taskData) && (strlen($taskData) > 0) ? $taskData : null;

        $_SERVER = [];
        foreach (self::$_reqServers as $key => $val) {
            $_SERVER[strtoupper($key)] = $val;
        }
        foreach (self::$_reqHeaders as $key => $val) {
            $_SERVER[strtoupper($key)] = $val;
        }
        if(!isset($_SERVER['HTTP_HOST'])){
            $_SERVER['HTTP_HOST'] = $this->_host . ':' . $this->_port;
        }
        if(!isset($_SERVER['REQUEST_URI'])){
            $_SERVER['REQUEST_URI'] = '/';
        }
        $_SERVER[Server::SERVER_DATA_KEY_TIMESTAMP] = time();
    }

    /**
     * 清理请求数据
     */
    private function clearRequest() {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_COOKIE = [];
        $_SERVER = [];
        $_SESSION = [];
        $GLOBALS['HTTP_RAW_POST_DATA'] = '';
        self::$_reqTask = null;
        self::$_reqHeaders = [];
        self::$_reqServers = [];
        self::$_response = null;
        self::$_reqId = '';
        self::$_rspMsg = '';

        //清除yaf注册常量
        Registry::del(Server::REGISTRY_NAME_REQUEST_HEADER);
        Registry::del(Server::REGISTRY_NAME_REQUEST_SERVER);
        Registry::del(Server::REGISTRY_NAME_RESPONSE_HEADER);
        Registry::del(Server::REGISTRY_NAME_RESPONSE_COOKIE);

        self::$_syServer->set(self::$_serverToken, [
            'memory_usage' => memory_get_usage(),
        ]);
    }

    /**
     * 处理请求头
     * @param array $headers 响应头配置
     * @return int
     */
    private function handleReqHeader(array &$headers) : int {
        $headers['Access-Control-Allow-Origin'] = $_SERVER['ORIGIN'] ?? '*';
        $headers['Access-Control-Allow-Credentials'] = 'true';
        if (isset($_SERVER['ACCESS-CONTROL-REQUEST-METHOD'])) { //校验请求方式
            $methodStr = ', ' . strtoupper(trim($_SERVER['ACCESS-CONTROL-REQUEST-METHOD']));
            if (strpos(', ' . $this->_cors['allow']['methodStr'], $methodStr) === false) {
                return self::RESPONSE_RESULT_TYPE_FORBIDDEN;
            }
        }
        if (isset($_SERVER['ACCESS-CONTROL-REQUEST-HEADERS'])) { //校验请求头
            $controlReqHeaders = explode(',', strtolower($_SERVER['ACCESS-CONTROL-REQUEST-HEADERS']));
            foreach ($controlReqHeaders as $eHeader) {
                $headerName = trim($eHeader);
                if ((strlen($headerName) > 0) && !in_array($headerName, $this->_cors['allow']['headers'])) {
                    return self::RESPONSE_RESULT_TYPE_FORBIDDEN;
                }
            }
        }

        $domainTag = $_SERVER['SY-DOMAIN'] ?? 'base';
        $cookieDomain = $this->_reqCookieDomains[$domainTag] ?? null;
        if(is_null($cookieDomain)){
            return self::RESPONSE_RESULT_TYPE_FORBIDDEN;
        }
        $_SERVER['SY-DOMAIN'] = $cookieDomain;

        $reqMethod = strtoupper(Tool::getArrayVal($_SERVER, 'REQUEST_METHOD', 'GET'));
        if ($reqMethod == 'OPTIONS') {
            //预请求OPTIONS的响应结果有效时间
            $headers['Access-Control-Max-Age'] = $this->_cors['options']['maxage'];
            $headers['Access-Control-Allow-Methods'] = $this->_cors['allow']['headerStr'];
            $headers['Access-Control-Allow-Headers'] = $this->_cors['allow']['methodStr'];
            return self::RESPONSE_RESULT_TYPE_ALLOW;
        }
        return self::RESPONSE_RESULT_TYPE_ACCEPT;
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
        self::$_response = $response;
        $this->initReceive($request);

        if(is_null(self::$_reqTask)){
            $rspHeaders = [];
            $handleHeaderRes = $this->handleReqHeader($rspHeaders);
            if($handleHeaderRes == HttpServer::RESPONSE_RESULT_TYPE_ACCEPT){
                self::$_rspMsg = $this->handleReqService($request, $rspHeaders);
                $this->setRspCookies($response, Registry::get(Server::REGISTRY_NAME_RESPONSE_COOKIE));
                $this->setRspHeaders($response, Registry::get(Server::REGISTRY_NAME_RESPONSE_HEADER));
            } else if($handleHeaderRes == HttpServer::RESPONSE_RESULT_TYPE_ALLOW){
                $rspHeaders['Content-Type'] = 'application/json; charset=utf-8';
                $this->setRspHeaders($response, $rspHeaders);
            } else {
                $rspHeaders['Content-Type'] = 'text/plain; charset=utf-8';
                $rspHeaders['Syresp-Status'] = 403;
                $this->setRspHeaders($response, $rspHeaders);
            }
        } else {
            self::$_syServer->incr(self::$_serverToken, 'request_times', 1);
//            $this->_server->task(self::$_reqTask, random_int(1, $this->_taskMaxId));
            $result = new Result();
            $result->setData([
                'msg' => 'task received',
            ]);
            self::$_rspMsg = $result->getJson();
            unset($result);
        }

        $response->end(self::$_rspMsg);
        $this->clearRequest();
//        $uri = Tool::getArrayVal($request->server, 'request_uri', '/');
//        $uriCheckRes = $this->checkRequestUri($uri);
//        if(strlen($uriCheckRes['error']) > 0){
//            $error = new Result();
//            $error->setCodeMsg(ErrorCode::COMMON_ROUTE_URI_FORMAT_ERROR, $uriCheckRes['error']);
//            $result = $error->getJson();
//            unset($error);
//            return $result;
//        }
//        $uri = $uriCheckRes['uri'];
//
//        $error = null;
//        $result = '';
//        $httpObj = new Http($uri);
//
//        try {
//            $result = $this->_app->bootstrap()->getDispatcher()->dispatch($httpObj)->getBody();
//            if(strlen($result) == 0){
//                $error = new Result();
//                $error->setCodeMsg(ErrorCode::SWOOLE_SERVER_NO_RESPONSE_ERROR, '未设置响应数据');
//            }
//        } catch (\Exception $e){
//            SyResponseHttp::header('Content-Type', 'application/json; charset=utf-8');
//            if(SY_REQ_EXCEPTION_HANDLE_TYPE){
//                $error = $this->handleReqExceptionByFrame($e);
//            } else {
//                $error = $this->handleReqExceptionByProject($e);
//            }
//        } finally {
//            unset($httpObj);
//            if(is_object($error)){
//                $result = $error->getJson();
//                unset($error);
//            }
//        }
//
//        $response->end($result);
    }
}