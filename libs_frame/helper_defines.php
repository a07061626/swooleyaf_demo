<?php
/**
 * 项目初始化基础配置
 * User: 姜伟
 * Date: 2019/1/16 0016
 * Time: 9:00
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set('PRC');
define('SY_VERSION', '6.3.1');

//请求异常处理类型 true:框架处理 false:项目处理
if(!defined('SY_REQ_EXCEPTION_HANDLE_TYPE')){
    define('SY_REQ_EXCEPTION_HANDLE_TYPE', true);
}