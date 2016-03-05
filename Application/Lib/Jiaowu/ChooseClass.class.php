<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-10-30
 * Time: 下午5:15
 */

namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

class ChooseClass
{

    static public function chooseClass($jwid,$jwpwd){

        $url = C('JIAOWU_INDEX_URL').'/xsxkqk.aspx';
        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);
        array_shift($ret);
        p($ret);
        //02010131
    }

}