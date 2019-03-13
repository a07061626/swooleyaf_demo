<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    /**
     * @SyFilter-{"field": "tag","explain": "标识","type": "string","rules": {"min": 1,"required":1}}
     */
    public function indexAction(){
        \Log\Log::log('xxx3');
        $this->SyResult->setData([
            'msg' => 'hello swoole',
        ]);

        $this->sendRsp();
    }
}