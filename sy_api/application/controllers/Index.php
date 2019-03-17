<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    /**
     * @SyFilter-{"field": "_ignoresign","explain": "签名标识","type": "string","rules": {"min": 0}}
     */
    public function indexAction(){
        $this->SyResult->setData([
            'msg' => 'hello world',
        ]);

        $this->sendRsp();
    }
}