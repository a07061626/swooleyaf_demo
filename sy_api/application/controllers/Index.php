<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    /**
     * @SyFilter-{"field": "_ignoresign","explain": "签名标识","type": "string","rules": {"min": 0}}
     */
    public function indexAction(){
        $data = $_GET;
        $getRes = \SyModule\SyModuleContent::getInstance()->sendApiReq('/Index/Index/index', $data);
        $this->sendRsp($getRes);
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