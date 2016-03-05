<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-18
 * Time: 下午12:44
 */

namespace Home\Controller;
use Think\Controller;
use Lib\Wechat\JSSDK;
use Lib\Wechat\WechatAuth;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\Schedule;
use Lib\Jiaowu\RankExam;
use Lib\Jiaowu\MakeupExam;
use Lib\Jiaowu\Grade;
use Lib\Jiaowu\UserInfo;
use Lib\Jiaowu\GetApiData;

class BindController extends Controller{

    public function index(){
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
//            //验证学号密码是否正确
            $curl = new Curl($jwid,$jwpwd);
            $ret = $curl->getLogin();

            //此处修改为从ECS调用接口获取数据 ---2016/01/14
//            $api_url = getApiUrl( $jwid );
//            $token = C( 'ECS_API_TOKEN' );
//            $api = new GetApiData( $jwid,$jwpwd,$openid,$api_url,$token );
            //成绩
//            $ret = $api->checkPass();
            if($ret === true){
                $data = array(
                    'jwid' => $jwid,
                    //对称加密密码
                    'jwpwd' => \Think\Crypt::encrypt($jwpwd,'jwpwd_wechat'),
                    'is_bind' => 1,
                    'bind_time' => time(),
                );

                if($userifo['id']){
                    $data['id'] = $userifo['id'];
                    if($user->data($data)->save()){

                        //请求成绩数据
                        $this-> getJiaowuData($jwid,$jwpwd,$openid);

                        //给点时间获取数据...
                        sleep(3);


                        $this->ajaxReturn(array('status' => 1));
                    }else{
                        //数据库操作错误
                        $this->ajaxReturn(array('status' => -1));
                    }
                }else{
                    $data['openid'] = $openid;
                    if($user->data($data)->add()){

                        //请求成绩数据
                        $this-> getJiaowuData($jwid,$jwpwd,$openid);

                        sleep(3);

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

        $result = $jwid % 2;
        if( $result == 0 ){
            $queue = new \SaeTaskQueue('getJiaowuDataBackup');
        }else{
            $queue = new \SaeTaskQueue('getJiaowuData');
        }
        $tasks = array();
        $tasks[] = array(
            'url' => U('Home/Queue/getJiaowuDataBySaeQueue',array('jwid' => $jwid,'jwpwd' => urlencode($jwpwd),'openid' => $openid )),
            'postdata' => "",
            'prior' => false,
            'options' => array(),
        );
        $queue->addTask($tasks);
        $ret = $queue->push();
        if ($ret === false)
            throw new \Exception($queue->errno().$queue->errmsg());
    }


}