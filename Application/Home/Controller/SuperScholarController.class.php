<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-11-12
 * Time: 下午7:59
 */

namespace Home\Controller;


use Think\Controller;

class SuperScholarController extends Controller
{

    public function index(){
        $openid = I('get.openid');
        $user = get_user_by_openid($openid);

        $g = M('grade');


        $map = array(
            'uid' => $user['id'],
        );
        //绩点和
        $this->grade_point_sum = $g->where($map)->sum('grade_point');
        //学分和
        $this->credits_sum = $g->where($map)->sum('credits');
        //平均绩点
        $this->grade_point_ave = $g->where($map)->avg('grade_point');
        //修的总科目数
        $this->grade_num = $g->where($map)->count();


        $grade = $g->where($map)->field('credits,grade_point,grade,makeup_grade,rebuild_grade')->select();

        //挂科数目
        $low_all_num = 0;
        //补考没过的数目
        $low_makeup_num = 0;

        foreach( $grade as $v ){
            //数字类成绩
            if( is_numeric($v['grade']) && $v['grade'] < 60 ){
                $low_all_num++;
                //挂科后检测是否补考过
                if( $v['makeup_grade'] == '' || $v['makeup_grade'] < 60 ){
                    $low_makeup_num++;
                }

            }elseif($v['grade'] == '不及格'){
                //文字类成绩
                $low_all_num++;
                //挂科后检测是否补考过
                if( $v['makeup_grade'] == '' || $v['makeup_grade'] == '不及格' ){
                    $low_makeup_num++;
                }
            }
        }
        $this->low_makeup_num = $low_makeup_num;
        $this->low_all_num = $low_all_num;




        $this->display();
    }

    private function getData($uid){

        $g = M('grade');


        $map = array(
            'uid' => $uid,
        );
        //成绩总数


        //成绩绩点平均值
        $grade_point_ave = $g->where($map)->avg('grade_point');

        //

        return $grade_num;

    }



}