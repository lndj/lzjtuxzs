<?php
namespace Admin\Controller;
use Think\Controller;

class UcenterController extends CommonController {

    /**
     * 个人中心配置列表
     */
    public function index(){
        $conf = M('config');
//        $this->defautHead = $conf->where(array('item' => 'DEFAULT_HEADIMGURL'))->order('id')->select();
//        $this->defautHeadBackgroung = $conf->where(array('item' => 'DEFAULT_HEADBACKGROUNG'))->order('id')->select();

        $condition['item'] = array(array('like','%DEFAULT_HEADIMGURL%'),'DEFAULT_HEADBACKGROUNG','or');
        $this->defautHead = $conf->where($condition)->select();
        $this->configCount = count($this->defautHead);
        $this->display();
    }

    /**
     * 个人中心配置项修改页视图
     */
    public function modify(){
        $id = I('get.id','','intval');
        if($id == 0) $this->error('缺少配置项ID！');

        $conf = M('config');
        $this->setting = $conf->where(array('id' => $id))->find();
        $this->display();
    }

    /**
     * 配置处理修改
     */
    public function modifyHandle(){
        if(!IS_POST) $this->error('访问错误！');

        $setting = I('post.');
        $conf = M('config');
        $setting['createtime'] = date("Y-m-d H:i:s");
        if($conf->data($setting)->save()){
            $this->success('修改成功','index');
        }else{
            $this->error('修改失败，请重新修改！');
        }
    }

    /**
     * 配置添加
     */
    public function settingAdd(){
        $this->display();

    }

    /**
     * 配置添加处理
     */
    public function settingAddHandle(){
        if(!IS_POST) $this->error('访问错误！');
        $setting = I('post.');
        $setting['createtime'] = date("Y-m-d H:i:s");
        $conf = M('config');
        if($conf->data($setting)->add()){
            $this->success('添加成功','index');
        }else{
            $this->error('添加失败，请重新添加！');
        }

    }

    /**
     * 配置删除
     */
    public function delete(){
        if(!IS_AJAX) $this->error('访问错误！');
        $err = array(
            'msg' => 'Parameter error!',
        );

        $id =I('post.id','','intval');
        if($id == 0) $this->ajaxReturn($err);
        if(I('post.status') == 1){
            $conf = M('config');
            $conf->where(array('id' => $id))->delete();
            $data = array(
                'status' => 1,
                'id' => $id,
            );
            $this->ajaxReturn($data);
        }else{
            $this->ajaxReturn($err);
        }

    }
}