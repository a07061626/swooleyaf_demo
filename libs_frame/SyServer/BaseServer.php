<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 10:17
 */
namespace SyServer;

use Tool\Tool;

abstract class BaseServer {
    /**
     * 请求服务对象
     * @var \swoole_websocket_server|\swoole_server
     */
    protected $_server = null;
    /**
     * 配置数组
     * @var array
     */
    protected $_configs = [];
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

    public function __construct(int $port){
        if (($port <= 1024) || ($port > 65535)) {
            exit('端口不合法' . PHP_EOL);
        }
        $this->_configs = Tool::getConfig('syserver.' . SY_ENV . SY_MODULE);
        $this->_configs['server']['port'] = $port;

        $this->_host = $this->_configs['server']['host'];
        $this->_port = $this->_configs['server']['port'];
    }

    private function __clone() {
    }

    /**
     * 开启服务
     */
    abstract public function start();
}