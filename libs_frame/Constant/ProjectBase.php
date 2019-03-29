<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2018/9/1 0001
 * Time: 15:02
 */
namespace Constant;

use Traits\SimpleTrait;

class ProjectBase {
    use SimpleTrait;

    //容量常量
    const SIZE_SERVER_PACKAGE_MAX = 6291456; //服务端容量-最大接收数据大小,单位为字节,默认为6M
    const SIZE_CLIENT_SOCKET_BUFFER = 12582912; //客户端容量-连接的缓存区大小,单位为字节,默认为12M
    const SIZE_CLIENT_BUFFER_OUTPUT = 4194304; //客户端容量-单次最大发送数据大小,单位为字节,默认为4M

    //校验器常量
    const VALIDATOR_STRING_TYPE_REQUIRED = 'string_required'; //字符串类型-必填
    const VALIDATOR_STRING_TYPE_MIN = 'string_min'; //字符串类型-最小长度
    const VALIDATOR_STRING_TYPE_MAX = 'string_max'; //字符串类型-最大长度
    const VALIDATOR_STRING_TYPE_REGEX = 'string_regex'; //字符串类型-正则表达式
    const VALIDATOR_STRING_TYPE_PHONE = 'string_phone'; //字符串类型-手机号码
    const VALIDATOR_STRING_TYPE_TEL = 'string_tel'; //字符串类型-联系方式
    const VALIDATOR_STRING_TYPE_EMAIL = 'string_email'; //字符串类型-邮箱
    const VALIDATOR_STRING_TYPE_URL = 'string_url'; //字符串类型-URL链接
    const VALIDATOR_STRING_TYPE_JSON = 'string_json'; //字符串类型-JSON
    const VALIDATOR_STRING_TYPE_SIGN = 'string_sign'; //字符串类型-请求签名
    const VALIDATOR_STRING_TYPE_BASE_IMAGE = 'string_baseimage'; //字符串类型-base64编码图片
    const VALIDATOR_STRING_TYPE_IP = 'string_ip'; //字符串类型-IP
    const VALIDATOR_STRING_TYPE_LNG = 'string_lng'; //字符串类型-经度
    const VALIDATOR_STRING_TYPE_LAT = 'string_lat'; //字符串类型-纬度
    const VALIDATOR_STRING_TYPE_NO_JS = 'string_nojs'; //字符串类型-不允许js脚本
    const VALIDATOR_STRING_TYPE_NO_EMOJI = 'string_noemoji'; //字符串类型-不允许emoji表情
    const VALIDATOR_STRING_TYPE_ZH = 'string_zh'; //字符串类型-中文,数字,字母
    const VALIDATOR_STRING_TYPE_ALNUM = 'string_alnum'; //字符串类型-数字,字母
    const VALIDATOR_STRING_TYPE_ALPHA = 'string_alpha'; //字符串类型-字母
    const VALIDATOR_STRING_TYPE_DIGIT = 'string_digit'; //字符串类型-数字
    const VALIDATOR_STRING_TYPE_LOWER = 'string_lower'; //字符串类型-小写字母
    const VALIDATOR_STRING_TYPE_UPPER = 'string_upper'; //字符串类型-大写字母
    const VALIDATOR_STRING_TYPE_DIGIT_LOWER = 'string_digitlower'; //字符串类型-数字,小写字母
    const VALIDATOR_STRING_TYPE_DIGIT_UPPER = 'string_digitupper'; //字符串类型-数字,大写字母
    const VALIDATOR_STRING_TYPE_SY_TOKEN = 'string_sytoken'; //字符串类型-框架令牌
    const VALIDATOR_INT_TYPE_REQUIRED = 'int_required'; //整数类型-必填
    const VALIDATOR_INT_TYPE_MIN = 'int_min'; //整数类型-最小值
    const VALIDATOR_INT_TYPE_MAX = 'int_max'; //整数类型-最大值
    const VALIDATOR_INT_TYPE_IN = 'int_in'; //整数类型-取值枚举
    const VALIDATOR_INT_TYPE_BETWEEN = 'int_between'; //整数类型-取值区间
    const VALIDATOR_DOUBLE_TYPE_REQUIRED = 'double_required'; //浮点数类型-必填
    const VALIDATOR_DOUBLE_TYPE_MIN = 'double_min'; //浮点数类型-最小值
    const VALIDATOR_DOUBLE_TYPE_MAX = 'double_max'; //浮点数类型-最大值
    const VALIDATOR_DOUBLE_TYPE_BETWEEN = 'double_between'; //浮点数类型-取值区间

    //时间常量
    const TIME_EXPIRE_LOCAL_USER_CACHE = 300; //超时时间-本地用户缓存,单位为秒
    const TIME_EXPIRE_LOCAL_API_SIGN_CACHE = 300; //超时时间-本地api签名缓存,单位为秒
    const TIME_EXPIRE_SWOOLE_CLIENT_HTTP = 3000; //超时时间-http服务客户端,单位为毫秒
    const TIME_EXPIRE_SWOOLE_CLIENT_RPC = 3000; //超时时间-rpc服务客户端,单位为毫秒
    const TIME_TASK_CLEAR_API_SIGN = 300000; //任务时间-清理api签名,单位为毫秒
    const TIME_TASK_CLEAR_LOCAL_USER = 300000; //任务时间-清理本地用户信息,单位为毫秒
    const TIME_TASK_CLEAR_LOCAL_WX = 300000; //任务时间-清理本地微信,单位为毫秒
    const TIME_TASK_REFRESH_TOKEN_EXPIRE = 540000; //任务时间-刷新令牌到期时间,单位为毫秒
    const TIME_EXPIRE_SESSION = 259200; //超时时间-session,单位为秒

    //服务预处理常量,标识长度为5位,第一位固定为/,后四位代表不同预处理操作,其中后四位全为数字的为框架内部预留标识
    const PRE_PROCESS_TAG_HTTP_FRAME_SERVER_INFO = '/0000'; //HTTP服务框架内部标识-服务信息
    const PRE_PROCESS_TAG_HTTP_FRAME_PHP_INFO = '/0001'; //HTTP服务框架内部标识-php环境信息
    const PRE_PROCESS_TAG_HTTP_FRAME_HEALTH_CHECK = '/0002'; //HTTP服务框架内部标识-健康检测
    const PRE_PROCESS_TAG_HTTP_FRAME_REFRESH_TOKEN_EXPIRE = '/0003'; //HTTP服务框架内部标识-更新令牌过期时间
    const PRE_PROCESS_TAG_RPC_FRAME_SERVER_INFO = '/0000'; //RPC服务框架内部标识-服务信息

    //任务常量,4位字符串,数字和字母组成,纯数字的为框架内部任务,其他为自定义任务
    const TASK_TYPE_CLEAR_API_SIGN_CACHE = '0001'; //任务类型-清理api签名缓存
    const TASK_TYPE_CLEAR_LOCAL_USER_CACHE = '0002'; //任务类型-清除本地用户信息缓存
    const TASK_TYPE_CLEAR_LOCAL_WX_CACHE = '0003'; //任务类型-清除本地微信缓存
    const TASK_TYPE_TIME_WHEEL_TASK = '0004'; //任务类型-时间轮任务
    const TASK_TYPE_REFRESH_TOKEN_EXPIRE = '0005'; //任务类型-刷新令牌到期时间

    //数据常量
    const DATA_KEY_SESSION_TOKEN = 'sytoken'; //键名-session标识
    const DATA_KEY_CACHE_UNIQUE_ID = self::REDIS_PREFIX_UNIQUE_ID . 'uniqueid'; //键名-缓存唯一ID

    //REDIS常量 后五位全数字的前缀为框架内部前缀
    const REDIS_PREFIX_SESSION = 'sy' . SY_PROJECT . '00001_'; //前缀-session
    const REDIS_PREFIX_SESSION_LIST = 'sy' . SY_PROJECT . '00002_'; //前缀-session列表
    const REDIS_PREFIX_UNIQUE_ID = 'sy' . SY_PROJECT . '00003_'; //前缀-唯一ID
}