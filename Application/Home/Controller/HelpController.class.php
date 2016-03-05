<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-11-24
 * Time: 下午10:47
 */

namespace Home\Controller;


use Think\Controller;

class HelpController extends Controller
{
    public function index(){
        $h = M('help');

        $this->help = $h->where(array('status' => 1))->select();
        counter_incr('help_page');
        $this->display();
    }
}