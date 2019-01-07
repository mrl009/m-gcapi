<?php
/**
 * 猪哥亮支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhugeliang_model extends Publicpay_model
{
    protected $c_name = 'zhugeliang';
    protected $p_name = 'ZGL';
    //支付接口签名参数 
    private $ks = '&key='; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        return $this->buildForm($data,'get');
    }
    
    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        $string = implode('',array_values($data));
        $data['refer'] = $this->returnUrl;
        $data['notify_url'] = $this->callback;
        $data['sign'] = md5($string);
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['money'] = $this->money;
        $data['record'] = $this->orderNum;
        $data['sdk'] = $this->merId;
        return $data;
    }
}