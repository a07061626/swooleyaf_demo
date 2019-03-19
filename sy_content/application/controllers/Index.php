<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    /**
     * @SyFilter-{"field": "tag","explain": "标识","type": "string","rules": {"min": 1,"required": 1}}
     */
    public function indexAction(){
        $tag = \Request\SyRequest::getParams('tag');
        $this->SyResult->setData([
            'msg' => 'tag is' . $tag,
        ]);

        $this->sendRsp();
    }
}