<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2018/7/21 0021
 * Time: 9:28
 */
namespace Helper;

use SyServer\HttpServer;
use Tool\Tool;

class ServiceRunner {
    /**
     * @param string $apiName api模块名称
     * @param array $totalModule 包含所有模块的数组
     */
    public static function run(string $apiName,array $totalModule){
        $moduleName = trim(Tool::getClientOption('-module', false, ''));
        if (strlen($moduleName) == 0) {
            exit('module name must exist' . PHP_EOL);
        } else if (!in_array($moduleName, $totalModule)) {
            exit('module name error' . PHP_EOL);
        }
        define('SY_MODULE', $moduleName);

        $port = trim(Tool::getClientOption('-port', false, ''));
        if(!ctype_digit($port)){
            exit('port must exist and is integer' . PHP_EOL);
        }
        $truePort = (int)$port;

        $server = new HttpServer($truePort);

        $action = Tool::getClientOption('-s', false, 'start');
        switch ($action) {
            case 'start' :
                $server->start();
                break;
            case 'stop' :
                $server->stop();
                break;
            case 'restart' :
                $server->stop();
                sleep(3);
                $server->start();
                break;
            case 'startstatus' :
                $server->getStartStatus();
                break;
            default :
                $server->help();
        }
    }
}