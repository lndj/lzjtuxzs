<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-8-16
 * Time: 下午3:29
 */

namespace Admin\Controller;
use Think\Controller;

class LoginController extends  Controller{

    public function index(){
        $this->display();
    }

    public function login(){

        if(!IS_POST) $this->error('地址不存在','index',3);
        $username = I('post.username');
        $password = I('post.password','','md5');

        $User = M('admin');
        $map['username'] = $username;
        $result = $User->where($map)->find();

        if(!$User || $password != $result['password']) $this->error('用户名或者密码错误!');
        if($result['status'] == 0) $this->error('用户被锁定,请联系超级管理员!');

        $data = array(
           'id' => $result['id'],
           'logintime' =>time(),
           'loginip' => get_client_ip(),
        );
//        dump($data);
        $User->save($data);

        session('uid',$result['id']);
        session('username',$result['username']);
        session('logintime',date('Y-m-d H:i:s',$result['logintime']));
        session('loginip',$result['loginip']);
//        $this->success("登录成功",U('Index/index'));
        $this->redirect('Index/index') ;
    }
}
