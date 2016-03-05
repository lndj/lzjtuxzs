<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-11-27
 * Time: 下午11:45
 */

namespace Home\Controller;


use Think\Controller;

class WebQueryController extends Controller
{
    public function index(){

        //TODO 写个查询的汇总页面？
    }

    public function grade(){

        counter_incr('web_grade');
        $uid = I('get.uid','','intval');

        //默认值为0的时候，表示全部成绩
        $school_year = I('get.school_year',0);
        $term = I('get.term',0,'intval');
        //全部成绩$school_eyear == 0 && $term == 0

        $g = M('grade');

        $map['uid'] = $uid;
        if($school_year != 0 && $term != 0){
            $map['school_year'] = $school_year;
            $map['term'] = $term;
        }

        $data = $g->where($map)
            ->field('school_year,term,course_code,course,course_attr,
            credits,grade_point,grade,makeup_grade,rebuild_grade,college')
            ->select();
        $this->assign('grade',$data);
//        p($data);
        //取出所有的学年学期，方便遍历出学年学期选择button
        $year_term = $g->where(array('uid' => $uid))->field('school_year,term')->select();

        $year_unique = array();
        foreach($year_term as $v){
            if( in_array( $v['school_year'],$year_unique ) ) {
                continue;
            }
            $year_unique[] = $v['school_year'];
        }

        //最后一个需要判断下是否有第2学期
        $last_year = array_pop($year_unique);

        $this->assign('last_year',$last_year);
        $this->assign('year_unique',$year_unique);

        $this->header = $this->web_grade_header( $school_year,$term );

        //方便VIEW层判断是否显示
        $this->is_2_term = $this->is_2_term( $last_year,$year_term );

        $this->assign('uid',$uid);
        $this->display();
    }

    /**
     * @param $last_year
     * @param $year_term
     * @return bool
     * 判断最后一个学年是否有第二学期
     */
    private function is_2_term($last_year,$year_term){
        foreach($year_term as $v){
            if( $v['school_year'] == $last_year && $v['term'] == 2 )
                return true;
        }
        return false;
    }

    /**
     * @param $school_year
     * @param $term
     * @return string
     * 返回成绩查询网页版的header显示字符串
     */
    private function web_grade_header( $school_year,$term ){
        if( $school_year == 0 || $term == 0 )
            return "历年成绩";

        return "{$school_year}学年第{$term}学期";
    }

}