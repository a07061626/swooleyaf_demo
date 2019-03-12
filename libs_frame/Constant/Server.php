<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/12 0012
 * Time: 8:19
 */
namespace Constant;

class Server {
    //进程常量
    const PROCESS_TYPE_TASK = 'Task'; //类型-task
    const PROCESS_TYPE_WORKER = 'Worker'; //类型-worker
    const PROCESS_TYPE_MANAGER = 'Manager'; //类型-manager
    const PROCESS_TYPE_MAIN = 'Main'; //类型-main
}