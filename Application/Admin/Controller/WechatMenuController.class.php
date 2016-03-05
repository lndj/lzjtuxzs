<?php
namespace Admin\Controller;
use Think\Controller;
use Lib\Wechat\WechatAuth;

/**
 * Class WechatMenuController
 * @package Admin\Controller
 * 微信自定义菜单控制器
 */
class WechatMenuController extends CommonController {

    public function index(){
        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $wechatauth = new WechatAuth($appid,$secret);
        $this->menu = $wechatauth->getMenu();
        $this->display();
    }
    public function handle(){
        if(!IS_POST) $this->error('访问错误！');

        $json = I('post.');
        //post json会被转义为HTML实体，此处转回来
        $json = htmlspecialchars_decode($json['menu']);

        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $wechatauth = new WechatAuth($appid,$secret);
        $errcode = $wechatauth->createMenu($json);

        $errcode = json_decode($errcode,true);
        if($errcode['errcode'] == 0){
            $this->ajaxReturn(array('status' => 1));
        }else{
            $this->ajaxReturn(array('status' => $errcode['errcode'].":".$errcode['errmsg']));

        }
    }
}


