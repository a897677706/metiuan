<?php
class util{
    public $appSecret='e624bdc675b1de7db19f015a32e71432a55cad3a';
    public $Appkey='4d798a5fac5ca935';
    // public $appSecret='6e66425fb167789fdea6515ce296c96ce9b2bc4c';
    // public $Appkey='a37b436e1f66d639';
    public $log_id='';
    public $timeAndNum=array(
   '10:00-10:30'=>1, '10:30-11:00'=>2,'11:00-11:30'=>3,'11:30-12:00'=>4,'12:00-12:30'=>5,'12:30-13:00'=>6,'13:00-13:30'=>7,'13:30-14:00'=>8,'14:00-14:30'=>9,'14:30-15:00'=>10,'15:00-15:30'=>11,'15:30-16:00'=>12,'16:00-16:30'=>13,'16:30-17:00'=>14,'17:00-17:30'=>15,'17:30-18:00'=>16,'18:00-18:30'=>17,'18:30-19:00'=>18,'19:00-19:30'=>19,'19:30-20:00'=>20,'20:00-20:30'=>21,'20:30-21:00'=>22,'21:00-21:30'=>23,'21:30-22:00'=>24
    );//,'22:00-22:30'=>25,'22:30-23:00'=>26,'23:00-23:30'=>27
    public $promo_type=array(
        '8'=>'商家抵用券', '16'=>'美团商家红包','17'=>'商家立减','18'=>'美团商家立减','22'=>'打折卡'
    );
    public $pay_type=array(0=>'免单',
                            1 => '刷卡(默认)',
                            2 => '现金',
                            3 =>'支付宝',
                            4 =>'微信',
                            5 => '大众点评',
                        );
     /*
     * 核销之后修改服务单核销状态
     * @param  $receipt_code 券码
     ＊ 
     */
    function updateServiceRcs($service_doc,$receipt_code){
        $log_id=$GLOBALS['db']->autoExecute('em_service_h',array('receipt_code'=>$receipt_code) , 'UPDATE'," service_doc='{$service_doc}'");
        return $log_id;
    }
    /*
    *$data签名的数据
    *获取签名
    */
    function toSign($data){

        foreach ($data as $key => $value) {
            if($value==''||$value==null){
                unset($data[$key]);
            }
        }

        ksort($data);
        $signstr ='';
        foreach($data as $k=>$v){
            if($k!='sign'&&$k!='sign_type'&&$v!==''){
                $signstr =$signstr.$k.$v;
            }
        }

        $signstr = $this->appSecret.$signstr.$this->appSecret;
        $md5Data=md5($signstr);
        $signstr=strtolower($md5Data);
        return $signstr;
    }
    /*
    *$data请求数据
    *$type请求方法
    *获取签名
    */
    function toRequest($url,$data,$type){
        $data=$this->createLinkstringUrlencode($data);

        if (empty($url)) {
            return false;
        }
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); // 抓取指定网页
                                             // 最初设定为8s，增加至30s
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 请求超时时间(秒)
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        if($type=='post'){
            curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $ret = curl_exec($ch); // 运行curl
        $error_no = curl_errno($ch);
        curl_close($ch);
        if ($error_no == 0) {
            return json_decode($ret);
        } else {
            return '4';//网络超时，请重试！
        }
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstringUrlencode($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".urlencode($val)."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        
        return $arg;
    }

    /*
    *$data要保存数据
    *保存日志
    */
    function getReturnData($code,$msg,$data){
        $retData=array('code'=>intval($code),'msg'=>$msg,'data'=>$data);
        if($code=='200'){
            $this->upMeiTuanLog('2');
        }else{
            $this->upMeiTuanLog('3');
        }
        $GLOBALS['db']->query('commit');
        echo json_encode($retData,1);exit();
    }

    /*
    *美团api log
    *
    */
    
    function meiTuanLog($data,$comment,$rcomp,$type){
        $saveData=array('data'=>serialize($data),'comment'=>$comment,'rcomp'=>$rcomp,'type'=>$type,'add_time'=>date('Y-m-d h:i:s'));
        $ret=$GLOBALS['db']->autoExecute('em_mt_log',$saveData , 'INSERT');
        $log_id = $GLOBALS['db']->insert_id();
        $this->log_id=$log_id;
        return $log_id;
    
    }
     /*
    *更新美团api log
    *
    */
    
    function upMeiTuanLog($status){
        $saveData=array('status'=>$status);
        $log_id=$this->log_id;
        if($log_id!=''){
            $log_id=$GLOBALS['db']->autoExecute('em_mt_log',$saveData , 'UPDATE'," id = '$log_id' ");
            return $log_id;
        }else{
            return false;
        }
    
    }
     /*
    *美团订单是否预约成功
    *
    */
    function  mt_order_is_create($data){
        
        $sql = "select * from em_mt_order_info where mt_order_id='".$data['order_id']."'";

        $mt_order_id=$GLOBALS['db']->getRow($sql);

        if(!empty($mt_order_id['mt_order_id'])){

            $retData=array('order_id'=>$mt_order_id['mt_order_id'],'app_order_id'=>$mt_order_id['app_order_id'],'mobile'=>$mt_order_id['mobile']);

            $this->getReturnData('200','success',$retData);

        }
    }

    /*
    *
    *创建服务单
    */
    function createService($data,$user_data,$booking=array()){
        $delivery_info=json_decode($data['delivery_info']);
        //插入表头数据
        $service_h = array(
            'stext' => $user_data['real_name'],
            'mobile' => $user_data['mobile'],
            'store_id' => $user_data['store_id'],
            'user_id' => '',
            'member_id' => $user_data['user_id'],
            'visitorsnum' => $data['quantity'],
            'pos_id' => '',
            'room_id' => $booking['room_id'],
            'trans_date' => date('Y-m-d'),
            'settle' => '',
            'booking_id' => $booking['booking_id']?$booking['booking_id']:'',
            'strtime' =>$booking['booking_time'],
            'endtime' =>$booking['end_time'],
            'remark' => $data['comment'],
            'total' => $data['amount'],
            'act_total' => $data['pay_amount']>0?$data['pay_amount']:$data['amount'],
            'mt_discount'=>$data['discount_amount'],
            'mt_order_id'=>$data['order_id'].'_'.$delivery_info->delivery_type
        );

        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('service_h'), $service_h, 'INSERT');
        $service_doc = $GLOBALS['db']->insert_id();

        $order_data=array('rcomp'=>$booking['rcomp'],
                          'store_id'=>$data['app_shop_id'],
                          'mt_order_id'=>$data['order_id'],
                          'show_id'=>$service_doc,
                          'type'=>'product',
                          'pay_amount'=>$data['pay_amount'],
                          'delivery_info'=>$data['delivery_info'],
                          'amount'=>$data['amount'],
                          'mobile'=>$data['mobile'],
                          'add_time'=>date('Y-m-d h:i:s')
                          );
        $order_log_id=$this->saveOrderData($order_data);

        if($order_log_id==false){
            return false;
        }

        $goods_list=array();
        if($booking['booking_id']){

            $saler=$this->mt_get_saler($booking);

            if($saler==false){
                return false;
            }

            $today = date('Y-m-d');
            $type=1;//服务单
            $goods_list[0]['app_product_id']=$data['app_product_id'];
            $goods_list[0]['product_id']=$data['product_id'];
            $goods_list[0]['qty']=$data['quantity'];
            $sql = 'select B.kbetr,P.product_sn as matnr_full,P.title,P.goods_id from '. $GLOBALS['ecs']->table('products') . ' as P '
                .' left join ' . $GLOBALS['ecs']->table('a154') . ' as A on P.product_id=A.product_id '
                .' left join ' . $GLOBALS['ecs']->table('konp') . ' as B on A.id=B.aid '
                ."where A.store_id={$user_data['store_id']} and A.strdat<='{$today}' and A.enddat>='{$today}' and A.product_id={$data['product_id']}";
            $goods_data = $GLOBALS['db']->getRow($sql);
            $goods_list[0]['price'] = $data['amount']/ $data['quantity'];
            $goods_list[0]['matnr_full'] = $goods_data['matnr_full'];
            $goods_list[0]['title']=$goods_data['title'];
            $goods_list[0]['goods_id']=$goods_data['goods_id'];

        }else{
            $type=2;//商品            
            $saler=$this->mt_get_saler($booking);

            if($saler==false){
                return false;
            }
            $app_products=json_decode($data['app_products']);
            foreach ($app_products as $key => $value) {
               
                $goods_list[$key]['qty']=$value->quantity;
                $sql = 'select product_id,product_sn as matnr_full,title,goods_id from '. $GLOBALS['ecs']->table('products') ."where goods_sn='{$value->app_product_id}'";

                $goods_data = $GLOBALS['db']->getRow($sql);
                if(empty($goods_data)){
                    return false;
                }

                $goods_list[$key]['product_id']=$goods_data['product_id'];
                $goods_list[$key]['price'] = $value->amount/$value->quantity;
                $goods_list[$key]['matnr_full'] = $goods_data['matnr_full'];
                $goods_list[$key]['title']=$goods_data['title'];
                $goods_list[$key]['goods_id']=$goods_data['goods_id'];
            }

        }

        $upRet=$this->proservice_detail_update($service_doc, $goods_list,$type,$saler[0]);
        if($upRet){
            return $service_doc;
        }else{
            return $upRet;
        }
    }

    /**
     * 服务单明细更新，因为插入和更新都需要使用，这边独立方法
     * @param type $service_doc
     * @param type $goods
     */
    function proservice_detail_update($service_doc,$goods_list,$type,$saler){
        //插入服务项目
        $item = 0;
        foreach($goods_list as $goods){
            if($type == '2'){
                //商品信息
                $goods_info = array(
                    'service_doc' => $service_doc,
                    'item' => ++$item,
                    'matnr_full' => $goods['matnr_full'],
                    'price' => $goods['price'],
                    'title' => $goods['title'],
                    'qty' => $goods['qty'],
                    'goods_id' => $goods['goods_id'],
                    'product_id' => $goods['product_id'],
                    'come_from' => 'mt',
                    'settle_rule' => 1
                );
                $ret1=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('service_d4'), $goods_info, 'INSERT');
                if($ret1){
                    $goods_employee = array(
                        'service_doc' => $service_doc,
                        'item' => $item,
                        'item_no' => $goods['product_id'],
                        'member_id' => $saler['user_id'],
                        'member_name' => $saler['real_name'],
                    );
                    $ret2=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('service_d41'), $goods_employee, 'INSERT');
                    if(!$ret2){
                        return false;
                    }
                }else{
                    return false;
                }
            }
            elseif($type == '1'){
                //服务信息
                $goods_info = array(
                    'service_doc' => $service_doc,
                    'item' => ++$item,
                    'matnr_full' => $goods['matnr_full'],
                    'qty' => $goods['qty'],
                    'price' => $goods['price'],
                    'title' => $goods['title'],
                    'goods_id' => $goods['goods_id'],
                    'product_id' => $goods['product_id'],
                    'come_from' => 'mt',
                    'settle_rule' => 1
                );
                $ret1=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('service_d2'), $goods_info, 'INSERT');
                if($ret1){
                    $goods_employee = array(
                        'service_doc' => $service_doc,
                        'item' => $item,
                        'item_no' => $goods['product_id'],
                        'member_id' => $saler['user_id'],
                        'member_name' => $saler['real_name'],
                    );
                    $ret2=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('service_d21'), $goods_employee, 'INSERT');
                    if(!$ret2){
                            return false;
                    }
                }else{

                     return false;
                }
                
            }
        }
        return true;
    }

    
    /*
    *根据手机号检查会员
    *没有就自动添加
    */
    function checkAndAddUser($data){

        if(empty($data['mobile'])){
            $this->getReturnData('1522','no mobile');
        }

        if(empty($data['open_shop_uuid'])&&empty($data['app_shop_id'])){
            $this->getReturnData('1522','no open_shop_uuid or app_shop_id');
        }
        try {
                if($data['open_shop_uuid']){
                    $sql = "select A.mtext as mobile ,B.real_name,B.user_name,A.user_id , B.member_type , B.member_id,S.store_id,S.rcomp from  em_info_0001 as A left join em_users as B on A.user_id=B.user_id left join em_store as S on S.store_id=B.store_id  where A.mtext='".$data['mobile']."' and A.comm_id='01' and A.mark=0 and S.open_shop_uuid='".$data['open_shop_uuid']."'";
                }else{
                    $sql = "select A.mtext as mobile ,B.real_name,B.user_name,A.user_id , B.member_type , B.member_id,S.store_id,S.rcomp from  em_info_0001 as A left join em_users as B on A.user_id=B.user_id left join em_store as S on S.store_id=B.store_id  where A.mtext='".$data['mobile']."' and A.comm_id='01' and A.mark=0 and S.store_id='".$data['app_shop_id']."'";
                }

                $userData=$GLOBALS['db']->getRow($sql);
                $mobile = trim($data['mobile']);
                $password = $mobile ;//密码默认为手机号码
                $sex = empty($data['user_gender']) ? 0 : intval($data['user_gender']);
                if($userData['user_id']){
                    $store_id = intval($userData['store_id']);
                    $rcomp = $userData['rcomp'];
                    $member_id=$userData['member_id'];
                    $user_id=$userData['user_id'];
                    $real_name = $userData['real_name'];  //default 用手机号
                    $email =$store_id.'_'.$mobile.'@cltn.com';
                    $username=$userData['user_name'];
                    if(in_array($userData['member_type'], array(1,7))){
                        return $userData;
                    }else{
                        if($userData['member_type']==2){
                            $member_type=7;
                        }
                    }
                    

                }else{

                    if($data['open_shop_uuid']){
                        $sqlStore = "select rcomp, store_id from  em_store where  open_shop_uuid='".$data['open_shop_uuid']."'";

                    }else{
                        $sqlStore = "select rcomp, store_id from  em_store where  store_id='".$data['app_shop_id']."'";

                    }
                    $storeData=$GLOBALS['db']->getRow($sqlStore);
                    $rcomp = $storeData['rcomp'];
                    $store_id = $storeData['store_id'];
                    $username = $mobile.'@'.$store_id;//empty($data['user_name']) ? $mobile.'@'.$store_id : trim($data['user_name']);//为空则使用[手机号码.store_id]作为username
                    $real_name =empty($data['user_name']) ? $mobile : trim($data['user_name']);  //default 用手机号
                    $email =$store_id.'_'.$mobile.'@cltn.com';

                    $sqll = "select B.user_id,B.member_type,B.real_name from  em_users as B  left join em_store as S on S.store_id=B.store_id  where B.user_name='".$username."'  and B.mark=0 and S.store_id='".$store_id."'";
                    $checkHasUser=$GLOBALS['db']->getRow($sqll);
                    if(empty($checkHasUser)){
                        $GLOBALS['db']->autoExecute('em_users', array('user_name'=>$username,'email'=>$email,'real_name'=>$real_name,'password'=>$password),'INSERT');
                        $user_id=$GLOBALS['db']->insert_id();
                    }else{
                        if($checkHasUser['member_type']==2||$checkHasUser['member_type']==7){
                            $member_type=7;
                        }
                        $user_id=$checkHasUser['user_id'];
                        $real_name =$checkHasUser['real_name'];
                    }
                    $member_id = sprintf("%010d", $user_id);
                    // $this->meiTuanLog(array('user_id'=>$user_id,'rcomp'=>$rcomp),'来自于美团的会员添加',$rcomp,'mt_adduser',$db);
                    $other_0050=array();
                    $other_0050['member_id'] = $member_id;
                    $other_0050['store_id'] = $store_id;
                    $other_0050['rcomp'] = $rcomp;
                    $other_0050['create_date'] = local_date('Y-m-d H:i:s');
                    $other_0050['update_date'] = local_date('Y-m-d H:i:s');
                    $other_0050['mark'] = '0';
                    $other_0050['marry'] = $data['married'];
                    // info_0050 End ======================================================================
                    $comm = array('member_id'=>$member_id,'user_id'=>$user_id,'comm_id'=>'01','mtext'=>$mobile,'add_time'=>date('Y-m-d H:i:s'),'other'=>'mt');
                    $sql = "select id,mtext from  em_info_0001  where mark=0 and user_id=" . $user_id . " and comm_id='01' " ;
                    $info_id = $GLOBALS['db']->getRow($sql);
                    if (empty($info_id)){
                        $GLOBALS['db']->autoExecute('em_info_0001', $comm, 'INSERT');
                    }else{
                        if($info_id['mtext']!=$mobile){
                            $GLOBALS['db']->autoExecute('em_info_0001', array('mtext'=>$mobile), 'UPDATE', "id = {$info_id['id']}");
                        }
                    }
                    $info = array('member_id'=>$member_id,'user_id'=>$user_id,'mtext'=>'','add_time'=>date('Y-m-d H:i:s'));
                    $GLOBALS['db']->autoExecute('em_info_0004', $info, 'INSERT');
                    $GLOBALS['db']->autoExecute('em_info_0005', $info, 'INSERT');
                    $GLOBALS['db']->autoExecute('em_info_0006', $info, 'INSERT');
                    $GLOBALS['db']->autoExecute('em_info_0007', $info, 'INSERT');
                    $GLOBALS['db']->autoExecute('em_info_0008', $info, 'INSERT');
                    $GLOBALS['db']->autoExecute('em_info_0009', $info, 'INSERT');
                    $sql50 = "select member_id from  em_info_0050  where member_id=" . $member_id  ;
                    $info_50 = $GLOBALS['db']->getOne($sql50);
                    if (empty($info_50)){
                        $GLOBALS['db']->autoExecute('em_info_0050', $other_0050, 'INSERT');
                    }
                }

                if($member_type==7){
                    $other['member_type']=7;
                    $other['identity_num'] = $data['identity_num'];
                }else{
                    $other['member_type']=1;
                    $other['come_from']='meituan';
                    $other['store_id']=$store_id;
                    $other['rcomp']=$rcomp;
                    $other['sex']        = $sex;
                    $other['member_id']=$member_id;
                    $other['identity_num'] = $data['identity_num'];
                }

                //更新会员联系方式,其他详细信息
                $GLOBALS['db']->autoExecute('em_users', $other, 'UPDATE', "user_id = '$user_id'");

                $retData['user_id']=$user_id;
                $retData['come_from']='meituan';
                $retData['store_id']=$store_id;
                $retData['rcomp']=$rcomp;
                $retData['member_id']=$member_id;
                $retData['mobile']=$mobile;
                $retData['user_name']=$username;
                $retData['real_name']=$real_name;
                return $retData;
        } catch (exception $e) {
            $this->getReturnData('1522','fail');
        }
    }
    function getStoreData($data){
        if($data['open_shop_uuid']){
            $sql = "select store_id,rcomp from  em_store where is_active=1 and open_shop_uuid='".$data['open_shop_uuid']."'";
            $storeData=$GLOBALS['db']->getRow($sql);
        }else if($data['app_shop_id']){
             $sql = "select store_id,rcomp from  em_store where is_active=1 and store_id='".$data['app_shop_id']."'";
            $storeData=$GLOBALS['db']->getRow($sql);
        }
        return $storeData;
    }
    function getStoreRoom($rcomp,$timeData){
        $max=count($timeData)-1;
        $sql = "select t.* from  em_mt_room_time t left join em_store_room r on r.id=t.room_id and r.rcomp='{$rcomp}'  where r.mt_ishow=1 and t.rcomp='{$rcomp}' and  (t.day >'{$timeData[0]}' or t.day ='{$timeData[0]}') and (t.day <'{$timeData[$max]}' or t.day ='{$timeData[$max]}')";
        $roomData=$GLOBALS['db']->getAll($sql);
        return $roomData;
    }
    function getStoreRoomAndBooking($rcomp,$timeData){
        $max=count($timeData)-1;
        $date=date('Y-m-d H:i:s');//and ('{$date}'>down_end_time or '{$date}'<down_start_time)
        $sql = "select id from  em_store_room where  rcomp='{$rcomp}'  and  mt_ishow=1 and mark=0 ";
        $data['roomData']=$GLOBALS['db']->getAll($sql);

        $sql = "select * from em_booking where rcomp = '{$rcomp}' and mark=0 and status_id <> 'CNCL' and  (booking_date >'{$timeData[0]}' or booking_date ='{$timeData[0]}') and (booking_date <'{$timeData[$max]}' or booking_date ='{$timeData[$max]}')";
        $data['booking']=$GLOBALS['db']->getAll($sql);
        return $data;
    }

      /**
     * 随机一个美容师
     * @param type $booking
     */
    function mt_get_saler($booking){
            $sql = 'select A.user_id,A.real_name from ' . $GLOBALS['ecs']->table('users') . ' as A ' ."left join " . $GLOBALS['ecs']->table('admin_user') . " as C on A.member_id=C.member_id where A.rcomp='{$booking['rcomp']}' and A.member_type in (2,7) and C.mark=0 and A.mark=0 and C.isboss<>1 and A.isboss<>1";
            $salerData = $GLOBALS['db']->getAll($sql);
            $salerCanUse=array();
            foreach ($salerData as $key => $value) {
                $sql = "select booking_id from " . $GLOBALS['ecs']->table('booking') 
                    ." where booking_date='" . $booking['booking_date'] . "'"
                    ." and booking_time < '" . $booking['end_time'] . "'"
                    ." and end_time > '" . $booking['booking_time'] . "'"
                    ." and artificer=" . $value['user_id']
                    ." and rcomp=" . $booking['rcomp']
                    ." and mark=0" 
                    ." and status_id <> 'CNCL'";
                $booking_id = $GLOBALS['db']->getRow($sql);

                if(empty($booking_id)){
                    $salerCanUse[]=$value;
                }
            }

            if (!empty($salerCanUse)){
                return $salerCanUse;
            }else{
                return false;
            } 
    }

    /*
    *保存美团订单的数据
    *
    */
    function saveOrderData($data){
        $data['app_order_id']= date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $log_id=$GLOBALS['db']->autoExecute('em_mt_order_info',$data , 'INSERT');
        if($log_id){
            return $data['app_order_id'];
        }else{
            return false;
        }
    }
    /*
    *保存美团订单的数据
    *
    */
    function upOrderData($data,$id){
        $log_id=$GLOBALS['db']->autoExecute('em_mt_order_info',$data , 'UPDATE'," id='{$id}' ");
        return $log_id;
    }

    /*
    *获取美团订单的数据
    *
    */
    function getOrderData($data,$type){
        $sql=" select  * from em_mt_order_info where type='{$type}' and show_id='{$data['booking_id']}' and rcomp='{$data['rcomp']}' ";
        $order_info = $GLOBALS['db']->getRow($sql);
        return $order_info;
    }

     /*
     * 平台在调用预订接口之后，第三方通过此接口异步通知该订单预订结果
     * @param  $BookData 预约数据
     ＊ @param  $book_ret 预约结果
     */
    function bookResultCallback($BookData,$book_ret,$code,$session,$order_info){
        $url="https://openapi.dianping.com/router/book/bookresultcallback";
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        //'app_shop_id'=>$BookData['store_id'],
                        'open_shop_uuid'=>$BookData['open_shop_uuid'],
                        'order_id'=> $order_info['mt_order_id'],
                        'book_status'=>$book_ret,
                        'code'=>$code,
                        'app_order_id'=>$order_info['app_order_id'],
                        'session'=>$session,
                        );
        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $ret=$this->toRequest($url,$postData,'post');

        if($ret->code=='200'){
            return true;
        }else{
            return false;
        }
    }
     /*
     * 平台在调用预订接口之后，第三方通过此接口异步通知该订单预订结果
     * @param  $BookData 预约数据
     ＊ @param  $book_ret 预约结果
     */
    function refundResultCallback($BookData,$audit_result,$session,$order_info){
        $url="https://openapi.dianping.com/router/ecommerce/refundauditresultsynctoplatform";
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        //'app_shop_id'=>$BookData['store_id'],
                        'open_shop_uuid'=>$BookData['open_shop_uuid'],
                        'order_id'=> $order_info['mt_order_id'],
                        'audit_result'=>$audit_result,
                        'session'=>$session,
                        );
        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $ret=$this->toRequest($url,$postData,'post');
        if($ret->code=='200'){
            return true;
        }else{
            return false;
        }
    }
    /*
     * 平台在调用下单接口之后，第三方通过此接口异步通知下单结果
     * @param  $BookData 预约数据
     ＊ @param  $book_ret 预约结果
     */
    function orderResultCallback($OrderData,$order_ret,$session,$order_info){
        $url="https://openapi.dianping.com/router/ecommerce/orderresultsynctoplatform";
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        'open_shop_uuid'=>$OrderData['open_shop_uuid'],
                        'order_id'=> $order_info['mt_order_id'],
                        'order_result'=>$order_ret,
                        'session'=>$session,
                        );
        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $ret=$this->toRequest($url,$postData,'post');
        if($ret->code=='200'){
            return true;
        }else{
            return false;
        }
    }
    /*
     * 第三方配送结果同步平台接口，比如商家发货后通知平台更新配送状态
     * @param  $BookData 预约数据
     ＊ @param  $book_ret 预约结果
     */
    function deliveryResultCallback($OrderData,$delivery_ret,$session,$order_info){
        $url="https://openapi.dianping.com/router/ecommerce/deliveryresultsynctoplatform";
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        'open_shop_uuid'=>$OrderData['open_shop_uuid'],
                        'order_id'=> $order_info['mt_order_id'],
                        'delivery_result'=>$delivery_ret,
                        'session'=>$session,
                        );
        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $ret=$this->toRequest($url,$postData,'post');
        if($ret->code=='200'){
            return true;
        }else{
            return false;
        }
    }
     /*
     * 根据团购券码，查询券码对应的dealid下，该用户可使用的券数据量
     * @param  $receipt_code 券码
     ＊ 
     */
    function prepare_code($receipt_code,$store_id,$session){
        $url="https://openapi.dianping.com/router/tuangou/receipt/prepare";
       
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d h:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        // 'app_shop_id'=>$store_id,
                        'open_shop_uuid'=> $this->getOpen_shop_uuid($store_id),
                        'receipt_code'=>$receipt_code,
                        'session'=>$session,
                        );
        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $ret=$this->toRequest($url,$postData,'post');
        if($ret->code=='200'){
            return $ret->data;
        }else{
            return false;
        }
    }
    /*
    *获取美团订单的数据
    *
    */
    function getServiceDocByOrderId($order_id,$rcomp){
        $sql=" select  show_id,type from em_mt_order_info where mt_order_id='{$order_id}' and rcomp='{$rcomp}'";
        $order_info = $GLOBALS['db']->getRow($sql);
        if(empty($order_info)){
            return false;
        }

        if($order_info['type']=='book'){
            $sql="select service_doc from em_service_h where booking_id={$order_info['show_id']} and mark=0";
            $serivce_doc = $GLOBALS['db']->getOne($sql);
            if(empty($serivce_doc)){
                return false;
            }else{
                return $serivce_doc;
            }
        }else{
            return $order_info['show_id'];
        }
    }

    /*
     * 根据团购券码，一次性验证同一订单下的若干团购券 同一个用户，同一个dealid下的券码
     * @param  $receipt_code 券码
     ＊ 
     */
    function consume_code($receipt_code,$rcomp){
        $sql="select * from em_mt_order_info where receipt_code='{$receipt_code}' and rcomp='{$rcomp}'";
        $order_info = $GLOBALS['db']->getRow($sql);
        $sql1="select access_token from em_mt_session where  rcomp='{$rcomp}'";
        $access_token = $GLOBALS['db']->getOne($sql1);
        if(!empty($order_info)){
            return $order_info;
        }else{
           // $url="https://openapi.dianping.com/router/book/selfpickupverify";
            $url="https://openapi.dianping.com/router/ecommerce/selfpickupverify";
            $postData=array('app_key'=>$this->Appkey,
                            'timestamp'=>date("Y-m-d H:i:s"),
                            'format'=>'json',
                            'sign_method'=>'MD5',
                            'v'=>1,
                            // 'app_shop_id'=>$store_id,
                            // 'order_id'=>'1111111',
                            'session'=>$access_token,
                            'verify_code'=>$receipt_code,
                            );

            $sign=$this->toSign($postData);
            $postData['sign']=$sign;
            $ret=$this->toRequest($url,$postData,'post');
            if($ret->code=='200'){
                $order_id=$ret->data;
                $data=array('receipt_code'=>$receipt_code ,'con_status'=>2);
                $log_id=$GLOBALS['db']->autoExecute('em_mt_order_info',$data , 'UPDATE'," mt_order_id='{$order_id}' ");
                if($log_id){
                    return array('mt_order_id'=>$order_id);
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

    }
     /*
     * 用户到店消费之后，第三方系统回调通知平台
     * @param  $receipt_code 券码
     ＊ 
     */
    function isvconsume($order_id,$store_id,$session){
        $url="https://openapi.dianping.com/router/book/isvconsume";
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d h:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        'open_shop_uuid'=>$this->getOpen_shop_uuid($store_id),
                        // 'open_shop_uuid'=>'',
                        'order_id'=>$order_id,
                        'session'=>$session,
                        );

        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $ret=$this->toRequest($url,$postData,'post');
        if($ret->code=='200'){
            return $ret->data;
        }else{
            return false;
        }
    }
     /*
     * 获取店铺id
     * @param  $open_shop_uuid 美团点评店铺id
     ＊ 
     */
    function getShop_id($open_shop_uuid){
       $sql="select store_id from em_store where open_shop_uuid='{$open_shop_uuid}' and is_active=1";
       $store_id = $GLOBALS['db']->getOne($sql);
       if($store_id){
        return $store_id;
       }else{
         $this->getReturnData('1522','shop no found');
       }
       
    }
    /*
     * 检查sesssion
     * @param  $open_shop_uuid 美团点评店铺id
     ＊ 
     */
    function checkShopSession($open_shop_uuid){
       $sql="select rcomp from em_store where open_shop_uuid='{$open_shop_uuid}' and is_active=1";
       $rcomp = $GLOBALS['db']->getOne($sql);
       $sql1="select access_token from em_mt_session where rcomp='{$rcomp}'";
       $access_token = $GLOBALS['db']->getOne($sql1);
       if(empty($access_token)){
         $this->getReturnData('1522','shop no session');
       }
       
    }
    /*
     * 获取店铺id
     * @param  $open_shop_uuid 美团点评店铺id
     ＊ 
     */
    function getShop_idByOrderId($mt_order_id){
       $sql="select store_id from em_mt_order_info where mt_order_id='{$mt_order_id}'";
       $store_id = $GLOBALS['db']->getOne($sql);
       if($store_id){
        return $store_id;
       }else{
         $this->getReturnData('1522','shop no found');
       }
       
    }
     /*
     * 获取店铺id
     * @param  $open_shop_uuid 美团点评店铺id
     ＊ 
     */
    function getOpen_shop_uuid($store_id){
       $sql="select open_shop_uuid from em_store where store_id='{$store_id}' and is_active=1";
       $open_shop_uuid = $GLOBALS['db']->getOne($sql);
       if($open_shop_uuid){
        return $open_shop_uuid;
       }else{
         $this->getReturnData('1522','shop no found');
       }
       
    }
     /*
     * 去掉数组里的双斜杠
     * @
     ＊ 
     */
    function removeSxg($data){
       foreach ($data as $key => $value) {
           $data[$key]=str_replace('\\', '', $value);
       }
       return $data;
    }
    /*
     * 适用店铺查询
     * @param  
     ＊ 
     */
    function getShopUuidFromMt($session,$bid,$rcomp){
        $url="https://openapi.dianping.com/router/oauth/session/scope";
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        'session'=>$session,
                        'bid'=>$bid,
                        );

        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        $url=$url.'?'.http_build_query($postData);
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); // 抓取指定网页
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 请求超时时间(秒)
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上

        $ret = curl_exec($ch); // 运行curl
        curl_close($ch);
        $ret=json_decode($ret);
        if($ret->code=='200'){
            foreach ($ret->data as $key => $value) {
                $name=str_replace('(', '（', $value->branchname);
                $name=str_replace(')', '）', $name);
                $GLOBALS['db']->autoExecute('em_store',array('open_shop_uuid'=>$value->open_shop_uuid) , 'UPDATE'," rcomp='".$rcomp."' and display_name='".$name."'");
            }
            return true;
        }else{
            return false;
        }
    }
    /*
    *获取店主id
    */
    public function getEmployeeId($store_id){

        $sql = "select B.user_id from " . $GLOBALS['ecs']->table("admin_user") . ' as A '
                .'inner join ' . $GLOBALS['ecs']->table("users") . " as B on B.store_id={$store_id} and  A.member_id=B.member_id "
                ."where A.isboss=1";
        $employee_id =  $GLOBALS['db']->getOne($sql);

        return $employee_id;
    }

    public function createOrder($data,$orderData){

    $sql="select r.member_name as saler_name,r.member_id as saler_id,d.product_id,d.service_doc ,d.goods_id ,d.qty ,d.title as goods_name ,d.price ,d.matnr_full ,h.stext,h.member_id,h.mobile ,h.store_id,h.user_id ,h.booking_id,h.remark,h.total,h.act_total,h.mt_discount from em_service_d4 as d left join em_service_h as h on d.service_doc=h.service_doc left join  em_service_d41 as r on r.service_doc=d.service_doc where d.service_doc={$orderData['show_id']} GROUP BY product_id";

    $service_data = $GLOBALS['db']->getAll($sql);   
    

    $sql="select order_id from em_postdl where service_doc={$orderData['show_id']} ";
    
    $order_id = $GLOBALS['db']->getOne($sql);   

    if(!empty($order_id)){
        return;
    }

    $order_info = array(
        'kokrs' => '00002',
        'rcomp' => $orderData['rcomp'],
        'work_date' => date('Y-m-d'),
        'trans_kind' => 'S1',
        'trans_time' => date('H:i:s'),
        'cashier_id' =>$this->getEmployeeId($orderData['store_id']),
        'trans_flag' => '0',
        'united_no' => '',
        'customer_title' => $service_data[0]['stext'],
        'items' =>count($service_data),
        'sub_total' => 0.00,
        'inv_total' => 0.00,
        'use_up' => 0,
        'member_id' => $service_data[0]['member_id'],
        'cust_phone' => $orderData['mobile'],
        'inv_no' => '',
        'no_of_customer' => 1,
       
    );

    $result = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('postdh'), $order_info, 'INSERT');
    if(!$result){
        return false;
    }
    $order_id = $GLOBALS['db']->insert_id();
    //插入表身信息
    $seq = 1;
    $sub_total = 0.00;
    $inv_total = 0.00;
    $money_num=0;
    $card_money_num=0;

    //遍历所有商品
    foreach($service_data as $keyg =>$goods){
            
        $postdl = array(
            'order_id' => $order_id,
            'kokrs' => '00002',
            'rcomp' => $orderData['rcomp'],
            'div_seq' => $seq,
            'work_date' => date('Y-m-d'),
            'trans_time' => date('H:i:s'),
            'plu_id' => '',
            'goo_nas' => $goods['goods_name'],
            'matnr_full' => $goods['matnr_full'],
            'discount' => $goods['mt_discount']*$goods['price']*$goods['qty']/$goods['total'],
            'qty' => $goods['qty'],
            'allSalerId' => $goods['saler_id'],
            'price' =>  $goods['price'],
            'pay_kind' =>1,
            'card_id' => 0,
            'dis_rate' => 0,
            'sub_total' =>$goods['price']*$goods['qty']-$goods['mt_discount']*$goods['price']*$goods['qty']/$goods['total'],
            'use_up' => 0,
            'service_doc' => $goods['service_doc'],
            'out_order_id'=>$data['order_id'],
        );
        
        
        $result = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('postdl'), $postdl, 'INSERT');
        if(!$result){
            return false;
        }
        $seq++;
      
        $inv_total += $postdl['sub_total'];
        
        $sub_total += $postdl['sub_total'];
    
    }
    //更新订单部分汇总信息
    $order_part = array(
        'sub_total' => $sub_total,
        'inv_total' => $inv_total,
    );

    $result = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('postdh'), $order_part, 'UPDATE', 'order_id='.$order_id);
    if(!$result){
        return false;
    }

    //更新服务单结算状态
    $result = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('service_h'), array('settle'=>'X' , 'docnr'=>$order_id), 'UPDATE', 'service_doc='.$service_data[0]['service_doc']);   
    if(!$result){
         return false;
    }
    

    
    if($service_data[0]['member_id']){
        $userret = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), array('last_time'=>date('Y-m-d')),'UPDATE','user_id='. $service_data[0]['member_id']);
    }      


    $GLOBALS['db']->query('COMMIT');
    //写入交易通知短信到 queue ,要判断加盟店的 交易短信设定 em_store-active_sms = 1 才发
    $sql = "select rcomp_bz,phone,display_name,active_sms from " . $GLOBALS['ecs']->table('store') . " where store_id=" . $orderData['store_id'];
    $active_sms = $GLOBALS['db']->getRow($sql); 
    
    if ( $active_sms['active_sms'] == 1){
        $sql = "select a.work_date , a.inv_total , a.trans_time , b.goo_nas , b.qty , b.sub_total , b.sales_flag from " . $GLOBALS['ecs']->table('postdh') . " as a left join " . $GLOBALS['ecs']->table('postdl') 
               . "as b on a.order_id = b.order_id where a.order_id=" . $order_id ;
        $trans_detail =     $GLOBALS['db']->getAll($sql); 
        
        $store_info = empty($active_sms['display_name']) ? $active_sms['rcomp_bz'] : $active_sms['display_name'] ;
        $item = '';
        foreach ($trans_detail as $row){
            $short_time = substr($row['trans_time'],0,5) ; 
            $work_date_time = $row['work_date'] . ';' . $short_time ;
            $inv_total = $row['inv_total'] ;
            if ($row['sales_flag'] == 1){
                $item = $item . "(赠)" . $row['goo_nas'] . ' 数量' . $row['qty'] . 'PC' . ' RMB' . $row['sub_total'] . ';';
            } else {
                $item = $item . $row['goo_nas'] . ' 数量' . $row['qty'] . 'PC' . ' RMB' . $row['sub_total'] . ';';
            }
            
        }                
        $sms_content = "尊敬的顾客您好，您在" . $work_date_time . '有一笔消费共计 ' . $inv_total . '元。'
                        . "明细如后：" . $item . "如有疑问，请在24小时内联系: " . $store_info . "Tel:"  . $active_sms['phone'] ;
    
        $sms_info = array('source_type' => 1 ,
                        'source_store_id' => $orderData['store_id'] ,
                        'source_rcomp' => $orderData['rcomp'] ,
                        'content' => $sms_content ,
                        'content_lenth' => strlen($sms_content) ,
                        'to_mobile' => $data['mobile'] ,
                        'create_date' => date('Y-m-d H:m:s')
                        );
        
        $result = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('sms_notice_pool'), $sms_info, 'INSERT');
        
        if(!$result){
                $GLOBALS['db']->query('ROLLBACK');
        }else{
                $GLOBALS['db']->query('COMMIT');
        }
    }
                        
    return $order_id;
    }
    /*
    *同步会员到美团
    */
   public function createUserTomt($user_data,$shopData){
        $url="https://openapi.dianping.com/router/mtchip/saasmember/upsert";
        $sql="select distinct i.user_id,u.to_mt,i.other,i.mtext,u.real_name,u.sex,u.birthday,u.aeedatime from em_users as u  left join em_info_0001 as i on i.user_id=u.user_id where   u.user_id in  (".implode(",", $user_data).")  GROUP BY user_id";

        $userData = $GLOBALS['db']->getAll($sql);  
        if(empty($userData)){
            return true;
        }

        foreach ($userData as $key => $value) {
            if( $value['to_mt']==1|| $value['other']=='mt'){
                unset($userData[$key]);
            }
        }
        
        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        'session'=>'7d4e0b0a8c44a64251b5451d75f9f4b95d9d0d50',
                        );
        $postData['requests']='[';
        $num=1;
        foreach ($userData as $key => $value) {

            $sex=$value['sex']==2?0:$value['sex'];
            if($num==count($userData)){
                $postData['requests']=$postData['requests'].'{"shopid":"'.$shopData['shopid'].'","app_member_id":"'.$value['user_id'].'","phone":"'.$value['mtext'].'","name":"'.$value['real_name'].'","sex":'.$sex.',"birthday":"'.date('Y-m-d',$value['birthday']).'","add_time":"'.date('Y-m-d',$value['aeedatime']).'","saas_shop_id":"'.$shopData['rcomp'].'"}]';
            }else{
                $postData['requests']=$postData['requests'].'{"shopid":"'.$shopData['shopid'].'","app_member_id":"'.$value['user_id'].'","phone":"'.$value['mtext'].'","name":"'.$value['real_name'].'","sex":'.$sex.',"birthday":"'.date('Y-m-d',$value['birthday']).'","add_time":"'.date('Y-m-d',$value['aeedatime']).'","saas_shop_id":"'.$shopData['rcomp'].'"},';
            }
            $num=$num+1;
        }

        if($postData['requests']=='['||strlen($postData['requests'])==1){
            return true;
        }

        $sign=$this->toSign($postData);
        $postData['sign']=$sign;
        error_log(var_export($postData,true),3,__FILE__.'.log');

        $ret=$this->toRequest($url,$postData,'post');
error_log(var_export($ret,true),3,__FILE__.'.log');

        if($ret->code=='200'){
            foreach ($userData as $key1 => $value1) {
               $userret = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), array('to_mt'=>1),'UPDATE','user_id='. $value1['user_id']);
            }
            return true;
        }else{
            return false;
        }
    }
    /*
    *同步订单到美团
    */
    public function createOrderTomt($orderData,$shopData){
        
        $url="https://openapi.dianping.com/router/mtchip/saastrade/upsert";

        $postData=array('app_key'=>$this->Appkey,
                        'timestamp'=>date("Y-m-d H:i:s"),
                        'format'=>'json',
                        'sign_method'=>'MD5',
                        'v'=>1,
                        'session'=>'7d4e0b0a8c44a64251b5451d75f9f4b95d9d0d50'//$shopData['access_token'],
                    );
        $requests='[';
                
        $num=1;                 
        foreach ($orderData as $key => $value) {  
            $goods_data='';
            $checkpay=array();
            $pay_data=',"pay_details":[';
            $discount=0;
            $num2=1;
            if($num==count($orderData)){
                $requests= $requests.'{"shopid":"'.$shopData['shopid'].'","member_shop_id":"'.$value['member_id'].'","app_member_id":"'.$value['member_id'].'","app_order_id":"'.$value['order_id'].'","order_time":"'.date('Y-m-d h:i:s',$value['work_date']).'","amount":'.$value['sub_total'].',"saas_shop_id":"'.$shopData['rcomp'].'","app_products":[';
              
                foreach ($value['goods'] as $keyg => $valueg) {
                    if($num2==count($value['goods'])){
                        $goods_data=$goods_data.'{"id":"'.$valueg['matnr_full'].'","name":"'.$valueg['goo_nas'].'","price":'.str_replace(',', '', number_format($valueg['price'],2)) .',"cost_price":'.str_replace(',', '',number_format($valueg['price']*$valueg['qty'],2))."}";
                       
                    }else{
                        $goods_data=$goods_data.'{"id":"'.$valueg['matnr_full'].'","name":"'.$valueg['goo_nas'].'","price":'.str_replace(',', '',number_format($valueg['price'],2)).',"cost_price":'.str_replace(',', '',number_format($valueg['price']*$valueg['qty'],2))."},";
                    
                    }
                    //支付方式
                    if( $valueg['pay_kind']>6){
                        $checkpay['会员卡']= $checkpay[$valueg['pay_kind']]+$valueg['price']*$valueg['qty'];
                    }else{
                        $checkpay[$this->pay_type[$valueg['pay_kind']]]= $checkpay[$this->pay_type[$valueg['pay_kind']]]+$valueg['price']*$valueg['qty'];
                      
                    }
                            
               

                    $discount= $discount+str_replace(',', '',number_format($valueg['discount'],2));
                    $num2=$num2+1;

                }

                $requests= $requests.$goods_data.'],"pay_details":[';
                $nump=1;
                foreach ($checkpay as $keyp => $valuep) {
                    if($nump==count($checkpay)){
                        $requests= $requests.'{"pay_type":"'.$keyp.'","pay_amount":'.str_replace(',', '',number_format($valuep,2))."}".']';
                    }else{
                        $requests= $requests.'{"pay_type":"'.$keyp.'","pay_amount":'.str_replace(',', '',number_format($valuep,2))."},";
                    }
                      $nump=$nump+1;
                }
                
                $requests= $requests.',"discount_details":{"discount_type":"会员卡","trade_discount_amount":'.$discount.'}}]';
            }else{
                $requests= $requests.'{"shopid":"'.$shopData['shopid'].'","member_shop_id":"'.$value['member_id'].'","app_member_id":"'.$value['member_id'].'","app_order_id":"'.$value['order_id'].'","order_time":"'.date('Y-m-d h:i:s',$value['work_date']).'","amount":'.$value['sub_total'].',"saas_shop_id":"'.$shopData['rcomp'].'","app_products":[';
            
                foreach ($value['goods'] as $keyg => $valueg) {
                    if($num2==count($value['goods'])){
                        $goods_data=$goods_data.'{"id":"'.$valueg['matnr_full'].'","name":"'.$valueg['goo_nas'].'","price":'.str_replace(',', '',number_format($valueg['price'],2)).',"cost_price":'.str_replace(',', '',number_format($valueg['price']*$valueg['qty'],2))."}";
                       
                    }else{
                        $goods_data=$goods_data.'{"id":"'.$valueg['matnr_full'].'","name":"'.$valueg['goo_nas'].'","price":'.str_replace(',', '',number_format($valueg['price'],2)).',"cost_price":'.str_replace(',', '',number_format($valueg['price']*$valueg['qty'],2))."},";
                     
                    }
                      //支付方式
                    if( $valueg['pay_kind']>6){
                        $checkpay['会员卡']= $checkpay[$valueg['pay_kind']]+$valueg['price']*$valueg['qty'];
                    }else{
                        $checkpay[$this->pay_type[$valueg['pay_kind']]]= $checkpay[$this->pay_type[$valueg['pay_kind']]]+$valueg['price']*$valueg['qty'];
                      
                    }
                    

                    $discount= $discount+number_format($valueg['discount'],2);
                    $num2=$num2+1;
                }

                $requests= $requests.$goods_data.'],"pay_details":[';
                $nump=1;
                foreach ($checkpay as $keyp => $valuep) {
                    if($nump==count($checkpay)){
                        $requests= $requests.'{"pay_type":"'.$keyp.'","pay_amount":'.str_replace(',', '',number_format($valuep,2))."}".']';
                    }else{
                        $requests= $requests.'{"pay_type":"'.$keyp.'","pay_amount":'.str_replace(',', '',number_format($valuep,2))."},";
                    }
                    $nump=$nump+1;
                }
                $requests= $requests.',"discount_details":{"discount_type":"会员卡","trade_discount_amount":'.$discount.'}},';
            }
            $num =$num + 1;
        }

        if($requests=='['||strlen($requests)==1){
            return true;
        }


        $postData['requests']=$requests;
        $sign=$this->toSign($postData);

        $postData['sign']=$sign;
        error_log(var_export($postData,true),3,__FILE__.'.log');

        $ret=$this->toRequest($url,$postData,'post');
error_log(var_export($ret,true),3,__FILE__.'.log');

        if($ret->code=='200'){
            foreach ($orderData as $key => $value) {  
                $userret = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('postdh'), array('to_mt'=>1),'UPDATE','order_id='. $value['order_id']);
            }
            return true;
        }else{
            return false;
        }
    }
}
