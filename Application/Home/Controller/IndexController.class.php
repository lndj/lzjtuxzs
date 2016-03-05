<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use Think\Controller;
use Lib\Wechat\WechatAuth;

/**
 * Class IndexController
 * @package Home\Controller
 */
class IndexController extends Controller{
    /**
     * 小助手宣传页视图
     */
    public function index(){
        $conf = M('config');
        $config = $conf->where(array('item' => 'INDEX_QRCODE'))->find();
        $this->qrcode = $config['config'];
        $this->display();
    }

    /**
     * 处理来自首页的反馈消息，发送模板消息通知管理员
     */
    public function handle(){
        if(!IS_AJAX) $this->error("访问错误！");
        $feedback = I('post.','','htmlspecialchars_decode');
        //缓存下用户的反馈内容
        S($feedback['email'],$feedback['message']);

        $appid = C('WEIXIN.APPID'); //AppID
        $secret = C('WEIXIN.SECRET');
        $wechatauth = new WechatAuth($appid,$secret);

        $data = array(
            'first' => array('value'=>'有来自首页的新反馈。', 'color'=>'#0A0A0A'),
            'keyword1' => array('value'=>$feedback['name'], 'color'=>'#0099CC'),
            'keyword2' => array('value'=>$feedback['message'], 'color'=>'#0099CC'),
            'keyword3' => array('value'=>date("Y-m-d H:i:s"), 'color'=>'#0099CC'),
            'remark' => array('value'=>'用户邮箱：'.$feedback['email'], 'color'=>'#173177'),
        );
        $openid = 'om9c7s6Ev7zXzqYJTnymC8cHgJPQ';
        $url = C('SYSTEME_DOMAIN').U('Index/feedback',array('email'=>$feedback['email']));
        $ret = $wechatauth->sendTemplateMessage($data,$openid,'5zvmeaHlBQA-f3B-cagZtTj6e-l5BqovFSA8V0G7-aE',$url);
        $ret = json_decode($ret,true);
        sleep(1);

        if($ret['errcode'] == 0){
            $this->ajaxReturn(array('status' => 1));
        }else{
            $this->ajaxReturn(array('status' => 0));
        }
    }

    /**
     * 管理员收到反馈，打开网页回复
     */
    public function feedback(){
        $email = I('get.email');
        $this->message = S(trim($email));

        $this->display();
    }

    /**
     * 处理回复
     * 由于是在网页端，没有OPENID,因此邮件方式回复
     */
    public function feedbackHandle(){
        if(!IS_AJAX) $this->error("访问错误！");
        $message = I('post.message');
        $this->ajaxReturn(array('status' => 1));
    }

}
