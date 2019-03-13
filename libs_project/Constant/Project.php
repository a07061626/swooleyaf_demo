<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2017/3/2 0002
 * Time: 11:30
 */
namespace Constant;

use Traits\SimpleTrait;

final class Project extends ProjectBase {
    use SimpleTrait;

    //服务预处理常量,标识长度为5位,第一位固定为/,后四位代表不同预处理操作,其中后四位全为数字的为框架内部预留标识
    const PRE_PROCESS_TAG_HTTP_PROJECT_TEST = '/a000'; //HTTP服务项目标识-测试
    const PRE_PROCESS_TAG_RPC_PROJECT_TEST = '/a000'; //RPC服务项目标识-测试
}
