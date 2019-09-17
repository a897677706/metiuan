<?php
/*
 * 美团商品预定类
 * @author:     jianghonggang
 * @date:       2019-01-03
 */
require('util.php');        

class product extends util{
    public function __construct(){
        $arr_pathInfo = explode('/dzopen/', $_SERVER['PATH_INFO']);
        $str=str_replace('/', '.', $arr_pathInfo[1]);
        $data=array_merge($_GET,$_POST);
        switch ($str) {
            case 'ecommerce.batchquerypartnerstock'://批量商品库存查询接口
                $this->partnerStock($data);
                break;
            case 'ecommerce.order'://下单接口
                $this->startOrder($data);
                break;
            case 'ecommerce.orderresultsynctopartner'://下单结果同步接口
                $this->orderResult($data);
                break;
            case 'ecommerce.deliveryresultsynctopartner'://配送结果同步接口
                $this->deliveryResult($data);
                break;
            case 'ecommerce.verifyresultsynctopartner'://核销状态同步接口
                $this->verifyResult($data);
                break;
            case 'ecommerce.refundauditsynctopartner'://取消订单审核接口
                $this->refundResult($data);
                break;
            case 'ecommerce.cancelordersynctopartner'://取消订单状态同步接口
                $this->cancelResult($data);
                break;
            
            default:
                # code...
                break;
        }
    }

    public function  partnerStock($data){
        $data=$this->removeSxg($data);
        // $signData['products']=$data['products'];
        $signData['app_key']=$data['app_key'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['open_shop_uuid']=$data['open_shop_uuid'];
        $sign=$this->toSign($signData);        
        if($sign==$data['sign']){
            if($data['open_shop_uuid']){
                $this->checkShopSession($data['open_shop_uuid']);
            }
            // $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
           // $this->meiTuanLog($data,'商品库存获取',0,'partnerStock');
            $ret=$this->getProductStore($data);
            $this->getReturnData('200','success',$ret);
        }else{
            $this->getReturnData('1513','sign is fail');
        }
      
    }

    public function  startOrder($data){
        $data=$this->removeSxg($data);
        $signData['app_key']=$data['app_key'];
        // $signData['session']=$data['session'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];

        $signData['open_shop_uuid']=$data['open_shop_uuid'];
        $signData['order_id']=$data['order_id'];
        $signData['pay_amount']=$data['pay_amount'];
        $signData['discount_amount']=$data['discount_amount'];
        $signData['pay_type']=$data['pay_type'];

        $signData['add_time']=$data['add_time'];
        // $signData['app_products']=$data['app_products'];
        $signData['user_name']=$data['user_name'];
        $signData['mobile']=$data['mobile'];
        $signData['amount']=$data['amount'];
        $signData['quantity']=$data['quantity'];
        $signData['comment']=$data['comment'];
        $sign=$this->toSign($signData);
        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
            $this->meiTuanLog($data,'商品下单',$data['app_shop_id'],'startOrder');
            $this->mt_order_is_create($data);
            //添加和获取会员信息
            $user_data=$this->checkAndAddUser($data);
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
            if($user_data['user_id']){
                try {   
                     $booking=$this->createService($data,$user_data,array('booking_date'=>date('Y-m-d'),'booking_time'=>'00:00','end_time'=>'00:00','rcomp'=>$user_data['rcomp']));
                     if($booking!=false){
                        // $service_id=$this->createService($data, $user_data,$booking);
                        // if($service_id){
                            $GLOBALS['db']->query('commit');
                            $retData=array('order_id'=>$data['order_id'],'app_order_id'=>$booking['app_order_id'],'mobile'=>$data['mobile']);
                            $this->getReturnData('200','success',$retData);

                        // }else{
                        //      $GLOBALS['db']->query('rollback');
                        //     $this->getReturnData('1522','fail');
                        // }
                    }else{
                        $GLOBALS['db']->query('rollback');
                        $this->getReturnData('1522','fail');
                    }
                } catch (Exception $e) {
                        $this->getReturnData('1522','fail');
                }
            }else{
                $this->getReturnData('1522','no user data');
            }
        }else{
            $this->getReturnData('1513','sign is fail');
        }
    }

    public function  orderResult($data){
       $data=$this->removeSxg($data);
        $signData['app_key']=$data['app_key'];
        $signData['session']=$data['session'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['open_shop_uuid']=$data['open_shop_uuid'];
        $signData['order_id']=$data['order_id'];
        $signData['pay_amount']=$data['pay_amount'];
        $signData['discount_amount']=$data['discount_amount'];
        $signData['pay_type']=$data['pay_type'];
        $signData['add_time']=$data['add_time'];
        // $signData['app_products']=$data['app_products'];
        $signData['user_name']=$data['user_name'];
        $signData['mobile']=$data['mobile'];
        $signData['amount']=$data['amount'];
        $signData['quantity']=$data['quantity'];
        $signData['comment']=$data['comment'];
        $signData['order_status']=$data['order_status'];
        $signData['order_channel']=$data['order_channel'];

        $sign=$this->toSign($signData);

        
        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
            $this->meiTuanLog($data,'商品订单更新',$data['app_shop_id'],'updateOrder');
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
                $order=$this->updateOrder($data);
                if($order!=false){
                 
                        $GLOBALS['db']->query('commit');
                        $retData=array('order_id'=>$data['order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $GLOBALS['db']->query('rollback');
                    $this->getReturnData('1525','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
   
    }
    private function getProductStore($data){
        $storeData=array(array('app_product_id'=>'11010007' ,'product_id'=>'11158944' ,'remain_count'=>10),
                        array('app_product_id'=>'11010004' ,'product_id'=>'11159064' ,'remain_count'=>10),
                        array('app_product_id'=>'11010012' ,'product_id'=>'11159162' ,'remain_count'=>10),
                        array('app_product_id'=>'11010002' ,'product_id'=>'11159463' ,'remain_count'=>10),
                        array('app_product_id'=>'11010006' ,'product_id'=>'11159624' ,'remain_count'=>10),
                        array('app_product_id'=>'11010010' ,'product_id'=>'11159700' ,'remain_count'=>10),
                        array('app_product_id'=>'11370003' ,'product_id'=>'11209123' ,'remain_count'=>10),
                        array('app_product_id'=>'11370004' ,'product_id'=>'11209205' ,'remain_count'=>10),
                        array('app_product_id'=>'11370005' ,'product_id'=>'11209264' ,'remain_count'=>10),
                        array('app_product_id'=>'11370006' ,'product_id'=>'11209347' ,'remain_count'=>10),
                        array('app_product_id'=>'11370007' ,'product_id'=>'11209406' ,'remain_count'=>10),
                        array('app_product_id'=>'11B70236' ,'product_id'=>'11209511' ,'remain_count'=>10),
                        array('app_product_id'=>'11390004' ,'product_id'=>'11166750' ,'remain_count'=>10),
                        array('app_product_id'=>'11390005' ,'product_id'=>'11166874' ,'remain_count'=>10),
                        array('app_product_id'=>'11100003' ,'product_id'=>'11185703' ,'remain_count'=>10),
                        array('app_product_id'=>'11100001' ,'product_id'=>'11185771' ,'remain_count'=>10),
                        array('app_product_id'=>'11B10001' ,'product_id'=>'11186031' ,'remain_count'=>10),
                        array('app_product_id'=>'11040023' ,'product_id'=>'11165854' ,'remain_count'=>10),
                        array('app_product_id'=>'11040024' ,'product_id'=>'11165936' ,'remain_count'=>10),
                        array('app_product_id'=>'11070002' ,'product_id'=>'11185401' ,'remain_count'=>10),
                        array('app_product_id'=>'11070006' ,'product_id'=>'11185464' ,'remain_count'=>10),
                        array('app_product_id'=>'11110005' ,'product_id'=>'11172191' ,'remain_count'=>10),
                        array('app_product_id'=>'11110004' ,'product_id'=>'11172280' ,'remain_count'=>10),
                        array('app_product_id'=>'11170006' ,'product_id'=>'11170754' ,'remain_count'=>10),
                        array('app_product_id'=>'11170014' ,'product_id'=>'11170860' ,'remain_count'=>10),
                        array('app_product_id'=>'11030073' ,'product_id'=>'11167891' ,'remain_count'=>10),
                        array('app_product_id'=>'11030074' ,'product_id'=>'11168011' ,'remain_count'=>10),
                        array('app_product_id'=>'11460001' ,'product_id'=>'11196081' ,'remain_count'=>10),
                        array('app_product_id'=>'11460002' ,'product_id'=>'11196149' ,'remain_count'=>10),
                        array('app_product_id'=>'11460003' ,'product_id'=>'11196273' ,'remain_count'=>10),
                        array('app_product_id'=>'11460004' ,'product_id'=>'11196228' ,'remain_count'=>10),
                        array('app_product_id'=>'11460005' ,'product_id'=>'11195971' ,'remain_count'=>10),
                        array('app_product_id'=>'11C50003' ,'product_id'=>'33372487' ,'remain_count'=>10),
                        array('app_product_id'=>'z2019888' ,'product_id'=>'36622798' ,'remain_count'=>10),
                         array('app_product_id'=>'000002737' ,'product_id'=>'39474296' ,'remain_count'=>10),
                        
        );
        $products=json_decode($data['products']);
        $retData=array();
        foreach ($products as $key => $value) {
            foreach ($storeData as $key1 => $value1) {
                if($value->app_product_id==$value1['app_product_id']){
                    $retData[]=$value1;
                }
            }
        }
       

        return $retData;
    }
    private function updateOrder($data){


            if(empty($data['order_id'])){
                $this->getReturnData('1525','no order_id');
            }
            
            $sql="select * from em_mt_order_info where mt_order_id='{$data['order_id']}' and store_id='{$data['app_shop_id']}'";
            $orderData=$GLOBALS['db']->getRow($sql);

            if(empty($orderData['id'])){
                $this->getReturnData('1525','no order');
            }

            
            $order_data=array(
                              'book_status'=>$data['order_status'],//下单状态，2-下单成功，3-下单失败
                              'book_channel'=>$data['order_channel'],//下单渠道，1-第三方渠道，2-美团点评内部渠道
                              'update_time'=>date('Y-m-d h:i:s')
                              );

            if($data['order_status']=='3'){
                if($orderData['con_status']!=2){
                    $order['mark'] = '-1';

                    //更新预约单
                    $result = $GLOBALS['db']->autoExecute('em_service_h', $order, 'UPDATE'," service_doc={$orderData['show_id']} ");

                    if(!$result){
                        return false;
                    }
                }else{
                    return false;
                }
            }
                 

            $order_log_id=$this->upOrderData($order_data,$orderData['id']);
            if(!$order_log_id){
                return false;
            }
            return $orderData;
    }
    public function  deliveryResult($data){
        $data=$this->removeSxg($data);
        $signData['app_key']=$data['app_key'];
        $signData['session']=$data['session'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['order_id']=$data['order_id'];
        $signData['delivery_status']=$data['delivery_status'];
    
        $sign=$this->toSign($signData);

        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_idByOrderId($data['order_id']);
            $this->meiTuanLog($data,'配送结果',$data['app_shop_id'],'deliveryResult');
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
                $order=$this->deliveryUpdate($data);
                if($order!=false){
                 
                        $GLOBALS['db']->query('commit');
                        $retData=array('order_id'=>$data['order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $GLOBALS['db']->query('rollback');
                    $this->getReturnData('1525','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
   
    }
    private function deliveryUpdate($data){

            if(empty($data['order_id'])){
                $this->getReturnData('1525','no order_id');
            }
            
            $sql="select id,mt_order_id,app_order_id from em_mt_order_info where mt_order_id='{$data['order_id']}'";
            $orderData=$GLOBALS['db']->getRow($sql);

            if(empty($orderData['id'])){
                $this->getReturnData('1525','no order');
            }

            
            $order_data=array(
                            'delivery_status'=>$data['delivery_status'],//配送状态，1-配送中 2-配送失败
                            'delivery_info'=>$data['delivery_info'],
                            'update_time'=>date('Y-m-d h:i:s')
                            );
                 

            $order_log_id=$this->upOrderData($order_data,$orderData['id']);
            if(!$order_log_id){
                return false;
            }
            return $orderData;

    }
    public function  verifyResult($data){
        $data=$this->removeSxg($data);
        $signData['app_key']=$data['app_key'];
        $signData['session']=$data['session'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['order_id']=$data['order_id'];
        $signData['verify_status']=$data['verify_status'];//核销状态 2=核销成功
        $signData['verify_channel']=$data['verify_channel'];//核销渠道 1=第三方平台核销 2=新美大平台核销

    
        $sign=$this->toSign($signData);

        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_idByOrderId($data['order_id']);
            $this->meiTuanLog($data,'核销状态同步',$data['app_shop_id'],'verifyResult');
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
                $order=$this->verifyUpdate($data);
                if($order!=false){
                 
                        $GLOBALS['db']->query('commit');
                        $retData=array('order_id'=>$data['order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $GLOBALS['db']->query('rollback');
                    $this->getReturnData('1525','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
   
    }
    private function verifyUpdate($data){


            if(empty($data['order_id'])){
                $this->getReturnData('1525','no order_id');
            }
            
            $sql="select id,mt_order_id,app_order_id,store_id,rcomp,mobile,con_status,show_id,delivery_info from em_mt_order_info where mt_order_id='{$data['order_id']}' and book_status<>3";
            $orderData=$GLOBALS['db']->getRow($sql);

            if(empty($orderData['id'])){
                $this->getReturnData('1525','no order');
            }

            if($orderData['con_status']==2){
                $this->getReturnData('200','success',array('order_id'=>$data['order_id']));
            }

            
            $order_data=array(
                            'con_status'=>$data['verify_status'],//核销状态 2=核销成功
                            'update_time'=>date('Y-m-d h:i:s')
                            );

            $order_log_id=$this->upOrderData($order_data,$orderData['id']);
            if(!$order_log_id){
                return false;
            }

            $delivery_info=json_decode($orderData['delivery_info']);
            if($delivery_info->delivery_type==2){
                $ret=$this->createOrder($data,$orderData);    

                if($ret==false){
                    return false;
                }
 
            }

            return $orderData;

    }

    public function  refundResult($data){
        $data=$this->removeSxg($data);
        $signData['app_key']=$data['app_key'];
        $signData['session']=$data['session'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['order_id']=$data['order_id'];
        $signData['reason']=$data['reason'];//发起取消包房预订的原因

        $sign=$this->toSign($signData);

        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_idByOrderId($data['order_id']);
            $order=$this->meiTuanLog($data,'取消订单审核',$data['app_shop_id'],'refundResult');
                if($order){
                        $retData=array('order_id'=>$data['order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $this->getReturnData('1525','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
   
    }
    public function cancelResult($data){
        $data=$this->removeSxg($data);
        $signData['app_key']=$data['app_key'];
        // $signData['session']=$data['session'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['order_id']=$data['order_id'];
        $signData['cancel_type']=$data['cancel_type'];//取消类型 1=规则取消：用户正常规则下取消 2=非规则取消：除规则取消之外的其他取消，如用户通过开放平台客服强制取消
        $signData['reason']=$data['reason'];
        $signData['refund_status']=$data['refund_status'];
        $signData['audit_channel']=$data['audit_channel'];
        $sign=$this->toSign($signData);
        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_idByOrderId($data['order_id']);
            $this->meiTuanLog($data,'取消订单状态同步', $data['app_shop_id'],'cancelResult');
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
                $order=$this->cancelUpdate($data);
                if($order!=false){
                 
                        $GLOBALS['db']->query('commit');
                        $retData=array('order_id'=>$data['order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $GLOBALS['db']->query('rollback');
                    $this->getReturnData('1525','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
    }
    private function cancelUpdate($data){
            if(empty($data['order_id'])){
                $this->getReturnData('1525','no order_id');
            }
            
            $sql="select * from em_mt_order_info where mt_order_id='{$data['order_id']}'";
            $orderData=$GLOBALS['db']->getRow($sql);

            if(empty($orderData['id'])){
                $this->getReturnData('1525','no order');
            }

            
            $order_data=array(
                              'cancel_type'=>$data['cancel_type'],//取消类型 1=规则取消：用户正常规则下取消 2=非规则取消：除规则取消之外的其他取消，如用户通过开放平台客服强制取消
                              'reason'=>$data['reason'],//取消订单原因
                              'refund_status'=>$data['refund_status'],//退款状态，1-退款中，2-退款成功，3-退款失败
                              'audit_channel'=>$data['audit_channel'],//取消订单审核渠道 1=三方平台审核 2=开放平台审核
                              'update_time'=>date('Y-m-d h:i:s')
                              );


            if($orderData['con_status']!=2){
                if($data['refund_status']==2){
                    $service['mark'] = '-1';

                    //更新服务单
                    $result = $GLOBALS['db']->autoExecute('em_service_h', $service, 'UPDATE'," service_doc={$orderData['show_id']} ");

                    if(!$result){
                        return false;
                    }
                }
               
            }else{
                return false;
            }

                 

            $order_log_id=$this->upOrderData($order_data,$orderData['id']);
            if(!$order_log_id){
                return false;
            }
            return $orderData;
    }
}