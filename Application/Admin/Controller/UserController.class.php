<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-8-16
 * Time: 下午10:58
 */

namespace Admin\Controller;
use Think\Controller;

class UserController extends CommonController{

    public function index(){



        $this->display();
    }



    /**
     * 管理员用户信息管理
     *
     */
    public function userinfo(){
        if($_POST['click'] == 1){
            $nickname = I('post.nickname');
            $email = I('post.email');

            $data = array(
                'id' => $_SESSION['uid'],
                'nickname' => $nickname,
                'email' => $email,
            );
            //实例化AdminModel
            $ret = D('admin');
            if (!$ret->create($data)){ // 验证数据
                 // 验证没有通过 输出错误提示信息
                 $this->error($ret->getError());
            }else{
                 // 验证通过 
                 $ret->save();
                 // $this->assign('closeWin',true);
                 $this->success("修改成功!");
            }
        }

        //数据
        $userinfo = M('admin')->where(array('id' => $_SESSION['uid']))->find();
        $this->assign('userinfo',$userinfo);
        $this->display();
    }

    public function changepwd(){
        $user = M("user");
        dump($user);
    }


}