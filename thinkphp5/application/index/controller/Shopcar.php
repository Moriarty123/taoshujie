<?php
namespace app\index\controller;
use \think\Controller;

class Shopcar extends \think\Controller
{
    public function index(){

        if(session('username')==NULL){
           $this->redirect('/index/login/index');
        }   
        $sid = session('userid');
        $ret = db('sale_book')->alias('a')->join('shopcar b',"b.user_id={$sid} and b.book_id=a.sale_id")->join('user c','b.user_id = c.user_id')->field('a.*,b.book_num,b.id,c.user_realname')->select();
        if ($ret==null) {
           return view('shopcarno');
        }

        $this->assign('ret',$ret);

        return view('shopcar');
    }

    //删除一条数据
    public function delete(){

        $sid= input('get.id');

        $ret = db('shopcar')->where("id={$sid}")->delete();
        if ($ret == true) {
            $this->success("删除成功","/index/shopcar/index");
        }else{
            $this->error("删除失败","/index/shopcar/index");
        }

    }

    //删除选中的所有数据
    public function deleteselect(){

        $submit = $_POST['Submit'];
        $ids = $_POST['ids']; //购物车订单被选中的所有购物车id
        // dump($ids);die;

        if( !isset($ids) || $ids == null ){
            echo "<script>alert('你还没有选中任何项！');</script>";
        }
        else{
            // session('sale_ids', $ids);
            if( $submit == '删除选中' ){

                echo "<script>alert('你确定要删除选中的所有项吗？');</script>";
                
                foreach ($ids as $value) {
                    $ret=db('shopcar')->where("id={$value}")->delete();

                }
                if ($ret == true) {
                    $this->success("删除成功","/index/shopcar/index");
                }
                else{
                    $this->error("删除失败","/index/shopcar/index");
                }
            }

            if( $submit == '结 算' ){

                //生成订单号
                $order_id = date("Y-m-dH-i-s");
                $order_id = str_replace("-","",$order_id);
                $order_id .= session('userid');
                $order_id .= rand(1000,999999);
                $price = 0;

                foreach ($ids as $value) {
                    $ret=db('shopcar')->where("id={$value}")->find();
                    $res = db('sale_book')->where("sale_id={$ret['book_id']}")->find();
                    $user_id = $ret['user_id'];
                    $book_id = $ret['book_id'];
                    $create_time =time();
                    $order_price=$res['sale_afprice'];
                    $order_sum = $ret['book_num'];

                    $data=[
                        'order_id'=>$order_id,
                        'book_id'=>$book_id,
                        'user_id'=>$user_id,
                        'create_time'=>$create_time,
                        'order_price'=>$order_price,
                        'order_sum'=>$order_sum
                    ];

                    
                    $price += $order_price;
                    $rew = db('shoporder')->insert($data);

                }


                if ($rew == false) {
                    $this->error('提交订单失败');
                }

                foreach ($ids as $value) {
                    db('shopcar')->where("id={$value}")->delete();
                }
                $res['sale_num']=$res['sale_num']-$ret['book_num'];

                db('sale_book')->where("sale_id={$ret['book_id']}")->update($res);


                

                $pay = new Pay();

                $no = $order_id;
                $subject = "测试商品";
                $price = $order_price;
                $body = "测试";
                $ret = $pay->pagepay2($no, $subject, $amount, $body);

                // dump($ret);  
            }
        }

    }

    //添加到订单
    public function insertOrder()
    {
        $order_id = session('order_id');
        $car_id = session('car_id');

        $where = "id = {$car_id}";
        $car = db('shopcar')->where($where)->find();

        $data = [
            'order_id'  => $order_id,
            'book_id'   => $car['book_id'],
            'user_id'   => $car['user_id'],
            'book_num'  => $car['book_num'],
            'price'     => $price,
        ];

    }


       //加入购物车
    public function insert(){

        //判断是否登录
        if(session('username')==NULL){
          $this->redirect('/index/login/index');
        }


        $user_id = session('userid');
        $book_id = $_GET['id'];
        $create_time = time();
        $book_num = 1;

        $find_id = db('sale_book')->where("sale_id={$book_id}")->find();
                        //判断是否为自己发布的书籍
        if($user_id==$find_id['user_id']){
            $this->error("对不起，不能购买您自己发布的书籍");
        }
        $find_book = db('shopcar')->where("user_id={$user_id}")->select();

        foreach ($find_book as $value) {
            if ($value['book_id']==$book_id) {
                $this->error("购物车已有该书籍，请前往购物车购买");
            }
        }

        $data = [

            'user_id'=>$user_id,
            'book_id'=>$book_id,
            'create_time'=>$create_time,
            'book_num'=>$book_num

        ];
        $ret = db('shopcar')->insert($data);
        if($ret == true){

            $this->success('加入购物车成功',"/index/shopcar/index");
        }
        else{
            $this->error('加入购物车失败');
        }
    }
        
    //购物车的购买数量+1
    public function add(){
        $sid = $_GET['id'];
        $ret = db('shopcar')->where("id = {$sid}")->find();

        $ret['book_num']+=1;
        $res = db('shopcar')->where("id = {$sid}")->update($ret);
        $this->redirect('/index/shopcar/index');
    }
            //购物车的购买数量-1
    public function sub(){

        $sid = $_GET['id'];
        $ret = db('shopcar')->where("id = {$sid}")->find();
        $ret['book_num']-=1;
                //判断是否为0，为0则删除
        if($ret['book_num']==0){
            db('shopcar')->where("id = {$sid}")->delete();
        }

        $res = db('shopcar')->where("id = {$sid}")->update($ret);
        $this->redirect('/index/shopcar/index');
    }


    //支付
    public function pay()
    {
        $submit = $_POST['Submit'];
        $ids = $_POST['ids']; //购物车订单被选中的所有购物车id
        // dump($ids);die;

        if( !isset($ids) || $ids == null ){
            echo "<script>alert('你还没有选中任何项！');</script>";
        }
        else{
            // session('sale_ids', $ids);
            if( $submit == '删除选中' ){

                echo "<script>alert('你确定要删除选中的所有项吗？');</script>";
                
                foreach ($ids as $value) {
                    $ret=db('shopcar')->where("id={$value}")->delete();

                }
                if ($ret == true) {
                 $this->success("删除成功","/index/shopcar/index");
                }else{
                    $this->error("删除失败","/index/shopcar/index");
                }
            }
        }

        session('submit', $submit);
        session('sale_ids', $ids);
        session('isbuy', false);

        //获取总金额
        $price = 0;
        foreach ($ids as $value) {
            $ret=db('shopcar')->where("id={$value}")->find();
            $res = db('sale_book')->where("sale_id={$ret['book_id']}")->find();
            $order_price=$res['sale_afprice'];
            
            $price += $order_price;

        }

        $pay = new Pay();

        //生成订单号
        $order_id = date("Y-m-dH-i-s");
        $order_id = str_replace("-","",$order_id);
        $order_id .= session('userid');
        $order_id .= rand(1000,999999);

        $no = $order_id;
        $subject = "测试商品";
        $amount = $price;
        $body = "测试";
        $ret = $pay->pagepay2($no, $subject, $amount, $body);
    }


    //添加到订单
    public function insertToOrder()
    {
        $submit = session('submit');
        $ids = session('sale_ids'); //购物车订单被选中的所有购物车id
        

        //dump($submit);
        // dump($ids);

        if( $submit == '结 算' ){

            //生成订单号
            $trade_no = session('trade_no');
            // dump($trade_no);die;

            foreach ($ids as $value) {
                //生成订单号
                $order_id = date("Y-m-dH-i-s");
                $order_id = str_replace("-","",$order_id);
                $order_id .= session('userid');
                $order_id .= rand(1000,999999);

                $ret=db('shopcar')->where("id={$value}")->find();
                $res = db('sale_book')->where("sale_id={$ret['book_id']}")->find();
                $user_id = $ret['user_id'];
                $book_id = $ret['book_id'];
                $create_time =time();
                $order_price=$res['sale_afprice'];
                $order_sum = $ret['book_num'];

                $data=[
                    'order_id'=>$order_id,
                    'book_id'=>$book_id,
                    'user_id'=>$user_id,
                    'create_time'=>$create_time,
                    'order_price'=>$order_price,
                    'order_sum'=>$order_sum,
                    'trade_no' => $trade_no
                ];


                $rew = db('shoporder')->insert($data);


                if($rew == true){
                
                    db('shopcar')->where("id={$value}")->delete();
                    $res['sale_num']=$res['sale_num']-$ret['book_num'];
                    
                    db('sale_book')->where("sale_id={$ret['book_id']}")->update($res);
                }
                else{
                    $this->error('提交订单失败');
                }
            }
            $this->success('提交订单成功',"/index/personalorder/alist");
        }
    }
}
