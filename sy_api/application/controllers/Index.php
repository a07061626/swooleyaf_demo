<?php
class IndexController extends CommonController {
    public $signStatus = false;

    public function init() {
        parent::init();
        $this->signStatus = false;
    }

    public function indexAction(){
        $this->SyResult->setData([
            'msg' => 'hello world',
        ]);

        $this->sendRsp();
    }
}