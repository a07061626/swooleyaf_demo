<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/12 0012
 * Time: 8:19
 */
namespace Constant;

use Traits\SimpleTrait;

class Server {
    use SimpleTrait;

    //进程常量
    const PROCESS_TYPE_TASK = 'Task'; //类型-task
    const PROCESS_TYPE_WORKER = 'Worker'; //类型-worker
    const PROCESS_TYPE_MANAGER = 'Manager'; //类型-manager
    const PROCESS_TYPE_MAIN = 'Main'; //类型-main

    //服务常量
    const SERVER_DATA_KEY_TIMESTAMP = 'SYREQ_TIME'; //服务端内部数据键名-请求时间戳

    //路由常量
    const ROUTE_TYPE_SIMPLE = 'simple'; //类型-简单路由

    //注册常量
    const REGISTRY_NAME_SERVICE_ERROR = 'SERVICE_ERROR'; //名称-服务错误
    const REGISTRY_NAME_REQUEST_HEADER = 'REQUEST_HEADER'; //名称-请求头
    const REGISTRY_NAME_REQUEST_SERVER = 'REQUEST_SERVER'; //名称-服务器信息
    const REGISTRY_NAME_RESPONSE_HEADER = 'RESPONSE_HEADER'; //名称-响应头
    const REGISTRY_NAME_RESPONSE_COOKIE = 'RESPONSE_COOKIE'; //名称-响应cookie
}