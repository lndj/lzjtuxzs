<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-9
 * Time: 下午9:08
 */
namespace Admin\Controller;
use Think\Controller;
use Lib\Wechat\WechatAuth;

use Lib\Tool\Page;
/**
 * Class WechatUserController
 * @package Admin\Controller
 * 微信关注者
 */
class WechatUserController extends CommonController{
    /**
     * 关注者列表
     */
    public function index(){
        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $wechatauth = new WechatAuth($appid,$secret);
//        $fansList = $wechatauth->userGet();
////        p($fansList);
//        $this->total = $fansList['total'];
//
//        $this->next_openid = $fansList['next_openid'];
//        foreach($fansList['data']['openid'] as $fl){
//            $userinfo[] = $wechatauth->userInfo($fl);
//        }
//        $this->userinfo = $userinfo;
//        p($this->userinfo);


        $u = M('user');

        $dataCount = $u->count();

        $p = new Page($dataCount,20,array('type' => 'all','key' => 'jwid'));
        $p->setConfig('prev','上一页');
        $p->setConfig('next','下一页');
        $p->setConfig('first','首页');
        $p->setConfig('last','末页');
        //关闭最后页显示总数字
        $p->lastSuffix = false;
        $p->rollPage = 9;
        $this->pagination = $p->show();

//        var_dump($this->pagination);

        $this->display();

    }

}