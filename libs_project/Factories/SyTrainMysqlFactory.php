<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-3-10
 * Time: 21:47
 */
namespace Factories;

use Entities\SyTrain\ShopBaseEntity;
use Traits\SimpleTrait;

class SyTrainMysqlFactory {
    use SimpleTrait;

    /**
     * @param string $dbName 数据库名
     * @return \Entities\SyTrain\ShopBaseEntity
     */
    public static function ShopBaseEntity(string $dbName=''){
        return new ShopBaseEntity($dbName);
    }
}