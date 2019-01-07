<?php
/**
 * 线上支付
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 14:58
 * 新增显示支付是需要规定
 * Jump_mode 跳转方式 默认为跳web页面  1
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Pay extends MY_Controller
{
    private $size = 6;  //二维码图片大小
    private $wx   = 52; //公司入款微信平台id号
    private $zfb  = 51; //公司入款支付宝平台id号
    private $qq   = 53; //qq钱包id
    private $jd  = 54; //京东钱包
    private $bd  = 55; //百度钱包
    private $xm  = 56; //小米钱包
    private $hw  = 57; //华为钱包
    private $sx  = 58; //三星钱包
    private $pg  = 59; //苹果钱包
    private $wangyi  = 60; //网易支付
    private $ym  = 61; //一码付
    private $cft  = 62; //财付通
    private $yl  = 63; //银联钱包
    private $kj  = 64; //快捷支付
    private $syt  = 65; //收银台
    private $other  = 66; //其他支付
    private $pay_arr =[51,52,53,54,55,56,57,58,61,62,63,64,65,66];
    private $cash_key = "cash:count:online";//入款成功后需要添加redis 记录 hash结构 加上pay_id
    private $keys_lock = '';


    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Pay_model');
        $this->load->model('pay/Pay_set_model');
        $this->load->model('pay/Online_model');
        $this->load->model('log/Log_model','LOG');
        if ($this->user && $this->user['status'] == 4) {
            $this->return_json(E_DENY,'没有权限');
        }
    }

    public function index()
    {
    }

    /**
     * 根据id 获取公司入款二维码
    */

    /**
     * 获取该会员的支付方式
     * bank   公司入款      qq qq钱包    xm 小米钱包
     * wx     微信         bd 百度钱包   hw 华为钱包
     * zfb    支付宝       jd 京东钱包
     * wy     网银         sx 三星钱包
     * card   优惠卡入款
     * 微信支付宝转账 归到 微信支付宝里面
     * 返回数据类型包含
     * 跳转方式 Jump_mode  int
     * 1:web页  2:表单  3 表单 表单加二维码  4二维码:4图片 5:打开APP
     * code值对应  : 0 对应为公司入款
     * @1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码#
     * 7网银#8QQ钱包#9京东钱包#
     * 10百度钱包#11点卡,12 qq钱包WAP  13 京东钱包Wap
    */
    public function pay_method()
    {
        $uid  = $this->user['id'];
        $select_user = 'a.*,b.img';
        $userData = $this->Online_model->get_user_new($uid,$select_user);
        //$userData = $this->Online_model->get_user($uid, 'is_card,username,level_id,balance');
        $is_card  = $userData['is_card'];
        $level_id = $userData['level_id'];
        $baseData = $this->Pay_model->get_method($level_id);
        $pay_set = $this->Online_model->get_pay_set($level_id);
        $pay_set = $pay_set['data']['data'];
        $set_pay = [
            'bank'   => [$pay_set['line_catm_min'],$pay_set['line_catm_max']],
            'online' => [$pay_set['ol_catm_min'],$pay_set['ol_catm_max']],
        ];


        $img  = $this->Online_model->base_bank_online('bank');

        $toname = array(
            'wx'  => '微信/QQ/财付通',
            'zfb' => '支付宝',
            'bank'=> '公司入款',
            'yl'  => '银联钱包',
            'syt'  => '收银台',
            'kj'  => '快捷支付',
            'wy'  => '网银',
            'qq'  => 'QQ钱包',
            'jd'  => '京东钱包',
            'bd'  => '百度钱包',
            'xm'  => '小米钱包' ,
            'hw'  => '华为钱包' ,
            'sx'  => '三星钱包' ,
            'ym'  => '一码付',
            'cft'  => '财付通',
            'card'=> '彩豆充值',
            'other'=> '其他支付',
        );
        $newData  = [
            //支付数据的集合
            'zhifu'=>[],
            'user' =>['username'=> $this->user['username'],'balance' => $userData['balance']],

        ];
        //临时装支付数据
        $data = [
            'bank' =>[],
            'wx'   =>[],
            'zfb'  =>[],
            'ym'   =>[],
            'yl'   =>[],
            'syt'   =>[],
            'kj'   =>[],
            'wy'   =>[],
            'temp' =>[],
            'qq'   =>[],
            'jd'   =>[],
            'bd'   =>[],
            'xm'   =>[],
            'hw'   =>[],
            'sx'   =>[],
            'cft'   =>[],
            'card' =>[],
            'other' =>[],
        ];
        $gcSet = $this->Pay_model->get_gcset();
        empty($gcSet['is_confirm'])?$is_confirm=0:$is_confirm=1;
        //数据整理分类
        //线上入款
        $online_code  = [1,2,3,4,5,6,8,9,10,12,13,15,16,17,18,19,20,21,22,23,24,25,26,33,36,37,38,39];
        //.条形码
        $txm_code  = [40,41];//.40 =>微信条形码  41 =>支付宝条形码
        $online_code = array_merge($online_code,$txm_code);
        //.条形码 end 
        $wyCode  = [7];
        $bank     = $this->Pay_model->base_bank('bank');
        foreach ($baseData['online'] as $k =>$v) {
            $a = json_decode(($v['describe']), true);
            $typeStr  = $this->return_type($v['pay_codex']);
            if (empty($a['title'])) {
                $a = [
                    'title'   => "扫码码转账",
                    'Prompt'  => "扫码码转账",
                ];
            }
            $code = $v['pay_codex'];
            if (in_array($code, $online_code)) {
                $kes  = $this->online_keys($code);
                $temp = [
                    'type'       => $this->get_type($code),
                    'title'      => $typeStr.$a['title'],
                    'Prompt'     => $typeStr.$a['prompt'],
                    'id'         => $v['id'],
                    'code'       => $code,
                    'qrcode'     => '',
                    'is_confirm' => $is_confirm,
                    'jump_mode'  => $this->Jump_mode($v['bank_o_id']),
                    'name'       => $this->return_type($v['pay_codex']),
                    'img'        => $this->online_img($code),
                    'catm_min'   => $set_pay['online'][0],
                    'catm_max'   => $set_pay['online'][1],
                ];
                if ($this->from_way == FROM_IOS && $code == 12) {
                    continue;
                }
                array_push($data[$kes], $temp);
            } elseif (in_array($code, $wyCode)) {
                if ($code == 7 || $code == 26) {
                    $temp = $this->img_name_bank($bank, $v, $set_pay['online']);
                    $data['temp'] = array_merge($data['temp'], $temp);
                }
            }
        }

        $data['wy'] = $data['temp'];
        unset($data['temp']);
        //公司入款
        foreach ($baseData['bank'] as $k => $v) {
            $is_confirm = (int)$v['is_confirm'];
            if ($v['bank_id'] == $this->wx) {
                $a = json_decode($v['describe'], true);
                if (empty($a)) {
                    $a['title']   = "微信转账";
                    $a['prompt']  = "微信转账扫一扫";
                }

                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '3',
                    'id'         => $v['id'],
                    'jump_mode'  => 4,
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,
                    'img'        => WX_GR_PNG,
                    'name'       => $v['card_num'],
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],

                ];
                array_push($data['wx'], $temp);
            } elseif ($v['bank_id'] == $this->zfb) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "支付宝转账";
                    $a['prompt']  = "支付宝转账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '6',
                    'id'         => $v['id'],
                    'img'        => ZFB_GR_PNG,
                    'jump_mode'  => 4,
                    'is_confirm' => $is_confirm,
                    'name'       => $v['card_num'],
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],

                ];
                array_push($data['zfb'], $temp);
            } elseif ($v['bank_id'] == $this->qq) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "QQ钱包转账";
                    $a['prompt']  = "QQ钱包转账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => QQ_IMG_PNG,
                    'jump_mode'  => 4,
                    'is_confirm' => $is_confirm,
                    'name'       => $this->return_type(8),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],

                ];
                array_push($data['qq'], $temp);
            } elseif ($v['bank_id'] == $this->bd) {
                $a = json_decode($v['describe'], true);

                if (empty($a['title'])) {
                    $a['title']   = "百度钱包转账";
                    $a['prompt']  = "百度钱包账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => BD_IMG_PNG,
                    'jump_mode'  => 4,
                    'name'       => $this->return_type(9),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,


                ];
                array_push($data['bd'], $temp);
            } elseif ($v['bank_id'] == $this->jd) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "京东钱包转账";
                    $a['prompt']  = "京东钱包账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => JD_IMG_PNG,
                    'jump_mode'  => 4,
                    'name'       => $this->return_type(10),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,


                ];
                array_push($data['jd'], $temp);
            } elseif ($v['bank_id'] == $this->xm) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "小米钱包转账";
                    $a['prompt']  = "小米钱包账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => $v['img'],
                    'jump_mode'  => 4,
                    'name'       => $this->return_type(10),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,


                ];
                array_push($data['xm'], $temp);
            } elseif ($v['bank_id'] == $this->hw) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "华为钱包转账";
                    $a['prompt']  = "华为钱包账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => $v['img'],
                    'jump_mode'  => 4,
                    'name'       => $this->return_type(10),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,


                ];
                array_push($data['hw'], $temp);
            } elseif ($v['bank_id'] == $this->sx) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "三星钱包转账";
                    $a['prompt']  = "三星钱包账扫一扫";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => $v['img'],
                    'jump_mode'  => 4,
                    'name'       => $this->return_type(10),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,


                ];
                array_push($data['sx'], $temp);
            }elseif ($v['bank_id'] == $this->ym) {
                $a = json_decode($v['describe'], true);
                if (empty($a['title'])) {
                    $a['title']   = "支持微信支付宝QQ钱包";
                    $a['prompt']  = "支持微信支付宝QQ钱包";
                }
                $temp = [
                    'title'      => $a['title'],
                    'Prompt'     => $a['prompt'],
                    'code'       => '0',
                    'id'         => $v['id'],
                    'img'        => $v['img'],
                    'jump_mode'  => 4,
                    'name'       => $this->return_type(14),
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,
                ];
                array_push($data['ym'], $temp);
            } else {
                $temp = [
                    'card_address' => $v['card_address'],
                    'code'         => '0',
                    'bank_name'    => $v['bank_name'],
                    'jump_mode'    => 2,
                    'id'           => $v['id'],
                    'num'          => $v['card_num'],
                    'name'         => $v['card_username'],
                    'catm_min'   => $set_pay['bank'][0],
                    'catm_max'   => $set_pay['bank'][1],
                    'qrcode'     => $v['qrcode'],
                    'is_confirm' => $is_confirm,


                ];
                array_push($data['bank'], $temp);
                $a = json_decode($v['describe'], true);
                if (!empty($a['title'])) {
                    $temp['img'] = $v['img'];
                    $temp['title'] = $a['title'];
                    $temp['Prompt'] = $a['prompt'];
                    array_push($data['zfb'], $temp);
                }
            }
        }

        $temp = [];
        if ($is_card == 1 && $gcSet['card_status'] == 1) {
            $temp = [
                'id'   => 'card',
                'code' => '11',
                'name' => '彩豆充值',
                'jump_mode' => 2,
            ];
        }
        //弹出提示添加
        if (!empty($gcSet['is_bomb_box']) && $gcSet['is_bomb_box'] == 1) {
            $bomb_box = $this->bomb_box();
        }
        if (!empty($bomb_box)) {
            $bomb_box = [ 'is_bomb_box' =>['bomb_box' => $bomb_box['content']]];
        }else{
            $bomb_box = [ 'is_bomb_box' => ['bomb_box' => '']];
        }
        if (!empty($temp)) {
            array_push($data['card'], $temp);
        }

        foreach ($data as $key => $value) {
            $temp = [
                'type' => $key,
                'name' => $toname[$key],
                'list' => $value
            ];
            if (!empty($value)) {
                $newData['zhifu'][]= $temp;
            }
        }
        $newData = array_merge($newData, $bomb_box);
        $newData['pay_url'] = empty($gcSet['pay_url']) ? PYA_URL : $gcSet['pay_url'];

        $this->return_json(OK, $newData);
    }

    /**
     * 支付开始
     * code值对应  : 0 对应为公司入款
     * id         : card 是为 点卡
     * bank_style   转帐方式，1：网银转帐，2：ATM自动柜员，3：ATM现金入款，
     *                      4：银行柜台，5：手机转帐，6：支付宝转帐，7：微信支付
     *                      8:qq 钱包 ,9:京东钱包 ,10:百度钱包,11:小米钱包,
     *                      12: 华为钱包 ,13:三星钱包
     * name         银行转账人的姓名
     * 当为支付宝和微信转账的时候需要返回一个支付确认码
     *
     */
    public function pay_do()
    {
        $money      = $this->P('money');   // 支付的钱
        $id         = $this->P('id');      //bank_card表的id
        //code 【支付类型@1微信#2微信app#3微信公司入款#4支付宝#5支付宝APP#6支付宝公司入款#7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡,12qq钱包WAP,13京东钱包WAP 逗号分割】
        //33 微信二维码，36 支付宝二维码
        $code       = $this->P('code');
        $card_pwd   = $this->P('card_pwd');//优惠卡密
        $from_way   = $this->from_way;//来源
        $bank_type  = $this->P('bank_type');//银行type
        $bank_style = $this->P('bank_style');//转账的方式
        $name       = $this->P('name'); //转款人的姓名
        $in_time    = $this->P('data'); //存款时间
        $confirm    = $this->P('confirm'); //确认订单号
        $step       = $this->P('step') ? $this->P('step') : 0;//快j捷支付步骤
        $order_num  = $this->P('order_num');

        if (empty($money)){
            $money = '1';
        }
        if (empty($in_time)) {
            $in_time =  $_SERVER['REQUEST_TIME'];
        } else {
            $in_time    = strtotime($in_time);
        }
        if ($step == 1) {//快捷支付步骤1不上锁
            $keys = "request_time:uid:".$this->user['id'];
            $bool = $this->Online_model->redis_setnx($keys, $_SERVER['REQUEST_TIME']);
            if (!$bool) {
                //todo 入款锁
                $this->return_json(E_ARGS, '提交太频繁!请稍后再试!');
            }
            $this->Online_model->redis_expire($keys, CASH_REQUEST_TIME);
        }

        if ($code == '0' || in_array($code, [3,6])) { //公司入款

            if ($code ==3) {
                $name = '微信扫码转账';
                $bank_style = 7;
            } elseif ($code == 6) {
                $name = '支付宝扫码转账';
                $bank_style = 6;
            }
            $bank_id   = $this->Online_model->get_one('bank_id,qrcode,status', 'bank_card', ['id' => $id]);

            if (empty($bank_id)) {
                $this->return_json(OK_OLINE_MAX, '没有该入款方式');
            }
            if ($bank_id['status'] != 1) {
                $this->return_json(OK_OLINE_MAX, '入款通道维护');
            }

            switch ($bank_id['bank_id']) {
                case $this->hw:
                    $bank_style = 12;
                    $name= '华为钱包扫码';
                    break;
                case $this->jd:
                    $bank_style = 9;
                    $name= '京东钱包扫码';
                    break;
                case $this->qq:
                    $bank_style = 8;
                    $name= 'QQ钱包扫码';
                    break;
                case $this->sx:
                    $bank_style = 13;
                    $name= '三星钱包扫码';
                    break;
                case $this->bd:
                    $bank_style = 10;
                    $name= '百度钱包扫码';
                    break;
                case $this->xm:
                    $bank_style = 11;
                    $name= '小米钱包扫码';
                    break;
                case $this->pg:
                    $bank_style = 12;
                    $name=  "苹果pay";
                    break;
                case $this->wangyi:
                    $bank_style = 13;
                    $name=  "网易支付";
                    break;
                case $this->ym:
                    $bank_style = 4;
                    $name=  "一码付";
                    break;
            }
            $data = [
                'money'      => $money,
                'id'         => $id,
                'code'       => $code,
                'from_way'   => $from_way,
                'name'       => $name,
                'bank_style' => $bank_style,
                'in_time'    => $in_time,
                'bank_id'    => $bank_id,
                'confirm'    => $confirm,
            ];
            $this->check_data($data, 'bank');
            $this->bank_do($data);
        } elseif ($id === 'card') { //优惠卡充值
            $data = [
                'card_num' => "",
                'card_pwd' => $card_pwd,
                'from_way' => $from_way,
            ];
            $this->check_data($data, 'card');
            $this->card_do($data);
        } else { //线上支付
            $data = [
                'money' => $money,//支付金额
                'id'    => $id,//gc_bank_online_pay表（第三方支付信息表）的id
                'code'  => $code,//【支付类型@1微信#2微信app#3微信扫码公司入款#33微信扫码#4支付宝#5支付宝APP#6支付宝扫码公司入款#36支付宝扫码#7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡,12qq钱包WAP,13京东钱包WAP 逗号分割】
                'from_way' => $from_way,//来源， 1：ios，2：android，3：PC，4：wap，5：未知
                'bank_type'=> $bank_type,//见函数头部
                'step'  => $step,
                'order_num' => $order_num
            ];
            $this->check_data($data, 'other');
            if ($step == 1) {
                $this->quick_pay($data);
            } else {
                $this->online_do($data);
            }
        }
    }

    //点卡测试方法
    public function test_card()
    {
        $this->load->model('pay/Online_model', 'online');
        //todo 点卡订单号
        $order = order_num(4, 4);
        $data = [
            'order_num'=> "$order",
            'card_pwd' => "1704",
            'from_way' => 1,
        ];
        $this->load->model('pay/Card_model', 'card');
        $datax = $this->card->creat();
        die;
        if ($datax['status'] != OK) {
            $this->return_json($datax['status'], $datax['msg']);
        } else {
            $arr = [
                'order_num' => $data['order_num'],
                'money'     => $datax['msg'],
            ];
            if ($this->user['username']) {
//                $this->push(MQ_COMPANY_RECHARGE, "会员{$this->user['username']}优惠卡充值 {$datax['msg']}元");
            } else {
               // $this->push(MQ_COMPANY_RECHARGE, "优惠卡充值 {$datax['msg']}元");
            }

            $this->return_json(OK, $arr);
        }
    }

    /***********************私有方法分割线************************/


    /**
     * 获取弹框内容
    */
    private function bomb_box()
    {
        //id 固定死
        $id = 37;
        return $this->Online_model->get_one('content','set_article',[ 'id' => $id]);
    }
    /**
     * 确认码
    */
    private function queren($datax)
    {
        $qrcode         = $this->Online_model->get_one('qrcode', 'bank_card', ['id' => $datax['id']]);
        $data['img']    = $qrcode['qrcode'];
        $this->return_json(OK, $data);
    }

    /**
     * 点卡充值业务流程
     * 点卡卡号表
     *
     */
    private function card_do($data)
    {
        $this->load->model('pay/Online_model', 'online');
        $order = order_num(4, 4);
        $data['order_num'] = $order;
        $this->load->model('pay/Card_model', 'card');

        $datax = $this->card->card_doing($data, $this->user['id'], $this->from_way);
        if ($datax['status'] != OK) {
            $this->return_json($datax['status'], $datax['msg']);
        } else {
            $this->push(MQ_COMPANY_RECHARGE, $this->user['username']."彩豆充值",$order);
            wlog(APPPATH.'logs/card_'.$this->card->sn.'_'.date('Ym').'.log', $this->user['username']."彩豆充值 {$datax['msg']}元,彩豆卡号:{$data['card_pwd']}");
            $arr = [
                'order_num' => $data['order_num'],
                'money'     => $datax['msg'],
                'confirm'   => '',
                'open_id'   => '',
                'img'       => '',
                'jump'      => '',
                'url'       => '',
            ];
            $this->return_json(OK, $arr);
        }
    }


    /**
     *
     * 公司入款业务流程
     * 银行卡表  bank_card  id
     * 现金记录表 cash_in_company
     */
    private function bank_do($data)
    {
        $bank_id   = $data['bank_id'];
        $uid       = $this->user['id'];
        $order_num = order_num(1, $bank_id['bank_id']);
        $data['order_num'] = $order_num;
        $bool = $this->Pay_set_model->set_in_lock($uid, $order_num);
        if (!$bool) {
            $arr = $this->Pay_set_model->set_in_lock($uid);
            if (empty($arr)) {
                $this->return_json(E_ARGS, '入款错误，请通知！');
            }
            $str = '你有'.(count($arr)).'笔订单等待处理';
            $this->return_json(E_ARGS, $str);
        }
        
        /***** 公司入款写入日志 ******/
        $dbn = $this->Online_model->sn;
        // wlog(APPPATH.'logs/pay/'.$dbn.'_incompany_'.date('Ym').'.log', json_encode($data).'-申请入款');

        $user      = $this->Online_model->get_user($uid);
        if (empty($user)) {
            $bool = $this->Pay_set_model->del_in_lock($uid, $order_num);
            $this->return_json(E_ARGS, '会员信息错误');
        }
        $pay_set   = $this->Online_model->get_pay_set($user['level_id'], false);
        $pay_set   = $pay_set['data']['data'];

        if (empty($pay_set)) {
            $bool = $this->Pay_set_model->del_in_lock($uid, $order_num);
            $this->return_json(E_ARGS, '获取支付设定失败');
        }
        if ($data['money'] > $pay_set['line_catm_max']) {
            $bool = $this->Pay_set_model->del_in_lock($uid, $order_num);
            $this->return_json(E_ARGS, '存款金额上限');
        }

        if ($data['money'] < $pay_set['line_catm_min']) {
            $bool = $this->Pay_set_model->del_in_lock($uid, $order_num);
            $this->return_json(E_ARGS, '存款金额下限');
        }

        //支付方式限额判断 由后台确认入款判断
        $qiota = $this->Pay_set_model->check_bank_quota($data['id'], $data['money'], $pay_set['line_catm_min']);
        if ($qiota['code'] === false) {
            $this->Pay_set_model->del_in_lock($uid, $order_num);
            if (isset($qiota['shuaxin'])) {
                //支付限额上限 客户端刷新支付方式列表
                $this->return_json(OK_OLINE_MAX, $qiota['msg']);
            }
            $this->return_json(E_ARGS, $qiota['msg']);
        }

        /**确认码添加开始**/
        $datax['money']  = $data['money'];
        $datax['open_id']= $data['bank_style'];
        $datax['order_num'] = $order_num;
        $datax['img']    =  $bank_id['qrcode'];
        $datax['url']    =  "";
        $datax['confirm'] =  (string) $data['confirm'];

        $gcSet = $this->Pay_model->get_gcset();

        if (in_array($bank_id['bank_id'], $this->pay_arr) ) {
            $datax['jump']      = 4;
            if (!empty($this->Pay_set_model->is_confirm)) {
                if (empty($datax['confirm']) || preg_match('/^[a-zA-Z0-9]{9}$/',$datax['confirm']) == 0) {
                    $this->Pay_set_model->del_in_lock($uid, $order_num);
                    $this->return_json(E_ARGS,'请输入第三方订单号后9位');
                }
            }

        } else {
            $datax['jump']     = 2;
        }
        /**结束**/

        $bank_id   = $bank_id['bank_id'];
        $youhui    = $this->Online_model->discount($data['money'], $pay_set, 'line');
        empty($youhui)? $is_discount = 0 : $is_discount  = 1;
        $insert = [
            'order_num'      => $order_num,
            'uid'            => $uid,
            'price'          => $data['money'],
            'total_price'    => $data['money']+$youhui,
            'discount_price' => $youhui,
            'name'           => $data['name'],
            'bank_id'        => $bank_id,
            'bank_style'     => $data['bank_style'],
            'bank_card_id'   => $data['id'],
            'status'         => OUT_NO,
            'is_first'       => "0",
            'addtime'        => $data['in_time'],
            'update_time'    => $_SERVER['REQUEST_TIME'],
            'from_way'       => $data['from_way'],
            'agent_id'       => $this->user['agent_id'],
        ];


        if (!empty($datax['confirm'])) {
            $insert['confirm']   = $datax['confirm'];
        }

        $bool = $this->Online_model->write('cash_in_company', $insert);
        if ($bool) {
            $this->push(MQ_COMPANY_IN, "会员申请入款",$order_num);
            $this->return_json(OK, $datax);
        } else {
            $bool = $this->Pay_set_model->del_in_lock($uid, $order_num);
            $this->return_json(E_ARGS, '请重试');
        }
    }

    /**
     * 现金记录表 cash_in_online
     * 支付配置   bank_online_pay bank_o_id
     * 线上入款业务流程
     *  $this->push(,)会员消息队列
     */
    private function online_do($data)
    {
        $uid       = $this->user['id'];
        $from_way  = $data['from_way'];
        /*查询出支付用户对应层级的第三方支付信息以及银行卡 start lqh*/
        $userData = $this->Pay_model->get_one('level_id,username', 'user', ['id'=> $uid]);
        $a = 'level_bank_online';
        $where  = [
            $a.'.pay_code'    => $data['code'],
            'b.id'   => $data['id'],
            'b.status'      => 1,
            $a.'.level_id'    => $userData['level_id'],
        ];

        $where2['join'] = array(
            array('table'=>'bank_online_pay as b','on'=>"$a.online_id = b.id")
        );
        $temp_pay  = $this->Pay_model->get_one('b.*', $a, $where, $where2);
        /*查询出支付用户对应层级的第三方支付信息以及银行卡 end*/
        //$temp_pay['bank_o_id'] 第三方支付id

        //验证支付id
        if (empty($temp_pay)) {
            $this->return_json(OK_OLINE_MAX, '充值方式维护');
        }
        //开始执行写入订单表

        //$temp_pay = $this->Online_model->get_one('','bank_online_pay',['id' => $data['id']]);
        //平台限额
        $m = $data['money'];
        if (intval($m) <= 0)
        {
            $msg = '该支付方式支付金额不能小于1';
            $this->return_json(E_ARGS, $msg);
        }
        $xiane = $this->Online_model->redis_hget($this->cash_key, $temp_pay['id']);
        if ($xiane + $data['money'] > $temp_pay['max_amount']) {
            if ($xiane+10 >= $temp_pay['max_amount']) {
                $this->Online_model->db->update('bank_online_pay', ['status'=> 2], ['id'=>$temp_pay['id']]);
                $this->Online_model->redis_del($this->cash_key, $temp_pay['id']);
                /*
                 * 新增(用户充值超限支付停用支付)记录 lqh 2018/08/05 
                 */
                $uid = $this->user['id'];
                $rec_msg = "用户{$uid}使用线上支付充值金额超限,";
                $rec_msg .= "线上支付id:{$temp_pay['id']}停用";
                $logData['content'] = $rec_msg;
                $this->LOG->record($uid, $logData);
                $this->return_json(OK_OLINE_MAX, '该方式充值额度已满,请更换充值方式');
            }
            $str = "该支付方式额度剩余".($temp_pay['max_amount']-$xiane);
            $this->return_json(E_ARGS, $str);
        }
        //获取支付配置信息
        $base_online = $this->Online_model->base_bank_online('bank_online', $temp_pay['bank_o_id']);
        $pay_data = [
            'pay_url'           => $base_online['pay_url'],
            'bank_o_id'         => $temp_pay['bank_o_id'],//支付平台的id号
            'pay_domain'        => $temp_pay['pay_domain'],//异步回调的地址
            'pay_return_url'    => $temp_pay['pay_return_url'],//同步回调的地址
            'pay_id'            => $temp_pay['pay_id'],//商户号
            'pay_key'           => $temp_pay['pay_key'],//商户密钥
            'pay_private_key'   => $temp_pay['pay_private_key'],//商户私钥
            'pay_public_key'    => $temp_pay['pay_public_key'],//商户公钥
            'pay_server_key'    => $temp_pay['pay_server_key'],//服务端公钥
            'pay_server_num'    => $temp_pay['pay_server_num'],//终端号
            'shopurl'           => $temp_pay['shopurl'],//商城域名
            'code'              => $data['code'],
            'bank_type'         => $data['bank_type'],
            'step'              => $data['step'],
            'from_way'          => $this->from_way
        ];
        $code = $data['code'] ;
        $data['bank_o_id'] = $temp_pay['bank_o_id'];
        $model_name = ucfirst($base_online['model_name']);//首字母转成大写
        /*
         @ 金额配置文fasten_price中文件名称的配置key值 (model文件名)
         @ lqh 2018/06/25
         */
        $mn = $model_name;
        $model_name .= '_model';
        //调用各支付对应的支付类文件
        $file = APPPATH.'models/online/'.$model_name.'.php';
        if (!file_exists($file)) {
            $this->Online_model->select_db('private');
            $this->Online_model->db->update('bank_online_pay', ['status'=> 2], ['id'=>$temp_pay['id']]);
            /*
             * 新增(用户充值确实支付文件停用支付)记录 lqh 2018/08/05 
             */
            $uid = $this->user['id'];
            $rec_msg = "用户{$uid}使用线上支付充值时,因缺少{$model_name}支付文件,";
            $rec_msg .= "线上支付id:{$temp_pay['id']}停用";
            $logData['content'] = $rec_msg;
            $this->LOG->record($uid, $logData);
            $this->return_json(OK_OLINE_MAX, "支付方式维护中,请更换支付方式");
        }
        /** 第三方支付限额 **/
        /*if ($pay_data['code'] != 7 && ($base_online['min_limit_price'] > $data['money'] ||
            $base_online['max_limit_price'] < $data['money'])) {
            $str = "充值金额允许范围：".$base_online['min_limit_price']."-".$base_online['max_limit_price'];
            $this->return_json(OK_OLINE_MAX, $str);
        }*/
        /*
        **系统默认 支付金额 增加小数点
        ** 部分第三方只接受整数金额
        ** 部分第三方 不得修改金额 
        * lqh 2018/05/30
         */
        //加载第三方支付 固定金额配置
        $this->config->load('fasten_price');
        $isy = $this->config->item('isy');
        $isn = $this->config->item('isn');
        /**
         * 判断第三方通道是否使用固定金额 默认使用随机2位小数
         */
        /* 默认金额配置 2位随机小数*/
        $data['money'] = intval($m) . '.' . rand(10,99);
        /* 金额配置1 原始金额配置 */
        if (isset($isn[$mn]))
        {
            $mp = $isn[$mn];
            if (in_array('all',$mp) || in_array($code,$mp))
            {
                $data['money'] = $m;
            }
        }
        /* 金额配置2 输入原始小数金额 只保留2位小数 */
        if (intval($m) <> $m)
        {
            $data['money'] = sprintf('%.2f',$m);
        }
        /* 金额配置3 固定金额配置  <固定金额配置不能被原始小数金额给覆盖> cz 2018-10-18*/
        if (isset($isy[$mn]))
        {
            $mp = $isy[$mn];
            if (in_array('all',$mp) || in_array($code,$mp))
            {
                $data['money'] = sprintf('%.2f',intval($m));
                //盛付通微信通道末位不要是 0、1、9提高成功率 cz 2018-10-18
                if($model_name=='Shengfutong_model' && $code=='1'){
                    $l_num =[2,3,4,5,6,7,8];
                    $result = $l_num[array_rand($l_num)];
                    $data['money']= sprintf('%.2f',substr(intval($m),0,-1).$result);
                }
            }
        }
        //删除各中间变量
        unset($isy,$isn,$mn,$m,$mp);
        /*支付限额添加 修改到私库 2019/1/4 cz*/
        if(!in_array($pay_data['code'],[7]) &&($temp_pay['min_limit_price'] > $data['money'] || $data['money'] > $temp_pay['max_limit_price'])){
            $str = "当前充值金额:".$data['money']."充值金额允许范围：".$temp_pay['min_limit_price']."-".$temp_pay['max_limit_price'];
            $this->return_json(OK_OLINE_MAX, $str);
        }
        $order_num = $data['step'] == 2 ? $data['order_num'] : order_num(3, $data['id']);//生成订单号
        //轻易付特殊订单号
        if ($model_name == 'Qinyifu_model' || $model_name == 'Shunfu_model' || $model_name == 'Qinyifu2_model') {
            $timezone =  date_default_timezone_get();
            date_default_timezone_set("Asia/Shanghai");
            $order_num = date("YmdHis").rand(1000, 9999);
            date_default_timezone_set("$timezone");
        }
        if ($model_name == 'Wangyou_model') {
            $order_num = '07'.$order_num;
        }
        $data['agent_id'] = $this->user['agent_id'];
        $insert    = $this->Online_model->insert_order($order_num, $data['money'], $uid, $from_way, $data);
        if ($insert['code'] != OK) {
            $this->return_json($insert['code'], $insert['data']);
        }
        $this->load->model('online/'.$model_name);
        $data = $this->$model_name->call_interface($order_num, $data['money'], $pay_data);
        //告诉app 打开哪种类型的app
        switch ($code) {
            case in_array($code, [1,2,3]):
                $open_id = '7';
                break;
            case in_array($code, [4,5,6]):
                $open_id = '6';
                break;
            case in_array($code, [7]):
                $open_id = '';
                break;
            default:
                $open_id = $code;
                break;

        }
        $this->push(MQ_ONLINE_IN, "会员申请线上入款",$order_num);
        $data['open_id'] = $open_id;
        switch ($data['jump']) {
            case 1://跳转web页（内嵌的网页）
                if (isset($data['json'])) {
                    $this->Online_model->set_get_detailo($order_num, $data['json']);
                    unset($data['json']);
                }
                $data['img'] = "";
                $data['confirm'] = "";
                $this->return_json(OK, $data);
                break;
            case 2://直接返回二维码
                if (isset($data['json'])) {
                    $this->Online_model->set_get_detailo($order_num, $data['json']);
                    unset($data['json']);
                }
                $data['confirm'] = "";
                $this->return_json(OK, $data);
                break;
            case 3://公众号二维码
                if (!empty($data['is_img'])) {
                    unset($data['is_img']);
                } else {
                    ob_start();//开启缓冲区
                    $this->qrcode_creat($data['img']);//生成二维码
                    $x  = ob_get_clean();//获取缓冲区内容后删除缓冲区内容并关闭缓冲区
                    $qrcode = base64_encode($x);//将图片转成base64格式
                    $this->Online_model->redis_setex("temp:qrcode:$order_num", 600, $qrcode);//插入redis并设置二维码600秒过期
                    $data['img'] = "{$pay_data['pay_domain']}/index.php/pay/pay_test/qrcode/$order_num";
                }
                Header("Content-type: application/json;charset=UTF-8");
                $data['url'] = "";
                $data['confirm'] = "";
                $this->return_json(OK, $data);
                break;
            case 5://打开APP(WAP/H5)
                if (isset($data['json'])) {
                    $this->Online_model->set_get_detailo($order_num, $data['json']);//redis设置订单信息
                    unset($data['json']);
                }
                $data['img'] = "";
                $data['confirm'] = "";
                $this->return_json(OK, $data);//url,jump,img,confirm;
                break;
        }
    }

    /**
     * 快捷支付第一步数据提交
     * @param $data
     */
    private function quick_pay($data)
    {
        //*询出支付用户对应层级的第三方支付信息以及银行卡
        $userData = $this->Pay_model->get_one('level_id,username', 'user', array('id' => $this->user['id']));
        $basic = [
            'level_bank_online.pay_code' => $data['code'],
            'b.id' => $data['id'],
            'b.status' => 1,
            'level_bank_online.level_id' => $userData['level_id'],
        ];
        $senior['join'] = array(['table' => 'bank_online_pay as b', 'on' => 'level_bank_online.online_id = b.id']);
        $pay = $this->Pay_model->get_one('b.*', 'level_bank_online', $basic, $senior);
        if (empty($pay)) {
            $this->return_json(OK_OLINE_MAX, '充值方式维护');
        }
        //平台限额
        $max = $this->Online_model->redis_hget($this->cash_key, $pay['id']);
        if ($max +$data['money'] > $pay['max_amount']) {
            if ($max+10 >= $pay['max_amount']) {
                $this->Online_model->db->update('bank_online_pay', ['status'=> 2], ['id'=>$pay['id']]);
                $this->Online_model->redis_del($this->cash_key, $pay['id']);
                /*
                 * 新增(用户充值超限支付停用支付)记录 lqh 2018/08/05 
                 */
                $uid = $this->user['id'];
                $rec_msg = "用户{$uid}使用快捷支付充值金额超限,";
                $rec_msg .= "线上支付id:{$pay['id']}停用";
                $logData['content'] = $rec_msg;
                $this->LOG->record($uid, $logData);
                $this->return_json(OK_OLINE_MAX, '该方式充值额度已满,请更换充值方式');
            }
            $str = "该支付方式额度剩余".($pay['max_amount']-$max);
            $this->return_json(E_ARGS, $str);
        }
        //获取支付配置信息
        $online = $this->Online_model->base_bank_online('bank_online', $pay['bank_o_id']);
        $pay_data = [
            'pay_url'           => $online['pay_url'],
            'bank_o_id'         => $pay['bank_o_id'],//支付平台的id号
            'pay_domain'        => $pay['pay_domain'],//异步回调的地址
            'pay_return_url'    => $pay['pay_return_url'],//同步回调的地址
            'pay_id'            => $pay['pay_id'],//商户号
            'pay_key'           => $pay['pay_key'],//商户密钥
            'pay_private_key'   => $pay['pay_private_key'],//商户私钥
            'pay_public_key'    => $pay['pay_public_key'],//商户公钥
            'pay_server_key'    => $pay['pay_server_key'],//服务端公钥
            'pay_server_num'    => $pay['pay_server_num'],//终端号
            'shopurl'           => $pay['shopurl'],//商城域名
            'code'              => $data['code'],
            'bank_type'         => $data['bank_type'],
            'step'              => $data['step']
        ];
        $model_name = ucfirst($online['model_name']);//首字母转成大写
        $model_name .= '_model';
        /** 第三方支付限额 **/
        if ($pay_data['code'] != 7 && ($online['min_limit_price'] > $data['money'] || $online['max_limit_price'] < $data['money'])) {
            $str = "充值金额允许范围：".$online['min_limit_price']."-".$online['max_limit_price'];
            $this->return_json(OK_OLINE_MAX, $str);
        }
        //引入模块文件
        $file = APPPATH.'models/online/'.$model_name.'.php';
        if (!file_exists($file)) {
            $this->Online_model->select_db('private');
            $this->Online_model->db->update('bank_online_pay', ['status'=> 2], ['id'=>$pay['id']]);
            /*
             * 新增(用户充值确实支付文件停用支付)记录 lqh 2018/08/05 
             */
            $uid = $this->user['id'];
            $rec_msg = "用户{$uid}使用快捷支付充值时,因缺少{$model_name}支付文件,";
            $rec_msg .= "线上支付id:{$pay['id']}停用";
            $logData['content'] = $rec_msg;
            $this->LOG->record($uid, $logData);
            $this->return_json(OK_OLINE_MAX, "支付方式维护中,请更换支付方式");
        }
        $order_num = order_num(3, $data['id']);//生成订单号
        $this->load->model('online/'.$model_name);
        $rs = $this->$model_name->call_interface($order_num, $data['money'], $pay_data);
        $this->return_json($rs['code'], $rs['data']);
    }


    /**
     * 数据验证
    */
    private function check_data($data, $scene)
    {
        $rule = [
            'money'    => 'require|number|gt:0',
            'id'       => 'require|number',
            'step'     => 'require|number',
            'order_num'=> 'require|number|gt:0',
            'code'     => 'require|number',
            'card_num' => 'require|number',
            'card_pwd' => 'require|number',
            'name'     => 'require|chsAlpha',
            'bank_style' => 'require|intGt0|between:1,13',
            "confirm"  => 'alphaNum|length:9'
        ];
        $msg  = [
            'money'    => '金额必须大于0',
            'id'       => 'id错误',
            'step'     => 'step错误',
            'order_num'=> '订单号错误',
            'code'     => 'code错误',
            'card_num' => '彩豆号错误',
            'card_pwd' => '彩豆卡密错误',
            'name'     => '姓名只能是汉字、字母',
            'bank_style' => '转账方式错误',
            "confirm"  => '第三方订单,单号只能是数字和字母长度9'
        ];

        $this->validate->rule($rule, $msg);
        $this->validate->scene('card', ['card_pwd','from_way']);
        $this->validate->scene('bank', ['id','code','money','from_way','name','bank_style,confirm']);
        if (isset($data['step']) && $data['step'] == 2) {
            $this->validate->scene('other', ['id','code','money','from_way','step','order_num']);
        } else {
            $this->validate->scene('other', ['id','code','money','from_way','step']);
        }

        if ($scene) {
            $result   = $this->validate->scene($scene)->check($data);
        } else {
            $result   = $this->validate->check($data);
        }

        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息        }else{
        } else {
            return true;
        }
    }


    //产生二维啊
    private function qrcode_creat($data)
    {
        $this->load->library('Qrcode');//加载第三方类库.
        return $this->qrcode->png($data, false, QR_ECLEVEL_L, $this->size); //显示二维码
    }



    /**
     * 根据code返回对应的支付方式
    */
    private function return_type($code)
    {
        switch ($code) {
            case 1:
                return '微信';
            case 2:
                return '微信app';
            case 33:
                return '微信h5';
            case 40:
                return '微信条形码';
            case 3:
                return '微信二维码';
            case 4:
                return '支付宝';
            case 5:
                return '支付宝APP';
            case 36:
                return '支付宝h5';
            case 41:
                return '支付宝条形码';
            case 6:
                return '支付宝二维码';
            case 7:
                return '网银';
            case 8:
                return 'QQ钱包';
            case 9:
                return '京东钱包';
            case 10:
                return '百度钱包';
            case 11:
                return '彩豆';
            case 12:
                return 'QQ钱包WAP';
            case 13:
                return '京东钱包WAP';
            case 14:
                return '一码付';
            case 15:
                return '京东二维码';
            case 16:
                return 'QQ扫码';
            case 17:
                return '银联钱包';
            case 18:
                return '银联钱包APP';
            case 19:
                return '银联钱包扫码';
            case 20:
                return '百度钱包APP';
            case 21:
                return '百度钱包二维码';
            case 22:
                return '财付通';
            case 23:
                return '财付通APP';
            case 24:
                return '财付通二维码';
            case 25:
                return '快捷支付';
            case 26:
                return '收银台';
            case 38:
                return '苏宁';
            case 39:
                return '苏宁';
        }
    }
    /**
     * 根据code 返回对应的keys 值
    */
    private function online_keys($code)
    {
        switch ($code) {
            case 1:
                return 'wx';
            case 2:
                return 'wx';
            case 3:
                return 'wx';
            case 33:
                return 'wx';
            case 40:
                return 'wx';  //.微信条形码
            case 4:
                return 'zfb';
            case 5:
                return 'zfb';
            case 6:
                return 'zfb';
            case 36:
                return 'zfb';
            case 41:
                return 'zfb'; //.支付宝条形码
            case 7:
                return 'wx';
            case 8:
                return 'wx';
            case 9:
                return 'other';
            case 10:
                return 'other';
            case 12:
                return 'wx';
            case 13:
                return 'other';
            case 15:
                return 'other';
            case 16:
                return 'wx';
            case 17:
                return 'other';
            case 18:
                return 'other';
            case 19:
                return 'other';
            case 20:
                return 'other';
            case 21:
                return 'other';
            case 22:
                return 'wx';
            case 23:
                return 'wx';
            case 24:
                return 'wx';
            case 25:
                return 'other';
            case 26:
                return 'other';
            default:
                return 'other';
        }
    }

    /**
     * 根据code返回类型
     * @param $code
     * @return string
     */
    private function get_type($code)
    {
        if (in_array($code, [1, 2, 3, 33, 34,40])) {
            $rs = 'wx';
        } elseif (in_array($code, [4, 5, 6, 36, 37,41])) {
            $rs = 'zfb';
        } elseif (in_array($code, [8, 12, 16])) {
            $rs = 'qq';
        } elseif (in_array($code, [9, 13, 15])) {
            $rs = 'jd';
        } elseif (in_array($code, [10, 20, 21])) {
            $rs = 'bd';
        } elseif (in_array($code, [17, 18, 19])) {
            $rs = 'yl';
        } elseif (in_array($code, [22, 23, 24])) {
            $rs = 'cft';
        } elseif (in_array($code, [17, 18, 19])) {
            $rs = 'yl';
        } else {
            $rs = 'wx';
        }
        return $rs;
    }

    /**
     * 根据id 返回对应的跳转方式
     * Jump_mode
     * 1:web页  2:表单  3 表单 表单加二维码  4二维码:4图片
    */
    private function Jump_mode($id)
    {
        switch ($id) {
            case 15:
                return 3;
            case 16:
                return 3;
            case 38:
                return 3;
            /*case 16:
                return 3;*/
            default:
                return 1;
        }
    }

    /**
     * 根据对应的code 值返回对应的背景图
    */
    private function online_img($code)
    {
        switch ($code) {
            case 1:
                return WX_IMG_PNG;
            case 2:
                return WX_WAP_PNG;
            case 3:
                return WX_IMG_PNG;
            case 33:
                return WX_IMG_PNG;
            case 40:
                return WX_WAP_PNG; //.微信条形码
            case 4:
                return ZFB_IMG_PNG;
            case 5:
                return ZFB_WAP_PNG;
            case 6:
                return ZFB_IMG_PNG;
            case 36:
                return ZFB_IMG_PNG;
            case 41:
                return ZFB_WAP_PNG; //.支付宝条形码
            case 7:
                return WX_IMG_PNG;
            case 8:
                return QQ_IMG_PNG;
            case 9:
                return JD_IMG_PNG;
            case 10:
                return BD_IMG_PNG;
            case 12:
                return QQ_IMG_PNG;
            case 13:
                return JD_IMG_PNG;
            case 11:
                return "";
            case 15:
                return JD_IMG_PNG;
            case 16:
                return QQ_IMG_PNG;
            case 17:
                return YL_IMG_PNG;
            case 18:
                return YL_IMG_PNG;
            case 19:
                return YL_IMG_PNG;
            case 20:
                return BD_IMG_PNG;
            case 21:
                return BD_IMG_PNG;
            case 22:
                return CFT_IMG_PNG;
            case 23:
                return CFT_IMG_PNG;
            case 24:
                return CFT_IMG_PNG;
            case 25:
                return KJ_IMG_PNG;
            case 26:
                return SY_IMG_PNG;
            case 38:
                return SN_IMG_PNG;
            case 39:
                return SN_WAP_PNG;
        }
    }



    //根据 支付id 返回对应的 图片地址和名字和bank_typxe
    private function img_name_bank($temp, $datax, $xx)
    {
        $this->config->load('bank_set');
        $pay_bank = $this->config->config['bank'];
        isset($pay_bank[$datax['model_name']])?$pay_bank = $pay_bank[$datax['model_name']]:$pay_bank = $pay_bank['default'];
        $data     = [];
        foreach ($pay_bank as $k => $v) {
            foreach ($temp['bank'] as $kk => $vv) {
                if ($v == $vv['bank_name']) {
                    $x=[
                        'name'      => $v,
                        'bank_type' => $k,
                        'img'       => $vv['img'],
                        'id'        => $datax['id'],
                        'jump_mode' => 1,
                        'code'      => $datax['pay_codex'],
                        'catm_min'  => $xx[0],
                        'catm_max'  => $xx[1],
                    ];
                    array_push($data, $x);
                }
            }
            if ($v =='银联支付') {
                $x=[
                    'name'      => $v,
                    'bank_type' => $k,
                    'img'       => YL_IMG_PNG,
                    'id'        => $datax['id'],
                    'jump_mode' => 1,
                    'code'      => $datax['pay_codex'],
                    'catm_min'  => $xx[0],
                    'catm_max'  => $xx[1],
                ];
                array_push($data, $x);
            }
        }
        return $data;
    }
}
