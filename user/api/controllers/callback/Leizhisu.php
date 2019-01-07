<?php
/**
 * 雷之速支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 09:08
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Leizhisu extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'LEIZHISU';
    //商户处理后通知第三方接口响应信息
    protected $success = "SUCCESS"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'key'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $mf = 'realprice'; //订单金额参数(实际支付金额)
    protected $vm = '0';//是否验证金额
    protected $vs = ['price'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    protected function verifySign($data,$key,$name)
    {
        //获取加密验证字段
        $sign = $data[$this->sf];
        //去除空元素和不参与加密字段
        unset($data['uid']);
        unset($data[$this->sf]);
        $data = array_filter($data);
        //升序排序并取出元素值组合排列
        ksort($data);
        $sign_data = array_values($data);
        $sign_string = implode('',$sign_data) . $key;
        $v_sign = md5($sign_string);
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
