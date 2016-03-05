<?php
/**
 * 课表获取
 */
namespace Lib\Jiaowu;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\ArrayAnalysis;

class Schedule{

    /**
     * @param $jwid
     * @param $jwpwd
     * @return mixed
     * 获取课表数据
     */

    //今日课表
    /**分割算法**/
    /*星期一　　　　　 星期二　　　   星期三　　     ....*/
    /*[1][2]           [1][3]         [1][4]          ...*/
    /*[3][1]           [3][2]         [3][3]         ....*/
    /*[5][2]           [5][3]         [5][4]         ....*/
    /*[7][1]           [7][2]         [7][3]         ....*/
    /*[9][2]           [9][3]         [9][4]         ....*/
    public static function schedule($jwid,$jwpwd){

        $url = C('JIAOWU_INDEX_URL').'/xskbcx.aspx';
        $curl = new Curl($jwid,$jwpwd);
        $schedule = $curl->get($url,array('xh' => $jwid));

        /*当设置的当前学期与所要查询的学期一样的时候，不需要POST*/

        if( getS( 'SCHEDULE_TERM' ) != getS( 'term_now' ) ){
            //匹配隐藏域表单值
            try{
                $viewstate = Curl::getHidden($schedule);
            }catch( \Exception $e ){
                //-------记录日志
                systemErrLog( $jwid,$e->getMessage() . '--课表数据获取--POST' );
                exit();
            }

            $post['__EVENTTARGET'] = 'xqd';
            $post['__EVENTARGUMENT'] = '';
            $post['__VIEWSTATE'] = $viewstate;
            $post['xnd'] = getS( 'SCHEDULE_SCHOOL_YEAR' );
            $post['xqd'] = getS( 'SCHEDULE_TERM' );
            $post = http_build_query($post);
            $schedule = $curl->post($url,$post,array('xh' => $jwid));
        }

        $schedule = mb_convert_encoding($schedule, "utf-8", "gb2312");
        $schedule = ArrayAnalysis::get_kb_array($schedule);
        array_shift($schedule);

        /*$i 代表周一至周日，1/3/5/7/9分别为一天的五讲课程*/
//        $everyday = array($schedule[1][$i + 1],$schedule[3][$i],$schedule[5][$i + 1],$schedule[7][$i],$schedule[9][$i + 1]);

        $mon = $schedule[1][2]."#".$schedule[3][1]."#".$schedule[5][2]."#".$schedule[7][1]."#".$schedule[9][2];
        $tues = $schedule[1][3]."#".$schedule[3][2]."#".$schedule[5][3]."#".$schedule[7][2]."#".$schedule[9][3];
        $wed = $schedule[1][4]."#".$schedule[3][3]."#".$schedule[5][4]."#".$schedule[7][3]."#".$schedule[9][4];
        $thur = $schedule[1][5]."#".$schedule[3][4]."#".$schedule[5][5]."#".$schedule[7][4]."#".$schedule[9][5];
        $fri = $schedule[1][6]."#".$schedule[3][5]."#".$schedule[5][6]."#".$schedule[7][5]."#".$schedule[9][6];
        $sat = $schedule[1][7]."#".$schedule[3][6]."#".$schedule[5][7]."#".$schedule[7][6]."#".$schedule[9][7];
        $sun = $schedule[1][8]."#".$schedule[3][7]."#".$schedule[5][8]."#".$schedule[7][7]."#".$schedule[9][8];

        $schedule = array(
            'mon' => $mon,
            'tues' => $tues,
            'wed' => $wed,
            'thur' => $thur,
            'fri' => $fri,
            'sat' => $sat,
            'sun' => $sun,
        );
        $sc = M('schedule');
        $uid = get_uid_by_jwid($jwid);

        if($uid !== false){
            $scid = $sc->where(array('uid' => $uid))->getField('id');
            if($scid){
                $schedule['id'] = $scid;
                $schedule['uid'] = $uid;
                $schedule['status'] = 1;
                $sc->data($schedule)->save();
            }else{
                $schedule['uid'] = $uid;
                $schedule['status'] = 1;
                $sc->data($schedule)->add();
            }
        }else{
            throw new \Exception('缺少用户数据！');
        }

        //清除缓存
        for ( $i = 0;$i < 7;$i++ ){
            S('schedule_cache_' . $uid . $i,null);
        }

        return $schedule;
    }




}