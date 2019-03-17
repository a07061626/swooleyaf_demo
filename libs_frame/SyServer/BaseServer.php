<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 10:17
 */
namespace SyServer;

abstract class BaseServer {
    /**
     * 请求服务对象
     * @var \swoole_websocket_server|\swoole_server
     */
    protected $_server = null;

    public function __construct(){
    }

    /**
     * 开启服务
     */
    abstract public function start();
}