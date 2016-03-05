<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-13
 * Time: 下午9:11
 */
namespace Lib\Jiaowu;

use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

class Grade{


    public static function grade($jwid,$jwpwd){
        $url = C('JIAOWU_INDEX_URL').'/xscjcx.aspx';

        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");

        //匹配隐藏域表单值
        try{
            $viewstate = Curl::getHidden($ret);
        }catch( \Exception $e ){
            //-------记录日志
            systemErrLog( $jwid,$e->getMessage() . '--成绩数据获取--POST' );
            exit();
        }
        //更改为这种方式 -----2016/01/15
        $post['__EVENTTARGET'] = '';
        $post['__EVENTARGUMENT'] = '';
        $post['__VIEWSTATE'] = $viewstate;
        $post['hidLanguage'] = '';
        $post['ddlXN'] = '';
        $post['ddlXQ'] = '';
        $post['ddl_kcxz'] = '';
        $post['btn_zcj'] = iconv('utf-8', 'gb2312', '历年成绩');
        $post = http_build_query($post);

        $ret = $curl->post($url,$post,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);

        array_splice($ret,0,5);
        array_splice($ret,-3);

        $grade = array();
        $fields = array('school_year','term','course_code','course','course_attr','course_belong','credits','grade_point','grade','minor_mark','makeup_grade','rebuild_grade','college','remark','rebuild_mark');

        foreach($ret as $key => $v){
            $v[0] = trim($v[0]);
            $grade[] = array_combine($fields,$v);
        }

        $gr = M('grade');
        $uid = get_uid_by_jwid($jwid);

        if($uid !== false){
            foreach($grade as $g){
                $g['course'] = trim($g['course']);
                $grid = $gr->where(array( 'course' => $g['course'],'uid' => $uid, ))->getField('id');
                $g['uid'] = $uid;
                $g['createtime'] = time();
                if($grid){
                    $g['id'] = $grid;
                    $gr->data($g)->save();
                }else{
                    $gr->data($g)->add();
                }
            }

            clear_sql_cache('grade_cache_' . $uid . '_1');
            clear_sql_cache('grade_cache_' . $uid . '_0');

        }else{
            throw new \Exception('缺少用户数据！');
        }
        return $grade;
    }

}
