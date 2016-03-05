<?php
/**
 * Created by PhpStorm.
 * 公用函数
 * User: luoning
 * Date: 15-9-4
 * Time: 下午3:12
 */

function p($arr){
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}

function http_get($url,$param = array()){

    $url = $url.'?'.http_build_query($param);
    $ch=curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($ch,CURLOPT_REFERER,$url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $str=curl_exec($ch);
    curl_close($ch);
    return $str;
}

function sendmail($tomail,$object,$content,$frommail='947703573@qq.com',$password='lndj-512823',$smtp='smtp.qq.com',$port=25){
    $mail = new \SaeMail();
    $ret = $mail->quickSend( $tomail , $object , $content , $frommail , $password , $smtp , $port ); // 指定smtp和端口
//    $ret = $mail->quickSend("to@sina.cn", "邮件标题", "邮件内容", "smtpaccount@gmail.com", "password");
    //发送失败时输出错误码和错误信息
    if ($ret === false)
        throw new \Exception($mail->errno(), $mail->errmsg());

    $mail->clean(); // 重用此对象
    return $ret;
}

/**
 *
     * addTask( string|array $tasks, string $postdata = NULL, boolean $prior = false, array $options = array() )
    添加任务
    Parameters
    $tasks
    任务要访问的URL或以数组方式传递的多条任务。添加多条任务时的数组格式：
    <?php
    $tasks = array(
                array(
                    "url" => "/test.php", //只支持相对URL，且"/"开头
                    "postdata" => "data", //要POST的数据。可选
                    "prior" => false,  //是否优先执行，默认为false，如果设为true，则将此任务插入到队列最前面。可选
                    "options" => array('key1' => 'value1', ....),  //附加参数，可选。
                 ),
 *           );
    ?>
    $postdata
    要POST的数据。可选，且仅当$tasks为URL时有效
    $prior
    是否优先执行，默认为false，如果设为true，则将此任务插入到队列最前面。可选，且仅当$tasks为URL时有效
    $options
    附加参数，可选，且仅当$tasks为URL时有效。目前支持的参数： - delay, 延时执行，单位秒，最大延时600秒。
    Returns
    boolean

 *
 */
function task_queue($queue_name,$tasks){

    $queue = new \SaeTaskQueue($queue_name);
    $queue->addTask($tasks);
    //将任务推入队列
    $ret = $queue->push();

    //任务添加失败时输出错误码和错误信息
    if ($ret === false)
        var_dump($queue->errno(), $queue->errmsg());
}

/**
 * @param $name
 * @return mixed
 * SaeCounter服务获取计数器值
 */
function counter_get($name){
    $c = new_sae_counter();
    $ret = $c->get($name);
    return $ret;
}

/**
 * @param $name
 * @param $value
 * @return mixed
 * 重置计数器
 */
function counter_set($name,$value){
    $c = new_sae_counter();
    $ret = $c->set($name,$value);
    return $ret;
}

/**
 * @param $name
 * @param int $value
 * @return mixed
 * 计数器增长，默认为1
 */
function counter_incr($name,$value = 1){
    $c = new_sae_counter();
    $ret = $c->incr($name,$value);
    return $ret;
}

/**
 * @param $name
 * @param int $value
 * @return mixed
 * 计数器递减，默认减1
 */
function counter_decr($name,$value = 1){
    $c = new_sae_counter();
    $ret = $c->decr($name,$value);
    return $ret;
}

/**
 * @param $name array(name1,name2,.....)
 * @return mixed
 * @throws Exception
 * 同时获取多个计数器值。成功返回hash数组，以计数器名为index，失败返回false。
 */
function counter_mget($name){
    if(!is_array($name))
        throw new \Exception("参数错误");
    $c = new_sae_counter();
    $ret = $c->mget($name);
    return $ret;
}

/**
 * @return mixed
 * 获取该应用所有计数器的值。返回同mget操作。
 */
function counter_getall(){
    $c = new_sae_counter();
    $ret = $c->getall();
    return $ret;
}

/**
 * @return SaeCounter
 * 实例化SaeCounter对象
 */
function new_sae_counter(){
    try{
        $c = new \SaeCounter();
    }catch(Exception $ex){
        die($ex->getMessage());
    }
    return $c;
}

function get_time_point_code($keyword){
    switch($keyword){
        case 12:
            $code = 1;
            break;
        case 34:
            $code = 2;
            break;
        case 56:
            $code = 3;
            break;
        case 78:
            $code = 4;
            break;
        case 910:
            $code = 5;
            break;
        case '上午':
            $code = 6;
            break;
        case '下午':
            $code = 7;
            break;
        case '白天':
            $code = 8;
            break;
        case '整天':
            $code = 9;
            break;
        default:
            $code = false;
            break;
    }
    return $code;
}

function get_uid_by_jwid($jwid){
    $user = M('user');
    $uid = $user->where(array('jwid' => $jwid))->getField('id');
    if($uid)
        return $uid;
    else
        return false;
}
function get_uid_by_openid($openid){
    $user = M('user');
    $uid = $user->where(array('openid' => $openid))->getField('id');
    if($uid)
        return $uid;
    else
        return false;
}

function get_user_by_uid($uid){
    $user = M('user');
    $userinfo = $user->where(array('id' => $uid))->field('jwid,jwpwd,openid')->find();
    //数据库取出来的密码需要对称解密
    $userinfo['jwpwd'] = \Think\Crypt::decrypt($userinfo['jwpwd'],'jwpwd_wechat');
    return $userinfo;
}
function get_user_by_openid($openid){
    $user = M('user');
    $userinfo = $user->where(array('openid' => $openid))->field('id,jwid,jwpwd')->find();
    //数据库取出来的密码需要对称解密
    $userinfo['jwpwd'] = \Think\Crypt::decrypt($userinfo['jwpwd'],'jwpwd_wechat');
    return $userinfo;
}
/**
 * @param $key
 * @return bool
 * 清除SQL查询缓存
 */
function clear_sql_cache($key){

    if($key == '')
        return false;
    $ret = S($key,null);
    if($ret)
        return true;
}

/**
 * @param $copy_key 对应功能的广告文案缓存key
 * @param $url_key 对应功能的广告链接缓存key
 * @return array|bool
 * 广告位优化
 * 返回单独栏位广告
 */
function ad_set($copy_key,$url_key){

    $ret_c = S($copy_key);
    $ret_u = S($url_key);

    //如果广告为标题为空，直接返回null
    //链接广告可以为空
    if( $ret_c === false )
        return false;

    $ad = array(
        $ret_c,
        '',
        $ret_u,
        '',
    );
    return $ad;
}

/**
 * @param $item 统计项目---依赖于counter
 * @param int $type 统计形式 1:PV 2:PU 3:IP
 * 统计
 * 计量周期为1h，配合counter/cron
 */
function access_statistics($item,$type = 1){

    switch( $type ){
        case 1:
            counter_incr($item);
            break;
        case 2:
            break;

    //TODO
    }
}
/**
 * @param $key
 * @param int $expir
 * @return mixed
 * S缓存使用F持久化，获取方法
 */
function getS( $key,$expir = 0 ){

    $res = S( $key );
    if( !$res ){
        $res = F( $key );
        if( $expir === 0 )
            S( $key,$res );
        else
            S( $key,$res,$expir );
    }
    return $res;
}

/**
 * @param $key
 * @param $value
 * @param int $expir
 * @return bool
 * 设置可以F持久化的S缓存
 */
function setS( $key,$value,$expir = 0 ){

    if( $expir === 0 ){
       $res_s = S( $key,$value );
       $res_f = F( $key,$value );
    }else{
        $res_s =  S( $key,$value,$expir );
        $res_f =  F( $key,$value,$expir );
    }

    if( $res_s )
        return true;
    else
        return false;
}


function access_stastistics_pu($item){

    get_client_ip();
    //TODO

}

/**
+----------------------------------------------------------
 * 生成随机字符串
+----------------------------------------------------------
 * @param int       $length  要生成的随机字符串长度
 * @param string    $type    随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function randCode($length = 5, $type = 0) {
    $arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
    if ($type == 0) {
        array_pop($arr);
        $string = implode("", $arr);
    } elseif ($type == "-1") {
        $string = implode("", $arr);
    } else {
        $string = $arr[$type];
    }
    $count = strlen($string) - 1;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $string[rand(0, $count)];
    }
    return $code;
}

/**
 * @param $url
 * @param array $param
 * @param array $data   如果不传则为GET，传值变为POST
 * @return mixed
 * Curl请求的方法
 */
function curl_request( $url, $param = array(), $data = array() ){

    $url = $url . '?' . http_build_query( $param );

    $ch = curl_init( $url );
    curl_setopt( $ch,CURLOPT_AUTOREFERER,true );
//    curl_setopt( $ch,CURLOPT_HEADER,true );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER,true );
    curl_setopt( $ch,CURLOPT_TIMEOUT,100 );
    if( !empty( $data ) ){
//        $data = http_build_query( $data );
        curl_setopt( $ch,CURLOPT_POST,true );
        curl_setopt( $ch,CURLOPT_POSTFIELDS,$data );
    }

    $output = curl_exec( $ch );
    curl_close( $ch );
    return $output;
}

/**
 * @param $jwid
 * @return bool|mixed
 * 超级简单的负载均衡，但是实用啊...
 * 学号是偶数的，用api.lzjtuhand.com
 * 是奇数的就用job.lzjtuhand.com
 */
function getApiUrl( $jwid ){

    if ( !is_numeric( $jwid ) )
        return false;

    $result = $jwid % 2;

    //学号为偶数的情况
    if( $result === 0 ){
        return C( 'ECS_API_URL_BACKUP' );
    }

    return C( 'ECS_API_URL' );
}

/**
 * @param $jwid
 * @param $info
 * 系统运行时的错误日志...
 */
function systemErrLog( $jwid,$info ){

    $data = array(
        'jwid' => $jwid,
        'info' => $info,
        'createtime' => time(),
    );

    M( 'errorLog' )->data( $data )->add();
}


