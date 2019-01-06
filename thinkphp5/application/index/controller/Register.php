<?php
namespace app\index\controller;
use \think\Controller;

class Register extends \think\Controller
{
    public function index()
    {
        return view('register');
    }
    //注册新用户
    public function insert(){

    	
    	$pwd =$_POST['password'];
        $username = $_POST['username'];
        
    	$password = md5($pwd);
        $user_name = db('user')->where("user_name='{$username}'")->find();
         if($user_name==true){

            $this->success("用户名已被使用",'/index/login/index');

        }
        
    	$data = [
    				'user_name' => $username,
    				'user_pwd' => $password
    			]; 
    	 $ret = db('user')->insert($data);
	    if($ret==true){
		$this->success("注册成功",'/index/login/index');
		}else{
			$this->error("注册失败",'/index/register/index');
			}

    }
}