<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-14
 * Time: 下午8:05
 */

namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

class RankExam{

    public static function rankExam($jwid,$jwpwd){

        $url = C('JIAOWU_INDEX_URL').'/xsdjkscx.aspx';

        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));

        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);
        array_shift($ret);

        $rankExam = array();
        $fields = array('school_year','term','exam','exam_num','exam_date','grade','listen_grade','read_grade','write_grade','compre_grade',);
        foreach ($ret as $key => $v) {
            $v[0] = trim($v[0]);
            $v[1] = intval($v[1]);
            $v[3] = trim($v[3]);//准考证号
            $rankExam[] = array_combine($fields,$v);
        }

        $rank = M('rankExam');
        $uid = get_uid_by_jwid($jwid);
        if($uid !== false){

            foreach ($rankExam as $re) {
                $re['uid'] = $uid;
                $re['createtime'] = time();
                //根据准考证号判断是更新还是添加
                $reid = $rank->where(array( 'exam_num' => $re['exam_num'],'uid' => $uid, ))->getField('id');
                if($reid){
                    $re['id'] = $reid;
                    $rank->data($re)->save();
                }else{
                    $rank->data($re)->add();
                }
            }
        }else{
            throw new \Exception('缺少用户数据！');
        }

        clear_sql_cache( 'rankexam_cache_' . $uid );
        return $rankExam;

    }

}