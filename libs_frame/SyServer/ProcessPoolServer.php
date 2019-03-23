<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 19-3-22
 * Time: 下午11:04
 */
namespace SyServer;

use Constant\Server;
use Log\Log;
use Tool\Dir;
use Tool\Tool;
use Traits\ProcessPoolFrameTrait;
use Traits\ProcessPoolProjectTrait;

class ProcessPoolServer {
    use ProcessPoolFrameTrait;
    use ProcessPoolProjectTrait;

    /**
     * 连接池对象
     * @var \swoole_process_pool
     */
    private $pool = null;

    /**
     * 请求域名
     * @var string
     */
    private $_host = '';
    /**
     * 请求端口
     * @var int
     */
    private $_port = 0;
    /**
     * pid文件
     * @var string
     */
    private $_pidFile = '';
    /**
     * 提示文件
     * @var string
     */
    protected $_tipFile = '';
    /**
     * 配置数组
     * @var array
     */
    private $_configs = [];

    public function __construct(int $port){
        if (($port <= Server::ENV_PORT_MIN) || ($port > Server::ENV_PORT_MAX)) {
            exit('端口不合法' . PHP_EOL);
        }
        $this->checkSystemEnv();
        $this->_configs = Tool::getConfig('sypool.' . SY_ENV . SY_MODULE);
        $bindRes = Tool::checkPortBind($this->_configs['process']['host'], $port);
        if($bindRes['err_no'] > 0){
            exit($bindRes['err_msg'] . PHP_EOL);
        }
        $this->checkPoolFrame();
        $this->checkPoolProject();

        define('SY_SERVER_IP', $this->_configs['process']['host']);

        $this->_configs['process']['port'] = $port;

        Dir::create(SY_ROOT . '/pidfile/');
        Dir::create(SY_ROOT . '/tipfile/');
        $this->_host = $this->_configs['process']['host'];
        $this->_port = $this->_configs['process']['port'];
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

        //设置日志目录
        Log::setPath(SY_LOG_PATH);
    }

    private function __clone(){
    }

    private function checkSystemEnv() {
        if(PHP_INT_SIZE < 8){
            exit('操作系统必须是64位' . PHP_EOL);
        }
        if(version_compare(PHP_VERSION, Server::VERSION_MIN_PHP, '<')){
            exit('PHP版本必须大于等于' . Server::VERSION_MIN_PHP . PHP_EOL);
        }
        if (!defined('SY_MODULE')) {
            exit('模块名称未定义' . PHP_EOL);
        }
        if(!in_array(SY_ENV, Server::$totalEnvProject)){
            exit('环境类型不合法' . PHP_EOL);
        }

        $os = php_uname('s');
        if(!in_array($os, Server::$totalEnvSystem)){
            exit('操作系统不支持' . PHP_EOL);
        }

        //检查必要的扩展是否存在
        $extensionList = [
            'yac',
            'yaf',
            'PDO',
            'pcre',
            'pcntl',
            'redis',
            'yaconf',
            'swoole',
            'SeasLog',
            'msgpack',
        ];
        foreach ($extensionList as $extName) {
            if(!extension_loaded($extName)){
                exit('扩展' . $extName . '未加载' . PHP_EOL);
            }
        }

        if(version_compare(SWOOLE_VERSION, Server::VERSION_MIN_SWOOLE, '<')){
            exit('swoole版本必须大于等于' . Server::VERSION_MIN_SWOOLE . PHP_EOL);
        }
        if(version_compare(SEASLOG_VERSION, Server::VERSION_MIN_SEASLOG, '<')){
            exit('seaslog版本必须大于等于' . Server::VERSION_MIN_SEASLOG . PHP_EOL);
        }
        if(version_compare(YAC_VERSION, Server::VERSION_MIN_YAC, '<')){
            exit('yac版本必须大于等于' . Server::VERSION_MIN_YAC . PHP_EOL);
        }
        if(version_compare(\YAF\VERSION, Server::VERSION_MIN_YAF, '<')){
            exit('yaf版本必须大于等于' . Server::VERSION_MIN_YAF . PHP_EOL);
        }
    }

    public function help(){
    }

    public function start(){
        $this->initTableFrame();
        $this->initTableProject();

        @cli_set_process_title(Server::PROCESS_TYPE_MAIN . SY_MODULE . $this->_port);
        \swoole_process::daemon(true, false);
        file_put_contents($this->_pidFile, getmypid());
        file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;31m \t[fail] \e[0m');

        $this->pool = new \swoole_process_pool($this->_configs['process']['num']['worker'], SWOOLE_IPC_SOCKET);
        $this->pool->on('workerStart', [$this, 'onWorkerStart']);
        $this->pool->on('workerStop', [$this, 'onWorkerStop']);
        $this->pool->on('message', [$this, 'onMessage']);
        $this->pool->listen($this->_host, $this->_port, $this->_configs['process']['num']['backlog']);
        $this->pool->start();
    }

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
    
    public function onWorkerStart(\swoole_process_pool $pool,int $workerId){
        @cli_set_process_title(Server::PROCESS_TYPE_WORKER . SY_MODULE . $this->_port);

        if($workerId == 0){
            file_put_contents($this->_tipFile, '\e[1;36m start ' . SY_MODULE . ': \e[0m \e[1;32m \t[success] \e[0m');
        }
    }

    public function onWorkerStop(\swoole_process_pool $pool,int $workerId){
    }

    public function onMessage(\swoole_process_pool $pool,string $data){
    }
}