<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/8
 * Time: 上午10:43
 */

class Quannengfu extends GC_Controller
{
    private $error      = "ERROR"; //错误响应
    private $success    = "SUCCESS"; //成功响应
    private $merchantNo = '7EC887686D677A19'; //商户机构号

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        //$this->load->model('online/quannengfu_model', 'qnf');
    }

    public function index()
    {
        echo '';
    }

    //返回支付数据
    public function callbackurl()
    {
        $data = $this->getData();
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['pay_OrderNo']);
        if (!$bool) exit('请稍后');
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['pay_OrderNo']);
        if (empty($pay)) 
        {
            $this->Online_model->online_erro('QNF', '无效的订单号:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证返回状态
        if (100 <> $data['pay_Status']) 
        {
            $this->Online_model->online_erro($pay['id'], '交易失败:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证签名
        $flag = $this->verifySign($data, $pay['pay_key']);
        if (!$flag) 
        {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_POST));
            exit($this->error);
        }
        if ($pay['price'] != $data['pay_OrderNo']) 
        {
            $this->Online_model->online_erro($pay['id'], '金额验证失败:' . json_encode($_POST));
            exit($this->error);
        }
        //已经确认
        if (2 == $pay['status']) exit($this->error);
        $bool = $this->Online_model->update_order($pay);
        if ($bool) exit($this->success);
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_POST));
        exit('加钱失败');
    }

    /*获取异步返回参数*/
    private function getData()
    {
        $data = $this->input->post();
        //验证参数是否正确
        if (empty($data['sign']) || empty($data['pay_MerchantNo'])
            || empty($data['pay_OrderNo']) || empty($data['pay_Amount'])
            || empty($data['pay_Status'])) 
        {
            exit('参数错误');
        }
        return $data;
    }

    /*验证sign签名费否正确*/
    private function verifySign($data, $key)
    {
        $sign_str = $this->merchantNo; //机构号
        $sign_str .= $data['pay_OrderNo'];
        $sign_str .= $data['pay_Amount'];
        $sign_str .= $key;
        $sign = md5($sign_str);
        return ($sign <> $data['sign']) ? true : false;
    }
}
