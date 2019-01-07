<?php
/**
 * Created by PhpStorm.
 * User: mr.xiaolin
 * Date: 2018/4/13
 * Time: 下午4:17
 */

class Tudou extends GC_Controller
{
    /**
     * 成功响应
     * @var string
     */
    public $success = "SUCCESS";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    /**
     * 异步回调接口
     */
    public function callbackurl()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
        if ($data['status'] != 'SUCCESS') {
            $this->Online_model->online_erro('TD', '支付未成功:' . json_encode($data));
            exit('支付未成功');
        }
        if (empty($data['sign']) || empty($data['orderId']) || empty($data['amount'])) {
            $this->Online_model->online_erro('TD', '参数错误:' . json_encode($data));
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderId']);
        if (!$bool) {
            exit('请稍后');
        }

        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderId']);
        if (empty($pay)) {
            $this->Online_model->online_erro('TD', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }
        // 验证签名
        $flag = $this->verify($data, $pay['pay_key']);
        if (!$flag) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($data));
            exit('签名验证失败');
        }

        if ($pay['price'] != $data['amount']) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($data));
            exit('订单金额验证失败');
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($data));
        exit('加钱失败');
    }

    /**
     * 验证签名
     * @param array $data 回调参数数组
     * @param string $key
     * @return boolean $flag
     */
    private function verify($data, $key)
    {
        $str = '';
        ksort($data);
        foreach ($data as $x => $x_value) {
            if ($x_value == '' || $x == 'sign')
                continue;
            $str = $str . $x . '=' . $x_value . '&';
        }
        $str = $str . 'key=' . $key;
        return $data['sign'] == strtoupper(md5($str)) ? true : false;
    }
}