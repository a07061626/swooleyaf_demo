<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    public function indexAction(){
        $this->SyResult->setData([
            'short_url' => \SyServer\HttpServer::getServerConfig('cookiedomain_base'),
        ]);

        $this->sendRsp();
    }
}