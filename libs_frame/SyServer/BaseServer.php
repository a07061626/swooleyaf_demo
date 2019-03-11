<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/11 0011
 * Time: 19:13
 */
namespace SyServer;

abstract class BaseServer {
    /**
     * @var \swoole_server
     */
    protected $_server = null;

    public function __construct(){
    }

    private function __clone(){
    }

    abstract public function start();
}