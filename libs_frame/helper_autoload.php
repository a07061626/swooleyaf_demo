<?php
require_once __DIR__ . '/helper_defines.php';

final class SyFrameLoader {
    /**
     * @var \SyFrameLoader
     */
    private static $instance = null;
    /**
     * @var array
     */
    private $preHandleMap = [];
    /**
     * aliOpenCore未初始化标识 true：未初始化 false：已初始化
     * @var bool
     */
    private $aliOpenCoreStatus = true;

    private function __construct() {
        $this->preHandleMap = [
            'AliOpen' => 'preHandleAliOpen',
        ];
    }

    private function __clone() {
    }

    /**
     * @return \SyFrameLoader
     */
    public static function getInstance() {
        if(is_null(self::$instance)){
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function preHandleAliOpen(string $className) : string {
        if($this->aliOpenCoreStatus){
            define('ALIOPEN_STS_PRODUCT_NAME', 'Sts');
            define('ALIOPEN_STS_DOMAIN', 'sts.aliyuncs.com');
            define('ALIOPEN_STS_VERSION', '2015-04-01');
            define('ALIOPEN_STS_ACTION', 'AssumeRole');
            define('ALIOPEN_STS_REGION', 'cn-hangzhou');
            define('ALIOPEN_ROLE_ARN_EXPIRE_TIME', 3600);
            define('ALIOPEN_ECS_ROLE_EXPIRE_TIME', 3600);
            define('ALIOPEN_AUTH_TYPE_RAM_AK', 'RAM_AK');
            define('ALIOPEN_AUTH_TYPE_RAM_ROLE_ARN', 'RAM_ROLE_ARN');
            define('ALIOPEN_AUTH_TYPE_ECS_RAM_ROLE', 'ECS_RAM_ROLE');
            define('ALIOPEN_AUTH_TYPE_BEARER_TOKEN', 'BEARER_TOKEN');
            define('ALIOPEN_LOCATION_SERVICE_PRODUCT_NAME', 'Location');
            define('ALIOPEN_LOCATION_SERVICE_DOMAIN', 'location.aliyuncs.com');
            define('ALIOPEN_LOCATION_SERVICE_VERSION', '2015-06-12');
            define('ALIOPEN_LOCATION_SERVICE_DESCRIBE_ENDPOINT_ACTION', 'DescribeEndpoints');
            define('ALIOPEN_LOCATION_SERVICE_REGION', 'cn-hangzhou');
            define('ALIOPEN_CACHE_EXPIRE_TIME', 3600);
            $this->aliOpenCoreStatus = false;

            require_once SY_FRAME_LIBS_ROOT . 'AliOpen/Core/Regions/init_endpoint.php';
        }

        return SY_FRAME_LIBS_ROOT . $className . '.php';
    }

    /**
     * 加载文件
     * @param string $className 类名
     * @return bool
     */
    public function loadFile(string $className) : bool {
        $nameArr = explode('/', $className);
        $funcName = $this->preHandleMap[$nameArr[0]] ?? null;
        if(is_null($funcName)){
            $nameArr = explode('_', $className);
            $funcName = $this->preHandleMap[$nameArr[0]] ?? null;
        }

        $file = is_null($funcName) ? SY_FRAME_LIBS_ROOT . $className . '.php' : $this->$funcName($className);
        if(is_file($file) && is_readable($file)){
            require_once $file;
            return true;
        }

        return false;
    }
}

final class SyProjectLoader {
    /**
     * @var \SyProjectLoader
     */
    private static $instance = null;

    private function __construct(){
    }

    /**
     * @return \SyProjectLoader
     */
    public static function getInstance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 加载文件
     * @param string $className 类名
     * @return bool
     */
    public function loadFile(string $className) : bool {
        $file = SY_PROJECT_LIBS_ROOT . $className . '.php';
        if(is_file($file) && is_readable($file)){
            require_once $file;
            return true;
        }

        return false;
    }
}

/**
 * 基础公共类自动加载
 * @param string $className 类全名
 * @return bool
 */
function syFrameAutoload(string $className) {
    $trueName = str_replace([
        '\\',
        "\0",
    ], [
        '/',
        '',
    ], $className);
    return SyFrameLoader::getInstance()->loadFile($trueName);
}

/**
 * 项目公共类自动加载
 * @param string $className 类全名
 * @return bool
 */
function syProjectAutoload(string $className) {
    $trueName = str_replace([
        '\\',
        "\0",
    ], [
        '/',
        '',
    ], $className);
    return SyProjectLoader::getInstance()->loadFile($trueName);
}

spl_autoload_register('syFrameAutoload');
spl_autoload_register('syProjectAutoload');