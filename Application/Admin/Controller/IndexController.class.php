<?php
namespace Admin\Controller;
use Think\Controller;
use Lib\Wechat\Wechat;
class IndexController extends CommonController {
    /**
     *
     */
    public function index(){
        $user = M('user');

        //当前绑定人数
        $map = array(
            'is_bind' => 1,
        );
        $this->bind_user_num = $user->where($map)->count();

        //当前总人数
        $user_info = M('user_info');
        $this->all_user_num = $user_info->count();

        //解除绑定人数
        $map['jwid'] = array('gt',0);
        $map['is_bind'] = 0;
        $this->bind_out_user_num = $user->where($map)->count();

        //今日绑定
        $date = date("Y-m-d") . '0:0:0';
        $today_start = strtotime($date);
        $map['bind_time'] = array('gt',$today_start);
        $map['is_bind'] = 1;
        $this->today_bind = $user->where($map)->count();

        //Counter统计数据
        $this->grade_wechat = counter_get('grade_wechat');
        $this->update_grade = counter_get('update_grade');
        $this->update_exam = counter_get('update_exam');
        $this->schedule_click = counter_get('schedule_click');
        $this->schedule_text = counter_get('schedule_text');
        $this->free_classroom = counter_get('free_classroom');
        $this->exam_wechat = counter_get('exam_wechat');

        $this->help_page = counter_get('help_page');
        $this->web_grade = counter_get('web_grade');

        //用户年级分布
        $user_grade = getS('everyday_user_attr');

        foreach($user_grade as $k => $v){
            $js_data_str .= '{value:'. $v . ',name:' . '"' . $k .'年级"' . '},';
            $js_legend_str .= '"' . $k . '年级"' . ',';

        }
        //组装好的饼状图的数据来源js代码
        $this->js_data_str = $js_data_str;
        $this->js_legend_str = $js_legend_str;

        //计算男女人数
        $this->boy = $user_info->where( array( 'sex' => 1 ) )->count();
        $this->girl = $user_info->where( array( 'sex' => 2 ) )->count();
        $this->sex_unkown = $user_info->where( array( 'sex' => 0 ) )->count();

        $this->display();
    }

    public function loginout(){
        session_unset();
        session_destroy();

        $this->redirect('Admin/Login/index');
    }



}