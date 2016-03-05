<?php
/**
 * Created by PhpStorm.
 * User: ln_dj
 * Date: 2016/1/15
 * Time: 23:22
 */

namespace Admin\Controller;


class ErrLogController extends CommonController
{

    public function index(){

        $this->data = M( 'errorLog' )->order( 'createtime desc' )->limit( 100 )->select();

        $this->display();
    }

}