<?php
/*
 * 美团服务预订类
 * @author:     jianghonggang
 * @date:       2019-01-03
 */
require('util.php');        

class book extends util{
    public function __construct(){
        $arr_pathInfo = explode('/dzopen/', $_SERVER['PATH_INFO']);
        $str=str_replace('/', '.', $arr_pathInfo[1]);
        $data=array_merge($_GET,$_POST);
        switch ($str) {
            case 'book.query.batch.remaincountbytime':
                $this->remainCount($data);
                break;
            case 'book.startbook':
                $this->startBook($data);
                break;
            case 'book.bookresultnotify':
                $this->bookResult($data);
                break;
            case 'book.cancelbook':
                $this->cancelBook($data);
                break;
            case 'book.query.consumestatus':
                $this->consumeStatus($data);
                break;
            
            default:
                echo "对不起，暂时没有此功能！";
                break;
        }
    }
    public function  remainCount($data){
        if( $data['inventory_product_info']){
            $data['inventory_product_info']=str_replace('\\', '', $data['inventory_product_info']);
        }
        $signData['app_key']=$data['app_key'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['v']=$data['v'];
        $signData['app_shop_id']=$data['app_shop_id'];
        $signData['bookdate']=$data['bookdate'];
        $signData['days']=$data['days'];
        if(!empty($data['durations'])){
            $signData['durations']=$data['durations'];
        }
        $sign=$this->toSign($signData);               
        if($sign==$data['sign']){
            $this->checkShopSession($data['open_shop_uuid']);
            $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
           // $this->meiTuanLog($data,'服务项目库存获取',$data['app_shop_id'],'remainCount');
            $ret=array_values($this->getRemainCount($data));
            $this->getReturnData('200','success',$ret);
        }else{
            $this->getReturnData('1513','sign is fail');
        }
      
    }
    public function  startBook($data){
        if( $data['products']){
            $data['products']=str_replace('\\', '', $data['products']);
        }
        $signData['app_key']=$data['app_key'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['app_shop_id']=$data['app_shop_id'];
        $signData['order_id']=$data['order_id'];
        $signData['app_product_id']=$data['app_product_id'];
        $signData['product_name']=$data['product_name'];
        $signData['begintime']=$data['begintime'];
        $signData['duration']=$data['duration'];
        $signData['endtime']=$data['endtime'];
        $signData['products']=$data['products'];
        $signData['user_name']=$data['user_name'];
        $signData['user_gender']=$data['user_gender'];
        $signData['mobile']=$data['mobile'];
        $signData['amount']=$data['amount'];
        $data['order_shoppromo_details']=$signData['order_shoppromo_details']=str_replace('\\', '', $data['order_shoppromo_details']);
        $signData['quantity']=$data['quantity'];
        $signData['comment']=$data['comment'];

        $sign=$this->toSign($signData);

        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
            $this->meiTuanLog($data,'服务项目预约',$data['app_shop_id'],'startBook');
            $this->mt_order_is_create($data);
            //添加和获取会员信息
            $user_data=$this->checkAndAddUser($data);
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
            if($user_data['user_id']){
                    try {                 
                         $booking=$this->createBook($data,$user_data);
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
    public function  bookResult($data){
        if( $data['products']){
            $data['products']=str_replace('\\', '', $data['products']);
        }
        $signData['app_key']=$data['app_key'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['app_shop_id']=$data['app_shop_id'];
        $signData['order_id']=$data['order_id'];
        $signData['app_product_id']=$data['app_product_id'];
        $signData['product_name']=$data['product_name'];
        $signData['begintime']=$data['begintime'];
        $signData['duration']=$data['duration'];
        $signData['endtime']=$data['endtime'];
        $signData['products']=$data['products'];
        $signData['user_name']=$data['user_name'];
        $signData['user_gender']=$data['user_gender'];
        $signData['mobile']=$data['mobile'];
        $signData['amount']=$data['amount'];
        $data['order_shoppromo_details']=$signData['order_shoppromo_details']=str_replace('\\', '', $data['order_shoppromo_details']);
        $signData['quantity']=$data['quantity'];
        $signData['comment']=$data['comment'];
        $signData['book_channel']=$data['book_channel'];
        $signData['book_status']=$data['book_status'];

        $sign=$this->toSign($signData);

        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
            $this->meiTuanLog($data,'服务项目更新',$data['app_shop_id'],'updateBook');
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
                $booking=$this->updateBook($data);
                if($booking!=false){
                 
                        $GLOBALS['db']->query('commit');
                        $retData=array('order_id'=>$data['order_id'],'app_order_id'=>$booking['app_order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $GLOBALS['db']->query('rollback');
                    $this->getReturnData('1525','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
   
    }
    public function  cancelBook($data){
        $signData['app_key']=$data['app_key'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['app_shop_id']=$data['app_shop_id'];
        $signData['order_id']=$data['order_id'];
        $signData['cancel_type']=$data['cancel_type'];
        $signData['audit_channel']=$data['audit_channel'];
        $signData['reason']=$data['reason'];

        $sign=$this->toSign($signData);

        if($sign==$data['sign']){
            $data['app_shop_id']=$this->getShop_id($data['open_shop_uuid']);
            $this->meiTuanLog($data,'服务项目取消',$data['app_shop_id'],'cancelBook');
            $GLOBALS['db']->query('SET AUTOCOMMIT=0');
            $GLOBALS['db']->query('BEGIN');
                $booking=$this->toCancelBook($data);
                if($booking!=false){
                 
                        $GLOBALS['db']->query('commit');
                        $retData=array('order_id'=>$data['order_id']);
                        $this->getReturnData('200','success',$retData);

                }else{
                    $GLOBALS['db']->query('rollback');
                    $this->getReturnData('1533','fail');
                }

        }else{
            $this->getReturnData('1513','sign is fail');
        }
        
    }
    public function  consumeStatus($data){
        $signData['app_key']=$data['app_key'];
        $signData['timestamp']=$data['timestamp'];
        $signData['format']=$data['format'];
        $signData['v']=$data['v'];
        $signData['sign_method']=$data['sign_method'];
        $signData['order_id']=$data['order_id'];
        $sign=$this->toSign($signData);
        if($sign==$data['sign']){
            $sql=" select  id,receipt_code from em_mt_order_info where  mt_order_id='{$data['order_id']}'";
            $con_status = $GLOBALS['db']->getRow($sql);
            if(empty($con_status)){
                 $this->getReturnData('1560','fail');
            }else{
                $retData=array('consume_status'=>$con_status['receipt_code']?2:1,'order_id'=>$data['order_id']);
                //是否核销：1.未核销 2.已核销
                $this->getReturnData('200','success',$retData);
            }
        }else{
            $this->getReturnData('1513','sign is fail');
        }
        
    }

    private function createBook($data,$user_data){

            
            if(empty($data['app_product_id'])&&empty($data['product_id'])){
                $this->getReturnData('1522','no app_product_id or product_id');
            }

            if(empty($data['order_id'])){
                $this->getReturnData('1522','no order_id');
            }

            $sql="select goods_sn, product_id,app_product_id,service_time from em_products where mark=0 and mt_active=1  and goods_sn = '{$data['app_product_id']}'";
            $pData=$GLOBALS['db']->getRow($sql);


            if(empty($pData['product_id'])||empty($pData['service_time'])){
                $this->getReturnData('1522','product no found');
            }

            $booking_date=date('Y-m-d',strtotime($data['begintime']));
            $booking_time=date('H:i',strtotime($data['begintime']));
            $endstr=strtotime($data['begintime'])+$pData['service_time']*60;
            $end_time=date('H:i',$endstr);

            $booking = array(
                'comm_id' => '01',
                'comm_cont' => $data['mobile'],
                'title' => $user_data['real_name'],
                'booking_date' => $booking_date,
                'booking_time' =>$booking_time,
                'end_time' => $end_time,
                'serve_id' => $pData['product_id'],
                'status_id' => 'WAIT',
                'user_id' => $user_data['user_id'],
                'rcomp' => $user_data['rcomp'],
                'store_id' => $user_data['store_id'],
                'people' => 1 ,
                'remark' => $data['comment'],
                'come_from' => 'meituan',//1=三方平台渠道，2=美大平台渠道
            );
            $booking['ername'] = '美团操作';
            $booking['ersda'] = date('Y-m-d');
            $booking['eratime'] = date('H:i:s');

            $saler=$this->mt_get_saler($booking);
            if($saler==false){
               $this->getReturnData('1521','fail');
            }

            $checkRet=$this->mt_check_room($booking);

            if(!$checkRet){
                $this->getReturnData('1521','fail');
            }
            $booking['room_id']=$checkRet[0];
            $booking['artificer']=$saler[0]['user_id'];
            $result = $GLOBALS['db']->autoExecute('em_booking', $booking, 'INSERT');

            if(!$result){
                return false;
            }

            $booking['booking_id'] = $GLOBALS['db']->insert_id();

            $order_data=array('rcomp'=>$booking['rcomp'],
                              'store_id'=>$data['app_shop_id'],
                              'order_shoppromo_details'=>$data['order_shoppromo_details'],
                              'mt_order_id'=>$data['order_id'],
                              'show_id'=>$booking['booking_id'],
                              'type'=>'book',
                              'amount'=>$data['amount'],
                              'mobile'=>$data['mobile'],
                              'add_time'=>date('Y-m-d h:i:s')
                              );
            $order_log_id=$this->saveOrderData($order_data);
            if($order_log_id==false){
                return false;
            }
            //预订房间时间段
            $room_time_span = array(
                'rcomp' => $booking['rcomp'],
                'room_id' => $booking['room_id'],
                'booking_id' => $booking['booking_id'],
                'day' => $booking['booking_date'],
                'strtime' => $booking['booking_time'],
            );  
            $result1 = $GLOBALS['db']->autoExecute('em_store_room_timespan', $room_time_span, 'INSERT');
            if(!$result1){
                return false;
            }
            //添加服务列表信息
            $serve_info = array(
                'booking_id' => $booking['booking_id'],
                'come_from' => 'mt',
                'serve_id' => $pData['product_id'],
                'add_time' => date('Y-m-d H:i:s')
            );

            $result2=$GLOBALS['db']->autoExecute('em_booking_serve', $serve_info, 'INSERT');

            if(!$result2){
                return false;
            }
            $booking['app_order_id']=$order_log_id;
            return $booking;
    }

    private function updateBook($data){

            
            if(empty($data['app_product_id'])&&empty($data['product_id'])){
                $this->getReturnData('1525','no app_product_id or product_id');
            }

            if(empty($data['order_id'])){
                $this->getReturnData('1525','no order_id');
            }
         
            $sql="select * from em_mt_order_info where mt_order_id='{$data['order_id']}' and store_id='{$data['app_shop_id']}'";
            $orderData=$GLOBALS['db']->getRow($sql);

            if(empty($orderData['id'])){
                $this->getReturnData('1525','no order');
            }


            
            // $booking_date=date('Y-m-d',strtotime($data['begintime']));
            // $booking_time=date('H:i',strtotime($data['begintime']));
            // $end_time=date('H:i',strtotime($data['endtime']));
            // $booking = array(
                // 'booking_date' => $booking_date,
                // 'booking_time' =>$booking_time,
                // 'end_time' => $end_time,
                // 'serve_id' => $data['app_product_id'],
                // 'rcomp' => $orderData['rcomp'],
                // 'people' => $data['quantity'],
                // 'remark' => $data['comment'],
                // 'come_from' => $data['book_channel'],//1=三方平台渠道，2=美大平台渠道
            // );
            // $booking['ername'] = '美团操作';
            // $booking['ersda'] = date('Y-m-d');
            // $booking['eratime'] = date('H:i:s');
            
            $order_data=array(
                              // 'order_shoppromo_details'=>$data['order_shoppromo_details'],
                              'book_status'=>$data['book_status'],//2=预订成功，3=预订失败
                              'book_channel'=>$data['book_channel'],//下单渠道，1-第三方渠道，2-美团点评内部渠道
                              'update_time'=>date('Y-m-d h:i:s')
                              );

            if($data['book_status']=='3'){
                if($orderData['con_status']!=2){
                    $booking['status_id'] = 'CNCL';

                    //更新预约单
                    $result = $GLOBALS['db']->autoExecute('em_booking', $booking, 'UPDATE'," booking_id={$orderData['show_id']} ");

                    if(!$result){
                        return false;
                    }
                }else{
                    return false;
                }
            }
            // else{
            //     $saler=$this->mt_get_saler($booking);
            //     if($saler==false){
            //        $this->getReturnData('1525','fail');
            //     }

            //     $checkRet=$this->mt_check_room($booking);
            //     if(!$checkRet){
            //         $this->getReturnData('1525','fail');
            //     }
            //     $booking['room_id']=$checkRet[0];
            //     $booking['artificer']=$saler[0]['user_id'];
            // }
                            

            $order_log_id=$this->upOrderData($order_data,$orderData['id']);
            if(!$order_log_id){
                return false;
            }

            // if($data['book_status']=='2'){
            //     //更新预订房间时间段
            //     $room_time_span = array(
            //         'room_id' => $booking['room_id'],
            //         'day' => $booking['booking_date'],
            //         'strtime' => $booking['booking_time'],
            //     );  
            //     $result1 = $GLOBALS['db']->autoExecute('em_store_room_timespan', $room_time_span, 'UPDATE'," booking_id={$orderData['show_id']} ");
            //     if(!$result1){
            //         return false;
            //     }
            //     //更新服务列表信息
            //     $serve_info = array(
            //         'serve_id' => $data['app_product_id'],
            //         'add_time' => date('Y-m-d H:i:s')
            //     );

            //     $result2=$GLOBALS['db']->autoExecute('em_booking_serve', $serve_info, 'UPDATE'," booking_id={$orderData['show_id']} ");

            //     if(!$result2){
            //         return false;
            //     }
            // }else{
            //     //关闭服务单
            //     // $result2=$GLOBALS['db']->autoExecute('em_service_h', array('mark'=>'-1'), 'UPDATE'," booking_id={$orderData['show_id']} ");
            // }

            return $orderData;
    }


    private function toCancelBook($data){
            if(empty($data['order_id'])){
                $this->getReturnData('1533','no order_id');
            }
         
            $sql="select * from em_mt_order_info where mt_order_id='{$data['order_id']}'";
            $orderData=$GLOBALS['db']->getRow($sql);


            if(empty($orderData['id'])){
                $this->getReturnData('1531','no order');
            }


            $sql1="select service_doc from em_service_h where booking_id='{$orderData['show_id']}'";
            $service_doc=$GLOBALS['db']->getOne($sql1);

            if(!empty($service_doc)){
                $this->getReturnData('1532','');
            }

            $booking = array(
                // 'come_from' => $data['book_channel'],//1=三方平台渠道，2=美大平台渠道
                 'status_id' => 'CNCL'
            );
 
            $order_data=array(
                              'book_status'=>4,//2=预订成功，3=预订失败 4=美团取消
                              'cancel_type'=>$data['cancel_type'],//1=规则取消：用户正常规则下取消 2=非规则取消：除规则取消之外的其他取消，如用户通过开放平台客服强制取消
                              'audit_channel'=>$data['audit_channel'],//取消预订审核渠道 1=三方平台审核 2=开放平台审核
                              'reason'=>$data['reason'],
                              'update_time'=>date('Y-m-d h:i:s')
                              );           

            //更新预约单
            $result = $GLOBALS['db']->autoExecute('em_booking', $booking, 'UPDATE'," booking_id={$orderData['show_id']} ");

            if(!$result){
                return false;
            }

            $order_log_id=$this->upOrderData($order_data,$orderData['id']);
            if(!$order_log_id){
                return false;
            }
          
            return $orderData;
    }



    /**
     * 检查床位是否被占用
     * @param type $booking
     */
    public function mt_check_room($booking){
            $sql = "select id from  em_store_room where  rcomp='{$booking['rcomp']}'  and  mt_ishow=1 and mark=0  ";
            $roomData = $GLOBALS['db']->getAll($sql);
            $roomCanUse=array();
            foreach ($roomData as $key => $value) {
                $sql = "select booking_id from " . $GLOBALS['ecs']->table('booking') 
                    ." where booking_date='" . $booking['booking_date'] . "'"
                    ." and booking_time < '" . $booking['end_time'] . "'"
                    ." and end_time > '" . $booking['booking_time'] . "'"
                    ." and room_id=" . $value['id']
                    ." and rcomp=" . $booking['rcomp']
                    ." and mark=0" 
                    ." and status_id <> 'CNCL'";
                $booking_id = $GLOBALS['db']->getRow($sql);

                if(empty($booking_id)){
                    $roomCanUse[]=$value['id'];
                }
            }
        
            if (!empty($roomCanUse)){
                return $roomCanUse;
            }else{
                return false;
            } 
    }

    private function  getRemainCount($data){
        if(empty($data['bookdate'])){
            $this->getReturnData('1522','no bookdate');
        }else{
            if(strtotime($data['bookdate'])<strtotime(date('Y-m-d'))){
                 $this->getReturnData('1510','');
            }
        }
        if(empty($data['days'])){
            $this->getReturnData('1522','no days');
        }else{
            // if($data['days']>7){
            //      $this->getReturnData('1514','');
            // }
        }

        if(empty($data['open_shop_uuid'])&&empty($data['app_shop_id'])){
            $this->getReturnData('1522','no open_shop_uuid or  app_shop_id');
        }


        for ($t=0; $t < $data['days'] ; $t++) { 
            $timeData[]=date('Y-m-d',strtotime('+'.$t.' day',strtotime($data['bookdate'])));
        }

        $product_id=array();
        if($data['inventory_product_info']){
            $data['inventory_product_info']=json_decode($data['inventory_product_info']);
            foreach ($data['inventory_product_info'] as $keyinfo => $valueinfo) {
                if(is_object($valueinfo)){
                    $product_id[]=$valueinfo->app_product_id;
                }else{   
                    $product_id[]=$valueinfo;
                }
            }
        }

        $storeData=$this->getStoreData($data);

        if(!$storeData['store_id']){
            $this->getReturnData('1522','no shop info');
        }


        if(!empty($product_id)){
            $sql="select goods_sn, product_id,app_product_id,service_time from em_products where mark=0 and mt_active=1   and goods_sn in ('". implode("','", $product_id) ."')";
        }else{
            $sql="select  p.goods_sn,p.product_id,p.app_product_id,p.service_time from em_products p left join em_goods g on g.goods_id=p.goods_id where g.serviceflag=1 and p.mark=0 and p.mt_active=1 and p.store_id=".$storeData['store_id'];
        }

        $goodsData=$GLOBALS['db']->getAll($sql);

        $RoomAndBookingData=$this->getStoreRoomAndBooking($storeData['rcomp'],$timeData);
        $allData=array();
        foreach ($goodsData as $key => $value) {

            if(empty($value['service_time'])){
                $this->getReturnData('1512','');
            }
            $zheng=intval($value['service_time']/30);
            $yu=$value['service_time']%30;
            if($yu>0){
                $zheng=$zheng+1;
            }

            $max=count($this->timeAndNum);
            foreach ($timeData as $keytd => $valuetd) {
               
                foreach ($this->timeAndNum as $keyn => $valuen) {
                    $time_bet=explode('-', $keyn);
                    $checkMax=$valuen+$zheng-1;
                    $newcheckTime=strtotime(date('Y-m-d').' '.$time_bet[0]);
                    //如果库存时间小于当前时间则不被记入库存
                    if($newcheckTime<time()&&date('Y-m-d')==$valuetd){
                        continue;
                    }

                    if($checkMax>$max){

                    }else{
                        $checkData=array();

                        for ($i=$valuen; $i <($valuen+$zheng) ; $i++) { 
                                $checkData[]=$i;
                        }

                        foreach ($RoomAndBookingData['roomData'] as $keyrd => $valuerd) {

                            $allRet=array();

                            foreach ($RoomAndBookingData['booking'] as $keybi => $valuebi) {

                                if($valuerd['id']==$valuebi['room_id']&&$valuebi['booking_date']==$valuetd){


                                        $hasBook=array();
                                        
                                        //找出时间段
                                        if($this->timeAndNum[$valuebi['booking_time'].'-'.$valuebi['end_time']]){
                                            $str=$this->timeAndNum[$valuebi['booking_time'].'-'.$valuebi['end_time']];
                                        }else{
                                           
                                            $start=explode(':', $valuebi['booking_time']);

                                            if($start[1]=='30'){
                                                $start[0]=$start[0]+1;
                                                $start[1]='00';
                                                $start=$valuebi['booking_time'].'-'.implode(':', $start);
                                            }else{
                                                $start[1]='30';
                                                $start=$valuebi['booking_time'].'-'.implode(':', $start);
                                            }

                                            $end=explode(':', $valuebi['end_time']);

                                            if($end[1]=='30'){
                                                $end[1]='00';
                                                $end=implode(':', $end).'-'.$valuebi['end_time'];
                                            }else{
                                                $end[0]=$end[0]-1;
                                                $end[1]='30';
                                                $end=implode(':', $end).'-'.$valuebi['end_time'];
                                            }
                                            $str=$this->timeAndNum[$start].'-'.$this->timeAndNum[$end];
                                        }
                                                

                                        $colData=explode('-', $str);
                                        if(count($colData)==1){
                                            $hasBook[]=$str;
                                        }else{     
                                            if($colData[0]){
                                                for ($j=$colData[0]; $j < $colData[1]+1; $j++) { 
                                                     $hasBook[]=$j;
                                                }
                                            }
                                        }
                                        
                                        //如果以预订的和按商品算出来的的时间段有共同的数字则是已经被预约，不算入库存
                                        $allRet= array_intersect($hasBook, $checkData);
                                        if(!empty($allRet)){
                                            break;
                                        }

                                }                

                            }
                            if(empty($allRet)){
                                $new_time_bet=$valuetd . ' ' . $time_bet[0];
                                $goods_key=$value['product_id'].'_'.$new_time_bet;
                                $duan=$valuen.'-'.$checkMax;
                                $allData[$goods_key]['duration']=intval($value['service_time']);
                                $allData[$goods_key]['booktime']=$valuetd;
                                $allData[$goods_key]['app_product_id']=$value['goods_sn'];
                                // $allData[$goods_key]['product_id']=intval($value['app_product_id']);
                                $allData[$goods_key]['remaincount']=intval($allData[$goods_key]['remaincount']+1);
                                $allData[$goods_key]['period']=array('app_period_id'=>$duan,'period_name'=>$time_bet[0]);
                            }
                        }
                        
                    }
                    
                }
            }
        }
        return $allData;
    }
}