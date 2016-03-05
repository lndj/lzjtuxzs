<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-15
 * Time: 下午3:45
 */

namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

/**
 * Class NopassGrade
 * @package Lib\Jiaowu
 * 至今未通过成绩
 * 可在教务网抓取，也可以自行从成绩数据中挑选，为减轻抓取压力，最好采用自行计算的方式
 */
class NopassGrade{

    public static function nopassGrade($jwid,$jwpwd){

        $url = C('JIAOWU_INDEX_URL').'/xscjcx.aspx';

        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);

        array_splice($ret,0,5);
        array_splice($ret,-3);




        p($ret);


    }
}