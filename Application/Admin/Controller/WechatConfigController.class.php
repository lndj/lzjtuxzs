<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-17
 * Time: 下午11:35
 */

namespace Admin\Controller;
//use Admin\CommonController;

class WechatConfigController extends CommonController {

    /**
     * 微信欢迎语设置
     */
    public function index(){
        $this->display();

    }

    /**
     * 处理微信关注欢迎语设置
     */
    public function handleWelcome(){
        if( !IS_AJAX )
            $this->error('访问错误！');

        $welcome = I('post.welcome');
        $ret = S('subscribe_welcome',$welcome);
        if($ret)
            $this->ajaxReturn(array('status' => 1));
        else
            $this->ajaxReturn(array('status' => 0));
    }

}