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

    //进程常量
    const PROCESS_TYPE_TASK = 'Task'; //类型-task
    const PROCESS_TYPE_WORKER = 'Worker'; //类型-worker
    const PROCESS_TYPE_MANAGER = 'Manager'; //类型-manager
    const PROCESS_TYPE_MAIN = 'Main'; //类型-main
}
