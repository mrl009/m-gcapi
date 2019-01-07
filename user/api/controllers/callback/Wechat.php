<?php

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/9
 * Time: 下午6:06
 */
class Wechat extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    //微信异步通知
    public function notify()
    {
        $data = $this->getData();
        if ($data == null || $data['return_code'] != 'SUCCESS' || empty($data['out_trade_no']) || empty($data['total_fee'])) {
            $this->Online_model->online_erro('WX', '参数错误:' . json_encode($data));
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['out_trade_no']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['out_trade_no']);
        if (empty($pay)) {
            $this->Online_model->online_erro('WX', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }
        //微信类
        $config = [
            'mch_id' => $pay['mch_id'],
            'apikey' => $pay['pay_server_key'],
        ];
        include_once APPPATH . 'libraries/Wechatpay.php';
        $weChatPay = new Wechatpay($config);
        //验证签名
        if (!$weChatPay->validate($data)) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($data));
            exit('签名验证失败');
        }
        //已经确认
        if ($pay['status'] == 2) {
            $weChatPay->response_back();
            exit;
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            $weChatPay->response_back();
            exit;
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($data));
        exit('加钱失败');
    }

    /**
     * 获取返回参数
     * @return mixed
     */
    private function getData()
    {
        $xml = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $rs = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $rs;
    }
}