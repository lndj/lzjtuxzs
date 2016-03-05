<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-14
 * Time: 下午9:11
 */
namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

class MakeupExam{
    /**
     * @param $jwid
     * @param $jwpwd
     * @return bool
     * 教务网默认查询当前学年《第一学期》的补考安排，可GET获取，其余学期的需POST请求
     *
     * 完成-------2016/01/15
     */
    public static function makeupExam($jwid,$jwpwd){

        $url = C('JIAOWU_INDEX_URL').'/XsBkKsCx.aspx';
        $makeup = M('makeupExam');
        $config = M('config');
        $school_year = $config->where(array('item' => 'SCHOOL_YEAR_NOW'))->getField('config');

        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->get($url,array('xh' => $jwid));

        /*当设置为第二学期的时候，此时开始POST获取*/
        if( getS( 'MAKEUP_TERM' ) == 2 ){
            //匹配隐藏域表单值
            try{
                $viewstate = Curl::getHidden($ret);
            }catch( \Exception $e ){
                //-------记录日志
                systemErrLog( $jwid,$e->getMessage() . '--补考数据获取--POST'  );
                exit();
            }

            $post['__EVENTTARGET'] = 'xqd';
            $post['__EVENTARGUMENT'] = '';
            $post['__VIEWSTATE'] = $viewstate;
            $post['xnd'] = getS( 'MAKEUP_SCHOOL_YEAR' );
            $post['xqd'] = getS( 'MAKEUP_TERM' );
            $post = http_build_query($post);

            $ret = $curl->post($url,$post,array('xh' => $jwid));
        }

        $ret = mb_convert_encoding($ret, "utf-8", "gb2312");
        $ret = ArrayAnalysis::get_td_array($ret);
        array_shift($ret);

        //没有补考信息  用 === 判断
        if(empty($ret)){
            return false;
        }

        $fields = array('choose_course_num','course','name','exam_date','exam_address','exam_type','position_num','school_year','term');
        $makeupExam = array();
        foreach($ret as $key => $v){
            $v[0] = trim($v[0]);
            $v[1] = trim($v[1]);
            //unset($v[7]);
            //从选课课号中分割学年学期2016/01/15
            $v[7] = substr($v[0],1,9);
            $v[8] = intval( substr($v[0],11,1) );
            $makeupExam[] = array_combine($fields,$v);
        }

        $uid = get_uid_by_jwid($jwid);

        //清楚SQL缓存
        S('makeup_cache_'.$uid,null);

        if($uid !== false){
            foreach($makeupExam as $me){
                $me['uid'] = $uid;
                $me['createtime'] = time();
                $me['school_year'] = $school_year;
                $meid = $makeup->where(array( 'course' => $me['course'],'uid' => $uid, ))->getField('id');
                if($meid){
                    $me['id'] = $meid;
                    $makeup->data($me)->save();
                }else{
                    $makeup->data($me)->add();
                }
            }
        }else{
            throw new \Exception('缺少用户数据！');
        }

        return $makeupExam;

    }
}
