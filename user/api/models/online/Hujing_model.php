<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Xiaoxiong_model.php';

class Hujing_model extends Xiaoxiong_model
{
    protected $c_name = 'hujing';
    private $p_name = 'HUJING';//商品名称
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }
}