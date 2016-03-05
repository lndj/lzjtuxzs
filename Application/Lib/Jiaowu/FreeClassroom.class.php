<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-10-26
 * Time: 下午7:27
 */

namespace Lib\Jiaowu;

use Lib\Jiaowu\Curl;

/**
 * Class FreeClassroom
 * @package Lib\Jiaowu
 * 获取当日的所有空教室，每日凌晨执行
 */

class FreeClassroom
{
    static private $jwid = '';
    static private $jwpwd = '';
    static private $url = '';

    public static function freeClassroom($jwid,$jwpwd){
        $url = C('JIAOWU_INDEX_URL').'/xxjsjy.aspx';

        self::$jwid = $jwid;
        self::$jwpwd = $jwpwd;
        self::$url = $url;

        //清空表中历史数据
        M('freeClassroom')->where('1')->delete();

        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");

        $viewstate = Curl::getHidden($ret);
        $time_value = self::getTimeValue($ret);
        $time_point = array(
            1 => '%271%27%7C%271%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            2 => '%272%27%7C%270%27%2C%273%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            3 => '%273%27%7C%270%27%2C%270%27%2C%275%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            4 => '%274%27%7C%270%27%2C%270%27%2C%270%27%2C%277%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            5 => '%275%27%7C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%279%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            6 => '%276%27%7C%271%27%2C%273%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            7 => '%277%27%7C%270%27%2C%270%27%2C%275%27%2C%277%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            8 => '%279%27%7C%271%27%2C%273%27%2C%275%27%2C%277%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
            9 => '%2710%27%7C%271%27%2C%273%27%2C%275%27%2C%277%27%2C%279%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27',
        );

        foreach($time_point as $k => $v){
            $ret = self::getClassroom($viewstate,$time_value,$time_value,$v,$k);
        }
        return $ret ? true : false;
    }

    /**
     * @param $viewstate
     * @param $time_start
     * @param $time_end
     * @param $time_point
     * 执行获取数据并存储的操作
     */
    private static function getClassroom($viewstate,$time_start,$time_end,$time_point,$time_point_code){
        $post_data = "__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE={$viewstate}&xiaoq=&jslb=&min_zws=0&max_zws=&kssj={$time_start}&jssj={$time_end}&xqj=1&ddlDsz=&sjd={$time_point}&Button2=%BF%D5%BD%CC%CA%D2%B2%E9%D1%AF&dpDataGrid1%3AtxtChoosePage=1&dpDataGrid1%3AtxtPageSize=300&xn=2015-2016&xq=1&ddlSyXn=2015-2016&ddlSyxq=1&dpDatagrid3%3AtxtChoosePage=1&dpDatagrid3%3AtxtPageSize=20";
        $curl = new Curl(self::$jwid,self::$jwpwd);
        $ret = $curl->post(self::$url,$post_data,array('xh' => self::$jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);
        array_shift($ret);

        $free_classroom = array();
        $fields = array('classroom_name','classroom_type','position_num_class','classroom_department','position_num_exam','class_floor_code','time_point',);

        foreach($ret as $v){
            unset($v[0]);
            unset($v[3]);
            $v[2] = self::getClassroomType($v[2]);
            $v[7] = self::getFloorCode($v[1]);
            $v[8] = $time_point_code;
            $free_classroom[] = array_combine($fields,$v);
        }

        $fc = M('freeClassroom');
        foreach($free_classroom as $v){
            $v['createtime'] = time();
            $fc->data($v)->add();
        }
        return true;
    }
    /**
     * @param $str
     * @return string
     * @throws \Exception
     * 获取当日的教务网空闲教室查询的开始时间的post值
     * 查询当日的取开始时间和结束时间相同
     */
    private static function getTimeValue($str){
        $date = date("Y-m-d");
        $pattern = '/<option selected="selected" value="(.*)">' . $date . '<\/option>/i';
        preg_match($pattern, $str, $matches);
        if(count($matches) > 1)
            return trim($matches[1]);
        else
            throw new \Exception('获取开始结束时间value失败！');
    }

    /**
     * @param $str
     * @return int
     * 截取教学楼号，艺术学院按照10号处理
     */
    private static function getFloorCode($str){
        $code = mb_substr($str,0,1);
        //艺术学院单独处理
        if(!is_numeric($code))
            $code = 10;
        return intval($code);
    }

    /**
     * @param $str
     * @return int|mixed
     * 获取教室类型代码
     * 不在数组的返回0
     */
    private static function getClassroomType($str){
        $str = trim($str);
        $classroom = array(
            1 => '多媒体教室',
            2 => '改装室',
            3 => '机房',
            4 => '评图室',
            5 => '普通教室',
            6 => '实验室',
            7 => '素描室',
            8 => '体育场',
            9 => '语音室',
            10 => '制图室',
        );
        if(in_array($str,$classroom))
            return array_search($str,$classroom);
        else
            return 0;
    }


}