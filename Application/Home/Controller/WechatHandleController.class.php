<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-17
 * Time: 下午8:25
 */

namespace Home\Controller;

use Think\Controller;

use Lib\Wechat\WechatAuth;
use Lib\Jiaowu\Curl;

/**
 * Class IndexController
 * @package Home\Controller
 * WechatApi父类，处理教务数据获取等逻辑
 */
class WechatHandleController extends Controller{
    /**
     * @param Object $wechat
     * @param  $openid
     * @param int $isAll //是否查询全部成绩
     * @return array
     * @throws \Exception
     * 成绩查询
     */
    protected function grade($wechat,$openid,$isAll = 0){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $grade = M('grade');
            $uid = get_uid_by_openid($openid);

//            $this->updateGradeAuto($uid);

            $map = array(
                'uid' => $uid,
            );
            //网页版查询地址
            $grade_web_url = C('SYSTEME_DOMAIN') . U( 'WebQuery/grade',array( 'school_year' => 0,'term' => 0,'uid' => $uid ) );

            if($isAll === 0){
                $map['school_year'] = getS('school_year_now');
                $map['term'] = getS('term_now');

                $grade_web_url = C('SYSTEME_DOMAIN') . U( 'WebQuery/grade',array( 'school_year' => $map['school_year'],'term' => $map['term'],'uid' => $uid ) );
            }
            $grade_data = $grade->where($map)->field('course,course_attr,credits,grade_point,grade,makeup_grade,createtime')->cache('grade_cache_' . $uid . '_' . $isAll,3600)->select();

            $send_data = '';

            foreach($grade_data as $gd){
                if($gd['makeup_grade'] == ''){
                    $makeup = '';
                }else{
                    $makeup = '-['.$gd['makeup_grade'].']';
                }
                $send_data .= $gd['course'].'-'.$gd['credits'].'-'.$gd['grade_point'].'-'.$gd['grade'].$makeup."\n";
            }

            $user_info = $this->userInfo($uid);

            //消息首部标题
            $_title = getS('school_year_now').'学年'.getS('term_now').'学期成绩信息';
            if($isAll === 1)
                $_title = '全部成绩信息';

            //数据更新时间
            if($grade_data[0]['createtime'])
                $update_time = "数据更新时间：" . date('Y-m-d H:i:s',$grade_data[0]['createtime']);
            else
                $update_time = "";

            $title = array(
                $_title,
                '',
                '',
                'http://cdn.iciba.com/news/word/big_2015-12-19b.jpg?rand=2983'
            );

            $grade_content = array(
                "姓名：{$user_info['realname']} | 学号：{$user_info['jwid']}" . PHP_EOL . "专业：{$user_info['profession']}" . PHP_EOL . "班级：{$user_info['class']}" . PHP_EOL . PHP_EOL . "课程-学分-绩点-成绩-[补考]" . PHP_EOL . $send_data."---------------------------------" . PHP_EOL . $update_time . PHP_EOL . '回复【更新成绩】可更新成绩数据，回复【全部成绩】查看历年成绩。',
                '',
                '',
                ''
            );

            $web_grade = array(
                '进入Web版，查询详细信息',
                '',
                $grade_web_url,
                'https://mmbiz.qlogo.cn/mmbiz/ibFNSSrIsf7M0sv47rqBNqQo2ia57UBmje9B6BvbDTe8ZJRibPKsQKjrNLfxWeVQ1Gz4370BKwMPU9ib6WAUwuiaqeg/0?wx_fmt=png'
            );
            //广告位优化----2015/11/27 22:39
            $ad = ad_set('grade_copy','grade_url');
            if( $ad === false ){
                $wechat->replyNews($title,$grade_content,$web_grade);
                exit;
            }
            $wechat->replyNews($title,$grade_content,$web_grade,$ad);
        }
    }

    /**
     * @param object $wechat
     * @param $openid
     * @return array
     * 等级考试查询
     */
    protected function rankExam($wechat,$openid){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $re = M('rankExam');
            $uid = get_uid_by_openid($openid);

            $map = array(
                'uid' => $uid,
            );

            $rank_exam = '';
            $rank_exam_data = $re->where($map)->cache('rankexam_cache_' . $uid,86400)->select();
            foreach($rank_exam_data as $v){
                $rank_exam .= "考试名称: {$v['exam']}" . PHP_EOL . "准考证号: {$v['exam_num']}" . PHP_EOL . "考试日期: {$v['exam_date']}" . PHP_EOL . "考试成绩: {$v['grade']}" . PHP_EOL . "听力成绩: {$v['listen_grade']}" . PHP_EOL . "阅读成绩: {$v['read_grade']}" . PHP_EOL . "写作成绩: {$v['write_grade']}" . PHP_EOL . "综合成绩: {$v['compre_grade']}" . PHP_EOL . "-----------------" . PHP_EOL;
            }

            $data_time = "数据更新时间: ".date('Y-m-d H:i:s',$rank_exam_data[0]['createtime']);
            if($rank_exam == ''){
                $rank_exam = "亲，教务系统没有您的等级成绩！";
                $data_time = '';
            }
            $rank_exam .= "如果没有查询到最新成绩,请【点击】进行网页版查询。" . PHP_EOL . $data_time;

            $user_info = $this->userInfo($uid);

            $title = array(
                '等级考试查询',
                '',
                '',
                'http://i2.tietuku.com/38c46a55794e56eat.jpg',
            );
            $ranke_exam_info = array(
                "姓名：{$user_info['realname']} | 学号：{$user_info['jwid']}\n\n{$rank_exam}",
                "",
                "http://aunt.sinaapp.com/app/cet/index2.php",
                ""
            );

            //广告位优化----2015/11/27 22:39
            $ad = ad_set('rank_exam_copy','rank_exam_url');
            if( $ad === false ){
                $wechat->replyNews($title,$ranke_exam_info);
                exit;
            }
            $wechat->replyNews($title,$ranke_exam_info,$ad);
        }
    }

    /**
     * @param $wechat
     * @param $openid
     * @return array
     * 补考安排查询
     */
    protected function makeupExam($wechat,$openid){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $me = M('makeupExam');
            $uid = get_uid_by_openid($openid);

            $map = array(
                'uid' => $uid,
            );
            $makeup_exam = '';
            $makeup_exam_data = $me->where($map)->cache('makeup_cache_'.$uid,86400)->select();
            foreach($makeup_exam_data as $v){
                $makeup_exam .= "学年: {$v['school_year']}\n学期: {$v['term']}\n课程: {$v['course']}\n时间: {$v['exam_date']}\n地点: {$v['exam_address']}\n座位: {$v['position_num']}\n形式: {$v['exam_type']}\n------------------------\n";
            }

            $data_time = "数据更新时间: ".date('Y-m-d H:i:s',$makeup_exam_data[0]['createtime']);
            if($makeup_exam == ''){
                $makeup_exam = "你是个好孩子，没有补考！";
                $data_time = '';
            }
            $makeup_exam .= $data_time;

            $user_info = $this->userInfo($uid);

            $title = array(
                '补考查询',
                '',
                '',
                'http://i2.tietuku.com/38c46a55794e56eat.jpg',
            );
            $makeup_exam_info = array(
                "姓名：{$user_info['realname']} | 学号：{$user_info['jwid']}" . PHP_EOL . PHP_EOL . "{$makeup_exam}",
                '',
                '',
                '',
            );
            //广告位优化----2015/11/27 22:55
            $ad = ad_set('makeup_exam_copy','makeup_exam_url');
            if( $ad === false ){
                $wechat->replyNews($title,$makeup_exam_info);
                exit;
            }
            $wechat->replyNews($title,$makeup_exam_info,$ad);
        }
    }

    /**
     * @param $wechat
     * @param $openid
     * @return array
     * 考试安排
     */
    protected function exam($wechat,$openid){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $ex = M('exam');
            $uid = get_uid_by_openid($openid);

            if( $uid === false ){
                $wechat->replyText('系统出现一点故障，您可以联系微信号 lndj512823 帮您排查！');
                exit;
            }

            $map = array(
                'uid' => $uid,
            );
            //
            $exam = '';
            $exam_data = $ex->where($map)->cache('exam_cache_' . $uid,3600)->select();
            foreach($exam_data as $v){
                $exam .= "课程: {$v['course']}\n时间: {$v['exam_date']}\n地点: {$v['exam_address']}\n座位: {$v['position_num']}\n形式: {$v['exam_type']}\n------------------------\n";
            }
            //时间取最后一个的
            $size = count($exam_data);
            //最后的一个的数组索引
            $array_index = $size - 1;
            $data_time = "数据更新时间: ".date('Y-m-d H:i:s',$exam_data[$array_index]['createtime']);
            if($exam == ''){
                $exam = "不急，暂时没有考试！\n";
                $data_time = '';
            }
            $exam .= $data_time;

            $user_info = $this->userInfo($uid);

            $title = array(
                '考试安排查询',
                '',
                '',
                'https://mmbiz.qlogo.cn/mmbiz/ibFNSSrIsf7M0sv47rqBNqQo2ia57UBmje6b7b6SgS1RpCxjMKuf87eKpZjzibnu0sZy8icHDwUbabpVsqRumbA7yA/0?wx_fmt=jpeg',
            );
            $exam_info = array(
                "姓名：{$user_info['realname']} | 学号：{$user_info['jwid']}" . PHP_EOL . PHP_EOL . $exam . PHP_EOL . '回复【更新考试】可更新当前的考试安排信息！',
                '',
                '',
                '',
            );

            //广告位优化----2015/11/27 22:55
            $ad = ad_set('exam_copy','exam_url');
            if( $ad === false ){
                $wechat->replyNews($title,$exam_info);
                exit;
            }
            $wechat->replyNews($title,$exam_info,$ad);
        }
    }

    /**
     * @param $openid
     * @param null $week
     * @return array|mixed|string
     * 课表
     */
    protected function schedule($wechat,$openid,$week = null){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $sd = M('schedule');
            $uid = get_uid_by_openid($openid);

            $map = array(
                'uid' => $uid,
            );
            if($week === null){
                $field = '*';
            }elseif($week == 1){
                $field = 'mon';
                $num = '一';
            }elseif($week == 2){
                $field = 'tues';
                $num = '二';
            }elseif($week == 3){
                $field = 'wed';
                $num = '三';
            }elseif($week == 4){
                $field = 'thur';
                $num = '四';
            }elseif($week == 5){
                $field = 'fri';
                $num = '五';
            }elseif($week == 6){
                $field = 'sat';
                $num = '六';
            }elseif($week == 0){
                $field = 'sun';
                $num = '日';
            }

            $schedule_data = $sd->where($map)->field($field)->cache('schedule_cache_' . $uid . $week,86400)->find();

            if($week === null)
                return $schedule_data;

            $schedule = '';

            foreach($schedule_data as $sdd){

                $explode = explode('#',$sdd);
                foreach($explode as $exp){
                    if($exp != '')
                        $schedule .= PHP_EOL . $exp  . PHP_EOL . "-------------------------";
                }
            }
            if($schedule == '')
                $schedule = PHP_EOL . "没课，去嗨皮吧！" . PHP_EOL . "-------------------------";
            $user_info = $this->userInfo($uid);

            $title = array(
                '课表查询',
                '',
                '',
                'https://mmbiz.qlogo.cn/mmbiz/ibFNSSrIsf7M0sv47rqBNqQo2ia57UBmjeBYAo9OXHsUg25Rqc03L1lSpf2BwRz7FOIj4z9lumoNWF6qAfIWfRibQ/0?wx_fmt=jpeg'
            );

            $schedule_info = array(
                "姓名：{$user_info['realname']} | 学号：{$user_info['jwid']}" . PHP_EOL . PHP_EOL . "您星期{$num}的课程有：" . PHP_EOL . "-------------------------" . $schedule,
                '',
                '',
                '',
            );
            //广告位优化----2015/11/27 22:55
            $ad = ad_set('schedule_copy','schedule_url');
            if( $ad === false ){
                $wechat->replyNews($title,$schedule_info);
                exit;
            }
            $wechat->replyNews($title,$schedule_info,$ad);
        }
    }

    /**
     * @param $wechat
     * @param $keyword //2+34  代表2教34节
     * 查询空闲教室
     */
    protected function freeClassroom($wechat,$keyword){

        $fc = M('freeClassroom');
        $code_arr = explode('+',$keyword);

        $code = get_time_point_code($code_arr[1]);
        if($code === false){
            $str = "指令错误！请按照以下规则回复：" . PHP_EOL . PHP_EOL . "例如：【教室2+12】，表示查询2教1,2节的空闲教室。" . PHP_EOL . PHP_EOL . "以2教为例，指令如下：" . PHP_EOL . "------------------------------" . PHP_EOL . "教室2+34、教室2+56、" . PHP_EOL . "教室2+78、教室2+910、" . PHP_EOL . "教室2+上午、教室2+下午、" . PHP_EOL . "教室2+白天" . PHP_EOL . "------------------------------" . PHP_EOL . "提示：查询机房请回复【教室10+12】等。" . PHP_EOL . "以上指令均不包含括号～";
            $wechat->replyText($str);
        }

        $map['time_point'] = $code;
        $map['classroom_type'] = array('in','1,5');
        $map['class_floor_code'] = $code_arr[0];
        $result = $fc->where($map)->field('classroom_name,position_num_class')->limit(30)->cache('freeclassroom_cache_' . $code_arr[0] . $code,86380)->select();
        $result_str = "";
        foreach($result as $v){
            $result_str .= "{$v['classroom_name']}---{$v['position_num_class']}" . PHP_EOL;
        }
        if($result_str == '')
            $result_str = "该时间段没有空闲教室！" . PHP_EOL;
        $result_str .= "---------------------------";

        $result_arr = array(
            array(
                '空闲教室查询',
                '',
                '',
                'http://i2.tietuku.com/38c46a55794e56eat.jpg',
            ),
            array(
                "今日的空闲教室有：" . PHP_EOL . "教室名称---座位数" . PHP_EOL . "---------------------------" . PHP_EOL . $result_str,
                '',
                '',
                '',
            ),
        );
        //广告位优化----2015/11/27 22:55
        $ad = ad_set('free_classroom_copy','free_classroom_url');
        if( $ad === false ){
            $wechat->replyNews($result_arr[0],$result_arr[1]);
            exit;
        }
        $wechat->replyNews($result_arr[0],$result_arr[1],$ad);
    }
    /**
     * @param $uid
     * @throws \Exception
     * 更新成绩数据
     * 应加入队列中
     */
    protected function updateGradeAuto($uid){

        $userinfo = get_user_by_uid($uid);

        //判断更新数据条件
        $config = getS('grade_update_time');

        if($config === false){
            $config = 0;
        }
        if($config !== 0){
            $gr = M('grade');
            $createtime = $gr->where(array('uid' => $uid))->getField('createtime');
            //解决null情况
            if(!$createtime){
                $createtime = 0;
            }
            $limit = time() - $createtime;
            if($limit > $config){
                //队列执行
                $queue = new \SaeTaskQueue('updateGradeAuto');
                $tasks = array();
                $tasks[] = array(
                    'url' => U('Home/Queue/updateGradeQueue',array('jwid' => $userinfo['jwid'],'jwpwd' => urlencode($userinfo['jwpwd']),'openid' => $userinfo['openid'])),
                    'postdata' => "",
                    'prior' => false,
                    'options' => array(),
                );
                $queue->addTask($tasks);
                $ret = $queue->push();
                if ($ret === false)
                    throw new \Exception($queue->errno().$queue->errmsg());
//                $curLength = $queue->curLength();
//                //返回当前队列长度
//                return $curLength;
            }
        }
    }



    /**
     * @param $uid
     * @return mixed
     * 获取用户教务网信息
     */
    protected function userInfo($uid){

        $u_i = M('userInfo');
        $userinfo_1 = $u_i->where(array('uid' => $uid))->field('realname,profession,class')->find();
        $user = M('user');
        $userinfo_2 = $user->where(array('id' => $uid))->field('jwid')->find();
        $userinfo = array_merge($userinfo_1,$userinfo_2);
        return $userinfo;
    }

    /**
     * @param object $wechat
     * @param $openid
     * @return array
     * 未绑定的用户返回提示绑定的消息
     */
    protected function bindNotice($wechat,$openid){
        $ret = $this->isBind($openid);
        if($ret === false){
            $title = array(
                '绑定教务账号',
                '',
                '',
                '',
            );
            $content = array(
                "点我即可绑定～",
                "",
                C('SYSTEME_DOMAIN').U('Bind/index',array('openid' => $openid)),
                "http://mmbiz.qpic.cn/mmbiz/ibFNSSrIsf7MLJHg7EHEusbzWWTAVFMIib8Jrs4ibnOkiaSl0Bh0lrW28WY5gGibe38eRfrgwNouKjtMu0CKvjibXZ6Q/0?wx_fmt=gif&tp=webp&wxfrom=5"
            );
            $wechat->replyNews($title,$content);
            exit;
        }else{
            return false;
        }
    }

    /**
     * @param $openid
     * @return bool
     * 检测是否绑定
     */
    protected function isBind($openid){
        $user = M('user');
        $is_bind = $user->where(array('openid' => $openid))->getField('is_bind');
        $is_bind = intval($is_bind);
        if($is_bind === 1){
            return true;
        }
        return false;
    }

    /**
     * @param $openid
     * @return bool
     * 取消绑定
     */
    protected function bindOut($wechat,$openid){
        $user = M('user');
        $userinfo = $user->where(array('openid' => $openid))->field('id,jwid')->find();

        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){

            $_data = array(
                'is_bind' => 0,
                'id' => $userinfo['id'],
            );

            $ret = $user->data($_data)->save();

           /* $appid = C('WEIXIN.APPID'); //AppID
            $secret = C('WEIXIN.SECRET');
            $wechatauth = new WechatAuth($appid,$secret);
            $template_url = C('SYSTEME_DOMAIN').U('Test/index');
            $template_id = '0DXhWbQnTApl9qvSuAZduCG7EwTphLkJunay9Uj66pI';

            if($ret){
                $template = array(
                    'first'=>array('value'=>'您好，您已成功解除绑定！', 'color'=>'#0A0A0A'),
                    'keyword1'=>array('value'=>$userinfo['jwid'], 'color'=>'#0E90D2'),
                    'keyword2'=>array('value'=>date('y-m-d H:i:s'), 'color'=>'#0E90D2'),
                    'keyword3'=>array('value'=>'回复【绑定】即可。', 'color'=>'#0E90D2'),
                    'remark'=>array('value'=>'您已经取消绑定。', 'color'=>'#173177'),
                );
            }else{
                $template = array(
                    'first'=>array('value'=>'您好，解除绑定失败！', 'color'=>'#0A0A0A'),
                    'keyword1'=>array('value'=>$userinfo['jwid'], 'color'=>'#0E90D2'),
                    'keyword2'=>array('value'=>date('y-m-d H:i:s'), 'color'=>'#0E90D2'),
                    'keyword3'=>array('value'=>'回复【绑定】即可。', 'color'=>'#0E90D2'),
                    'remark'=>array('value'=>'请您稍后再试，点击即可查看使用帮助。', 'color'=>'#173177'),
                );
            }
            $wechatauth->sendTemplateMessage($template,$openid,$template_id,$template_url);*/

            if($ret)
                $wechat->replyText('你已经取消绑定，下次查询需要重新绑定哦！');
            else
                $wechat->replyText('貌似出了点差错，请稍后再试一次！');

        }

    }

    /**
     * @param $openid 用户openid
     * 用户手动更新成绩
     */
    protected function updateGradeByUser($wechat,$openid){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $userinfo = get_user_by_openid($openid);
            //设置每小时只可请求一次
            $ret = queue_request_limit("updateGradeByUser:".$openid,3600);
            if($ret === true){
                //队列执行
                $queue = new \SaeTaskQueue('updateGradeByUser');
                $tasks = array();
                $tasks[] = array(
                    'url' => U('Home/Queue/updateGradeQueue',array('jwid' => $userinfo['jwid'],'jwpwd' => urlencode($userinfo['jwpwd']),'openid' => $openid )),
                    'postdata' => "",
                    'prior' => false,
                    'options' => array(),
                );
                $queue->addTask($tasks);
                $ret = $queue->push();
                if ($ret === false)
                    throw new \Exception($queue->errno().$queue->errmsg());
                $curLength = $queue->curLength();

                if($curLength == 0)
                    $wechat->replyText( "小助手正在为您努力更新中，请稍后...\n <a href=\"http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd\">打赏小助手</a>");

                $wechat->replyText( "成功加入到更新队列中，您的前面一共还有".$ret."位用户在排队更新，请稍等片刻...\n <a href=\"http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd\">打赏小助手</a>");
            }else{
                //没通过访问限制
                $wechat->replyText("亲耐的，小助手为了保护教务系统稳定服务，1个小时内只能手动更新一次!");
                return false;
            }
        }
    }



    /**
     * @param $openid 用户openid
     * 用户手动更新考试安排
     * @return bool
     */
    public function updateExamByUser($wechat,$openid){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $userinfo = get_user_by_openid($openid);
            //设置每小时只可请求一次
            $ret = queue_request_limit("updateExamByUser:".$openid,3600);
            if($ret === true){
                //队列执行
                $queue = new \SaeTaskQueue('updateExamByUser');
                $tasks = array();
                $tasks[] = array(
                    'url' => U('Home/Queue/updateExamQueue',array('jwid' => $userinfo['jwid'],'jwpwd' => urlencode($userinfo['jwpwd']),'openid' => $openid )),
                    'postdata' => "",
                    'prior' => false,
                    'options' => array(),
                );
                $queue->addTask($tasks);
                $ret = $queue->push();
                if ($ret === false)
                    throw new \Exception($queue->errno().$queue->errmsg());
                $curLength = $queue->curLength();
                if($curLength == 0)
                    $wechat->replyText("小助手正在为您努力更新中，请稍后... \n<a href=\"http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd\">打赏小助手</a>");

                $wechat->replyText("成功加入到更新队列中，您的前面一共还有".$ret."位用户在排队更新，请稍等片刻...\n <a href=\"http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd\">打赏小助手</a>");
            }else{
                //没通过访问限制
                $wechat->replyText("亲耐的，小助手为了保护教务系统稳定服务，1个小时内只能手动更新一次!");
                return false;
            }
        }
    }

    /**
     * @param $wechat
     * @param $openid
     * @return bool
     * @throws \Exception
     * 手动更新课表数据
     */
    public function updateScheduleByUser($wechat,$openid){
        //检测是否绑定
        $ret = $this->bindNotice($wechat,$openid);
        if($ret === false){
            $userinfo = get_user_by_openid($openid);
            //设置每小时只可请求一次
            $ret = queue_request_limit("updateScheduleByUser:".$openid,3600);
            if($ret === true){
                //队列执行
                $queue = new \SaeTaskQueue('updateExamByUser');
                $tasks = array();
                $tasks[] = array(
                    'url' => U('Home/Queue/getScheduleBySaeQueue',array('jwid' => $userinfo['jwid'],'jwpwd' => urlencode($userinfo['jwpwd']),'openid' => $openid )),
                    'postdata' => "",
                    'prior' => false,
                    'options' => array(),
                );
                $queue->addTask($tasks);
                $ret = $queue->push();
                if ($ret === false)
                    throw new \Exception($queue->errno().$queue->errmsg());
                $curLength = $queue->curLength();
                if($curLength == 0)
                    $wechat->replyText("小助手正在为您努力更新中，请稍后... \n<a href=\"http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd\">打赏小助手</a>");

                $wechat->replyText("成功加入到更新队列中，您的前面一共还有".$ret."位用户在排队更新，请稍等片刻...\n <a href=\"http://mp.weixin.qq.com/s?__biz=MjM5NTA1OTkzMg==&mid=400001922&idx=1&sn=eb25e659dce9e30e0da14a6168989ad5#rd\">打赏小助手</a>");
            }else{
                //没通过访问限制
                $wechat->replyText("亲耐的，小助手为了保护教务系统稳定服务，1个小时内只能手动更新一次!");
                return false;
            }
        }
    }


    /**
     * @param $wechat
     * @param string $date
     * 每日一句
     */
    protected function dayilyEnglish($wechat,$date = ''){
        $apiUrl = 'http://open.iciba.com/dsapi';
        $param = array(
            'date' => $date, //默认为当日,格式:2015-10-25
        );
        $result = http_get($apiUrl, $param);
        $result = json_decode($result, true);
        if($date == '')
            $date = date('Y-m-d');
        $result_arr = array(
            "每日一句 {$date}",
            $result['content']."\n\n".$result['note'],
            '',
            $result['picture2'],
        );
        $wechat->replyNews($result_arr);
    }
    /**
     * @param $wechat  wechat 对象
     * @param $keyword
     * 有道词典api
     */
    protected function youdaoTranslate($wechat,$keyword){

        $apiUrl = 'http://fanyi.youdao.com/openapi.do';
        $param = array(
            'keyfrom' => 'lzjtujwzs', //申请APIKEY 时所填表的网站名称的内容
            'key' => 1038638365,   //从有道申请的APIKEY
            'type' => 'data',
            'doctype' => 'json',
            'version' => 1.1,
            'q' => $keyword,
        );
        $result = http_get($apiUrl, $param);
        $result = json_decode($result, true);
        $result_arr = array(
            array( '有道词典翻译结果', '', '', '', ),
            array( "基本释义: {$result['translation'][0]}\n音标: /{$result['basic']['phonetic']}/\n网络释义: {$result['basic']['explains'][0]}", '', '', '', ),
        );
        if($result['errorCode'] == 0)
            $wechat->replyNews($result_arr[0],$result_arr[1]);
        else
            $wechat->replyText('无法进行有效的翻译');
    }

    /**
     * @param $wechat wenchat对象
     * @param $keyword   用户关键词
     * @param string $openid  实现上下文而有的userid
     * @return array
     * 图灵机器人api
     */
    protected function tulingRobot($wechat,$keyword,$openid = ''){
        $apiKey = "6019ba8b60b62ad10d695aed384010df";
        $apiUrl = 'http://www.tuling123.com/openapi/api';
        $result = http_get($apiUrl,array('key' => $apiKey,'info' => $keyword,'userid' => $openid));
        $result = json_decode($result,true);
        F('tuling',$result);
        switch($result['code']){
            case 302000:  //新闻消息
                $result_arr = array();
                foreach($result['list'] as $v){
                    if($v['icon'] == '')
                        $v['icon'] = 'http://mmbiz.qpic.cn/mmbiz/ibFNSSrIsf7MLJHg7EHEusbzWWTAVFMIib8Jrs4ibnOkiaSl0Bh0lrW28WY5gGibe38eRfrgwNouKjtMu0CKvjibXZ6Q/0?wx_fmt=gif&tp=webp&wxfrom=5';
                    $result_arr[] = array(
                        $v['article'],
                        "来源：".$v['source'],
                        $v['detailurl'],
                        $v['icon'],
                    );
                }
                $wechat->replyNews($result_arr[0],$result_arr[1],$result_arr[2],$result_arr[3],$result_arr[4]);
                break;
            case 305000:  //列车消息
                $result_str = '';
                $arr = $result['list'];
                $count = count($arr);
                for($i = 0;$i < $count;$i++){
                    $result_str .= ($i+1).".{$arr[$i]['trainnum']}\n{$arr[$i]['start']}---{$arr[$i]['terminal']}\n运行时间：{$arr[$i]['starttime']}---{$arr[$i]['endtime']}\n---------------------\n";
                }
                $result_arr = array(
                    array(
                        '小助手已帮您找到列车信息',
                        '',
                        '',
                        '',
                    ),
                    array(
                        $result_str."【PS】(+1)表示次日到达；更多信息请点击进入网页查询",
                        '',
                        $arr[0]['detailurl'],
                        '',
                    ),
                );
                $wechat->replyNews($result_arr[0],$result_arr[1]);
                break;
            case 308000:  //菜谱消息
                $result_arr = array();
                $arr = $result['list'];
                foreach($arr as $v){
                    if($v['icon'] == '')
                        $v['icon'] = 'http://mmbiz.qpic.cn/mmbiz/ibFNSSrIsf7MLJHg7EHEusbzWWTAVFMIib8Jrs4ibnOkiaSl0Bh0lrW28WY5gGibe38eRfrgwNouKjtMu0CKvjibXZ6Q/0?wx_fmt=gif&tp=webp&wxfrom=5';
                    $result_arr[] = array(
                        $v['name'],
                        "原料：".$v['info'],
                        $v['detailurl'],
                        $v['icon'],
                    );
                }
                $wechat->replyNews($result_arr[0],$result_arr[1],$result_arr[2],$result_arr[3],$result_arr[4]);
                break;
            case 200000:
                $wechat->replyText("小助手帮您找到了航班信息>>><a href='".$result['url']."'>点我查看</a>");
            default:
                $wechat->replyText($result['text']);
                break;
        }
    }

    protected function redPackage($wechat){

        $redpackage_12 = '030398';
        $redpackage_16 = '887498';
        $redpackage_18 = '695298';


        $date = date('Y-m-d H:i:s');

        //中午12点整至中午12：30分之间
        if( $date >= '2016-01-01 12:00:00' && $date <= '2016-01-01 12:30:00' ){

            $wechat->replyText( '你来晚啦！红包已经被抢光了！' . PHP_EOL . '不过别灰心，下午16点整，第二轮准时开抢！' . PHP_EOL . '18点整第三轮！' . PHP_EOL . PHP_EOL . '如何抢红包？'  . PHP_EOL . '点击菜单抢红包或回复【抢红包】即可！获得口令后前往【支付宝】-【红包】兑换！手慢无！！！' );
            return;
        }

        //下午16点整至16：30分之间
        if( $date >= '2016-01-01 16:00:00' && $date <= '2016-01-01 16:30:00' ){

            $wechat->replyText( '当前的红包口令为：' . $redpackage_16 . PHP_EOL . '快去你的支付宝兑换吧！晚了可就被别人抢光咯！' . PHP_EOL . PHP_EOL . '如何抢红包？'  . PHP_EOL . '点击菜单抢红包或回复【抢红包】即可！获得口令后前往【支付宝】-【红包】兑换！手慢无！！！' );
            return;
        }

        //下午18点整至18：30分之间
        if( $date >= '2016-01-01 18:00:00' && $date <= '2016-01-01 18:30:00' ){

            $wechat->replyText( '当前的红包口令为：' . $redpackage_18 . PHP_EOL . '快去你的支付宝兑换吧！晚了可就被别人抢光咯！' . PHP_EOL . PHP_EOL . '如何抢红包？'  . PHP_EOL . '点击菜单抢红包或回复【抢红包】即可！获得口令后前往【支付宝】-【红包】兑换！手慢无！！！' );
            return;
        }

        //还未开始时间
        if( $date < '2016-01-01 12:00:00' ){

            $wechat->replyText( '小助手准备了支付宝口令红包发给大家，祝大家新年快乐，顺便感谢大家对小助手的支持！' . PHP_EOL . '红包将在2016年1月1日中午12点、16点、18点整公布，抢到口令的童鞋快去支付宝红包兑换，晚了可不管小助手的事哦~' );
            return;
        }

        //已经结束
        if( $date > '2016-01-01 18:30:00' ){

            $wechat->replyText( '小助手准备了支付宝口令红包发给大家，祝大家新年快乐，顺便感谢大家对小助手的支持！' . PHP_EOL . '红包已经在2016年1月1日中午12点、16点、18点整公布完咯~，没有抢到的童鞋下次记得多多关注小助手，也可在社区吐槽一下~' );
            return;
        }

        //12点后，不到16点
        if( $date >= '2016-01-01 12:30:00' && $date < '2016-01-01 16:00:00' ){

            $wechat->replyText( '小助手准备了支付宝口令红包发给大家，祝大家新年快乐，顺便感谢大家对小助手的支持！' . PHP_EOL . '红包在2016年1月1日中午12点、16点、18点整公布~，12点的红包已发放结束，16点整再来吧！' . PHP_EOL . '欢迎到社区吐槽哦~' . PHP_EOL . PHP_EOL . '如何抢红包？'  . PHP_EOL . '点击菜单抢红包或回复【抢红包】即可！获得口令后前往【支付宝】-【红包】兑换！手慢无！！！' );
            return;
        }

        //16点后，不到18点
        if( $date >= '2016-01-01 16:30:00' && $date < '2016-01-01 18:00:00' ){

            $wechat->replyText( '小助手准备了支付宝口令红包发给大家，祝大家新年快乐，顺便感谢大家对小助手的支持！' . PHP_EOL . '红包在2016年1月1日中午12点、16点、18点整公布咯~，12点及16点的红包已发放结束，18点整再来吧！' . PHP_EOL . '欢迎到社区吐槽哦~' . PHP_EOL . PHP_EOL . '如何抢红包？'  . PHP_EOL . '点击菜单抢红包或回复【抢红包】即可！获得口令后前往【支付宝】-【红包】兑换！手慢无！！！' );
            return;
        }

    }

}