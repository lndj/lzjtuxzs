<?php
/**
 * Created by PhpStorm.
 * User: ln_dj
 * Date: 2016/1/13
 * Time: 21:54
 */

namespace Lib\Jiaowu;


class GetApiData
{
    public $api_url = '';
    public $uid = '';
    public $jwid = '';
    public $base_post_data = array();

    public function __construct( $jwid,$jwpwd,$openid,$api_url,$token = '' )
    {
        $this->api_url = $api_url;
        $this->jwid = $jwid;

        $this->uid = M( 'user' )->where(array('openid' => $openid))->getField('id');

        $this->base_post_data = array(
            'jwid' => $jwid,
            'jwpwd' => $jwpwd,
            'token' => $token,
        );
    }

    /**
     * @throws \Exception
     * 从接口获取课表数据并保存
     */
    public function schedule ( ){

        $schedule = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'schedule' ) ) );
        $schedule = json_decode( $schedule,true );

        //如果密码错误了，那就取消绑定咯
        $this->bindOutWrongPass( $schedule['errcode'] );

        if( $schedule['errcode'] != 0 )
//            throw new \Exception( $schedule['errmsg'] );

        if( $this->uid == '' )
            throw new \Exception( '没有查询到用户ID' );

        //去除数组里的提示信息
        $this->unsetTips($schedule);

        $sc = M('schedule');
        $scid = $sc->where( array( 'uid' => $this->uid ) )->getField('id');

        if($scid){
            $schedule['id'] = $scid;
            $schedule['uid'] = $this->uid;
            $schedule['status'] = 1;
            $sc->data($schedule)->save();
        }else{
            $schedule['uid'] = $this->uid;
            $schedule['status'] = 1;
            $sc->data($schedule)->add();
        }

        //清除缓存
        for ( $i = 0;$i < 7;$i++ ){
            S('schedule_cache_' . $this->uid . $i,null);
        }
    }

    public function grade ( ){

        $grade = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'grade' ) ) );
        $grade = json_decode( $grade,true );

        //如果密码错误了，那就取消绑定咯
        $this->bindOutWrongPass( $grade['errcode'] );

        if( $grade['errcode'] != 0 )
            throw new \Exception( $grade['errmsg'] );

        //去除数组里的提示信息
        $grade = $this->unsetTips($grade);

        if( $this->uid == '' )
            throw new \Exception( '没有查询到用户ID' );

        $gr = M('grade');
        foreach($grade as $g){
            $g['course'] = trim($g['course']);
            $grid = $gr->where(array( 'course' => $g['course'],'uid' => $this->uid, ))->getField('id');
            $g['uid'] = $this->uid;
            $g['createtime'] = time();
            if($grid){
                $g['id'] = $grid;
                $gr->data($g)->save();
            }else{
                $gr->data($g)->add();
            }
        }
        //清除SQL查询缓存
        S( 'grade_cache_' . $this->uid . '_1',null );
        S( 'grade_cache_' . $this->uid . '_0',null );
    }

    public function exam(  ){

        $exam = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'exam' ) ) );
        $exam = json_decode( $exam,true );

        //如果密码错误了，那就取消绑定咯
        $this->bindOutWrongPass( $exam['errcode'] );

        if( $exam['errcode'] != 0 )
//            throw new \Exception( $exam['errmsg'] );

        if( $this->uid == '' )
            throw new \Exception( '没有查询到用户ID' );

        //去除数组里的提示信息
        $exam = $this->unsetTips($exam);

        $ex = M('exam');
        foreach($exam as $v){
            $v['uid'] = $this->uid;
            $v['createtime'] = time();
            $exid = $ex->where(array( 'course' => $v['course'],'uid' => $this->uid, ))->getField('id');
            if($exid){
                $v['id'] = $exid;
                $ex->data($v)->save();
            }else{
                $ex->data($v)->add();
            }
        }
        //清除SQL查询缓存
        S('exam_cache_' . $this->uid,null);
    }

    public function rankExam(  ){

        $rankExam = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'rankExam' ) ) );
        $rankExam = json_decode( $rankExam,true );

        //如果密码错误了，那就取消绑定咯
        $this->bindOutWrongPass( $rankExam['errcode'] );

        if( $rankExam['errcode'] != 0 )
//            throw new \Exception( $rankExam['errmsg'] );

        if( $this->uid == '' )
            throw new \Exception( '没有查询到用户ID' );

        //去除数组里的提示信息
        $rankExam = $this->unsetTips($rankExam);

        $rank = M('rankExam');

        foreach ($rankExam as $re) {
            $re['uid'] = $this->uid;
            $re['createtime'] = time();
            //根据准考证号判断是更新还是添加
            $reid = $rank->where(array( 'exam_num' => $re['exam_num'],'uid' => $this->uid, ))->getField('id');
            if($reid){
                $re['id'] = $reid;
                $rank->data($re)->save();
            }else{
                $rank->data($re)->add();
            }
        }
        //清除SQL查询缓存
        S('rankexam_cache_' . $this->uid,null);
    }

    public function makeupExam( ){

        $makeupExam = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'makeup' ) ) );
        $makeupExam = json_decode( $makeupExam,true );

        //如果密码错误了，那就取消绑定咯
        $this->bindOutWrongPass( $makeupExam['errcode'] );

        if( $makeupExam['errcode'] != 0 )
//            throw new \Exception( $makeupExam['errmsg'] );

        if( $this->uid == '' )
            throw new \Exception( '没有查询到用户ID' );

        //去除数组里的提示信息
        $makeupExam = $this->unsetTips($makeupExam);

        $makeup = M('makeupExam');

        foreach($makeupExam as $me){
            $me['uid'] = $this->uid;
            $me['createtime'] = time();
            $meid = $makeup->where(array( 'course' => $me['course'],'uid' => $this->uid, ))->getField('id');
            if($meid){
                $me['id'] = $meid;
                $makeup->data($me)->save();
            }else{
                $makeup->data($me)->add();
            }
        }

        //清除SQL查询缓存
        S('makeup_cache_' . $this->uid,null);
    }

    public function userInfo(  ){

        $userInfo = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'userInfo' ) ) );
        $userInfo = json_decode( $userInfo,true );

        //如果密码错误了，那就取消绑定咯
        $this->bindOutWrongPass( $userInfo['errcode'] );

        if( $userInfo['errcode'] != 0 )
            throw new \Exception( $rankExam['errmsg'] );

        if( $this->uid == '' )
            throw new \Exception( '没有查询到用户ID' );


        //去除数组里的提示信息
        $userInfo = $this->unsetTips($userInfo);

        $user_info =M('userInfo');

        $userInfo['uid'] = $this->uid;
        $userInfo['createtime'] = time();
        $uiid = $user_info->where(array('uid' => $this->uid, ))->getField('id');
        if($uiid){
            $userInfo['id'] = $uiid;
            $user_info->data($userInfo)->save();
        }else{
            $user_info->data($userInfo)->add();
        }

    }
    /**
     * 获取教务网照片数据，这个不同于其他，不需要json序列化，直接写入storage
     */
    public function userImg(  ){

        $userImg = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'userImg' ) ) );

        //上传到storage
        $upload = new \SaeStorage();
        $upload->write ( 'jwpictures' ,  $this->jwid.'.jpeg' , $userImg );
        $imgUrl = $upload->getUrl('jwpictures',$this->jwid.'.jpeg');

        //将图片链接存储到数据库
        $user_info =M('userInfo');

        $userInfo = array(
            'jw_picture' => $imgUrl,
        );
        $userInfo['createtime'] = time();
        $uiid = $user_info->where(array('uid' => $this->uid, ))->getField('id');
        if($uiid){
            $userInfo['id'] = $uiid;
            $user_info->data($userInfo)->save();
        }else{
            $user_info->data($userInfo)->add();
        }
    }

    public function checkPass( ){

        $result = curl_request( $this->api_url,array(),array_merge( $this->base_post_data,array( 'item' => 'checkpass' ) ) );
        $result = json_decode( $result,true );

        if( $result['result'] == 1 ){
            return true;
        }

        return false;
    }

    private function unsetTips($arr){
        unset($arr['errcode']);
        unset($arr['errmsg']);
        return $arr;
    }

    private function bindOutWrongPass( $errcode ){

        if( $errcode == -5 ){

            $user = M('user');
            $_data = array(
                'is_bind' => 0,
                'id' => $this->uid,
            );
            $ret = $user->data($_data)->save();
            exit();
        }

        return false;
    }


}