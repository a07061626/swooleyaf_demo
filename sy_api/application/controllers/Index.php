<?php
class IndexController extends CommonController {
    public $signStatus = true;

    public function init() {
        parent::init();
        $this->signStatus = true;
    }

    /**
     * @SyFilter-{"field": "tag","explain": "标识","type": "string","rules": {"min": 1,"required":1}}
     */
    public function indexAction(){
        $this->SyResult->setData([
            'msg' => 'hello swoole',
        ]);

        $this->sendRsp();
    }
}