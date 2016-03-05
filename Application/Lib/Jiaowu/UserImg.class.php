<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-10-16
 * Time: 下午9:10
 */

namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;

/**
 * Class UserImg
 * @package Lib\Jiaowu
 * 抓取教务网的个人信息页照片
 */
class UserImg
{
    public static function userImg($jwid,$jwpwd){
        $url = C('JIAOWU_INDEX_URL').'/readimagexs.aspx';
        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));

        $upload = new \SaeStorage();
        $upload->write ( 'jwpictures' ,  $jwid.'.jpeg' , $ret );
        $url = $upload->getUrl('jwpictures',$jwid.'.jpeg');

        return $url;
    }

}