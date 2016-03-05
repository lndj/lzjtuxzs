<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-11-8
 * Time: 下午9:29
 */

namespace Home\Controller;


use Think\Controller;
use Lib\Wechat\WechatAuth;

/**
 * Class WarningController
 * @package Home\Controller
 * 服务运行出错回调地址!
 */
class WarningController extends Controller
{
    public function index(){

//        $openid = "o3LbBjgr9xeEfPoRRch4HuQ0PYa0";
        $action = I('get.action');

        $content = 'TaskQueue服务运行出错，队列名称：' . $action;

        sendmail('luoning@luoning.me','TaskQueue Error!',$content);
    }



}