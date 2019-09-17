<?php
/*
 * 美团授权工具类
 * @author:     jianghonggang
 * @date:       2019-01-03
 */
require('util.php');        

class oauth extends util{
    public function __construct(){
        
    }

    function mtCallBack($data){
        $url='https://'.$_SERVER['SERVER_NAME'];

        if($data['auth_code']){
            $sess_ret=$this->getMtSession($data);
            if($sess_ret){
                $_SESSION['MT_TOKEN_SESSION'] = $ret;
                $url=$url.'/store/mtUtil.php?act=showMtRet&ret=1';
                header("Location:".$url);
            }else{
                $url=$url.'/store/mtUtil.php?act=showMtRet&ret=2';
                header("Location:".$url);
            }
        }else{
             $url=$url.'/store/mtUtil.php?act=showMtRet&ret=2';
            header("Location:".$url);
        }
    }

    function getMtSession($data){
        $redirect_url='https://storeapp.chlitina.com.cn/meituan/index.php/ktmtoauth';
        $request_url='https://openapi.dianping.com/router/oauth/token';
        $getdata['app_key']=$this->Appkey;
        $getdata['app_secret']=$this->appSecret;
        $getdata['auth_code']=$data['auth_code'];
        $getdata['grant_type']='authorization_code';
        $getdata['redirect_url']=$redirect_url; 
        $ret=$this->toRequest($request_url,$getdata,'post');
        if($ret->code==200){
            $save_data['update_time']=date('Y-m-d h:i:s');
            $save_data['rcomp']=$data['state'];
            $save_data['access_token']=$ret->access_token;
            $save_data['expires_in']=$ret->expires_in;
            $save_data['refresh_token']=$ret->refresh_token;
            $save_data['bid']=$ret->bid;
            $sql="select id from em_mt_session where bid='".$ret->bid ."' and rcomp='".$data['state']."'";
            $id=$GLOBALS['db']->getOne($sql);
            // $openID=$this->getShopUuidFromMt($save_data['access_token'],$save_data['bid'],$data['state']);
            // if($openID==false){
            //     return false;
            // }
            if($id){
               $saveRet=$GLOBALS['db']->autoExecute('em_mt_session', $save_data, 'UPDATE',"id={$id}");      
                if($saveRet){
                        return $ret->access_token;
                }else{
                    return false;
                }   
            }else{
               $saveRet=$GLOBALS['db']->autoExecute('em_mt_session', $save_data, 'INSERT');   
                if($saveRet){
                    return $ret->access_token;
                }else{
                    return false;
                } 
            }
        }else{
            return false;
        }

    }

    function toGetOauth($store_code){
        $redirect_url='https://storeapp.chlitina.com.cn/meituan/index.php/ktmtoauth';
        $url='https://e.dianping.com/dz-open/merchant/auth?app_key=' . $this->Appkey . '&state='. $store_code .'&redirect_url='.$redirect_url;
        header("Location:".$url);
    }


}