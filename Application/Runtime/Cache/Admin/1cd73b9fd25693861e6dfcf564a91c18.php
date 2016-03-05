<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>小助手后台管理-用户资料</title>
    <meta name="description" content="小助手管理系统">
    <meta name="keywords" content="小助手,校园,创新保险,挂科险">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="icon" type="ico" href="/wxxzs/Public/i/favicon.ico">
    <link rel="stylesheet" href="/wxxzs/Public/css/amazeui.min.css"/>
    <link rel="stylesheet" href="/wxxzs/Public/css/admin.css">
    <!--[if (gte IE 9)|!(IE)]><!-->
    <script src="/wxxzs/Public/js/jquery.min.js"></script>
    <!--<![endif]-->
    <script src="/wxxzs/Public/js/amazeui.min.js"></script>
    <script src="/wxxzs/Public/js/app.js"></script>
</head>
<body>
<!--[if lte IE 9]>
<p class="browsehappy">你正在使用<strong>过时</strong>的浏览器，Amaze UI 暂不支持。 请 <a href="http://browsehappy.com/" target="_blank">升级浏览器</a>
    以获得更好的体验！</p>
<![endif]-->

<header class="am-topbar admin-header">
    <div class="am-topbar-brand">
        <strong>小助手</strong> <small>后台管理系统</small>
    </div>

    <button class="am-topbar-btn am-topbar-toggle am-btn am-btn-sm am-btn-success am-show-sm-only" data-am-collapse="{target: '#topbar-collapse'}"><span class="am-sr-only">导航切换</span> <span class="am-icon-bars"></span></button>

    <div class="am-collapse am-topbar-collapse" id="topbar-collapse">

        <?php $user = M('admin')->where(array('id' => $_SESSION['uid']))->find(); ?>

        <ul class="am-nav am-nav-pills am-topbar-nav am-topbar-right admin-header-list">
            <li><a href="javascript:;"><span class="am-icon-envelope-o"></span> 收件箱 <span class="am-badge am-badge-warning">5</span></a></li>
            <li class="am-dropdown" data-am-dropdown>
                <a class="am-dropdown-toggle" data-am-dropdown-toggle href="javascript:;">
                    <span class="am-icon-users"></span><?php echo ($user["nickname"]); ?> <span class="am-icon-caret-down"></span>
                </a>
                <ul class="am-dropdown-content">
                    <li><a href="#"><span class="am-icon-user"></span> 资料</a></li>
                    <li><a href="#"><span class="am-icon-cog"></span> 设置</a></li>
                    <li><a href="<?php echo U('Index/loginout');?>"><span class="am-icon-power-off"></span> 退出</a></li>
                </ul>
            </li>
            <li class="am-hide-sm-only"><a href="javascript:;" id="admin-fullscreen"><span class="am-icon-arrows-alt"></span> <span class="admin-fullText">开启全屏</span></a></li>
        </ul>
    </div>
</header>


  <!-- sidebar start -->
<div class="am-cf admin-main">
    <!-- sidebar start -->
    <div class="admin-sidebar am-offcanvas" id="admin-offcanvas">
        <div class="am-offcanvas-bar admin-offcanvas-bar">
            <ul class="am-list admin-sidebar-list">
                <li><a href="<?php echo U('Index/index');?>"><span class="am-icon-home"></span> 首页</a></li>
                <li class="admin-parent">
                    <a class="am-cf" data-am-collapse="{target: '#collapse-nav'}"><span class="am-icon-file"></span> 用户设置 <span class="am-icon-angle-right am-fr am-margin-right"></span></a>
                    <ul class="am-list am-collapse admin-sidebar-sub am-in" id="collapse-nav">
                        <li><a href="<?php echo U('User/userinfo');?>" class="am-cf"><span class="am-icon-check"></span> 个人资料<span class="am-icon-star am-fr am-margin-right admin-icon-yellow"></span></a></li>
                        <!--<li><a href="admin-help.html"><span class="am-icon-puzzle-piece"></span> 帮助页</a></li>-->
                        <!--<li><a href="admin-gallery.html"><span class="am-icon-th"></span> 相册页面<span class="am-badge am-badge-secondary am-margin-right am-fr">24</span></a></li>-->
                        <!--<li><a href="admin-log.html"><span class="am-icon-calendar"></span> 系统日志</a></li>-->
                        <!--<li><a href="admin-404.html"><span class="am-icon-bug"></span> 404</a></li>-->
                    </ul>
                </li>

                <li class="admin-parent">
                    <a class="am-cf" data-am-collapse="{target: '#usercenter-nav'}"><span class="am-icon-file"></span> 个人中心管理 <span class="am-icon-angle-right am-fr am-margin-right"></span></a>
                    <ul class="am-list am-collapse admin-sidebar-sub am-in" id="usercenter-nav">
                        <li><a href="<?php echo U('Ucenter/index');?>" class="am-cf"><span class="am-icon-check"></span> 配置列表<span class="am-icon-star am-fr am-margin-right admin-icon-yellow"></span></a></li>
                        <li><a href=""><span class="am-icon-puzzle-piece"></span>添加配置</a></li>
                    </ul>
                </li>
                <li><a href="<?php echo U('WechatMenu/index');?>"><span class="am-icon-list"></span> 自定义菜单设置</a></li>
                <li><a href="<?php echo U('WechatUser/index');?>"><span class="am-icon-users"></span> 关注者列表</a></li>
                <li><a href="admin-form.html"><span class="am-icon-pencil-square-o"></span> 表单</a></li>
                <li><a href="#"><span class="am-icon-sign-out"></span> 注销</a></li>
            </ul>

            <div class="am-panel am-panel-default admin-sidebar-panel">
                <div class="am-panel-bd">
                    <p><span class="am-icon-bookmark"></span> 公告</p>
                    <p>时光静好，与君语；细水流年，与君同。—— Amaze UI</p>
                </div>
            </div>

            <div class="am-panel am-panel-default admin-sidebar-panel">
                <div class="am-panel-bd">
                    <p><span class="am-icon-tag"></span> wiki</p>
                    <p>Welcome to the Amaze UI wiki!</p>
                </div>
            </div>
        </div>
    </div>

  <!-- sidebar end -->

  <!-- content start -->
  <div class="admin-content">
    <div class="am-cf am-padding">
      <div class="am-fl am-cf"><strong class="am-text-primary am-text-lg">个人资料</strong> / <small>Personal information</small></div>
    </div>
    <hr/>

    <div class="am-g">

      <div class="am-u-sm-12 am-u-md-4 am-u-md-push-8">
        <div class="am-panel am-panel-default">
          <div class="am-panel-bd">
            <div class="am-g">
              <div class="am-u-md-4">
                <img class="am-img-circle am-img-thumbnail" src="http://s.amazeui.org/media/i/demos/bw-2014-06-19.jpg?imageView/1/w/200/h/200/q/80" alt=""/>
              </div>
              <div class="am-u-md-8">
                <p>你可以使用<a href="#">gravatar.com</a>提供的头像或者使用本地上传头像。 </p>
                <form class="am-form">
                  <div class="am-form-group">
                    <input type="file" id="user-pic">
                    <p class="am-form-help">请选择要上传的文件...</p>
                    <button type="button" class="am-btn am-btn-primary am-btn-xs">保存</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="am-panel am-panel-default">
          <div class="am-panel-bd">
            <div class="user-info">
              <p>用户状态</p>
              <div class="am-progress am-progress-sm">
                <div class="am-progress-bar" style="width: 60%"></div>
              </div>
              <p class="user-info-order">当前状态：
              <?php if($userinfo["status"] == 1): ?><strong style="color: green;">正常</strong>
              <?php elseif($userinfo["status"] == 0): ?>
              <strong style="color: red">已锁定</strong>
              <?php else: ?>
              <strong>其他</strong><?php endif; ?>
              <br />登录时间：<strong><?php echo (date('Y-m-d H:i:s',$userinfo["logintime"])); ?></strong>
              <br />登录IP：<strong><?php echo ($userinfo["loginip"]); ?></strong></p>
            </div>
            <div class="user-info">
              <p>信用信息</p>
              <div class="am-progress am-progress-sm">
                <div class="am-progress-bar am-progress-bar-success" style="width: 80%"></div>
              </div>
              <p class="user-info-order">信用等级：正常当前 信用积分：<strong>80</strong></p>
            </div>
          </div>
        </div>

      </div>

      <div class="am-u-sm-12 am-u-md-8 am-u-md-pull-4">
        <form class="am-form am-form-horizontal" method="POST" action="<?php echo U('User/userinfo');?>">
          <div class="am-form-group">
            <label for="user-name" class="am-u-sm-3 am-form-label">姓名 / Name</label>
            <div class="am-u-sm-9">
              <input type="text" id="nickname" name="nickname" value="<?php echo ($userinfo["nickname"]); ?>">
              <small>管理员姓名/昵称</small>
            </div>
          </div>

          <div class="am-form-group">
            <label for="user-email" class="am-u-sm-3 am-form-label">电子邮件 / Email</label>
            <div class="am-u-sm-9">
              <input type="email" id="email" name="email" value="<?php echo ($userinfo["email"]); ?>">
              <small>邮箱你懂得...</small>
            </div>
          </div>

            <!-- <div class="am-form-group">
               <label for="user-phone" class="am-u-sm-3 am-form-label">电话 / Telephone</label>
               <div class="am-u-sm-9">
                 <input type="email" id="user-phone" placeholder="输入你的电话号码 / Telephone">
               </div>
             </div>

             <div class="am-form-group">
               <label for="user-QQ" class="am-u-sm-3 am-form-label">QQ</label>
               <div class="am-u-sm-9">
                 <input type="email" id="user-QQ" placeholder="输入你的QQ号码">
               </div>
             </div>

             <div class="am-form-group">
               <label for="user-weibo" class="am-u-sm-3 am-form-label">微博 / Twitter</label>
               <div class="am-u-sm-9">
                 <input type="email" id="user-weibo" placeholder="输入你的微博 / Twitter">
               </div>
             </div>

             <div class="am-form-group">
               <label for="user-intro" class="am-u-sm-3 am-form-label">简介 / Intro</label>
               <div class="am-u-sm-9">
                 <textarea class="" rows="5" id="user-intro" placeholder="输入个人简介"></textarea>
                 <small>250字以内写出你的一生...</small>
               </div>
             </div>-->

          <div class="am-form-group">
            <div class="am-u-sm-9 am-u-sm-push-3">
                <input type="hidden" name="click" value=1 />
              <button type="submit" class="am-btn am-btn-primary">保存修改</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- content end -->

</div>
<a href="#" class="am-show-sm-only admin-menu" data-am-offcanvas="{target: '#admin-offcanvas'}">
    <span class="am-icon-btn am-icon-th-list"></span>
</a>

<footer>
    <hr>
    <p class="am-padding-left">© 2015 AllRights Reserved. Licensed By MIT.</p>
</footer>

<!--[if lt IE 9]>
<script src="http://libs.baidu.com/jquery/1.11.1/jquery.min.js"></script>
<script src="http://cdn.staticfile.org/modernizr/2.8.3/modernizr.js"></script>
<script src="/wxxzs/Public/js/amazeui.ie8polyfill.min.js"></script>
<![endif]-->


</body>
</html>