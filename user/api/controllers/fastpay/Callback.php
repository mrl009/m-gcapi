<?php
/**
 * 快速直通车回调 接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/24
 * Time: 16:38
 */
defined('BASEPATH')or exit('No direct script access allowed');

class Callback extends GC_Controller
{
    private $r_name = 'fast';
    private $success = "SUCCESS"; //成功响应

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('common_helper');
        $this->load->model('pay/Online_model','PM');
        $this->load->model('pay/Base_pay_model','BPM');
    }

    //回调接口数据
    public function callbackurl()
    {
        $name = $this->r_name;
        //获取异步返回的数据
        $data = $this->getReturnData();
        //根据订单号获取用户信息/支付信息 
        $merch = $data['MerchantId'];
        $order_num = $data['OrderId'];
        //对需要处理的订单加锁
        $bool = $this->BPM->fbs_lock('temp:new_order_num' . $order_num);
        if (!$bool) exit('正在处理,请稍后');
        $pay = $this->BPM->order_detail_fast($merch,$order_num);
        if (empty($pay)) 
        {
            $msg = "无效的订单号";
            $this->BPM->online_erro($name, "{$msg}:{$order_num}");
            exit($msg);
        }
        //验证IP并设置私钥信息
        $this->getSkey($pay,$name);
        $data = $this->verifyData($data['Data'],$name);
        //验证数据
        if ($order_num <> $data['OrderId'])
        {
            $msg = "订单号错误";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        //验证支付状态
        if ('SUCCESS' <> $data['PayStatus'])
        {
            $msg = "未成功支付";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        //验证签名
        $this->verifySign($data,$pay['pay_key'],$name);
        //验证金额 
        if ($pay['price'] <> $data['Amount'])
        {
            $msg = "金额验证失败";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        $pay['pay_code'] = $data['PayType'];
        //判断订单状态是否已经处理
        if (1 <> $pay['status']) exit($this->success);
        //更新订单信息及用户余额
        $bool = $this->BPM->update_order($pay);
        if ($bool) exit($this->success);
        $this->BPM->online_erro($name, '写入现金记录失败:');
        exit('加钱失败');
    }

    private function getReturnData()
    {
        //redis记录支付错误信息标识
        $name = $this->r_name;
        $data = file_get_contents("php://input");
        if (!empty($data))
        {
            $this->BPM->online_erro("{$name}_put",$data);
        } else {
            $data = $_REQUEST;
            $this->PM->online_erro("{$name}_data",json_encode($data,true));
        }
        //获取需要的参数
        if (empty($data['OrderId']) || empty($data['Data'])
        || empty($data['MerchantId']))
        {
            $msg = "缺少必要参数";
            $this->BPM->online_erro($name, "{$msg}OrderId、Data、MerchantId");
            exit($msg);
        }
        return $data;
    }

    /**
     * 获取解析以后的json数据并转化为数组
     */
    private function verifyData($data,$name)
    {
        $data = $this->decodePay($data,$name);
        if (empty($data))
        {
            $msg = "解析数据错误";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        $data = json_decode($data,true);
        if (empty($data))
        {
            $msg = "解析数据格式错误";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        if (empty($data['OrderId']) || empty($data['Amount'])
            || empty($data['PayStatus']) || empty($data['Sign'])
            || empty($data['PayType']))
        {
            $msg = "缺少必要参数";
            $must = 'OrderId、Amount、PayStatus、PayType、Sign';
            $this->BPM->online_erro($name, "{$msg}：{$must}");
            exit($msg);
        }
        return $data;
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    private function verifySign($data,$key,$name)
    {
        //获取待验证签名
        $v_sign = $data['Sign'];
        unset($data['Sign']);
        //构造要验证签名
        ksort($data); 
        $string = data_to_string($data) . "&key={$key}";
        $sign = hash('sha256', $string);
        if ($sign <> $v_sign)
        {
            $this->BPM->online_erro($name, "签名验证失败:{$v_sign}");
            exit($msg);
        }
    }
 
    /*
     * 获取用户私钥数据
     */
    private function getSkey($pay,$name)
    {
        //验证IP
        $ip = get_ip();
        if (!empty($pay['validate_ip']))  
        {
            $verify_ip = explode(',',$pay['validate_ip']); 
        }
        if (empty($pay['validate_ip']) || !in_array($ip,$verify_ip)) 
        {
            $msg = "IP白名单限制";
            $this->BPM->online_erro($name, "{$msg}:{$ip}");
            exit($msg);
        }
        //私钥赋值
        if (empty($pay['pay_private_key']))
        {
            $msg = "商户私钥不存在";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        //给私钥增加头部信息
        $pk = loadPubPriKey('',$pay['pay_private_key']);
        if (!empty($pk['privateKey'])) $pk = $pk['privateKey'];
        $this->p_key = $pk;
    }

   
    /*
     * 秘钥解密方式
     */
    private function decodePay($data,$name)
    {
        $crypto = '';
        //解密数据
        $data = base64_decode($data);
        if (empty($data))
        {
            $msg = "data不是64加密格式数据";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        //使用私钥进行二次解密
        $pk = openssl_pkey_get_private($this->p_key);
        if (empty($pk))
        {
            $msg = "商户私钥解析错误";
            $this->BPM->online_erro($name, "{$msg}");
            exit($msg);
        }
        //分段解密   
        foreach (str_split($data, 128) as $chunk) 
        {
            openssl_private_decrypt($chunk, $decryptData, $pk);
            $crypto .= $decryptData;
        }
        return $crypto;
    }
}

