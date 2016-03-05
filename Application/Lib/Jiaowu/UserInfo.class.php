<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-14
 * Time: 下午11:32
 */
namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\UserImg;

class UserInfo{
    /**
     * @param $jwid
     * @param $jwpwd
     * @return array
     * @throws \Exception
     * 教务网个人信息
     */
    public static function userInfo($jwid,$jwpwd){

        $url = C('JIAOWU_INDEX_URL').'/xsgrxx.aspx';
        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));
        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");

        $name = self::matches('xm',$ret);
        $_sex = self::matches('lbl_xb',$ret);
        $birthday = self::matches('lbl_csrq',$ret);
        $nation = self::matches('lbl_mz',$ret);
        $political = self::matches('lbl_zzmm',$ret,' height="7"');
        $id_num = self::matches('lbl_sfzh',$ret,' colSpan="2"');
        $fromarea = self::matches('lbl_lydq',$ret);
        $college = self::matches('lbl_xy',$ret);
        $profession = self::matches('lbl_zymc',$ret);
        $class = self::matches('lbl_xzb',$ret);
        $school_system = self::matches('lbl_xz',$ret);
        $dgree = self::matches('lbl_CC',$ret,' colSpan="2"');

        if(trim($_sex[1]) == '男'){
            $sex = 1;
        }elseif(trim($_sex[1]) == '女'){
            $sex = 2;
        }else{
            $sex = 0;
        }
        //获取教务网照片
        $userImgUrl = UserImg::userImg($jwid,$jwpwd);

        $info = array(
            'realname' => $name[1],
            'sex' => $sex,
            'birthday' => $birthday[1],
            'nation' => $nation[1],
            'id_num' => $id_num[1],
            'political' => $political[1],
            'fromarea' => $fromarea[1],
            'college' => $college[1],
            'profession' => $profession[1],
            'class' => $class[1],
            'school_system' => $school_system[1],
            'degree' => $dgree[1],
            'jw_picture' => $userImgUrl,
            );
        $user_info =M('userInfo');
        $uid = get_uid_by_jwid($jwid);
        if($uid !== false){
            $info['uid'] = $uid;
            $info['createtime'] = time();
            $uiid = $user_info->where(array('uid' => $uid, ))->getField('id');
            if($uiid){
                $info['id'] = $uiid;
                $user_info->data($info)->save();
            }else{
                $user_info->data($info)->add();
            }
        }else{
            throw new \Exception('缺少用户数据！');
        }

        return $info;

    }

    /**
     * @param $tag 标签的id 值
     * @param $str
     * @param $_tag TD 后面的样式属性，部分有,注意空格
     * @return mixed
     * 正则匹配个人信息，格式如： <TD><span id="lbl_csrq">19950428</span></TD>
     */
    private static function matches($tag,$str,$_tag = ''){

        $pattern = '/<TD'.$_tag.'><span id="'.$tag.'">(.*)<\/span><\/TD>/i';
        preg_match($pattern,$str,$matches);
        return $matches;
    }
}