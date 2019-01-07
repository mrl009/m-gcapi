<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/8
 * Time: 上午10:43
 */

class Gefu extends GC_Controller
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
        $data = array(
            'parter' => $_REQUEST['parter'],
            'orderid' => $_REQUEST['orderid'],
            'opstate' => $_REQUEST['opstate'],
            'ovalue' => $_REQUEST['ovalue'],
        );
        // 验证参数
        if (empty($data['parter']) || empty($data['orderid']) || empty($data['ovalue']) || empty($data['opstate'])) {
            $this->Online_model->online_erro('GF', '参数错误:' . json_encode($_REQUEST));
            exit('参数错误');
        }
        //验证签名
        $sign = $_REQUEST['sign'];
        ksort($data);
        $this->setKey();
        $local_sign = md5(urldecode(http_build_query($data) . '&key=' . $this->key));
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('GF', '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderid']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderid']);
        if (empty($pay)) {
            $this->Online_model->online_erro('GF', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        //金额不同
        if ($pay['price'] != $data['ovalue']) {
            $pay['total_price'] = $data['ovalue'];
            $pay['price'] = $data['ovalue'];
            $this->Online_model->write('cash_in_online', array('total_price' => $data['ovalue'], 'price' => $data['ovalue']), array('order_num' => $data['orderid']));
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }

    private function setKey()
    {
        $pay = $this->Online_model->get_one('pay_key', 'bank_online_pay', ['bank_o_id' => 89]);
        if (empty($pay)) {
            $this->Online_model->online_erro('GF', '无效的支付:' . json_encode($_REQUEST));
            exit('无效的支付');
        }
        $this->key = $pay['pay_key'];
    }
}