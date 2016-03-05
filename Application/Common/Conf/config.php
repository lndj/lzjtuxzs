<?php
return array(
    //'配置项'=>'配置值'
    'DEFAULT_MODULE' => 'Home',
    'MODULE_DENY_LIST' => array('Common'),
    'MODULE_ALLOW_LIST' => array('Home','Admin'),
    'SYSTEME_DOMAIN' => 'http://www.lzjtuhand.com',

    'DB_TYPE'   => 'mysqli', // 数据库类型
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => 'wxxzs', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'luoning',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'xzs_', // 数据库表前缀
    'DB_CHARSET' =>  'utf8',      // 数据库编码默认采用utf8

    //注册一个自动加载命名空间
    'AUTOLOAD_NAMESPACE' => array(
        'Lib' => APP_PATH.'Lib',
    ),
    //SQL解析缓存
    'DB_SQL_BUILD_CACHE' => true,
    // SQL 缓存的队列长度
    'DB_SQL_BUILD_LENGTH' => 20,

    'WEIXIN'=>array(
        'TOKEN'  => 'weixin',
        'APPID'  => 'wxd653c72470f4d162',
        'SECRET' => '127a208103fd31c7bc50968b8552f3b2',
        'AESKEY' => 'De8QfNB6VogPoxeJVJoVSPkzrpwSrUxejdCbHdCTYKu',
    ),
    'JIAOWU_LOGIN_URL' => 'http://xuanke.lzjtu.edu.cn/default_ysdx.aspx',
    'JIAOWU_INDEX_URL' => 'http://xuanke.lzjtu.edu.cn/',

//    'JIAOWU_LOGIN_URL' => 'http://lnanddj.xicp.net/default_ysdx.aspx',
//    'JIAOWU_INDEX_URL' => 'http://lnanddj.xicp.net/',
    //80端口映射走香港服务器，非80速度更快
//    'JIAOWU_LOGIN_URL' => 'http://lzjtuxzs.wicp.net:17322/default_ysdx.aspx',
//    'JIAOWU_INDEX_URL' => 'http://lzjtuxzs.wicp.net:17322/',

//    'JIAOWU_LOGIN_URL' => 'http://lzjtuapi.lzjtuhand.com:22037/default_ysdx.aspx',
//    'JIAOWU_INDEX_URL' => 'http://lzjtuapi.lzjtuhand.com:22037/',

    'ADMIN_JWID' => 201201148,
    'ADMIN_JWPWD' => 'luowei2008',

    'ECS_API_URL' => 'http://job.lzjtuhand.com/api.php',

    'ECS_API_URL_BACKUP' => 'http://api.lzjtuhand.com/api.php',

    'ECS_API_TOKEN' => 'jiaowu',
);
