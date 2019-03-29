<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/2/22 0022
 * Time: 16:52
 */
namespace Traits\Server;

use Constant\Project;
use DesignPatterns\Singletons\RedisSingleton;
use Tool\Tool;

trait BasicBaseTrait {
    /**
     * 服务配置信息表
     * @var \swoole_table
     */
    protected static $_syServer = null;
    /**
     * 注册的服务信息表
     * @var \swoole_table
     */
    protected static $_syServices = null;
    /**
     * 用户信息列表
     * @var \swoole_table
     */
    protected static $_syUsers = null;
    /**
     * 最大用户数量
     * @var int
     */
    private static $_syUserMaxNum = 0;
    /**
     * 当前用户数量
     * @var int
     */
    private static $_syUserNowNum = 0;

    protected function checkServerBase() {
        $numModules = $this->_configs['server']['cachenum']['modules'];
        if ($numModules < 1) {
            exit('服务模块缓存数量不能小于1');
        } else if (($numModules & ($numModules - 1)) != 0) {
            exit('服务模块缓存数量必须是2的指数倍');
        }

        self::$_syUserNowNum = 0;
        self::$_syUserMaxNum = $this->_configs['server']['cachenum']['users'];
        if (self::$_syUserMaxNum < 1) {
            exit('用户信息缓存数量不能小于1');
        } else if ((self::$_syUserMaxNum & (self::$_syUserMaxNum - 1)) != 0) {
            exit('用户信息缓存数量必须是2的指数倍');
        }

        //检测redis服务是否启动
        RedisSingleton::getInstance()->checkConn();

        $this->checkServerBaseTrait();
    }

    /**
     * 获取服务配置信息
     * @param string $field 配置字段名称
     * @param null $default
     * @return mixed
     */
    public static function getServerConfig(string $field=null, $default=null) {
        if (is_null($field)) {
            $data = self::$_syServer->get(self::$_serverToken);
            return $data === false ? [] : $data;
        } else {
            $data = self::$_syServer->get(self::$_serverToken, $field);
            return $data === false ? $default : $data;
        }
    }

    /**
     * 通过模块名称获取注册的服务信息
     * @param string $moduleName
     * @return array
     */
    public static function getServiceInfo(string $moduleName) {
        $serviceInfo = self::$_syServices->get($moduleName);
        return $serviceInfo === false ? [] : $serviceInfo;
    }

    /**
     * 添加本地用户信息
     * @param string $sessionId 会话ID
     * @param array $userData
     * @return bool
     */
    public static function addLocalUserInfo(string $sessionId,array $userData) : bool {
        if (self::$_syUsers->exist($sessionId)) {
            $userData['session_id'] = $sessionId;
            $userData['add_time'] = Tool::getNowTime();
            self::$_syUsers->set($sessionId, $userData);
            return true;
        } else if (self::$_syUserNowNum < self::$_syUserMaxNum) {
            $userData['session_id'] = $sessionId;
            $userData['add_time'] = Tool::getNowTime();
            self::$_syUsers->set($sessionId, $userData);
            self::$_syUserNowNum++;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取本地用户信息
     * @param string $sessionId 会话ID
     * @return array
     */
    public static function getLocalUserInfo(string $sessionId){
        $data = self::$_syUsers->get($sessionId);
        return $data === false ? [] : $data;
    }

    /**
     * 删除本地用户信息
     * @param string $sessionId 会话ID
     * @return bool
     */
    public static function delLocalUserInfo(string $sessionId) {
        $delRes = self::$_syUsers->del($sessionId);
        if($delRes){
            self::$_syUserNowNum--;
        }
        return $delRes;
    }

    /**
     * 清理本地用户信息缓存
     */
    protected function clearLocalUsers() {
        $time = Tool::getNowTime() - Project::TIME_EXPIRE_LOCAL_USER_CACHE;
        $delKeys = [];
        foreach (self::$_syUsers as $eUser) {
            if($eUser['add_time'] <= $time){
                $delKeys[] = $eUser['session_id'];
            }
        }
        foreach ($delKeys as $eKey) {
            self::$_syUsers->del($eKey);
        }
        self::$_syUserNowNum = self::$_syUsers->count();
    }

    protected function initTableBase() {
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

        self::$_syServices = new \swoole_table($this->_configs['server']['cachenum']['modules']);
        self::$_syServices->column('module', \swoole_table::TYPE_STRING, 30);
        self::$_syServices->column('host', \swoole_table::TYPE_STRING, 128);
        self::$_syServices->column('port', \swoole_table::TYPE_STRING, 5);
        self::$_syServices->column('type', \swoole_table::TYPE_STRING, 16);
        self::$_syServices->create();

        $this->initTableBaseTrait();
    }
}