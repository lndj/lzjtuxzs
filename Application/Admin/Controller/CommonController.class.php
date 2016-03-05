<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-8-16
 * Time: 下午5:25
 */

namespace Admin\Controller;


use Think\Controller;

class CommonController extends Controller{
    //禁止未登录进入后台
    public function _initialize(){
        if(!isset($_SESSION['uid']) || !isset($_SESSION['username'])){
            $this->redirect('Admin/Login/index');
        }
    }
}
