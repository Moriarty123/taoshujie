<?php
namespace app\index\controller;
use \think\Controller;

class Personalscomment extends \think\Controller
{
	//个人中心的求购留言列表
    public function index()
    {
    	$sid = session('userid');
        $ret = db('scomment')->alias('a')->join('inquiry_book b',"a.user_id={$sid} and a.sbook_id=b.inquiry_id")->select();
   		
        $this->assign('ret',$ret);

        return view('scomment');
    }
    //删除求购留言
      public function delete(){
        $sid = $_GET['id'];
        $ret = db('scomment')->where("scomment_id={$sid}")->delete();
        $this->redirect('/index/personalscomment/index');
    }
}
