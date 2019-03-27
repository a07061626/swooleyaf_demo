<?php
namespace Entities\SyTrain;

use DB\Entities\MysqlEntity;

class ShopBaseEntity extends MysqlEntity {
    public function __construct(string $dbName='') {
        $this->_dbName = isset($dbName{0}) ? $dbName : 'sytrain';
        parent::__construct($this->_dbName, 'shop_base', 'id');
    }

    /**
     * 
     * @var int
     */
    public $id = null;

    /**
     * 名称
     * @var string
     */
    public $title = '';

    /**
     * 创建时间戳
     * @var int
     */
    public $created = 0;

    /**
     * 修改时间戳
     * @var int
     */
    public $updated = 0;
}
