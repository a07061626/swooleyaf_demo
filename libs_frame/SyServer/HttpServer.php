<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:14
 */
namespace SyServer;

use Constant\ErrorCode;
use Constant\Server;
use Request\RequestSign;
use Response\Result;
use Tool\Tool;
use Traits\HttpServerTrait;
use Traits\Server\BasicHttpTrait;
use Yaf\Registry;
use Yaf\Request\Http;

class HttpServer extends BaseServer {
    use BasicHttpTrait;
    use HttpServerTrait;

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
     * 请求开始毫秒级时间戳
     * @var float
     */
    protected static $_reqStartTime = 0.0;

    public function __construct(int $port){
        parent::__construct($port);

//        $projectLength = strlen(SY_PROJECT);
//        $serverType = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.modules.' . substr(SY_MODULE, $projectLength) . '.type');
        $serverType = 'frontgate';
        if(!in_array($serverType, [Server::SERVER_TYPE_API_GATE, Server::SERVER_TYPE_FRONT_GATE])){
            exit('服务端类型不支持' . PHP_EOL);
        }
        define('SY_SERVER_TYPE', $serverType);
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

    public function onWorkerStart(\swoole_server $server, $workerId){
        $this->basicWorkStart($server, $workerId);
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

        $rspHeaders = [];
        $this->initRequest($request, $rspHeaders);
        $httpObj = new Http($uri);

        try{
            $result = $this->_app->bootstrap()->getDispatcher()->dispatch($httpObj)->getBody();
        }catch(\Exception $e){
            $errObj = new Result();
            $errObj->setCodeMsg($e->getCode(), $e->getMessage());
            $result = $errObj->getJson();
            unset($errObj);
        }

        $response->end($result);
    }

    public function onMessage(\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
        $server->push($frame->fd, "this is websocket server");
    }

    public function start(){
        $this->initTableHttp();
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