<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2017/3/2 0002
 * Time: 11:18
 */
namespace Tool;

use Traits\SimpleTrait;

class Tool {
    use SimpleTrait;

    /**
     * 获取命令行输入
     * @param string|int $key 键名
     * @param bool $isIndexKey 键名是否为索引 true:是索引 false:不是索引
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function getClientOption($key,bool $isIndexKey=false, $default=null) {
        global $argv;

        $option = $default;
        if($isIndexKey){
            if(isset($argv[$key])){
                $option = $argv[$key];
            }
        } else {
            foreach ($argv as $eKey => $eVal) {
                if(($key == $eVal) && isset($argv[$eKey+1])){
                    $option = $argv[$eKey+1];
                    break;
                }
            }
        }

        return $option;
    }

    /**
     * 获取数组值
     * @param array $array 数组
     * @param string|int $key 键值
     * @param object $default 默认值
     * @param bool $isRecursion 是否递归查找,false:不递归 true:递归
     * @return mixed
     */
    public static function getArrayVal(array $array, $key, $default=null,bool $isRecursion=false){
        if(!$isRecursion){
            return $array[$key] ?? $default;
        }

        $keyArr = explode('.', (string)$key);
        $tempData = $array;
        unset($array);
        foreach ($keyArr as $eKey) {
            if(is_array($tempData) && isset($tempData[$eKey])){
                $tempData = $tempData[$eKey];
            } else {
                return $default;
            }
        }

        return $tempData;
    }

    /**
     * 获取配置信息
     * @param string $tag 配置标识
     * @param string $field 字段名称
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function getConfig(string $tag,string $field='', $default=null){
        $configs = \Yaconf::get($tag);
        if(is_null($configs)){
            return $default;
        } else if(is_array($configs) && (strlen($field) > 0)){
            return self::getArrayVal($configs, $field, $default);
        } else {
            return $configs;
        }
    }

    /**
     * 执行系统命令
     * @param string $command
     * @return array
     */
    public static function execSystemCommand(string $command) : array {
        $trueCommand = trim($command);
        if(strlen($trueCommand) == 0){
            return [
                'code' => 9999,
                'msg' => '执行命令不能为空',
            ];
        }

        $code = 0;
        $output = [];
        $msg = exec($trueCommand, $output, $code);
        if($code == 0){
            return [
                'code' => 0,
                'data' => $output,
            ];
        } else {
            return [
                'code' => $code,
                'msg' => $msg,
            ];
        }
    }
}
