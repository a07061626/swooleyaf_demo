<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:13
 */
namespace SyServer;

use Constant\ErrorCode;
use Constant\Project;
use Constant\Server;
use Exception\Swoole\ServerException;
use Log\Log;
use Tool\Dir;
use Tool\Tool;
use Traits\BaseServerTrait;
use Traits\Server\BasicBaseTrait;
use Yaf\Application;

abstract class BaseServer {
    use BasicBaseTrait;
    use BaseServerTrait;

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
    /**
     * pid文件
     * @var string
     */
    protected $_pidFile = '';
    /**
     * 提示文件
     * @var string
     */
    protected $_tipFile = '';
    /**
     * @var \Yaf\Application
     */
    protected $_app = null;
    /**
     * 请求ID
     * @var string
     */
    protected static $_reqId = '';
    /**
     * 服务token码,用于标识不同的服务,每个服务的token不一样
     * @var string
     */
    protected static $_serverToken = '';
    /**
     * 请求开始毫秒级时间戳
     * @var float
     */
    protected static $_reqStartTime = 0.0;

    public function __construct(int $port){
        if(($port <= 1024) || ($port > 65535)){
            exit('端口不合法' . PHP_EOL);
        }
        $this->_configs = Tool::getConfig('syserver.' . SY_ENV . SY_MODULE);
        $this->_configs['server']['port'] = $port;
        //关闭协程
        $this->_configs['swoole']['enable_coroutine'] = false;
        //日志
        $this->_configs['swoole']['log_level'] = SWOOLE_LOG_INFO;
        //开启TCP快速握手特性,可以提升TCP短连接的响应速度
        $this->_configs['swoole']['tcp_fastopen'] = true;
        //启用异步安全重启特性,Worker进程会等待异步事件完成后再退出
        $this->_configs['swoole']['reload_async'] = true;
        //进程最大等待时间,单位为秒
        $this->_configs['swoole']['max_wait_time'] = 60;
        //设置请求数据尺寸
        $this->_configs['swoole']['open_length_check'] = true;
        $this->_configs['swoole']['package_max_length'] = Project::SIZE_SERVER_PACKAGE_MAX;
        $this->_configs['swoole']['socket_buffer_size'] = Project::SIZE_CLIENT_SOCKET_BUFFER;
        $this->_configs['swoole']['buffer_output_size'] = Project::SIZE_CLIENT_BUFFER_OUTPUT;

        define('SY_SERVER_IP', $this->_configs['server']['host']);
        define('SY_REQUEST_MAX_HANDLING', (int)$this->_configs['server']['request']['maxnum']['handling']);
        $this->_host = $this->_configs['server']['host'];
        $this->_port = $this->_configs['server']['port'];
        $this->_pidFile = SY_ROOT . '/pidfile/' . SY_MODULE . $this->_port . '.pid';
        $this->_tipFile = SY_ROOT . '/tipfile/' . SY_MODULE . $this->_port . '.txt';
        if(is_dir($this->_tipFile)){
            exit('提示文件不能是文件夹' . PHP_EOL);
        } else if(!file_exists($this->_tipFile)){
            $tipFileObj = fopen($this->_tipFile, 'wb');
            if(is_bool($tipFileObj)){
                exit('创建或打开提示文件失败' . PHP_EOL);
            }
            fwrite($tipFileObj, '');
            fclose($tipFileObj);
        }

        //生成服务唯一标识
        self::$_serverToken = hash('crc32b', $this->_configs['server']['host'] . ':' . $this->_configs['server']['port']);

        //设置日志目录
        Log::setPath(SY_LOG_PATH);
    }

    private function __clone(){
    }

    /**
     * 创建请求ID
     */
    protected function createReqId() {
        self::$_reqId = hash('md4', Tool::getNowTime() . Tool::createNonceStr(5));
    }

    /**
     * @return string
     */
    public static function getReqId() : string {
        return self::$_reqId;
    }

    /**
     * 检测请求URI
     * @param string $uri
     * @return array
     */
    protected function checkRequestUri(string $uri) : array {
        $nowUri = $uri;
        $checkRes = [
            'uri' => '',
            'error' => '',
        ];

        $uriRes = Tool::handleYafUri($nowUri);
        if(strlen($uriRes) == 0){
            $checkRes['uri'] = $nowUri;
        } else {
            $checkRes['error'] = $uriRes;
        }

        return $checkRes;
    }

    abstract public function start();

    /**
     * 获取服务启动状态
     */
    public function getStartStatus(){
        $fileContent = file_get_contents($this->_tipFile);
        $command = 'echo -e "\e[1;31m ' . SY_MODULE . ' start status fail \e[0m"';
        if(is_string($fileContent)){
            if(strlen($fileContent) > 0){
                $command = 'echo -e "' . $fileContent . '"';
            }
            file_put_contents($this->_tipFile, '');
        }
        system($command);
        exit();
    }

    /**
     * 关闭服务
     */
    public function stop(){
        if(is_file($this->_pidFile) && is_readable($this->_pidFile)){
            $pid = (int)file_get_contents($this->_pidFile);
        } else {
            $pid = 0;
        }

        $msg = ' \e[1;31m \t[fail]';
        if($pid > 0){
            if(\swoole_process::kill($pid)){
                $msg = ' \e[1;32m \t[success]';
            }
            file_put_contents($this->_pidFile, '');
        }
        system('echo -e "\e[1;36m stop ' . SY_MODULE . ': \e[0m' . $msg . ' \e[0m"');
        exit();
    }

    /**
     * 帮助信息
     */
    public function help(){
        print_r('帮助信息' . PHP_EOL);
        print_r('-s 操作类型: restart-重启 stop-关闭 start-启动 startstatus-启动状态' . PHP_EOL);
        print_r('-n 项目名' . PHP_EOL);
        print_r('-module 模块名' . PHP_EOL);
        print_r('-port 端口,取值范围为1001-65535' . PHP_EOL);
    }

    protected function basicWorkStart(\swoole_server $server, $workerId){
        //设置错误和异常处理
        set_exception_handler('\SyError\ErrorHandler::handleException');
        set_error_handler('\SyError\ErrorHandler::handleError');
        //设置时区
        date_default_timezone_set('PRC');
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        //设置bc数学函数小数点保留位数
        $bcConfigs = Tool::getConfig('project.' . SY_ENV . SY_PROJECT . '.bcmath');
        bcscale($bcConfigs['scale']);

        $this->_app = new Application(APP_PATH . '/conf/application.ini', SY_ENV);
        $this->_app->bootstrap()->getDispatcher()->returnResponse(true);
        $this->_app->bootstrap()->getDispatcher()->autoRender(false);

        if($workerId >= $server->setting['worker_num']){
            @cli_set_process_title(Server::PROCESS_TYPE_TASK . SY_MODULE . $this->_port);
        } else {
            @cli_set_process_title(Server::PROCESS_TYPE_WORKER . SY_MODULE . $this->_port);
        }
    }

    protected function basicWorkStop(\swoole_server $server,int $workId) {
        $errCode = $server->getLastError();
        if($errCode > 0){
            Log::error('swoole work stop,workId=' . $workId . ',errorCode=' . $errCode . ',errorMsg=' . print_r(error_get_last(), true));
        }
    }

    protected function basicWorkError(\swoole_server $server, $workId, $workPid, $exitCode){
        if($exitCode > 0){
            $msg = 'swoole work error. work_id=' . $workId . '|work_pid=' . $workPid . '|exit_code=' . $exitCode . '|err_msg=' . $server->getLastError();
            Log::error($msg);
        }
    }

    /**
     * 获取预处理函数
     * @param string $uri
     * @param array $frameMap
     * @param array $projectMap
     * @return bool|string
     */
    protected function getPreProcessFunction(string $uri,array $frameMap,array $projectMap) {
        $funcName = '';
        if(strlen($uri) == 5){
            if (isset($frameMap[$uri])) {
                $funcName = $frameMap[$uri];
                if(strpos($funcName, 'preProcessFrame') !== 0){
                    $funcName = false;
                }
            } else if(isset($projectMap[$uri])){
                $funcName = $projectMap[$uri];
                if(strpos($funcName, 'preProcessProject') !== 0){
                    $funcName = false;
                }
            }
        }

        return $funcName;
    }

    /**
     * 检查请求限流
     * @throws \Exception\Swoole\ServerException
     */
    protected static function checkRequestCurrentLimit() {
        $nowHandlingNum = self::$_syServer->incr(self::$_serverToken, 'request_handling', 1);
        if($nowHandlingNum > SY_REQUEST_MAX_HANDLING){
            throw new ServerException('服务繁忙', ErrorCode::COMMON_SERVER_BUSY);
        }
    }

    /**
     * 记录耗时长的请求
     * @param string $uri 请求uri
     * @param string|array $data 请求数据
     * @param int $limitTime 限制时间,单位为毫秒
     */
    protected function reportLongTimeReq(string $uri, $data,int $limitTime) {
        $handleTime = (int)((microtime(true) - self::$_reqStartTime) * 1000);
        self::$_reqStartTime = 0;
        if($handleTime > $limitTime){ //执行时间超过限制的请求记录到日志便于分析具体情况
            $content = 'handle req use time ' . $handleTime . ' ms,uri:' . $uri . ',data:';
            if(is_string($data)){
                $content .= $data;
            } else {
                $content .= Tool::jsonEncode($data, JSON_UNESCAPED_UNICODE);
            }

            Log::warn($content);
        }
    }

    public function onClose(\swoole_server $server,int $fd,int $reactorId) {
    }

    public function onManagerStart(\swoole_server $server) {
        @cli_set_process_title(Server::PROCESS_TYPE_MANAGER . SY_MODULE . $this->_port);
    }

    public function onStart(\swoole_server $server) {
        @cli_set_process_title(Server::PROCESS_TYPE_MAIN . SY_MODULE . $this->_port);

        Dir::create(SY_ROOT . '/pidfile/');
        if (file_put_contents($this->_pidFile, $server->master_pid) === false) {
            Log::error('write ' . SY_MODULE . ' pid file error');
        }
        file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;32m \t[success] \e[0m');

        $config = Tool::getConfig('project.' . SY_ENV . SY_PROJECT);
        Dir::create($config['dir']['store']['image']);
        Dir::create($config['dir']['store']['music']);
        Dir::create($config['dir']['store']['resources']);
        Dir::create($config['dir']['store']['cache']);

        //为了防止定时任务出现重启服务的时候,导致重启期间(1-3s内)的定时任务无法处理,将定时器时间初始化为当前时间戳之前6秒
        $timerAdvanceTime = (int)Tool::getArrayVal($config, 'timer.time.advance', 6, true);
        $initTimerTime = time() - $timerAdvanceTime;
        self::$_syServer->set(self::$_serverToken, [
            'memory_usage' => memory_get_usage(),
            'timer_time' => $initTimerTime,
            'request_times' => 0,
            'request_handling' => 0,
            'host_local' => $this->_host,
            'storepath_image' => $config['dir']['store']['image'],
            'storepath_music' => $config['dir']['store']['music'],
            'storepath_resources' => $config['dir']['store']['resources'],
            'storepath_cache' => $config['dir']['store']['cache'],
            'token_etime' => time() + 7200,
            'unique_num' => 100000000,
        ]);
    }

    /**
     * 启动工作进程
     * @param \swoole_server $server
     * @param int $workerId 进程编号
     */
    abstract public function onWorkerStart(\swoole_server $server, $workerId);
    /**
     * 退出工作进程
     * @param \swoole_server $server
     * @param int $workerId
     * @return mixed
     */
    abstract public function onWorkerStop(\swoole_server $server, int $workerId);
    /**
     * 工作进程错误处理
     * @param \swoole_server $server
     * @param int $workId 进程编号
     * @param int $workPid 进程ID
     * @param int $exitCode 退出状态码
     */
    abstract public function onWorkerError(\swoole_server $server, $workId, $workPid, $exitCode);
}