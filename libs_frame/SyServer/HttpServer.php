<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:14
 */
namespace SyServer;

use Constant\ErrorCode;
use Constant\Project;
use Constant\Server;
use Exception\Validator\ValidatorException;
use Log\Log;
use Request\RequestSign;
use Response\Result;
use Response\SyResponseHttp;
use Tool\SyPack;
use Tool\Tool;
use Traits\HttpServerTrait;
use Traits\PreProcessHttpFrameTrait;
use Traits\PreProcessHttpProjectTrait;
use Traits\Server\BasicHttpTrait;
use Yaf\Registry;
use Yaf\Request\Http;

class HttpServer extends BaseServer {
    use BasicHttpTrait;
    use HttpServerTrait;
    use PreProcessHttpFrameTrait;
    use PreProcessHttpProjectTrait;

    const RESPONSE_RESULT_TYPE_FORBIDDEN = 0; //响应结果类型-拒绝请求
    const RESPONSE_RESULT_TYPE_ACCEPT = 1; //响应结果类型-允许请求执行业务
    const RESPONSE_RESULT_TYPE_ALLOW = 2; //响应结果类型-不执行业务，直接返回响应

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
     * @var \Tool\SyPack
     */
    private $_messagePack = null;

    public function __construct(int $port){
        parent::__construct($port);

//        $projectLength = strlen(SY_PROJECT);
//        $serverType = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.modules.' . substr(SY_MODULE, $projectLength) . '.type');
        $serverType = 'api';
        if(!in_array($serverType, [Server::SERVER_TYPE_API_GATE, Server::SERVER_TYPE_FRONT_GATE])){
            exit('服务端类型不支持' . PHP_EOL);
        }
        define('SY_SERVER_TYPE', $serverType);

        $this->_messagePack = new SyPack();
        $this->_cors = Tool::getConfig('cors.' . SY_ENV . SY_PROJECT);
        $this->_cors['allow']['headerStr'] = isset($this->_cors['allow']['headers']) ? implode(', ', $this->_cors['allow']['headers']) : '';
        $this->_cors['allow']['methodStr'] = isset($this->_cors['allow']['methods']) ? implode(', ', $this->_cors['allow']['methods']) : '';
        $this->_reqCookieDomains = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.domain.cookie');
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

    private function initRequest(\swoole_http_request $request,array $rspHeaders) {
        self::$_reqStartTime = microtime(true);
        self::$_syServer->incr(self::$_serverToken, 'request_times', 1);
        $_GET = $request->get ?? [];
        $_FILES = $request->files ?? [];
        $_COOKIE = $request->cookie ?? [];
        $GLOBALS['HTTP_RAW_POST_DATA'] = $request->rawContent();
        $_POST[RequestSign::KEY_SIGN] = $_GET[RequestSign::KEY_SIGN] ?? '';
        unset($_GET[RequestSign::KEY_SIGN]);
        //注册全局信息
        Registry::set(Server::REGISTRY_NAME_REQUEST_HEADER, self::$_reqHeaders);
        Registry::set(Server::REGISTRY_NAME_REQUEST_SERVER, self::$_reqServers);
        Registry::set(Server::REGISTRY_NAME_RESPONSE_HEADER, $rspHeaders);
        Registry::set(Server::REGISTRY_NAME_RESPONSE_COOKIE, []);
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

    /**
     * 处理请求业务
     * @param \swoole_http_request $request
     * @param array $initRspHeaders 初始化响应头
     * @return string
     */
    private function handleReqService(\swoole_http_request $request,array $initRspHeaders) : string {
        $uri = Tool::getArrayVal(self::$_reqServers, 'request_uri', '/');
        $uriCheckRes = $this->checkRequestUri($uri);
        if(strlen($uriCheckRes['error']) > 0){
            $error = new Result();
            $error->setCodeMsg(ErrorCode::COMMON_ROUTE_URI_FORMAT_ERROR, $uriCheckRes['error']);
            $result = $error->getJson();
            unset($error);
            return $result;
        }
        $uri = $uriCheckRes['uri'];
        self::$_reqServers['request_uri'] = $uriCheckRes['uri'];

        $funcName = $this->getPreProcessFunction($uri, $this->preProcessMapFrame, $this->preProcessMapProject);
        if(is_bool($funcName)){
            $error = new Result();
            $error->setCodeMsg(ErrorCode::COMMON_SERVER_ERROR, '预处理函数命名不合法');
            $result = $error->getJson();
            unset($error);
            return $result;
        } else if(strlen($funcName) > 0){
            return $this->$funcName($request);
        }

        $this->initRequest($request, $initRspHeaders);

        $error = null;
        $result = '';
        $httpObj = new Http($uri);
        try {
            self::checkRequestCurrentLimit();
            $result = $this->_app->bootstrap()->getDispatcher()->dispatch($httpObj)->getBody();
            if(strlen($result) == 0){
                $error = new Result();
                $error->setCodeMsg(ErrorCode::SWOOLE_SERVER_NO_RESPONSE_ERROR, '未设置响应数据');
            }
        } catch (\Exception $e){
            SyResponseHttp::header('Content-Type', 'application/json; charset=utf-8');
            if (!($e instanceof ValidatorException)) {
                Log::error($e->getMessage(), $e->getCode(), $e->getTraceAsString());
            }

            $error = new Result();
            if(is_numeric($e->getCode())){
                $error->setCodeMsg((int)$e->getCode(), $e->getMessage());
            } else {
                $error->setCodeMsg(ErrorCode::COMMON_SERVER_ERROR, '服务出错');
            }
        } finally {
            self::$_syServer->decr(self::$_serverToken, 'request_handling', 1);
            $this->reportLongTimeReq($uri, array_merge($_GET, $_POST), Project::TIME_EXPIRE_SWOOLE_CLIENT_HTTP);
            unset($httpObj);
            if(is_object($error)){
                $result = $error->getJson();
                unset($error);
            }
        }

        return $result;
    }

    /**
     * 设置响应头信息
     * @param \swoole_http_response $response
     * @param array|bool $headers
     */
    private function setRspHeaders(\swoole_http_response $response, $headers) {
        if(is_array($headers)){
            if(!isset($headers['Content-Type'])){
                $response->header('Content-Type', 'application/json; charset=utf-8');
            }

            foreach ($headers as $headerName => $headerVal) {
                $response->header($headerName, $headerVal);
            }

            if(isset($headers['Location'])){
                $response->status(302);
            } else if(isset($headers['Syresp-Status'])){
                $response->status($headers['Syresp-Status']);
            }
        } else {
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    /**
     * 设置响应cookie信息
     * @param \swoole_http_response $response
     * @param array|bool $cookies
     */
    private function setRspCookies(\swoole_http_response $response, $cookies) {
        if(is_array($cookies)){
            foreach ($cookies as $cookie) {
                if(is_array($cookie) && isset($cookie['key'])
                   && (is_string($cookie['key']) || is_numeric($cookie['key']))){
                    $cookieName = preg_replace('/[^0-9a-zA-Z\-\_]+/', '', $cookie['key']);
                    $value = Tool::getArrayVal($cookie, 'value', null);
                    $expires = Tool::getArrayVal($cookie, 'expires', 0);
                    $path = Tool::getArrayVal($cookie, 'path', '/');
                    $domain = Tool::getArrayVal($cookie, 'domain', '');
                    $secure = Tool::getArrayVal($cookie, 'secure', false);
                    $httpOnly = Tool::getArrayVal($cookie, 'httponly', false);
                    $response->cookie($cookieName, $value, $expires, $path, $domain, $secure, $httpOnly);
                }
            }
        }
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

    public function onWorkerStart(\swoole_server $server, $workerId){
        $this->basicWorkStart($server, $workerId);

        if($workerId == 0){
            $this->addTaskBase($server);
            $this->_messagePack->setCommandAndData(SyPack::COMMAND_TYPE_SOCKET_CLIENT_SEND_TASK_REQ, [
                'task_module' => SY_MODULE,
                'task_command' => Project::TASK_TYPE_CLEAR_API_SIGN_CACHE,
                'task_params' => [],
            ]);
            $taskDataSign = $this->_messagePack->packData();
            $this->_messagePack->init();

            $server->tick(Project::TIME_TASK_CLEAR_API_SIGN, function() use ($server, $taskDataSign) {
                $server->task($taskDataSign, 0);
            });

            $this->_messagePack->setCommandAndData(SyPack::COMMAND_TYPE_SOCKET_CLIENT_SEND_TASK_REQ, [
                'task_module' => SY_MODULE,
                'task_command' => Project::TASK_TYPE_REFRESH_TOKEN_EXPIRE,
                'task_params' => [],
            ]);
            $taskDataToken = $this->_messagePack->packData();
            $this->_messagePack->init();

            $server->tick(Project::TIME_TASK_REFRESH_TOKEN_EXPIRE, function() use ($server, $taskDataToken) {
                $server->task($taskDataToken, 0);
            });
            $this->addTaskHttpTrait($server);
        }
    }

    public function onWorkerStop(\swoole_server $server, int $workerId){
        $this->basicWorkStop($server, $workerId);
    }

    public function onWorkerError(\swoole_server $server, $workId, $workPid, $exitCode){
        $this->basicWorkError($server, $workId, $workPid, $exitCode);
    }

    public function onRequest(\swoole_http_request $request,\swoole_http_response $response) {
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
            $this->_server->task(self::$_reqTask, random_int(1, $this->_taskMaxId));
            $result = new Result();
            $result->setData([
                'msg' => 'task received',
            ]);
            self::$_rspMsg = $result->getJson();
            unset($result);
        }

        $response->end(self::$_rspMsg);
        $this->clearRequest();
    }

    public function onMessage(\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
        $server->push($frame->fd, "this is websocket server");
    }

    public function onTask(\swoole_server $server, int $taskId, int $fromId, string $data){
        $baseRes = $this->handleTaskBase($server, $taskId, $fromId, $data);
        if(is_array($baseRes)){
            $taskCommand = Tool::getArrayVal($baseRes['params'], 'task_command', '');
            switch ($taskCommand) {
                case Project::TASK_TYPE_CLEAR_API_SIGN_CACHE:
                    $this->clearApiSign();
                    break;
                default:
                    $traitRes = $this->handleTaskHttpTrait($server, $taskId, $fromId, $baseRes);
                    if(strlen($traitRes) > 0){
                        return $traitRes;
                    }
            }

            $result = new Result();
            $result->setData([
                'result' => 'success',
            ]);
            return $result->getJson();
        } else {
            return $baseRes;
        }
    }

    public function start(){
        $this->initTableHttp();
        $this->_server = new \swoole_websocket_server($this->_host, $this->_port);
        $this->baseStart([
            'request' => 'onRequest',
            'message' => 'onMessage',
            'close' => 'onClose',
            'start' => 'onStart',
            'workerStart' => 'onWorkerStart',
            'workerStop' => 'onWorkerStop',
            'workerError' => 'onWorkerError',
            'shutdown' => 'onShutdown',
            'managerStart' => 'onManagerStart',
            'task' => 'onTask',
            'finish' => 'onFinish',
        ]);
    }
}