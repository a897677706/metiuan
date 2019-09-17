<?php
define('IN_ECS', true);
$dzopen=strrpos($_SERVER['PATH_INFO'],'/dzopen/book/');
$pro=strrpos($_SERVER['PATH_INFO'],'/dzopen/ecommerce/');
$oauth=strrpos($_SERVER['PATH_INFO'],'/ktmtoauth');
require('../includes/init.php');    
if($dzopen===0){
    require('api/book.php');
    $book=new book();
}else if($pro===0){
    require('api/product.php');
    $product=new product();
}else if($oauth===0){
    require('api/oauth.php');
    $data=array_merge($_GET,$_POST);
    $oauth=new oauth();
    $oauth->mtCallBack($data);
}else{
    echo "对不起，暂时没有此功能！";
}





