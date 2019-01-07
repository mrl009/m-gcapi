<?php
/**
 * 快速直通车支付 接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/24
 * Time: 16:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Fast extends MY_Controller
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

    //支付方式  超级直通车支付
    public function pay_fast()
    {
        //接受參數
        $id = input("param.id",0,'intval'); //支付方式
        $money = input("param.money",0,'intval'); //金额
        $code = input("param.code",0,'intval'); // 支付方式 目前不支持
        $uid = $this->user['id'];
        //驗證參數
        if (empty($id) || empty($money) || empty($uid))
        {
            $this->return_json(E_ARGS, '缺少必要参数');
        }
        //獲取用戶層級信息
        $this->BPM->get_user_info($uid);
        $fast = $this->BPM->get_fast_info($id);
        if (empty($fast))   
        {
            $msg = "該充值方式正在維護,請更換其他方式充值";
            $this->return_json(E_ARGS, $msg);
        }
        /**
         * 判斷充值金額是否在允許範圍  
         */
        if (!empty($fast['fixed_amount']))
        {
            $fm = explode(',', $fast['fixed_amount']);  
            if (!in_array($money,$fm)) 
            {
                $msg = "只能使用固定{$fast['fixed_amount']}的金額進行充值";
                $this->return_json(E_ARGS, $msg);
            } 
            unset($fm);
            $money = sprintf('%.2f',$money);  
        } else {
            //開啟使用小數金額 默認隨機增加2位小數
            if (isset($fast['is_use_decimal']) && (1 == $fast['is_use_decimal']))
            {
                $money = $money . '.' . mt_rand(10,50); 
            }
            $money = sprintf('%.2f',$money); 
        }
        //如果設定單次存款限制
        $min_amount = isset($fast['min_amount']) ? $fast['min_amount'] : 0;  
        $max_amount = isset($fast['max_amount']) ? $fast['max_amount'] : 0;
        $block_amount = isset($fast['block_amount']) ? $fast['block_amount'] : 0;
        if (($money < $min_amount) || ($money > $max_amount))
        {
            $msg = "單次存款允許範圍：{$min_amount} - {$max_amount}";
            $msg .= ",且不包括{$max_amount}";
            $this->return_json(E_ARGS, $msg);
        }
        //如果設定有支付限額 則判斷當前金額是否超過限制
        if (0 < $block_amount)
        {
            //獲取當前支付的已使用金額
            $used_amount = $this->BPM->get_block_amount($id,'fast');
            $max_amount = ($used_amount + $money);
            //金額超限,該支付自動停用并寫入日誌信息
            if ($max_amount > $fast['block_amount'])
            {
                $this->BPM->stop_fast($id);
                $this->BPM->delet_block_amount($id,'fast');
                //記錄日誌信息
                $msg = "用戶{$uid}使用直通車支付充值金额超限";
                $msg .= "直通車支付id:{$id}自動停用";
                $this->set_log($msg);
                //返回信息
                $this->return_json(OK_OLINE_MAX, '该方式充值额度已满,请更换充值方式');
            }
        }
        /** 初始化参数 */
        $this->pay_id = $id; //入款商户ID
        $this->pay_code = $code; //支付方式code
        $this->pay_money = $money; //金额
        //构造支付订单的信息数据
        $order_data = $this->set_order_data();
        $insert = $this->BPM->insert_order($order_data);
        $this->push(MQ_ONLINE_IN, "会员申请线上入款",$order_data['order_num']);
        //返回訂單信息給前端，跳轉至接入方進行付款
        $data = $this->return_api_data($fast);
        if (!empty($data['json']))
        {
            //设置redis订单信息
            $this->BPM->set_get_detailo($order_data['order_num']);
            unset($data['json']);
        }
        $this->return_json(OK, $data);
    }

    /**
     * @構造第四方接口信息數據
     */
    private function return_api_data($fast)
    {
        //获取接入方参数信息
        $pay = $this->get_base_data($fast);
        $pay = $this->get_pay_data($pay,$fast['pay_key']);
        //构造返回前端信息
        $temp = array(
            'method' => 'POST',
            'data' => $pay_data,
            'url' => $fast['pay_gateway']
        );
        //发起支付的地址
        $jump_url = "/index.php/pay/pay_test/pay_sest/" . $this->order_num;
        $rs['jump'] = 5;
        $res['url'] = $fast['pay_domain'] . $jump_url;
        $res['json'] = json_encode($temp,320);
        $res['img'] = "";
        $res['confirm'] = "";
        //返回数据
        return $res;
    }

    /**
     * @構造接入方数据信息數據
     * @return send_data 接入方参数信息
     */
    private function get_base_data($fast)
    {
        //获取设备类型
        switch ($this->from_way) 
        {
            case 1:
            case 2:
                $sname = 'APP';
                break;
            case 4:
            case 6:
                $sname = 'Mobile';
                break;
            default:
                $sname = 'PC';
                break;
        }
        $notify_url = "{$fast['pay_domain']}/index.php/fastpay/fastpay/callbackurl";
        //构造参数
        $data = array(
            'MerchantId' => $fast['merch'],
            'Amount' => $this->pay_money,
            'OrderId' => $this->order_num,
            'SourceName' => $sname,
            'OrderTime' => date('Y-m-d H:i:s'),
            'ValidTime' => 1200,
            'NotifyUrl' => $notify_url
        );
        return $data;
    }

    //对数组进行加密
    private function get_pay_data($data,$key)
    {
        ksort($data);
        $string = data_to_string($data);
        $string .= "&key={$key}";
        $data['Sign'] = hash('sha256', $string);
        return $data;
    }

    /**
     * @構造訂單信息數據
     * @return order_data 訂單信息
     */
    private function set_order_data()
    {
        $this->order_num = order_num(3, $this->pay_id); //生成订单号
        $order_data = array (
            'order_num' => $this->order_num,
            'uid' => $this->user['id'],
            'price' => $this->pay_money,
            'total_price' => 0,
            'discount_price' => 0,
            'pay_id' => 0,
            'status' => 1,
            'is_first' => 0,
            'addtime' => time(),
            'is_discount' => 0,
            'from_way' => $this->from_way,
            'remark' => $this->user['username'],
            'agent_id' => $this->user['agent_id'],
            'pay_code' => $this->pay_code,
            'online_id' => $this->pay_id,
            'pay_serve_type' => 'fast'
        );
        return $order_data;
    }

    /**
     * @支付過程中需要記錄的日誌信息
     * @return msg 日誌內容
     */
    private function set_log($msg)
    {
        $uid = $this->user['id'];
        $logData['content'] = $msg;
        $this->load->model('log/Log_model','LOG');
        $this->LOG->record($uid, $logData);
    }

}
