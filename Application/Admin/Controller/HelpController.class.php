<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-11-24
 * Time: 下午11:35
 */

namespace Admin\Controller;


use Think\Controller;

class HelpController extends CommonController
{
    public function index(){

        $h = M('help');
        $map = array('status' => 1);
        $this->help = $h->where($map)->select();
        $this->count = $h->where($map)->count();
        $this->display();
    }

    public function edit(){
        $id = I('get.id','','intval');
        $h = M('help');
        $map = array('id' => $id);
        $this->help = $h->where($map)->find();

        $this->display();
    }
    /**
     * 编辑处理
     */
    public function editHandle(){
        if(!IS_POST) $this->error('访问错误！');

        $help = I('post.');
        $h = M('help');
        $help['createtime'] = time();
        if($h->data($help)->save()){
            $this->success('修改成功','index');
        }else{
            $this->error('修改失败，请重新修改！');
        }
    }


    /**
     * 新增帮助内容
     */
    public function add(){
        $title = I('post.title');
        $content = I('post.content');
        $tags = I('post.tags');
        $data = array(
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
            'createtime' => time(),
        );

        $h = M('help');
        if($h->data($data)->add())
            $this->ajaxReturn(array('status' => 1));
        else
            $this->ajaxReturn(array('status' => 0));
    }

    /**
     * 删除
     */
    public function delete(){
        if(!IS_AJAX) $this->error('访问错误！');
        $err = array(
            'msg' => 'Parameter error!',
        );

        $id =I('post.id','','intval');
        if($id == 0) $this->ajaxReturn($err);
        if(I('post.status') == 1){
            $h = M('help');
            $h->where(array('id' => $id))->delete();
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