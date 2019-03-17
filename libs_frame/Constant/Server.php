<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2017/3/12 0012
 * Time: 9:30
 */
namespace Constant;

use Traits\SimpleTrait;

final class Server {
    use SimpleTrait;

    //服务常量
    public static $totalServerType = [
        self::SERVER_TYPE_API_GATE => 'api入口',
        self::SERVER_TYPE_API_MODULE => 'api模块',
        self::SERVER_TYPE_FRONT_GATE => '前端入口',
    ];
    const SERVER_TYPE_API_GATE = 'api'; //服务端类型-api入口
    const SERVER_TYPE_API_MODULE = 'rpc'; //服务端类型-api模块
    const SERVER_TYPE_FRONT_GATE = 'frontgate'; //服务端类型-前端入口
    const SERVER_HTTP_TAG_RESPONSE_EOF = "\r\r\rswoole@yaf\r\r\r"; //服务端http标识-响应结束符
    const SERVER_HTTP_TAG_REQUEST_HEADER = 'swoole-yaf'; //服务端http标识-请求头名称
    const SERVER_DATA_KEY_TASK = '_sytask'; //服务端内部数据键名-task
    const SERVER_DATA_KEY_TIMESTAMP = 'SYREQ_TIME'; //服务端内部数据键名-请求时间戳

    //进程常量
    const PROCESS_TYPE_TASK = 'Task'; //类型-task
    const PROCESS_TYPE_WORKER = 'Worker'; //类型-worker
    const PROCESS_TYPE_MANAGER = 'Manager'; //类型-manager
    const PROCESS_TYPE_MAIN = 'Main'; //类型-main

    //路由常量
    const ROUTE_TYPE_SIMPLE = 'simple'; //类型-简单路由

    //注册常量
    const REGISTRY_NAME_SERVICE_ERROR = 'SERVICE_ERROR'; //名称-服务错误
    const REGISTRY_NAME_REQUEST_HEADER = 'REQUEST_HEADER'; //名称-请求头
    const REGISTRY_NAME_REQUEST_SERVER = 'REQUEST_SERVER'; //名称-服务器信息
    const REGISTRY_NAME_RESPONSE_HEADER = 'RESPONSE_HEADER'; //名称-响应头
    const REGISTRY_NAME_RESPONSE_COOKIE = 'RESPONSE_COOKIE'; //名称-响应cookie
}
