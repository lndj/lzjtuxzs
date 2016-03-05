<?php
/**
 * 测试微信类的接口
 */

namespace Home\Controller;

use Think\Controller;
use Lib\Wechat\WechatAuth;
use Lib\Wechat\JSSDK;
use Lib\Jiaowu\Curl;

class TestController extends Controller{
    /**
     *  微信授权登录起始操作
     */
    public function index(){
        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $r = new WechatAuth($appid,$secret);
        $ret = $r->getAccessToken();
//        var_dump($ret);
//        echo time();
//        S('access_token',null);
//        die;
        //网页授权第一步获取code
        $result = $r->getRequestCodeURL('http://wechat.lzjtuhand.com/index.php/Home/Test/show','test','snsapi_base');
        redirect($result);
        dump($result);
    }

    /**
     * 微信授权登录页面
     */
    public function show(){

        //微信授权认证后会自动跳转至此方法，GET获取code或者state
        $code = I('get.code');
        dump($code);




        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $r = new WechatAuth($appid,$secret);
        $openid = session('openid');


        $ret = $r->getAccessTokenAndOpenId($code);
        p($ret);

        $userinfo = $r->getUserInfo($ret['access_token'],$ret['openid']);
        p($userinfo);



//        var_dump($openid);
//        session('openid',null);
        die;
        $User = M('user');
        $data = array();
        if($openid){
            $userdata = $User->where(array('openid' => $openid))->find();
            $this->userdata = $userdata;
        }else{
            //获取accessToken 和openid
            $ret = $r->getAccessTokenAndOpenId($code);
            //将获取到的openid写入sesion
            session('openid',$ret['openid']);
            //获取用户信息
            $userinfo = $r->getUserInfo($ret['access_token'],$ret['openid']);
//            p($userinfo);
            $this->userdata = $userinfo;

            $userdata = $User->where(array('openid' => $ret['openid']))->find();
            $data = array(
                'openid' => $userinfo['openid'],
                'nickname' => $userinfo['nickname'],
                'sex' => $userinfo['sex'],
                'city' => $userinfo['city'],
                'province' => $userinfo['province'],
                'headimgurl' => $userinfo['headimgurl'],
                'status' => 1,
            );
            if(!$userdata){
                $User->data($data)->add();
            }else{
                $data['id'] = $userdata['id'];
                $User->data($data)->save();
            }
        }
        //分享时候采用JSSDK
        $jssdk = new JSSDK($appid,$secret);
        $this->signPackage = $jssdk->getSignPackage();

        //读取数据库配置
        $Config = M('config');
        $this->defaultHeadurl = $Config->where(array('item' => 'DEFAULT_HEADIMGURL'))->find();
        $this->defaultHeadBackground = $Config->where(array('item' => 'DEFAULT_HEADBACKGROUNG'))->order('rand()')->find();
        $this->display();
    }

    public function bind(){
        $appid = C('WEIXIN.APPID'); //AppID
        $secret = C('WEIXIN.SECRET');
        $jssdk = new JSSDK($appid,$secret);
        $this->signPackage = $jssdk->getSignPackage();

        $this->openid = I('get.openid');
        $this->display();

    }

    public function handle(){
        $jwid = I('post.jwid');
        $jwpwd = I('post.jwpwd','','urldecode'); //此处记得urldecode
        $openid = I('post.openid');

        $user = M('user');
        $userifo = $user->where(array('openid' => $openid))->field('id,is_bind')->find();
        if(intval($userifo['is_bind']) === 1){
            $this->ajaxReturn(array('status' => 0));
        }else{
            //验证学号密码是否正确
            $curl = new Curl($jwid,$jwpwd);
            $ret = $curl->getLogin();
            if($ret === true){
                //请求成绩数据
                $this-> getJiaowuData($jwid,$jwpwd,$openid);

                //清除SQL查询缓存
                clear_sql_cache('grade_cache_' . $userifo['id'] . '_1');
                clear_sql_cache('grade_cache_' . $userifo['id'] . '_0');


                $data = array(
                    'jwid' => $jwid,
                    //对称加密密码
                    'jwpwd' => \Think\Crypt::encrypt($jwpwd,'jwpwd_wechat'),
                    'is_bind' => 1,
                    'bind_time' => time(),
                );

                if($userifo['id']){
                    $data['id'] = $userifo['id'];
                    if( $user->data($data)->save() ){
                        sleep(1);
                        $this->ajaxReturn(array('status' => 1));
                    }else{
                        //数据库操作错误
                        $this->ajaxReturn(array('status' => -1));
                    }
                }else{
                    $data['openid'] = $openid;
                    if($user->data($data)->add()){
                        sleep(1);
                        $this->ajaxReturn(array('status' => 1));
                    }else{
                        $this->ajaxReturn(array('status' => -1));
                    }
                }

            }
            //密码错误
            $this->ajaxReturn(array('status' => -2));
        }

    }

    public function getJiaowuData($jwid,$jwpwd,$openid){

        $queue = new \SaeTaskQueue('getJiaowuData');

        $tasks = array();
        $tasks[] = array(
            'url' => U('Home/Queue/getQueue',array('jwid' => $jwid,'jwpwd' => $jwpwd,'openid' => $openid )),
            'postdata' => "",
            'prior' => false,
            'options' => array(),
        );
        $queue->addTask($tasks);
        $ret = $queue->push();
        if ($ret === false)
            throw new \Exception($queue->errno().$queue->errmsg());
    }

    public function html(){
        $this->id = 1;
        $this->user_sex_icon = 'mars';

        $this->uid = 1;

        $appid = C('WEIXIN.APPID'); //AppID
        $secret = C('WEIXIN.SECRET');
        $jssdk = new JSSDK($appid,$secret);
        $this->signPackage = $jssdk->getSignPackage();

        $this->display();

    }
    public function publish(){

        $name = I('post.name_new');
        $content = I('post.content_new');
        $uid = I('post.uid');
        //图片上传至微信服务器后获取到的serverId.使用微信多媒体接口下载图片到自己的服务器
        $server_id = I('post.server_id');

        //插入数据库的id
        $id = 1;
        sleep(10);
        $this->ajaxReturn( array( 'status' => 1,'id' => $id ) );

    }

    /**
     * 回复
     */
    public function reply(){
        sleep(1);
        $this->ajaxReturn( array( 'status' => 1 ) );

    }

    /**
     * 点赞处理
     */
    public function praise(){
        $id = I('post.');

        $this->ajaxReturn( array( 'status' => 1 ) );
    }

    /**
     * 点踩处理
     */
    public function trample(){

        $this->ajaxReturn( array( 'status' => 1 ) );
    }



    public function jq(){
        $this->display();
    }



}