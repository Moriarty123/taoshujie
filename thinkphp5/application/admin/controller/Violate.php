<?php

namespace app\admin\controller;

use app\admin\controller\Common;

use think\Controller;
use think\Db;
use think\Log;

class Violate extends Common 
{
	public function index()
	{
		return $this->fetch('index');
	}

	//违规用户处理列表
	public function violateList()
	{
		Log::record('显示违规用户处理列表', 'notice');

		$violateList = Db::table('violation')
		->alias('a')
		->join('user b', 'a.user_id = b.user_id')
		->paginate(15);

		$violateNumber = Db::table('violation')
		->alias('a')
		->join('user b', 'a.user_id = b.user_id')
		->count();

		$this->assign('violateList', $violateList);
		$this->assign('violateNumber', $violateNumber);

		

		return $this->fetch('violateList');
	}

	//添加违规页面
	public function addPage()
	{	
		Log::record('添加新违规记录页面', 'notice');

		$user = Db::table('user')->select();

		$this->assign('user', $user);

		return $this->fetch('violateAdd');
	}

	//修改违规页面
	public function editPage()
	{
		Log::record('修改违规记录页面', 'notice');

		// dump($_GET);
		$violate_id = input('get.violate_id');

		$where = "violate_id = {$violate_id}";
		$violate = Db::table('violation')
		->where($where)
		->alias('a')
		->join('user b', 'a.user_id = b.user_id')
		->find(); 

		$this->assign('violate', $violate);

		return $this->fetch('violateEdit');
	}

	//添加违规
	public function violateAdd()
	{
		// dump($_POST);, 
		log::record('添加新违规记录操作', 'notice');
		//1.
		$user_id 		= input('post.user_id');
		$punish_time 	= input('post.punish_time');
		$punish_case 	= input('post.punish_case');
		$punish_way 	= input('post.punish_way');

		//2.
		$data = [
			'user_id' => $user_id,
			'punish_time'=> strtotime($punish_time),
			'punish_case' => $punish_case,
			'punish_way' => $punish_way,
			'add_time' => time()
		];
		
		if($punish_way == "封号") {
			Db::table('user')->where("user_id = {$user_id}")->update(['Sensitivewords' => 1]);
		}
		else if($punish_way == "禁言"){
			Db::table('user')->where("user_id = {$user_id}")->setInc('Sensitivewords');
		}
		

		$ret = Db::table('violation')->insert($data);

		if($ret == false)
		{
			$this->error('添加新违规失败！', '/admin/violate/violateList');
		}

		$this->success('添加新违规成功！', '/admin/violate/violateList');

	}

	//模糊查找
	public function violateSearch()
	{
		log::record('模糊搜索违规用户', 'notice');
		// dump($_POST);
		$search = input('post.search');

		if(!empty($search)) {
			session('violatesearch', $search);
			$where['user_name'] = array('like','%'.$search.'%');//封装模糊查询 赋值到数组	
		}
		else 
		{
			$search = session('violatesearch');
			$where['user_name'] = array('like','%'.$search.'%');	
		}

		$violateList = Db::table('violation')
		->where($where)
		->alias('a')
		->join('user b', 'a.user_id = b.user_id')
		->paginate(15);

		$violateNumber = Db::table('violation')
		->where($where)
		->alias('a')
		->join('user b', 'a.user_id = b.user_id')
		->count();

		$this->assign('violateList', $violateList);
		$this->assign('violateNumber', $violateNumber);

		// Log::record('显示违规用户处理列表', 'notice');

		return $this->fetch('violateList');

	}

	//删除违规记录
	public function violateDelete()
	{
		log::record('删除违规记录', 'notice');

		$violate_id = input('get.violate_id');
		$where = "violate_id = {$violate_id}";

		// dump($where);

		//暂时取消外链，删除后恢复
		Db::query('SET FOREIGN_KEY_CHECKS = 0;');
		$ret = Db::table('violation')->where($where)->delete();
		Db::query('SET FOREIGN_KEY_CHECKS = 1;');

		log::record('删除违规记录，暂时取消外链，删除后恢复', 'warning');

		if ($ret == false) {
			$this->error('删除违规记录失败！', '/admin/violate/violateList');
		}

		$this->success('删除违规记录成功！', '/admin/violate/violateList');
	}

	//批量删除
	public function checkedViolateDelete()
	{
		Log::record('批量删除违规记录', 'notice');
		// dump($_POST);
		//TP5的post方法不能提交数组，在表单name添加/a表示要提交有关数组，获取时同样要添加/a
		$ids = input('post.ids/a');

		// dump($ids);
		
		foreach ($ids as $key => $id) {
			$where = "violate_id = {$id}";
			//暂时取消外链，删除后恢复
			Db::query('SET FOREIGN_KEY_CHECKS = 0;');
			$ret = Db::table('violation')->where($where)->delete();
			Db::query('SET FOREIGN_KEY_CHECKS = 1;');
		}

		$this->success('删除用户成功！', '/admin/violate/violateList');

	}

	//修改违规记录
	public function violateEdit()
	{
		// dump($_POST);, 
		log::record('修改违规记录操作', 'notice');
		//1.
		$violate_id 		= input('post.violate_id');
		$punish_time 	= input('post.punish_time');
		$punish_case 	= input('post.punish_case');
		$punish_way 	= input('post.punish_way');

		$where = "violate_id = {$violate_id}";
		//2.
		$data = [
			'punish_time'=> strtotime($punish_time),
			'punish_case' => $punish_case,
			'punish_way' => $punish_way,
			// 'add_time' => time()
		];

		$ret = Db::table('violation')->where($where)->update($data);

		if($ret == false)
		{
			$this->error('修改违规记录失败！', '/admin/violate/violateList');
		}

		$this->success('修改违规记录成功！', '/admin/violate/violateList');

	}

}