<?php
namespace Admin\Model;
use Think\Model;
class AdminModel extends Model{
   protected $_validate = array(
     array('nickname','require','姓名必须！'), //默认情况下用正则进行验证
     array('email','email','邮件格式不对'), // 验证确认密码是否和密码一致
   );
}
?>