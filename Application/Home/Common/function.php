<?php

function curl_get($url){

    $ch=curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch,CURLOPT_REFERER,$url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $str=curl_exec($ch);
    curl_close($ch);
    return $str;
}

/**
 * 用户反馈功能中，将value值转换为文本显示给用户
 */
function feedback_type_trans($type){
    if($type == 'jiaowu'){
        $type = '教务功能';
    }elseif($type == 'cooperation'){
        $type = '商务合作';
    }elseif($type == 'gouda'){
        $type = '勾搭功能';
    }elseif($type == 'else'){
        $type = '其他';
    }
    return $type;
}

/**
 * @param $user 访问用户
 * @param $time 间隔时间段 单位：s
 * 服务限制请求次数限制，$time时间内只能访问1次
 */
function queue_request_limit($user,$time){

    $_limit = S($user);
    if(!$_limit){
        //说明通过限制
        S($user,1,$time);
        return true;
    }
    return false;
}