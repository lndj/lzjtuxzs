<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-10-30
 * Time: 下午5:40
 */

namespace Lib\Jiaowu;

use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

/**
 * Class Exam
 * @package Lib\Jiaowu
 * 考试安排由于偶尔会禁止访问，队列执行时放在最后
 */
class Exam
{
    static public function exam($jwid,$jwpwd){

        //修改--单独调用的时候

        $url = C('JIAOWU_INDEX_URL').'/xskscx.aspx';
        //本学期的考试内容直接GET获取
        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);
        array_shift($ret);

        $ex = M('exam');
        $uid = get_uid_by_jwid($jwid);

        //没有考试安排信息  用 === 判断
        //此时仅仅更新时间戳即可
        if(empty($ret)){
            $data['createtime'] = time();
            if( $ex->where(array('uid' => $uid))->data($data)->save() ){
                return true;
            }
        }

        $fields = array('choose_course_num','course','name','exam_date','exam_address','exam_type','position_num','school_year','term');
        $exam = array();
        foreach($ret as $key => $v){

            $v[0] = trim($v[0]);
            $v[1] = trim($v[1]);
//            unset($v[7]);
            //从选课课号中分割学年学期2016/01/07
            $v[7] = substr($v[0],1,9);
            $v[8] = intval( substr($v[0],11,1) );
            $exam[] = array_combine($fields,$v);
        }

        //此处修改根据选课课号前面括号里面的数字来确定学年学期  2016/01/07

        if($uid !== false){
            foreach($exam as $v){
                $v['uid'] = $uid;
                $v['createtime'] = time();
                $exid = $ex->where(array( 'course' => $v['course'],'uid' => $uid, ))->getField('id');
                if($exid){
                    $v['id'] = $exid;
                    $ex->data($v)->save();
                }else{
                    $ex->data($v)->add();
                }
            }
        }else{
            throw new \Exception('缺少用户数据！');
        }
        //清除SQL查询缓存
        S('exam_cache_' . $uid,null);
        return $exam;
    }


}