<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    /**
     * @SyFilter-{"field": "_ignoresign","explain": "签名标识","type": "string","rules": {"min": 0}}
     * @SyFilter-{"field": "tag","explain": "标识","type": "string","rules": {"min": 1,"required": 1}}
     */
    public function indexAction(){
        $this->SyResult->setData([
            'msg' => 'hello world',
        ]);

        $this->sendRsp();
    }

    /**
     * @SyFilter-{"field": "_ignoresign","explain": "签名标识","type": "string","rules": {"min": 0}}
     */
    public function test2Action() {
        \Response\SyResponseHttp::header('Content-Type', 'text/html; charset=utf-8');
        $renderRes = $this->getView()->render('index/index.html', [
            'aaa' => 'xxdd',
        ]);

        $this->sendRsp($renderRes);
    }
}