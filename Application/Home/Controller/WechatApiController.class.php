<?php

namespace Home\Controller;

use Think\Controller;
use Lib\Wechat\Wechat;
use Lib\Wechat\WechatAuth;
use Home\Controller\WechatHandle;

/**
 * Class IndexController
 * @package Home\Controller
 */
class WechatApiController extends WechatHandleController{
    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function index(){
        //调试
        try{
            $appid = C('WEIXIN.APPID'); //AppID(应用ID)
            $token = C('WEIXIN.TOKEN'); //微信后台填写的TOKEN
            $secret = C('WEIXIN.SECRET');
            $crypt = C('WEIXIN.AESKEY'); //消息加密KEY（EncodingAESKey）

            /* 加载微信SDK */
            $wechat = new Wechat($token, $appid, $crypt);
            $wechatauth = new WechatAuth($appid,$secret);

            /* 获取请求信息 */
            $data = $wechat->request();

            if($data && is_array($data)){
                /**
                 * 你可以在这里分析数据，决定要返回给用户什么样的信息
                 * 接受到的信息类型有10种，分别使用下面10个常量标识
                 * Wechat::MSG_TYPE_TEXT       //文本消息
                 * Wechat::MSG_TYPE_IMAGE      //图片消息
                 * Wechat::MSG_TYPE_VOICE      //音频消息
                 * Wechat::MSG_TYPE_VIDEO      //视频消息
                 * Wechat::MSG_TYPE_SHORTVIDEO //视频消息
                 * Wechat::MSG_TYPE_MUSIC      //音乐消息
                 * Wechat::MSG_TYPE_NEWS       //图文消息（推送过来的应该不存在这种类型，但是可以给用户回复该类型消息）
                 * Wechat::MSG_TYPE_LOCATION   //位置消息
                 * Wechat::MSG_TYPE_LINK       //连接消息
                 * Wechat::MSG_TYPE_EVENT      //事件消息
                 *
                 * 事件消息又分为下面五种
                 * Wechat::MSG_EVENT_SUBSCRIBE    //订阅
                 * Wechat::MSG_EVENT_UNSUBSCRIBE  //取消订阅
                 * Wechat::MSG_EVENT_SCAN         //二维码扫描
                 * Wechat::MSG_EVENT_LOCATION     //报告位置
                 * Wechat::MSG_EVENT_CLICK        //菜单点击
                 */

                /* 响应当前请求(自动回复) */
//                $wechat->response($content, $type);


                /**
                 * 响应当前请求还有以下方法可以使用
                 * 具体参数格式说明请参考文档
                 *
                 * $wechat->replyText($text); //回复文本消息
                 * $wechat->replyImage($media_id); //回复图片消息
                 * $wechat->replyVoice($media_id); //回复音频消息
                 * $wechat->replyVideo($media_id, $title, $discription); //回复视频消息
                 * $wechat->replyMusic($title, $discription, $musicurl, $hqmusicurl, $thumb_media_id); //回复音乐消息
                 * $wechat->replyNews($news, $news1, $news2, $news3); //回复多条图文消息
                 * $wechat->replyNewsOnce($title, $discription, $url, $picurl); //回复单条图文消息
                 *
                 */

                //执行
                $this->run($wechat, $wechatauth,$data);
            }
        } catch(\Exception $e){
            F('wechat_handle_failed', json_encode($e->getMessage()));
        }

    }

    /**
     * @param  Object $wechat Wechat对象
     * @param Object $wechatauth
     * @param  array  $data   接受到微信推送的消息
     */
    private function run($wechat,$wechatauth,$data){

        switch ($data['MsgType']) {
            case Wechat::MSG_TYPE_EVENT:
//                $this->lastActiveTime($data['FromUserName']);
                switch ($data['Event']) {
                    case Wechat::MSG_EVENT_SUBSCRIBE:
                        $userinfo = $wechatauth->getUserBaseInfo($data['FromUserName']);
                        $userinfo = json_decode($userinfo,true);
                        $this->subcribeHandle($data,$userinfo);
                        $wechat->replyText(S('subscribe_welcome'));
                        break;

                    case Wechat::MSG_EVENT_UNSUBSCRIBE:
                        $this->unsubcribeHandle($data);
                        break;

                    case Wechat::MSG_EVENT_CLICK:
                        switch($data['EventKey']){
                            case '课表查询':
                                counter_incr('schedule_click');
                                $this->schedule($wechat,$data['FromUserName'],date('w'));
                                break;
                            case '每日一句':
                                $this->dayilyEnglish($wechat);
                                break;
                            case '成绩查询':
                                counter_incr('grade_wechat');
                                $this->grade($wechat,$data['FromUserName'],0);
                                break;
                            case '全部成绩':
                                counter_incr('grade_wechat');
                                $this->grade($wechat,$data['FromUserName'],1);
                                break;
                            case '四六级':
                                $this->rankExam($wechat,$data['FromUserName']);
                                break;
                            case '考试查询':
                                counter_incr('exam_wechat');
                                $this->exam($wechat,$data['FromUserName']);
                                break;
                            case '空闲教室':
//                                $str = "请按照以下规则回复：" . PHP_EOL . PHP_EOL . "例如：【教室2+12】，表示查询2教1,2节的空闲教室。" . PHP_EOL . PHP_EOL . "以2教为例，指令如下：" . PHP_EOL . "------------------------------" . PHP_EOL . "教室2+34、教室2+56、" . PHP_EOL . "教室2+78、教室2+910、" . PHP_EOL . "教室2+上午、教室2+下午、" . PHP_EOL . "教室2+白天" . PHP_EOL . "------------------------------" . PHP_EOL . "提示：查询机房请回复【教室10+12】等。" . PHP_EOL . "以上指令均不包含括号，且查询结果均为当日～";
                                $str = '当前此功能不可用~';
                                $wechat->replyText($str);
                                break;
                            case '更新考试':
                                counter_incr('update_exam');
                                $this->updateExamByUser($wechat,$data['FromUserName']);
                                break;
                            case '红包':
                                counter_incr('redpacket');
                                $this->redPackage($wechat);
                                break;
                            default:
                                $wechat->replyText("回复【补考】、【全部成绩】、【课表3】等指令试试！\n回复【更新成绩】可加入更新队列...");
                                break;
                        }
                        break;
                    default:
                        $wechat->replyText($userinfo['nickname']."的事件类型：{$data['Event']}，EventKey：{$data['EventKey']}");
                        break;
                }
                break;

            case Wechat::MSG_TYPE_TEXT:

//                $this->lastActiveTime($data['FromUserName']);

                switch ( trim( $data['Content'] ) ) {
                    case '抢红包':
                        counter_incr('redpacket');
                        $this->redPackage($wechat);
                        break;
                    case '取消绑定':
                    case '解除绑定':
                        $this->bindOut($wechat,$data['FromUserName']);
                        break;
                    case '绑定':
                    case '重新绑定':
                        $ret = $this->bindNotice($wechat,$data['FromUserName']);
                        if(false === $ret)
                            $wechat->replyText('您已经绑定,若要解除,请回复【取消绑定】');
                        break;
                    case '成绩':
                    case '成绩查询':
                    case '查成绩':
                        counter_incr('grade_wechat');
                        $this->grade($wechat,$data['FromUserName'],0);
                        break;
                    case '全部成绩':
                    case '所有成绩':
                    case '历年成绩':
                        counter_incr('grade_wechat');
                        $this->grade($wechat,$data['FromUserName'],1);
                        break;
                    case '更新成绩':
                    case '成绩更新':
                        counter_incr('update_grade');
                        $this->updateGradeByUser($wechat,$data['FromUserName']);
                        break;
                    case '更新考试':
                    case '考试更新':
                        counter_incr('update_exam');
                        $this->updateExamByUser($wechat,$data['FromUserName']);
                        break;
                    case '更新课表':
                    case '课表更新':
                        counter_incr('update_schedule');
                        $this->updateScheduleByUser($wechat,$data['FromUserName']);
                        break;
                    case '等级考试':
                    case '四六级':
                    case '四级':
                    case '六级':
                    case '六级查询':
                    case '四级查询':
                    case '四六级查询':
                        $this->rankExam($wechat,$data['FromUserName']);
                        break;
                    case '补考':
                    case '补考查询':
                        $this->makeupExam($wechat,$data['FromUserName']);
                        break;
                    case '考试':
                    case '考试查询':
                    case '考试安排':
                        counter_incr('exam_wechat');
                        $this->exam($wechat,$data['FromUserName']);
                        break;
                    case '每日一句':
                        $this->dayilyEnglish($wechat);
                        break;
//                    case '图文':
//                        $wechat->replyNewsOnce(
//                            "全民创业蒙的就是你，来一盆冷水吧！",
//                            "全民创业已经如火如荼，然而创业是一个非常自我的过程，它是一种生活方式的选择。从外部的推动有助于提高创业的存活率，但是未必能够提高创新的成功率。第一次创业的人，至少90%以上都会以失败而告终。创业成功者大部分年龄在30岁到38岁之间，而且创业成功最高的概率是第三次创业。",
//                            "http://www.luoning.me",
//                            "http://yun.topthink.com/Uploads/Editor/2015-07-30/55b991cad4c48.jpg"
//                        ); //回复单条图文消息
//                        break;

                    default:
                        $str_trans = mb_substr($data['Content'],0,2,"UTF-8");
                        $word = mb_substr($data['Content'], 2, 202, "UTF-8");

                        if($str_trans == '翻译' && !empty($word)){
                            //翻译
                            $this->youdaoTranslate($wechat,$word);
                        }elseif($str_trans == '教室' && !empty($word)){
                            counter_incr('free_classroom');
                            //空闲教室查询
                            $this->freeClassroom($wechat,$word);
                        }elseif(preg_match('/^课表[1-7]?$/',$data['Content'])){
                            counter_incr('schedule_text');
                            //课表查询
                            if($word == ''){
                                $word = date('w');
                            }
                            $date = intval($word);
                            $this->schedule($wechat,$data['FromUserName'],$date);
                        }else{
                            //图灵机器人
                            $this->tulingRobot($wechat,$data['Content'],$data['FromUserName']);
                        }
                        break;
                }
                break;

            default:
//                $this->lastActiveTime($data['FromUserName']);
                break;
        }
    }



    /**
     * @param $data
     * @param $userinfo
     * 关注时逻辑处理,存储用户资料，设置关注状态,
     */
    private function subcribeHandle($data,$userinfo){

        if($data['Event'] == 'subscribe'){
            $openid = $data['FromUserName'];

            $user = M('user');
            $uid = $user->where(array('openid' => $openid))->getField('id');
            $userinfo['is_subscribe'] = 1;
//            $userinfo['last_active_time'] = time();

            if($uid){
                $userinfo['id'] = $uid;
                $user->data($userinfo)->save();
            }else{
                $user->data($userinfo)->add();
            }
        }
    }

    /**
     * @param $data
     * 当用户取消关注的时候，改变用户表状态
     */
    private function unsubcribeHandle($data){
        if($data['Event'] == 'unsubscribe'){
            $openid = $data['FromUserName'];
            $user = M('user');
            $userdata = array(
                'is_subscribe' => 0,
//                'last_active_time' => time(),
            );
            $user->data($userdata)->where(array('openid' => $openid))->save();
        }
    }

    /**
     * @param $openid
     * 改变最后互动时间
     */
    private function lastActiveTime($openid){
        $user = M('user');
        $last_active = array(
            'last_active_time' => time(),
        );
        $uid = $user->where(array('openid' => $openid))->getField('id');
        if($uid){
            $last_active['id'] = $uid;
            $user->data($last_active)->save();
        }else{
            $user->data($last_active)->add();
        }
    }


}
