<?php

/**
 * (新)捷付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/30
 * Time: 18:31
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Jiefuapi extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'JIEFUAPI';
    //商户处理后通知第三方接口响应信息
    protected $error = '{"error_msg":"失败 ","status":"0"}'; //错误响应
    protected $success = '{"error_msg":" ","status":"1"}'; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'merchant_order_no'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)


    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model','PM');
    }

    //第三方回调接口
    public function callbackurl()
    {
        //获取异步返回的参数
        $name = $this->r_name;
        $data = $_REQUEST;
        //redis记录支付错误信息标识
        $name = $this->r_name;
        //获取返回的数据
        if(empty($data)){
            $this->PM->online_erro("{$name}_MUST", '未获取到需要的数据');
            exit($this->error);
        }else{
            //如果是数组 转化成json记录数据库
            if(is_array($_REQUEST))
            {
                //数组转化成json 录入数据
                $temp = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
                $this->PM->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
                unset($temp);
                $data = $_REQUEST;
            }
            //如果json格式 记录数据 同时转化成数组
            if (is_string($_REQUEST) && (false !== strpos($_REQUEST,'{'))
                && (false !== strpos($_REQUEST,'}')))
            {
                $this->PM->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST);
                $data = json_decode($_REQUEST,true);
            }
        }

        //接收回调信息
        $m_sign =  $data['sign'];
        $m_merchant_code = $data['merchant_code'];
        $m_data =  $data['data'];
        //获取公钥和私钥配置
        $this->get_key();
        //验证签名
        $result = $this->vfySign($m_data,$m_sign);
        if ($result == '1') {
            //解密返回的data数据
            $r_data = $this->depositCallback($m_data);
            if (empty($r_data))
            {
                $this->PM->online_erro("{$name}", '解密出来数据不是json数据');
                exit($this->error);
            }else{
                $this->PM->online_erro("{$name}", '解密出来的数据:' . $r_data);
            }
            $data = json_decode($r_data,true);
            $this->verifyLast($data);
        }else{
            //验证签名失败
            $msg = "签名验证失败:{$m_sign}";
            $this->PM->online_erro($m_sign,$msg);
        }

    }

    /**
     * 验证回调金额和更新订单状态
     * @param $data
     */
    protected  function verifyLast($data)
    {
        $name = $this->r_name;
        $of   = $this->of;
        $money = $data[$this->mf]; //实际支付金额
        //订单号中含有商户信息需要处理
        $order_num = $data[$of]; //返回的订单号

        //对需要处理的订单加锁
        $bool = $this->PM->fbs_lock('temp:new_order_num' . $order_num);
        if (!$bool) exit('正在处理,请稍后');
        //根据订单号获取配置信息
        $pay = $this->PM->order_detail($order_num);
        if (empty($pay))
        {
            $msg = "无效的订单号:{$order_num}";
            $this->PM->online_erro($name, $msg);
            exit($this->error);
        }
        //验证支付状态
        if (!empty($this->tf))
        {
            if ($data[$this->tf] <> $this->tc)
            {
                $this->PM->online_erro($name, '交易失败:未成功支付');
                exit($this->error);
            }
            unset($vc,$tc);
        }
        /*
        * 验证金额部分 三种验证方式
        * @1 金额单位类型为元 (默认方式)
        * @2 金额单位类型为分 (部分第三方使用单位)
        * @3 金额不一致 更新金额为实际支付金额
         */
        //如果不严格验证订单金额（更新金额为实际支付金额）
        if (!isset($this->vm) || (1 <> $this->vm))
        {
            //以分为单位金额需要转换成元
            if('fen' == $this->vt) $money = fen_to_yuan($money);
            //不验证的金额数据 需实际比较返回的金额与实际金额差距
            $ap = abs($pay['price'] - $money);
            if (1 < $ap)
            {
                $msg = "返回金额{$money}与订单金额{$pay['price']}差距过大";
                $this->PM->online_erro($name,$msg);
                exit($this->error);
            }
            unset($ap);
            $pay['price'] = $money;
            $pay['total_price'] = ($money + $pay['discount_price']);
        } else {
            $this->verifyPrice($pay,$money,$name);
        }
        //判断订单状态是否已经处理
        if (1 <> $pay['status']) exit($this->success);
        //更新订单信息及用户余额
        $bool = $this->PM->update_order($pay);
        if ($bool) exit($this->success);
        $this->PM->online_erro($name, '写入现金记录失败:');
        exit('加钱失败');
    }

      // 获取公钥和私钥
    private function get_key()
    {
        $name = $this->r_name;
        //切换数据库
        $this->PM->select_db('public');
        //设置查询条件
        $where = ['model_name' => 'Jiefuapi'];
        //获取公库支付配置信息
        $pid = $this->PM->get_one('id', 'bank_online', $where);
        if (empty($pid) || empty($pid['id'])) {
            $this->PM->online_erro($name, '该支付方式不存在');
            exit($this->error);
        }
        //切换私库
        $this->PM->select_db('private');
        //获取该支付方式的key值
        $id = intval($pid['id']);
        $where = ['bank_o_id' => $id];
        $key = $this->PM->get_one('*', 'bank_online_pay', $where);
        if (empty($key)) {
            $this->PM->online_erro($name, '该支付方式未设置');
            exit($this->error);
        }
        //设置使用的支付key值
        $this->s_key = $key['pay_server_key'];
        $this->ps_public_key = $key['pay_public_key'];
        $this->ps_private_key = $key['pay_private_key'];
    }
    //获取返回数据 公钥验签
    public function vfySign($data,$sign)
    {// 捷付支付后台公钥
        $tmp_sign = openssl_verify( $data, base64_decode(($sign)), $this->ps_public_key  );//平台公钥验签
        $result = $tmp_sign;
        return  $result;
    }
    /*
     * 私钥解密返回的data数据
     */
    public function depositCallback($data){
        $decrypted = '';
        $decodeStr = base64_decode($data);
        $enArray = str_split($decodeStr, 256);
        foreach ($enArray as $va) {
            openssl_private_decrypt($va,$decryptedTemp,$this->ps_private_key );//私钥解密
            $decrypted .= $decryptedTemp;
        }
        $result=$decrypted;
        return  $result;
    }
}