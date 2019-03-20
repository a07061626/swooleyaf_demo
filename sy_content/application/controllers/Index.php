<?php
class IndexController extends CommonController {
    public function init() {
        parent::init();
    }

    /**
     * 总站添加地区
     * @api {post} /Index/Index/index 总站添加地区
     * @apiDescription 总站添加地区
     * @apiGroup Index
     * @apiParam {string} region_name 地区名称
     * @apiParam {number} region_sort 地区排序
     * @apiParam {number} region_level 地区级别
     * @apiParam {string} [region_ptag] 父地区标识
     * @SyFilter-{"field": "region_name","explain": "地区名称","type": "string","rules": {"required": 1,"min": 1}}
     * @SyFilter-{"field": "region_sort","explain": "地区排序","type": "int","rules": {"required": 1,"min": 1,"max": 1000}}
     * @SyFilter-{"field": "region_level","explain": "地区级别","type": "int","rules": {"required": 1,"min": 1,"max": 3}}
     * @SyFilter-{"field": "region_ptag","explain": "父地区标识","type": "string","rules": {"min": 0,"max": 6}}
     * @apiUse CommonSuccess
     * @apiUse CommonFail
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