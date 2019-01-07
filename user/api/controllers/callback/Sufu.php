<?php

class Sufu extends GC_Controller
{
    private $key;
    private $success = "success";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data=$_REQUEST;
        // 验证参数
        if (empty($data['order_no'])||empty($data['sign'])) {
            $this->Online_model->online_erro('SF', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $_REQUEST['sign'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['order_no']);
        $this->key = $pay['pay_key'];
        $local_sign = $this->sign($data);
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('SF', '签名验证失败:' . json_encode($_REQUEST).'key:'.$this->key);
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['order_no']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('SF', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('SUCCESS');
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        ksort($data);
        $arg = "";
        foreach ($data as $k => $v) {
            if ($k == 'sign' || $v == '') {
                continue;
            }
            $arg .= $k . "=" . $v . "&";
        }
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return md5($arg .'key='.$this->key);
    }
}