<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-10-16
 * Time: 下午1:16
 */

namespace Home\Controller;

use Think\Controller;
use Lib\Wechat\WechatAuth;
use Lib\Jiaowu\Grade;
use Lib\Jiaowu\Schedule;
use Lib\Jiaowu\RankExam;
use Lib\Jiaowu\MakeupExam;
use Lib\Jiaowu\Exam;
use Lib\Jiaowu\UserInfo;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\GetApiData;

class QueueController extends Controller
{
    //微信对象
    private $wechatAuth;

    /**
     * @throws \Exception
     * 自动/手动更新成绩的队列执行地址
     */
    public function updateGradeQueue(){
        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');
//        $uid = I('get.uid','','intval');
        $openid = I('get.openid');

        //此处修改为从ECS调用接口获取数据 ---2016/01/14
        $api_url = getApiUrl( $jwid );
        $token = C( 'ECS_API_TOKEN' );
        $api = new GetApiData( $jwid,$jwpwd,$openid,$api_url,$token );
//        //成绩
        $api->grade();

//        $ret = Grade::grade($jwid,$jwpwd);
    }
    /**
     * @throws \Exception
     * 自动/手动更新考试安排的队列执行地址
     */
    public function updateExamQueue(){
        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');
        $uid = I('get.uid','','intval');
        $openid = I('get.openid');

        //此处修改为从ECS调用接口获取数据 ---2016/01/14
        $api_url = getApiUrl( $jwid );
        $token = C( 'ECS_API_TOKEN' );
        $api = new GetApiData( $jwid,$jwpwd,$openid,$api_url,$token );
        $api->exam();
//
//
//        $ret = Exam::exam($jwid,$jwpwd);
//        if($ret){
////            $this->wechatAuth();
////            $this->wechatAuth->sendText($openid,"您的考试信息已经成功更新,快去查询吧...");
//            //清除SQL查询缓存
//            clear_sql_cache("exam_cache_" . $uid);
//        }
    }


    /**
     * @throws \Exception
     * 绑定时的数据获取队列执行地址...
     */
    public function getJiaowuDataQueue(){
        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');
        $openid = I('get.openid');

        //此处修改为从ECS调用接口获取数据 ---2016/01/14
        $api_url = getApiUrl( $jwid );
        $token = C( 'ECS_API_TOKEN' );

        $api = new GetApiData( $jwid,$jwpwd,$openid,$api_url,$token );

        //获取成绩
        $api->grade();
        //课表
        $api->schedule();
        //考试
        $api->exam();
        //补考
        $api->makeupExam();
        //用户信息
        $api->userInfo();
        //照片
        $api->userImg();
        //等级考试
        $api->rankExam();

        /*下面为采用SAE本地抓取的方式*/
       /* $ret1 = Schedule::schedule($jwid,$jwpwd);
        $ret5 = Exam::exam($jwid,$jwpwd);
        MakeupExam::makeupExam($jwid,$jwpwd);
        RankExam::rankExam($jwid,$jwpwd);
        UserInfo::userInfo($jwid,$jwpwd);
        Grade::grade($jwid,$jwpwd);
        if($ret1 && $ret5){
            $this->wechatAuth();
            $this->wechatAuth->sendText($openid,"小助手已经成功获取到您的教务数据了...快去查查看！\n <a href='http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd'>打赏小助手</a>");
        }*/
    }

    /**
     * @throws \Exception
     * 采用SAE本地抓取的方式
     */
    public function getJiaowuDataBySaeQueue(){
        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');
//        $openid = I('get.openid');

        /*下面为采用SAE本地抓取的方式*/
        Schedule::schedule($jwid,$jwpwd);
        MakeupExam::makeupExam($jwid,$jwpwd);
        RankExam::rankExam($jwid,$jwpwd);
        UserInfo::userInfo($jwid,$jwpwd);
        Grade::grade($jwid,$jwpwd);
        Exam::exam($jwid,$jwpwd);
    }

    public function getScheduleBySaeQueue(){

        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');

        Schedule::schedule($jwid,$jwpwd);
    }

    public function getScheduleByApiQueue(){

        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');
        $openid = I('get.openid');

        //此处修改为从ECS调用接口获取数据 ---2016/01/14
        $api_url = getApiUrl( $jwid );
        $token = C( 'ECS_API_TOKEN' );

        $api = new GetApiData( $jwid,$jwpwd,$openid,$api_url,$token );

        //课表
        $api->schedule();
    }

    /**
     * @throws \Exception
     * 获取教务个人资料
     */
    public function getUserInfoQueue(){
        $jwid = I('get.jwid');
        $jwpwd = I('get.jwpwd','','urldecode');
        $openid = I('get.openid');

        //此处修改为从ECS调用接口获取数据 ---2016/01/14
//        $api_url = getApiUrl( $jwid );
//        $token = C( 'ECS_API_TOKEN' );

//        $api = new GetApiData( $jwid,$jwpwd,$openid,$api_url,$token );
        //用户信息
//        $api->userInfo();
        //照片
//        $api->userImg();


        UserInfo::userInfo($jwid,$jwpwd);
    }

    /**
     * 获取用户的微信个人资料
     */
    public function getUserInfo(){
        $user = M('user');
        $map['id'] = array('gt',470);
        $map['is_bind'] = 1;
        $openid = $user->where($map)->field('openid')->select();
        foreach($openid as $v){
            $this->wechatAuth();
            $userinfo = $this->wechatAuth->getUserBaseInfo($v['openid']);
            $userinfo = json_decode($userinfo,true);
            $uid = $user->where(array('openid' => $v['openid']))->getField('id');
            $userinfo['id'] = $uid;
            $user->data($userinfo)->save();
        }
    }

    public function checkAllPassword(){

        $times = counter_get('checkpass');

        $offset = 32;
        $start = ( $offset * $times + 1 ) ?  ( $offset * $times + 1 ) : 1;

        echo "#start:";
        echo $start;

        $count = M( 'user' )->where( array( 'is_bind' => 1 ) )->count();

        echo "#count:";
        echo $count;

        if( $start > $count ){
            counter_set('checkpass',0);
        }

        S( 'checkpass_start',$start );
        S( 'checkpass_offset',$offset );

        $user = M('user')->where(array('is_bind' => 1))->field('jwid,jwpwd')->order( 'id' )->limit($start,$offset)->select();

        $i = 0;
        $j = 0;
        $k = 0;

        foreach ( $user as $u ) {
            $u[ 'jwpwd' ] = \Think\Crypt::decrypt( $u[ 'jwpwd' ], 'jwpwd_wechat' );

            S( 'cookie_' . $u[ 'jwid' ], null );

            $curl = new Curl( $u[ 'jwid' ], $u[ 'jwpwd' ] );
            $ret = $curl->getLogin();

            if ( $ret === false ) {
                //密码错误
                $change = array(
                    'is_bind' => 0,
                );

                if ( M( 'user' )->where( array( 'jwid' => $u[ 'jwid' ] ) )->save( $change ) ) {
                    $i++;
                } else {
                    $j++;
                }

            } else {

                $k++;

            }
        }

        echo "#quxiao:";
        echo $i;

        echo "#shibai:";
        echo $j;

        echo "#right:";
        echo $k;

        counter_incr('checkpass');
    }

    public function sendTemplateMessage(){
        $template = array(
            'first'=>array('value'=>'您好，小助手升级啦！', 'color'=>'#0A0A0A'),
            'keyword1'=>array('value'=>'罗宁', 'color'=>'#0E90D2'),
            'keyword2'=>array('value'=>"小助手升级了整个系统，系统运行可靠性更高，对学校教务系统依赖性降低，且使用技术手段大幅度减轻教务网压力。", 'color'=>'#0E90D2'),
            'remark'=>array('value'=>"最重要的是，您的教务账户更加安全了，教务密码采用对称加密技术，加密解密过程唯一且不可破，就算数据库泄露，您的教务密码也不会泄露，所以大家可以安心使用啦～\n【Bug修复】：修复了回复更新成绩后依然是旧数据的问题。\n\n点击看点小文章～", 'color'=>'#0E90D2'),
        );

        $appid = C('WEIXIN.APPID'); //AppID
        $secret = C('WEIXIN.SECRET');
        $wechatauth = new WechatAuth($appid,$secret);
        $template_url = 'http://a.chinacentos.com/u?mu=b29SeVNzeVJURHM5azM2Y0Fpa3dKX2ZNa29zUQ==';
        $template_id = 'KvHLQtbF0nNSFVi3GuqwiP4SbbM1mSLHgzRGyfsvSno';

        $user = M('user');
        $map['id'] = array('lt',115);
        $map['id'] = array('gt',113);
//        $map['is_bind'] = 1;
        $openid = $user->where($map)->field('openid')->select();
        foreach($openid as $v){
            $ret .= $wechatauth->sendTemplateMessage($template,$v['openid'],$template_id,$template_url);
        }
        p($ret);
    }
    private function wechatAuth(){

        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $wechatAuth = new WechatAuth($appid,$secret);

        $this->wechatAuth = $wechatAuth;
    }

    public function weisuo(){

        $snoopy = new \Lib\Tool\Snoopy();
        $snoopy->agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; TheWorld)";

        for($i = 0;$i < 100;$i++){

            $str = randCode(6,1);

            $url = 'http://www.rooroor.com/bangding.php?openid=' . $str;

            $snoopy->referer = $url;

            $postdata['xuehao'] = "201101625";
            $postdata['mima'] = "zww113113";
            $postdata['openid'] = "1'";
            $postdata['bangding'] = '%E7%BB%91%E5%AE%9A';


            $snoopy->submittext( $url,$postdata );
//            echo $snoopy->results;
        }


    }


}