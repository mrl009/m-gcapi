<?php
/**
 * 快速直通车支付 测试接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/24
 * Time: 16:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Test extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->helper('common_helper');
        $this->load->model('pay/Base_pay_model','BPM');
    }

    public function index()
    {

    }

    public function pay()
    {
        //接受参数
        $params = input("param.");
        if (empty($params['MerchantId']) || 
            empty($params['OrderId']) || 
            empty($params['Amount']))
        {
            exit('缺少必要参数');
        }
        //初始化参数
        $this->merch = $params['MerchantId'];
        $this->money = $params['Amount'];
        $this->order_num = $params['OrderId'];
        $this->getKey();
        //生成测试二维码
        $qrcode = '';
        //构造回调数据
        $data = $this->getBaseData();
        $data = $this->getPaySign($data);
        $data = $this->getPayData($data);
        //构造表单回调数据
        //$html = $this->buldFrom($params['NotifyUrl'],$sdata);
        //echo $html;
        $sdata['data'] = $data;
        $sdata['url'] = $params['NotifyUrl'];
        $this->load->view('fast_pay.html', $sdata);
    }

    private function buldFrom($url,$data)
    {
        $html = "<form merch='POST' action='{$url}'>";
        foreach($data as $key => $val)
        {
            $html .= "<input type='hidden' name='{$key}' value='{$val}'><br/>";
        }
        $html .= "<input type='submit' value='回调测试'><br/></form>";
        return $html;
    }


    private function getPayData($data)
    {
        $sdata = array(
           'MerchantId' => $this->merch,
           'OrderId' => $this->order_num
        );
        $string = json_encode($data,320);
        $sign = $this->encodePay($string);
        $sdata['data'] = $sign;
        return $sdata;
    }

    private function getBaseData()
    {
        $code = [1,2,4,5,7,8,9,12,13,17,18];
        $data = array(
            'MerchantId' => $this->merch,
            'OrderId' => $this->order_num,
            'Amount' => $this->money,
            'PayStatus' => 'SUCCESS',
            'PayTime' => date('Y-m-d H:i:s'),
            'PayType' => array_rand($code,1)
        );
        return $data;
    }


    private function getPaySign($data)
    {
        ksort($data);
        $key = $this->key;
        $string = data_to_string($data);
        $string .= "&key={$key}";
        $data['Sign'] = hash('sha256', $string);
        return $data;
    }
    

    //获取商户KEY
    private function getKey()
    {
        $where['merch'] = $this->merch;
        $info = $this->BPM->get_one('*','bank_fast_pay',$where);
        if (empty($info)) exit('商户不存在');
        $pk = loadPubPriKey($info['pay_public_key'],'');        
        $this->key = $info['pay_key'];
        $this->p_key = $pk['publicKey'];
    }

    //数据加密
    private function encodePay($data)
    {
        $str = '';
        $encryptData = '';
        $sk = openssl_pkey_get_public($this->p_key);
        if (empty($sk)) exit('解析商户公钥失败');
        foreach (str_split($data, 117) as $chunk) 
        {
            openssl_public_encrypt($chunk, $encryptData, $sk);
            $str = $str . $encryptData;
        }
        return base64_encode($str);
    }

}
