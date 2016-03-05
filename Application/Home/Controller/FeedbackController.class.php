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
use Lib\Wechat\JSSDK;

class FeedbackController extends Controller{
    /**
     * 反馈通道用户视图
     */
    public function index(){
        $wechatauth = $this->importWechatAuth();
        $url = C('SYSTEME_DOMAIN').U('Feedback/feedback');
        //网页授权第一步获取code
        $result = $wechatauth->getRequestCodeURL($url,'feedback','snsapi_base');
        redirect($result);
    }

    /**
     * 授权获取openid
     */
    public function feedback(){
        //微信授权认证后会自动跳转至此方法，GET获取code或者state
        $code = I('get.code');

        $wechatauth = $this->importWechatAuth();
        $openid = session('openid');
        if(!$openid){
            //获取accessToken 和openid
            $ret = $wechatauth->getAccessTokenAndOpenId($code);
            //将获取到的openid写入session
            session('openid',$ret['openid']);
            $this->openid = $ret['openid'];
        }else{
            $this->openid = $openid;
        }
        //关闭网页时候采用JSSDK
        $appid = C('WEIXIN.APPID'); //AppID
        $secret = C('WEIXIN.SECRET');
        $jssdk = new JSSDK($appid,$secret);
        $this->signPackage = $jssdk->getSignPackage();

        $this->display();
    }

    /**
     * 用户反馈处理
     */
    public function handle(){
        if(!IS_AJAX) $this->error("访问错误！");
        $type = I('post.type');
        $suggestion = I('post.suggestion');
        $openid = I('post.openid');

        $feedback = M('feedback');
        $data = array(
            'type' => $type,
            'content' => $suggestion,
            'openid' => $openid,
            'createtime' => date("Y-m-d H:i:s"),
            'status' => 0,
        );
        if($feedback->create($data)){
            $result = $feedback->add();
            if($result){
                $id = $result;
            }else{
                $this->ajaxReturn(array('status' => 0));
            }
        }else{
            $this->ajaxReturn(array('status' => -1));
        }

        $type = feedback_type_trans($type);


        $this->ajaxReturn( array( 'status' => 1 ) );
    }

    /**
     * 管理员点击模板消息可回复反馈者
     */
    public function reply(){
        $id = I('get.id');
        $feedback = M('feedback');
        $this->suggestion = $feedback->where(array('id' => $id))->find();
        $this->display();
    }

    /**
     * 回复处理
     */
    public function replyHandle(){
        if(!IS_AJAX) $this->error("访问错误！");
        $message = I('post.message');
        $type = I('post.type');
        $type = feedback_type_trans($type);
        $id = I('post.id','','intval');

        $reply = M('feedbackReply');
        $data = array(
            'fid' => $id,
            'content' => $message,
            'createtime' => date('Y-m-d H:i:s'),
        );
        if($reply->create($data)){
            $result = $reply->add();
            if($result){
                $reply_id = $result;
            }else{
                //数据添加失败
                $this->ajaxReturn(array('status' => 0));
            }
        }else{
            //数据对象创建失败
            $this->ajaxReturn(array('status' => -1));
        }

        $wechatauth = $this->importWechatAuth();
        $data = array(
            'first' => array('value'=>'您好，您反馈的问题已被回复！', 'color'=>'#0000FF'),
            'keyword1' => array('value'=>$type, 'color'=>'#0099CC'),
            'keyword2' => array('value'=>date("Y-m-d H:i:s"), 'color'=>'#0099CC'),
            'remark' => array('value'=>'回复内容：'.$message, 'color'=>'#173177'),
        );
        $openid_admin = 'om9c7s6Ev7zXzqYJTnymC8cHgJPQ';
        $url = C('SYSTEME_DOMAIN').U('Feedback/result',array('id' => $id,'rid' => $reply_id));
        $ret = $wechatauth->sendTemplateMessage($data,$openid_admin,'ji7xFnAaoyEkGuvmhbVmedSJ9w90g8G7aG5796n50TE',$url);
        $ret = json_decode($ret,true);

        sleep(1);

        if($ret['errcode'] == 0){
            //回复成功则更新字段为1
            $reply->where(array('id' => $reply_id))->save(array('status' => 1));
            M('feedback')->where(array('id' => $id))->save(array('status' => 1));
            $this->ajaxReturn(array('status' => 1));

        }else{
            $this->ajaxReturn(array('status' => $ret['errcode']));
        }

    }

    /**
     * 用户点集查看详情页
     */
    public function result(){
        $id = I('get.id','','intval');
        $rid = I('get.rid','','intval');
        $reply = M('feedbackReply');
        $feedback = M('feedback');

        $this->reply_content = $reply->where(array('id' => $rid))->find();
        $this->feedback_content = $feedback->where(array('id' => $id))->find();

        $this->display();
    }

    /**
     * @return WechatAuth
     * 实例化WechatAuth类
     */
    private function importWechatAuth(){
        $appid = C('WEIXIN.APPID'); //AppID
        $secret = C('WEIXIN.SECRET');
        $wechatauth = new WechatAuth($appid,$secret);
        return $wechatauth;
    }


}