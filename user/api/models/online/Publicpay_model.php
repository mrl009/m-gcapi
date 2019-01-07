<?php
/**
 * 支付模块基类 共用部分
 * @author      lqh
 * @package     model/online/publicpay
 * @version     v1.0 2018/05/04
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Publicpay_model extends MY_Model
{
    //扫码支付code
    protected $scan_code = [1,4,8,9,10,16,17,19,21,22,24,33,36];  
    //wap支付code
    protected $wap_code = [2,5,11,12,13,18,20,23,27];
    //网银快捷支付
    protected $short_code = [7,25,26,40,41];
    
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('common_helper');
    }

    /**
     * 构造支付相关数据 并返回前端使用数据
     * @param string $order_num 订单号
     * @param string $money 订单金额
     * @param array $pay 支付参数
     * @return array
     */
    public function call_interface($order_num, $money, $pay)
    {
        /*设置订单参数*/
        $this->money = isset($money) ? $money : '1.00';  //订单金额 元
        $this->orderNum = isset($order_num) ? $order_num : ''; //订单号
        //获取支付方式code
        $this->code = isset($pay['code']) ? intval($pay['code']) : 0;
        //银行卡信息 (银行类型)
        $this->cardNo = isset($pay['cardNo']) ? trim($pay['cardNo']) : '';
        $this->bank_type = isset($pay['bank_type']) ? $pay['bank_type'] : '';
        $this->cardType = isset($pay['cardType']) ? intval($pay['cardType']) : 1;
        //获取商户信息 (商户号 秘钥等)
        $mid = 'pay_id'; //数据库中 商户号的字段
        $mk = 'pay_key'; //数据库中 支付秘钥字段
        $pk = 'pay_private_key'; //数据库中 商户私钥字段
        $bk = 'pay_public_key'; //数据库中 商户公钥字段
        $sk = 'pay_server_key'; //数据库中 服务端(第三方)公钥字段
        $sm = 'pay_server_num'; //数据库中 机构号字段
        $this->merId = isset($pay[$mid]) ? trim($pay[$mid]) : '';
        $this->key = isset($pay[$mk]) ? trim($pay[$mk]) : '';
        $this->p_key = isset($pay[$pk]) ? trim($pay[$pk]) : '';
        $this->b_key = isset($pay[$bk]) ? trim($pay[$bk]) : '';
        $this->s_key = isset($pay[$sk]) ? trim($pay[$sk]) : '';
        $this->s_num = isset($pay[$sm]) ? trim($pay[$sm]) : '';
        unset($mid,$mk,$pk,$bk,$sk,$sm);
        //支付平台设置参数(网关地址 回调地址等)
        $cname = $this->c_name; //回调文件名称
        $pd = 'pay_domain'; //数据库中 支付域名参数
        $pu = 'pay_return_url'; //数据库中 支付返回地址参数
        $bc = "/index.php/callback/{$cname}/callbackurl";//设置回调地址
        $pd = isset($pay[$pd]) ? trim($pay[$pd]) : '';
        $pu = isset($pay[$pu]) ? trim($pay[$pu]) : $_SERVER['HTTP_REFERER'];
        $this->domain = $pd; //支付域名
        $this->returnUrl = $pu; //设置同步地址
        $this->callback = $pd . $bc; //设置异步回调地址
        $this->url = $this->getPayUrl($pay);//请求支付网关地址
        unset($pd,$bc,$pu,$cname);
        //构造支付参数 并返回前端使用数据
        $data = $this->getPayData();
        return $this->returnApiData($data);
    }

    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
        //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
        //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildForm($data);
        }
    }
    
    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $pay_url = trim($pay['pay_url']);
        }
        return $pay_url;
    }

    /**
     * 创建表单提交数据
     * @param array $data 表单内容
     * @return array
     */
    protected function buildForm($data,$method="post")
    {
        $temp = [
            'method' => $method,
            'data'   => $data,
            'url'    => $this->url,
        ];
        $rs['jump'] = 5;
        $rs['url']  = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }  

    /**
     * 直接表单提交数据
     * @param array $form 表单内容字符串
     * @return array
     */ 
    protected function useForm($data)
    {
        //获取返回form表单数据
        $html = $this->getPayResult($data);
        //构造表单数据
        $temp = ['data' => $html];
        //构造前端返回数据 
        $rs['jump'] = 5;
        $rs['url']  = $this->domain . '/index.php/pay/pay_test/pay_uses/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }

    /**
     * 扫码支付返回,返回二维码图片无需再生成
     * @param $data
     *
     * @return array
     */
    protected function buidImage($data)
    {
        //第三方支付返回 二维码地址
        $qrcode_url = $this->getPayResult($data);
        $res = [
            'jump'      => 2,
            'img'       => $qrcode_url,
            'money'     => $this->money,
            'order_num' => $this->orderNum,
        ];
        return $res;
    }
    /**
     * 创建扫码支付数据
     * @param array $data 支付参数
     * @return array
     */
    protected function buildScan($data)
    {
        //第三方支付返回 二维码地址
        $qrcode_url = $this->getPayResult($data);
        $res = [
            'jump'      => 3,
            'img'       => $qrcode_url,
            'money'     => $this->money,
            'order_num' => $this->orderNum,
        ];
        return $res;
    }

    /**
     * Wap支付数据
     * @param array $data 支付参数
     * @return array
     */
    protected function buildWap($data)
    {
        //第三方支付返回 支付地址
        $pay_url = $this->getPayResult($data);
        $res = [
            'jump' => 5,
            'url' => $pay_url 
        ];
        return $res;
    }

    /**
     * 直接以重定向的方式提交数据
     * @param array $data 支付参数
     * @return array
     */
    protected function Redirect($data)
    {
        //把参数按照地址的形式拼接出来
        $parameter  = http_build_query($data);
        $pay_url = $this->url . '/?' . $parameter;
        $res = [
            'jump' => 5,
            'url' => $pay_url 
        ];
        return $res;
    }


   /**
     * 支付同步返回错误提示信息
     * @param array $msg 
     * @return json
     */
    protected function retMsg($msg)
    {
        $err = array('code' => E_OP_FAIL, 'msg' => $msg);
        echo json_encode($err,JSON_UNESCAPED_UNICODE);
        exit;
    }
}