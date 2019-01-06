<?php
namespace app\index\controller;

use think\Db;
use think\Controller;

class Refund extends Controller
{
	public function refund()
	{
		//获取退款数据
		$order_id = input('get.order_id');

		$where = "order_id = {$order_id}";
		$order = Db::table('shoporder')->where($where)->find();



		//调用支付宝接口
		$pay = new Pay();

        $no 		= $order['order_id'];
        //$trade_no 	= $order['trade_no'];
        $amount 	= $order['order_price'];
        // $reason		= $order['reason'];
        // $request_no	= $order['request_no'];


        $trade_no = null;

        $iscar = session('iscar');

        if ($iscar == true) {
        	$trade_no = $order['trade_no'];
        	$no = null;
        	session('iscar', null);
        }

        $reason = "不想要了";
        $request_no = null;

        $ret = $pay->refund2($no, $trade_no, $amount, $reason, $request_no);


	}

	public function refundPage()
	{
		return $this->fetch('refundPage');
	}
}