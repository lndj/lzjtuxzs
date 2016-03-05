<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-11-11
 * Time: 下午6:46
 */

namespace Admin\Controller;

use Think\Controller;
use Lib\Tool\Page;
use Lib\Jiaowu\Curl;

class JiaowuController extends CommonController
{
    /**
     * 教务文案设置
     */
    public function index(){
        $this->display();
    }

    /**
     * 学期设置
     */
    public function term(){
        $this->display();
    }

    /**
     * 教务用户管理
     * 自己写分页
     */
    public function user(){

        $this->page = I('get.p',1,'intval');

        //使得显示全部按钮不可用
        $this->show_all_btn = 'am-disabled';


        if( isset( $_GET['search_jwid'] ) ){
            $map = 'and u.jwid = '.I('get.search_jwid','','intval');
            $this->show_all_btn = '';
        }


        //搜索框为空的时候，直接显示全部内容
        if( I('get.search_jwid','','intval') == '')
            $map = '';
        if( isset( $_GET['type']) && $_GET['type'] == 'all')
            $map = '';

        /*自己写的分页代码，配合View层*/
        /*$this->pageSize = 10;

        //页面显示偏移量
        $this->offset = 4;
        //总记录数
        $this->dataCount = M('userInfo')->count();
        //总页数
        $this->totlePage = ceil($this->dataCount / $this->pageSize);
        //上一页
        $this->prePage = ($this->page > 1) ? $this->page - 1 : 1;
        //下一页
        $this->nextPage = ($this->page < $this->totlePage) ? $this->page + 1 : $this->totlePage;

        //页面起始值
        $start = $this->page - $this->offset;
        $this->start = ( $start > 0 ) ? $start : 1;

        //页面结束值
        $end = $this->page + $this->offset;
        $this->end = ($end > $this->totlePage) ? $this->totlePage : $end;

        //保证在最后及最前几页的时候，页面始终显示相同多的页码数字
        if( $this->page + $this->offset > $this->totlePage )
            $this->start = $this->start - ( $this->page + $this->offset - $this->end );
        if( $this->page - $this->offset < 1 )
            $this->end = $this->end + ( $this->offset - $this->page + 1 );*/



        $pageSize = 15;
//        $dataCount = M('userInfo')->count();
        $dataCount = M()->table(array('xzs_user' => 'u','xzs_user_info' => 'ui'))->where('u.id = ui.uid '.$map)->count();
        $this->dataCount = $dataCount;
        $ret = M()->table(array('xzs_user' => 'u','xzs_user_info' => 'ui'))
            ->field('u.id,u.jwid,u.openid,u.is_bind,u.bind_time,ui.realname,ui.sex,ui.jw_picture,ui.profession,ui.createtime')
            ->where('u.id = ui.uid '.$map)->page($this->page,$pageSize)->order('u.bind_time desc')->select();
//        p($ret);
        $this->data = $ret;

        //分页
        $p = new Page($dataCount,$pageSize);
        $p->setConfig('prev','上一页');
        $p->setConfig('next','下一页');
        $p->setConfig('first','首页');
        $p->setConfig('last','末页');
        //关闭最后页显示总数字
        $p->lastSuffix = false;
        $p->rollPage = 9;
        $this->pagination = $p->show();


        $this->display();
    }

    /**
     * 分类的用户列表
     */
    public function typeUser(){

        $type = I( 'get.type','binded' );

        switch ( $type ){
            case 'binded':
                $map = 'and u.is_bind = 1';
                $this->binded_disable = 'am-disabled';
                break;
            case 'bindout':
                $map = 'and u.is_bind = 0 and u.jwid != 0';
                $this->bindout_disable = 'am-disabled';
                break;
            default:
                #
                break;
        }

        $pageSize = 15;
        $pageNow = I('get.p',1,'intval');

        $dataCount = M()->table( array( 'xzs_user' => 'u','xzs_user_info' => 'ui' ) )->where( 'u.id = ui.uid ' . $map )->count();
        $this->dataCount = $dataCount;
        $this->data = M()->table( array( 'xzs_user' => 'u','xzs_user_info' => 'ui' ) )
            ->field( 'u.id,u.jwid,u.openid,u.is_bind,u.bind_time,ui.realname,ui.sex,ui.jw_picture,ui.profession,ui.createtime' )
            ->where( 'u.id = ui.uid ' . $map )->page( $pageNow,$pageSize )->order( 'u.bind_time desc' )->select();

        //分页
        $p = new Page($dataCount,$pageSize);
        $p->setConfig('prev','上一页');
        $p->setConfig('next','下一页');
        $p->setConfig('first','首页');
        $p->setConfig('last','末页');
        //关闭最后页显示总数字
        $p->lastSuffix = false;
        $p->rollPage = 9;
        $this->pagination = $p->show();

        $this->dataCount = $dataCount;


        $this->display();
    }

    /**
     * 用户详细资料
     */
    public function userDetail(){
        $uid = I('get.uid','','intval');
        if($uid == '' || $uid === 0)
            $this->error('缺少UID!');

        $userinfo = M()->table(array('xzs_user' => 'u','xzs_user_info' => 'ui'))
            ->field('u.id,u.jwid,u.openid,ui.realname,ui.sex,ui.birthday,ui.nation,ui.id_num,ui.political,ui.fromarea,ui.college,ui.profession,ui.class,ui.school_system,ui.degree,ui.jw_picture')
            ->where('u.id = ui.uid and u.id = '.$uid)->find();
        $this->assign('userinfo',$userinfo);
        $this->display();

    }

    /**
     * 显示用户教务数据
     */
    public function userJiaowuData(){
        $uid = I('get.uid','','intval');
        if($uid == '' || $uid === 0)
            $this->error('缺少UID!');
        $map = array('uid' => $uid);

        $this->grade = M('grade')->where($map)->limit(80)->select();

        $this->exam = M('exam')->where($map)->limit(15)->select();


        $schedule = M('schedule')->where( $map )->limit(1)->find();
        $this->mon = explode( '#',$schedule['mon'] );
        $this->tues = explode( '#',$schedule['tues'] );
        $this->wed = explode( '#',$schedule['wed'] );
        $this->thur = explode( '#',$schedule['thur'] );
        $this->fri = explode( '#',$schedule['fri'] );
        $this->sat = explode( '#',$schedule['sat'] );
        $this->sun = explode( '#',$schedule['sun'] );

        $this->makeup = M('makeupExam')->where($map)->limit(15)->select();

        $this->display();
    }

    /**
     * 更新教务数据按钮处理
     */
    public function updateUserData(){
        if(!IS_AJAX)
            $this->error('访问错误');

        $uid = I('post.id','','intval');
        if( $uid == '' || $uid == 0 )
            $this->ajaxReturn(array('status' => 0));

        $user = M('user')->where(array( 'id' => $uid ))->field('jwid,jwpwd,openid')->limit(1)->find();
        $jwpwd = \Think\Crypt::decrypt( $user['jwpwd'],'jwpwd_wechat');

        //查看密码
        if(isset($_POST['type']) && $_POST['type'] == 'jwpwd')
            $this->ajaxReturn(array('status' => 1,'jwpwd' => $jwpwd,'jwid' => $user['jwid'] ));

        $queue = new \SaeTaskQueue('getJiaowuDataBackup');

        $tasks = array();
        $tasks[] = array(
            'url' => U('Home/Queue/getJiaowuDataQueue',array('jwid' => $user['jwid'],'jwpwd' =>urlencode($jwpwd),'openid' => $user['openid'],'type' => 'admin' )),
            'postdata' => "",
            'prior' => false,
            'options' => array(),
        );
        $queue->addTask($tasks);
        $ret = $queue->push();
        if ($ret === false)
            throw new \Exception($queue->errno().$queue->errmsg());

        $this->ajaxReturn(array('status' => 1));
    }

    /**
     * 检测密码是否正确
     */
    public function checkPassword(){
        if(!IS_AJAX)
            $this->error('访问错误');
        $uid = I('post.uid','','intval');

        if( $uid == '' || $uid == 0 ){
            $this->ajaxReturn(array('status' => 0,'errmsg' => $uid ));
        }


        $user = M('user')->where(array( 'id' => $uid ))->field('jwid,jwpwd')->find();
        $jwpwd = \Think\Crypt::decrypt( $user['jwpwd'],'jwpwd_wechat');

        $curl = new Curl($user['jwid'],$jwpwd);
        $ret = $curl->getLogin();
        //如果登陆失败了，再试试管理员登陆，确认是不是网络问题
        if( $ret === false ){

            $curl_admin = new Curl( C('ADMIN_JWID'),C('ADMIN_JWPWD'));
            $ret = $curl_admin->getLogin();
            if( $ret === true ){
                //确认不是教务网的问题,密码确实错误
                $this->ajaxReturn(array('status' => 1,'is_true' => 0,'errmsg' => 'The password is wrong!'));

            }else{
                //教务网有问题
                $this->ajaxReturn(array('status' => -1,'errmsg' => 'The Jiaowu net is bad!'));
            }
        }else{
            //密码正确
            $this->ajaxReturn(array('status' => 1,'is_true' => 1,'errmsg' => 'The password is right!'));
        }

    }

    public function handleBindOut(){
        if(!IS_AJAX)
            $this->error('访问错误');
        $uid = I('post.uid','','intval');

        if( $uid === 0 )
            $this->ajaxReturn( array( 'status' => -1,'errmsg' => 'The uid is wrong!' ) );

        if( M('user')->where( array( 'id' => $uid ) )->data( array( 'is_bind' => 0 ) )->save() ){
            $this->ajaxReturn( array( 'status' => 1,'errmsg' => 'ok' ) );
        }

        $this->ajaxReturn( array( 'status' => 0,'errmsg' => 'Save failed!' ) );
    }



    public function handleCopy(){
        $content = I('post.content','','htmlspecialchars');
        $key = I('post.key');
        if($content == "")
            $content = null;
        $ret = setS($key,$content);
        if($ret)
            $this->ajaxReturn(array('status' => 1,'content' => $content));
    }

    /**
     * 处理设置学年学期
     */
    public function handleTerm(){
        $term_now = I('post.term_now');
        $school_year_now = I('post.school_year_now');

        $ret1 = setS('school_year_now',$school_year_now);
        $ret2 = setS('term_now',$term_now);
//        sleep(1);
        if($ret1 && $ret2)
            $this->ajaxReturn(array('status' => 1));
        else
            $this->ajaxReturn(array('status' => $ret1 . $ret2));
    }

    /**
     * 处理课表学年学期的设置
     */
    public function handleTermSchedule(){

        $schedule_term = I('post.SCHEDULE_TERM');
        $schedule_school_year = I('post.SCHEDULE_SCHOOL_YEAR');

        $ret1 = setS('SCHEDULE_SCHOOL_YEAR',$schedule_school_year);
        $ret2 = setS('SCHEDULE_TERM',$schedule_term);
//        sleep(1);
        if($ret1 && $ret2)
            $this->ajaxReturn(array('status' => 1));
        else
            $this->ajaxReturn(array('status' => 0));

    }

    /**
     * 处理补考的学年学期的设置
     */
    public function handleTermMakeup(){

        $schedule_term = I('post.MAKEUP_TERM');
        $schedule_school_year = I('post.MAKEUP_SCHOOL_YEAR');

        $ret1 = setS('MAKEUP_SCHOOL_YEAR',$schedule_school_year);
        $ret2 = setS('MAKEUP_TERM',$schedule_term);
//        sleep(1);
        if($ret1 && $ret2)
            $this->ajaxReturn(array('status' => 1,'info' => $schedule_term));
        else
            $this->ajaxReturn(array('status' => $ret1 . $ret2));

    }




}