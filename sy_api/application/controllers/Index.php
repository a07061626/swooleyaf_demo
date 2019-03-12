<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    public function indexAction(){
        $this->SyResult->setData([
            'msg' => 'hello swoole',
        ]);

        $this->sendRsp();
    }
}