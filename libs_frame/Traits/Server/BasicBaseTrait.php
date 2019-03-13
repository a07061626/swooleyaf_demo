<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/2/22 0022
 * Time: 16:52
 */
namespace Traits\Server;

trait BasicBaseTrait {
    /**
     * 服务配置信息表
     * @var \swoole_table
     */
    protected static $_syServer = null;

    protected function checkServerBase() {
        $this->checkServerBaseTrait();
    }

    protected function initTableBase() {
        register_shutdown_function('\SyError\ErrorHandler::handleFatalError');

        self::$_syServer = new \swoole_table(1);
        self::$_syServer->column('memory_usage', \swoole_table::TYPE_INT, 4);
        self::$_syServer->column('timer_time', \swoole_table::TYPE_INT, 4);
        self::$_syServer->column('request_times', \swoole_table::TYPE_INT, 4);
        self::$_syServer->column('request_handling', \swoole_table::TYPE_INT, 4);
        self::$_syServer->column('host_local', \swoole_table::TYPE_STRING, 20);
        self::$_syServer->column('storepath_image', \swoole_table::TYPE_STRING, 150);
        self::$_syServer->column('storepath_music', \swoole_table::TYPE_STRING, 150);
        self::$_syServer->column('storepath_resources', \swoole_table::TYPE_STRING, 150);
        self::$_syServer->column('storepath_cache', \swoole_table::TYPE_STRING, 150);
        self::$_syServer->column('token_etime', \swoole_table::TYPE_INT, 8);
        self::$_syServer->column('unique_num', \swoole_table::TYPE_INT, 8);
        self::$_syServer->create();

        $this->initTableBaseTrait();
    }
}