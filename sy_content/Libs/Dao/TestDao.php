<?php
/**
 * Created by PhpStorm.
 * User: 姜伟
 * Date: 2019/3/17 0017
 * Time: 15:08
 */
namespace Dao;

use Factories\SyTrainMysqlFactory;
use Tool\Tool;
use Traits\SimpleDaoTrait;

class TestDao {
    use SimpleDaoTrait;

    public static function addShop(array $data){
        $nowTime = Tool::getNowTime();
        $shopBase = SyTrainMysqlFactory::ShopBaseEntity();
        $shopBase->title = Tool::createNonceStr(8);
        $shopBase->created = $nowTime;
        $shopBase->updated = $nowTime;
        $shopId = $shopBase->getContainer()->getModel()->insert($shopBase->getEntityDataArray());
        unset($shopBase);

        return [
            'shop_id' => $shopId,
        ];
    }

    public static function getShopList(array $data){
        $shopBase = SyTrainMysqlFactory::ShopBaseEntity();
        $ormResult1 = $shopBase->getContainer()->getModel()->getOrmDbTable();
        $ormResult1->where('`id`>?', [3])->order('`id` DESC');
        $shopList = $shopBase->getContainer()->getModel()->findPage($ormResult1, $data['page'], $data['limit']);
        unset($ormResult1, $shopBase);

        return $shopList;
    }
}