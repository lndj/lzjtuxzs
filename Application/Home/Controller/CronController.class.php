<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-10-22
 * Time: 下午8:24
 */

/**
 * Cron
 */
namespace Home\Controller;


use Think\Controller;
use Lib\Jiaowu\FreeClassroom;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\Schedule;

class CronController extends Controller
{
    /**
     * 每日凌晨执行
     * 获取教务网当日空闲教室
     */
    public function everyday_free_classroom(){

        $ret = FreeClassroom::freeClassroom(C('ADMIN_JWID'),C('ADMIN_JWPWD'));
        if($ret)
            echo "success!";
        else
            echo "failed!";
    }
    /**
     * @throws \Exception
     *user表有，user_info表没有的
     */
    public function everyday_get_jiaowu_info(){

        $user = M()->query('SELECT xzs_user.jwid, xzs_user.jwpwd
                            FROM xzs_user
                            WHERE NOT
                            EXISTS (
                            SELECT xzs_user_info.uid
                            FROM xzs_user_info
                            WHERE xzs_user.id = xzs_user_info.uid
                            ) AND xzs_user.is_bind = 1'
        );
        $i = 0;
        foreach ( $user as $u ){
            $u['jwpwd'] = \Think\Crypt::decrypt($u['jwpwd'],'jwpwd_wechat');

            $queue = new \SaeTaskQueue('getJiaowuData');

            $tasks = array();
            $tasks[] = array(
                'url' => U('Home/Queue/getUserInfoQueue',array('jwid' =>  $u['jwid'],'jwpwd' =>  $u['jwpwd'] )),
                'postdata' => "",
                'prior' => false,
                'options' => array(),
            );
            $queue->addTask($tasks);
            $ret = $queue->push();
            if ($ret === false)
                throw new \Exception($queue->errno().$queue->errmsg());
            //计数器
            $i++;

            if($ret)
                echo "{$i}:success!";
        }
    }
    /**
     * @throws \Exception
     * 获取那些没有获取到资料的，user 和 user_info 表都有的
     * 但是user_info表资料没有成功获取
     */
    public function get_jiaowu_userinfo(){
        $user =  M()
            ->table('xzs_user u, xzs_user_info ui')
            ->where('u.id = ui.uid and ui.sex = 0')
            ->field('u.jwid as jwid, u.jwpwd as jwpwd')
            ->select();
//        p($user);
        $i = 0;
        foreach ( $user as $u ){
            $u['jwpwd'] = \Think\Crypt::decrypt($u['jwpwd'],'jwpwd_wechat');

            S('cookie_' . $u['jwid'],null);

            $queue = new \SaeTaskQueue('getJiaowuData');

            $tasks = array();
            $tasks[] = array(
                'url' => U('Home/Queue/getUserInfoQueue',array('jwid' =>  $u['jwid'],'jwpwd' =>  $u['jwpwd'] )),
                'postdata' => "",
                'prior' => false,
                'options' => array(),
            );
            $queue->addTask($tasks);
            $ret = $queue->push();
            if ($ret === false)
                throw new \Exception($queue->errno().$queue->errmsg());
            //计数器
            $i++;

            if($ret)
                echo "{$i}:success!";
        }
    }

    /**
     * 每日获取年纪分布数据
     */
    public function everyday_get_user_attr(){
        //先清除前一天的数据
        //TODO 以后写好统计的时候，这个数据持久化
        setS('everyday_user_attr',null);
        $u = M('user');
        $map['jwid'] = array('neq',0);
        $jwid = $u->where($map)->field('jwid')->select();

        $jwid_pre_array = array();

        foreach( $jwid as $j ){
            if ( strlen($j['jwid']) == 6 )
                continue;

            //获取学号的前4位
            $jwid_pre = substr($j['jwid'],0,4);
            //判断key是否存在
            $exits = array_key_exists($jwid_pre,$jwid_pre_array);

            if($exits){
                //存在这个key则给value+1
                $jwid_pre_array[$jwid_pre]++;
            }else{
                //不存在的时候创建这个key并初始化value=1
                $jwid_pre_array[$jwid_pre] = 1;
            }
        }
        //存储这个数组
        setS('everyday_user_attr',$jwid_pre_array);
        $output = '';
        foreach($jwid_pre_array as $k => $v){
            $output .= $k . ':' .$v . ' || ';
        }
        echo $output;
    }

    public function checkAllPassword(){
        $queue = new \SaeTaskQueue('updateGradeAuto');

        $tasks = array();
        $tasks[] = array(
            'url' => U( 'Home/Queue/checkAllPassword' ),
            'postdata' => "",
            'prior' => false,
            'options' => array(),
        );
        $queue->addTask($tasks);
        $ret = $queue->push();
        if ($ret === false)
            throw new \Exception($queue->errno().$queue->errmsg());
        else
            var_dump($ret);

    }

    /**     *
     * 更新课表数据
     */
    public function updateScheduleBySystem(  ){

        $times = counter_get('updateschedule');

        $offset = 30;
        $start = ( $offset * $times + 1 ) ?  ( $offset * $times + 1 ) : 1;

        echo "#start:";
        echo $start;
        echo "<br />";
        $count = M( 'user' )->where( array( 'is_bind' => 1 ) )->count();

        echo "#count:";
        echo $count;
        echo "<br />";
        if( $start > $count ){
            counter_set('updateschedule',0);
        }

        $user = M('user')->where(array('is_bind' => 1))->field('jwid,jwpwd')->order( 'id' )->limit($start,$offset)->select();

        foreach ( $user as $u ) {
            $u[ 'jwpwd' ] = \Think\Crypt::decrypt( $u[ 'jwpwd' ], 'jwpwd_wechat' );
            echo "jwid:";echo $u['jwid'];echo "|||";echo "jwpwd:";echo $u['jwpwd'];echo "<br />";
            Schedule::schedule( $u['jwid'],$u['jwpwd'] );
        }

        counter_incr('updateschedule');
    }

    /**
     * @throws \Exception
     *
     * 处理密码正确，却被取消绑定的
     */
    public function updateBindout(){
        $times = counter_get('updateBindout');

        $offset = 200;
        $start = ( $offset * $times + 1 ) ?  ( $offset * $times + 1 ) : 1;

        echo "#start:";
        echo $start;
        echo "<br />";
        $count = M()->table( array( 'xzs_user' => 'u','xzs_user_info' => 'ui' ) )->where( 'u.id = ui.uid and u.is_bind = 0 and u.jwid != 0' )->count();
        echo "#count:";
        echo $count;
        echo "<br />";
        if( $start > $count ){
            counter_set('updateBindout',0);
        }

        $user = M()->table( array( 'xzs_user' => 'u','xzs_user_info' => 'ui' ) )
            ->field( 'u.jwid,u.jwpwd' )
            ->order( 'u.id' )
            ->where( 'u.id = ui.uid and u.is_bind = 0 and u.jwid != 0' )
            ->limit($start,$offset)
            ->select();
        $k = 0;
        foreach ( $user as $u ) {
            $u[ 'jwpwd' ] = \Think\Crypt::decrypt( $u[ 'jwpwd' ], 'jwpwd_wechat' );
            echo "jwid:";echo $u['jwid'];echo "|||";echo "jwpwd:";echo $u['jwpwd'];echo "<br />";

            $curl = new Curl( $u[ 'jwid' ], $u[ 'jwpwd' ] );
            $ret = $curl->getLogin();

            if ( $ret === true ) {
                //密码正确，就给绑定回来
                $change = array(
                    'is_bind' => 1,
                );
                M( 'user' )->where( array( 'jwid' => $u[ 'jwid' ] ) )->save( $change );

                $queue = new \SaeTaskQueue('updateGradeAuto');
                $tasks = array();
                $tasks[] = array(
                    'url' => U( 'Home/Queue/getJiaowuDataBySaeQueue',array( 'jwid' => $u['jwid'],'jwpwd' => urlencode( $u['jwpwd'] ) ) ),
                    'postdata' => "",
                    'prior' => false,
                    'options' => array(),
                );
                $queue->addTask($tasks);
                $ret = $queue->push();
                if ($ret === false)
                    throw new \Exception($queue->errno().$queue->errmsg());
                else
                    var_dump($ret);

            } else {
                $k++;
            }
        }
        echo "<br />Wrong password num:" . $k;
        counter_incr('updateBindout');
    }




    public function weisuo(){
        $queue = new \SaeTaskQueue('weisuo');

        $tasks = array();
        $tasks[] = array(
            'url' => U('Home/Queue/weisuo'),
            'postdata' => "",
            'prior' => false,
            'options' => array(),
        );
        for( $i = 0;$i < 10;$i++ ){
            $queue->addTask($tasks);
            $ret = $queue->push();
            if ($ret === false)
                throw new \Exception($queue->errno().$queue->errmsg());
            else
                var_dump($ret);
        }
    }



    /**
     *
     */
    public function updateNoDataUser(){

        $user =  M()
            ->table('xzs_user u, xzs_user_info ui')
            ->where('u.id = ui.uid and ui.sex = 0 and u.is_bind = 1')
            ->field('u.jwid as jwid, u.jwpwd as jwpwd,u.openid as openid')
            ->select();
//        p($user);
        $i = 0;
        $j = 0;
        foreach ( $user as $u ){
            $u['jwpwd'] = \Think\Crypt::decrypt($u['jwpwd'],'jwpwd_wechat');

            $queue = new \SaeTaskQueue('updateDataBySystem');

            $tasks = array();
            $tasks[] = array(
                'url' => U('Home/Queue/getJiaowuDataQueue',array('jwid' =>  $u['jwid'],'jwpwd' =>  urlencode($u['jwpwd'] ),'openid' => $u['openid'] ) ),
                'postdata' => "",
                'prior' => false,
                'options' => array(),
            );
            $queue->addTask($tasks);
            $ret = $queue->push();
            if ($ret === false){
                $j++;
                throw new \Exception($queue->errno().$queue->errmsg());
            }else{
                //计数器
                $i++;
            }
        }
        echo "{$i} users join to update,{$j} users join failed!";

    }


    /**
     * @throws \Exception
     * 更新用户考试成绩，前10000名
     */
    public function updateExam_pre(){

        $user = M('user')->where( array( 'is_bind' => 1 ) )->field('id,jwid,jwpwd')->limit(10000)->select();

        foreach( $user as $v ){
            //队列执行
            $queue = new \SaeTaskQueue('updateExam');
            $tasks = array();
            $tasks[] = array(
                'url' => U('Home/Queue/updateExamQueue',array('jwid' => $v['jwid'],'jwpwd' => $v['jwpwd'],'uid' => $v['id'])),
                'postdata' => "",
                'prior' => false,
                'options' => array(),
            );
            $queue->addTask($tasks);
            $ret = $queue->push();
            if ($ret === false)
                throw new \Exception($queue->errno().$queue->errmsg());
        }
    }

    /**
     * @throws \Exception
     * 10000个之后的
     */
    public function updateExam_next(){

        $user = M('user')->where( array( 'is_bind' => 1 ) )->field('id,jwid,jwpwd')->limit(10000,10000)->select();

        foreach( $user as $v ){
            //队列执行
            $queue = new \SaeTaskQueue('updateExam');
            $tasks = array();
            $tasks[] = array(
                'url' => U('Home/Queue/updateExamQueue',array('jwid' => $v['jwid'],'jwpwd' => $v['jwpwd'],'uid' => $v['id'])),
                'postdata' => "",
                'prior' => false,
                'options' => array(),
            );
            $queue->addTask($tasks);
            $ret = $queue->push();
            if ($ret === false)
                throw new \Exception($queue->errno().$queue->errmsg());
        }
    }



}