<?php
/**
 * @file bank.php
 * @brief  银行卡 第三方支付的操作
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package controllers
 * @author Langr <hua@langr.org> 2017/03/13 16:59
 *
 *
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Bank extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('level/Bank_model');
        $this->load->model('MY_Model', 'core');
    }
    private $bank_id    = '';
    private $wx_bankid  = 52;
    private $zfb_bankid = 51;
    private $bankArr    = [51,52,53,54,55,56,57,58,59,60];
    public function index()
    {
    }



    /**
     * 展示支付平台信息
     * is_box   1:商户秘钥,2:商户私钥,3商户公钥,4服务端公钥,5终端号  一逗号分隔
     */
    public function bank_list()
    {
        $status = 1;

        if ($status>2||$status < 0) {
            $this->return_json(E_ARGS, 'status 参数错误');
            die;
        }
        $where = [];
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $arr = $this->Bank_model->bank_list("bank", $where);
        $this->return_json(OK, $arr);
    }

    public function online_list()
    {
        $status = 1;
        if ($status>2||$status < 0) {
            $this->return_json(E_ARGS, 'status 参数错误');
            die;
        }
        $where = [];
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $arr = $this->Bank_model->bank_list("bank_online", $where);
        $this->return_json(OK, $arr);
    }

    //.获取自动出款支付方式
    public function out_list()
    {
        $status = 1;
        if ($status>2||$status < 0) {
            $this->return_json(E_ARGS, 'status 参数错误');
            die;
        }
        $where = [];
        if (!empty($status)) {
            $where['status'] = $status;
        }
        $arr = $this->Bank_model->bank_list("out_online", $where);
        $this->return_json(OK, $arr);
    }

    //删除银行 暂无
    public function bank_del()
    {
        $bank_id = $this->P('bank_id');
        $type    = $this->P('type');

        if (!in_array($type, ['bank','bank_online'])) {
            $this->return_json(E_ARGS, 'type 参数错误');
            die;
        }

        $tb   =  $type;


        $bool = $this->Bank_model->bank_del($tb, explode(',', $bank_id));
        if ($bool) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK);
        }
    }

    //第三方支付平台添加
    public function online_bank_add()
    {
        $bank_id=(int)$this->P('onlin_bank_id');
        if ($bank_id < 0) {
            $this->return_json(E_ARGS, 'onlin_bank_id错误');
        }

        $data=[
            'online_bank_name' => trim($this->P('online_bank_name')),
            'status'           => $this->P('status'),
            'online_bank_url'  => trim($this->P('online_bank_url')),
            'pay_url'          => trim($this->P('pay_url')),
        ];

        $rule=[
            'online_bank_name' => 'require|max:100',
            'status'           => 'require|between:1,2',
            'online_bank_url'  => 'require|url',
            'pay_url'          => 'require|url',
        ];

        $msg=[
            'online_bank_name.require' => '平台名称必填',
            'online_bank_name.max'     => 'name过长',
            'status'                   => 'status参数错误',
            'online_bank_url'          => 'online_bank_url不是一个有效的url',
            'pay_url'                  => 'pay_url不是一个有效的url',
        ];

        $this->validate->rule($rule, $msg);
        $result   = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        }
        $arr = $this->Bank_model->bank_add($data, $bank_id, 'bank_online');

        $this->return_json($arr['code'], $arr['msg']);
    }

    /**
     * 添加银行卡更改银行卡  日志信息添加
     */
    public function bank_card_add()
    {
        $card_id = $this->P('id');
        $data =[
            'bank_id'       => $this->P('bank_id'),       //银行id
            'card_num'      => trim($this->P('card_num')), //银行卡号
            'card_username' => trim($this->P('card_username')),  //姓名
            'card_address'  => trim($this->P('card_address')),  //开户行
            'max_amount'    => $this->P('max_amount'),   //停用额度
            'remark'        => trim($this->P('remark')),  //备注
            'status'        => trim($this->P('status')),      //状态
            'qrcode'        => trim($this->P('qrcode')),      //状态
            're_order'        => $this->P('re_order')?$this->P('re_order'):0,      //状态
            'is_confirm'    => $this->P('is_confirm') ? trim($this->P('is_confirm')) : 0,      //状态
        ];

        $miaosu = [
            'title'  => $this->P('title'),
            'prompt' => $this->P('prompt'),
        ];
        $where = [];
        if ($card_id) {
            $where['id'] = $card_id;
        }

        if ($data['bank_id'] == $this->wx_bankid ||$data['bank_id'] == $this->zfb_bankid || !empty($miaosu['title']) || !empty($miaosu['title'])) {
            $this->check_card($miaosu, 'miaosu');
        }
        $this->bank_id = $data['bank_id'];
        if ($this->check_card($data, 'card')) {//验证数据
            $this->Bank_model->select_db('public');
            $bank = $this->Bank_model->get_one('', 'bank', ['id'=> $data['bank_id']]);
            $this->Bank_model->select_db('private');

            if ($bank['is_qcode'] == 1) {
                if (empty($data['qrcode'])) {
                    $this->return_json(E_ARGS, '请上传二维码');
                }
            }
            if ($card_id) {
                $bank_status = $this->Bank_model->get_one('status', 'bank_card', ['id'=>$card_id]);
            }

            if (!empty($bank_status)&&$bank_status['status'] == 2 && $data['status'] == 1) {
                $key = "cash:count:bank_card";
                $Bool = $this->Bank_model->redis_del($key, $card_id);
            }
            $data['describe'] = json_encode($miaosu, JSON_UNESCAPED_UNICODE);
            $arr = $this->Bank_model->write('bank_card', $data, $where);


            //操作日志添加
            ($arr)?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            if (empty($bank_id)) {
                $logData['content'] = "添加银行卡:卡号{$data['card_num']}"."状态:$x";//内容自己对应好
            } else {
                $logData['content'] = "更改银行卡:卡号{$data['card_num']}"."状态:$x";//内容自己对应好
            }
            $this->Log_model->record($this->admin['id'], $logData);
            if ($arr) {
                $arr = [];
                $arr['status'] = OK;
                $arr['msg']    = '更新成功';
            } else {
                $arr = [];
                $arr['status'] = E_OK;
                $arr['msg']    = '数据没有更新';
            }


            $this->return_json($arr['status'], $arr['msg']);
        }
    }

    /**
     * 删除银行卡 添加日志
    */
    public function bank_card_del()
    {
        $id   = $this->P('id');
        $data =explode(',', $id);
        $bool = $this->Bank_model->db_private->where_in('id', $data)->delete('bank_card');
//        $this->Bank_model->db_private->where_in('id',$data)->get('bank_card')
        /***********日志*******/
        ($bool)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $logData['content'] = "删除银行卡:银行id:$id"."状态:$x";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);
        /***********日志*******/

        if ($bool) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK);
        }
    }

    /**
     * 删除第三方的支付方式信息
    */
    public function bank_online_del()
    {
        $id   = $this->P('id');
        $data = explode(',', $id);

        $bool = $this->Bank_model->db_private->where_in('id', $data)->delete('bank_online_pay');

        /***********日志*******/
        ($bool)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $logData['content'] = "删除第三方支付:银行id:$id"."状态:$x";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);
        /***********日志*******/

        if ($bool) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK);
        }
    }

     /**
     * 删除第三方的自动出款
    */
    public function out_del()
    {
        $id   = $this->P('id');
        $data = explode(',', $id);

        $bool = $this->Bank_model->db_private->where_in('id', $data)->delete('out_online_set');

        /***********日志*******/
        ($bool)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $logData['content'] = "删除第三方自动出款:银行id:$id"."状态:$x";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);
        /***********日志*******/

        if ($bool) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK);
        }
    }

    /**
     * 展示银行卡信息
    */
    public function bank_card_show($cardi_id=null)
    {
        $status = $this->G('status');
        $where = [];
        $page  = [];
        if ($cardi_id) {
            if ($cardi_id < 0) {
                $this->return_json(E_ARGS, 'card_错误');
            }
            $where['a.id'] = $cardi_id;
        }
        $sort= $this->P('order');
        $where2 = [
            'orderby'=>array('a.re_order'=>$sort), // 排序
        ];
        if ($status) {
            $where['a.status'] = $status;
        }
        $page   = [
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->P('order'),
            'sort'  => $this->P('sort'),
            'total' => -1,

        ];
        $this->core->select_db('public');
        $bankArr = $this->core->get_all('*', 'bank');
        $this->core->select_db('private');
        $arrBank=[];
        foreach ($bankArr as $item) {
            $arrBank[$item['id']]['bank_name'] = $item['bank_name'];
            $arrBank[$item['id']]['is_qcode']  = $item['is_qcode'];
        }

        if ($cardi_id) {
            $arr    = $this->core->get_list('', 'bank_card', $where, $where2);
            $arr    =$arr[0];
            if (!empty($arr)) {
                $arr['bank_name'] = $arrBank[$arr['bank_id']]['bank_name'];
                $arr['is_qcode']  = $arrBank[$arr['bank_id']]['is_qcode'];
            }
        } else {
            $arr    = $this->core->get_list('', 'bank_card', $where, $where2, $page);
            foreach ($arr['rows'] as $k => $v) {
                $arr['rows'][$k]['bank_name'] = $arrBank[$v['bank_id']]['bank_name'];
                $arr['rows'][$k]['is_qcode']  = $arrBank[$v['bank_id']]['is_qcode'];
            }
        }

        $this->return_json(OK, $arr);
    }
    /**
     * 第三方支付添加  日志添加
    */
    public function online_add()
    {
        $id= $this->P('id');
        if ($id) {
            $online_id = ['id'=>(int)$id];
        } else {
            $online_id = [];
        }

        $data = [
            'bank_o_id'       => $this->P('bank_o_id')    ,//支付id
            'pay_domain'      => trim($this->P('pay_domain')) ,//支付域名
            'pay_return_url'  => trim($this->P('pay_return_url')), //支付返回地址
            'shopurl'         => trim($this->P('shopurl')), //商城地址
            'pay_id'          => trim($this->P('pay_id')), //商户id
            'pay_key'         => trim($this->P('pay_key')), //商户密钥
            'pay_private_key' => trim($this->P('pay_private_key')), //商户私钥
            'pay_public_key'  => trim($this->P('pay_public_key')), //商户公钥
            'pay_server_key'  => trim($this->P('pay_server_key')), //服务端公钥匙
            'pay_server_num'  => trim($this->P('pay_server_num')), //终端号
            'max_amount'      => trim($this->P('max_amount')), //最大收款额度（支付限额）
            'shopurl'         => trim($this->P('shopurl')), //状态@
            're_order'         => $this->P('re_order')?$this->P('re_order'):0, //排序@
            'is_card'         => trim($this->P('is_card')) ,//是否为点卡支付@1:是,0:否
            'min_limit_price' => trim($this->P('min_limit_price'))?
                                 $this->P('min_limit_price'):0 ,//第三方最小入款金额
            'max_limit_price' => trim($this->P('max_limit_price'))?
                                 $this->P('max_limit_price'):50000,//第三方最大入款金额
        ];
        //最小金额不能超过最大金额
        if($data['min_limit_price'] > $data['max_limit_price']){
            $this->return_json(E_OK, '最小金额不能超过最大金额');//返回错误信息
        }
        if (empty($data['pay_domain'])) {
            $arr = $this->core->get_gcset(['pay_domain']);
            $data['pay_domain'] = $arr['pay_domain'];
        }
        if (empty($data['pay_return_url'])) {
            $arr = $this->core->get_gcset(['domain']);
            $data['pay_return_url'] = $arr['domain'];
        }
        /*$str1 =substr($data['pay_domain'],0,4);
        $str2 =substr($data['pay_return_url'],0,4);
        $str3 =substr($data['shopurl'],0,4);

        if ($str1 =='http'||$str2 =='http'||$str3 == 'http') {
            $this->return_json(E_ARGS,'域名请不要带上http头');
        }*/
        $miaoshu = [
            'title'   => $this->P('title'),
            'prompt'  => $this->P('prompt')
        ];

        if (empty($data['bank_o_id'])) {
            $this->return_json(E_ARGS, '支付平台id号错误');
        }
        foreach ($data as $k=>$v) {
            if (empty($v)&&$k!='status'&&$k!='is_card'&&$k!='re_order') {
                unset($data[$k]);
            }
        }
        $this->core->select_db('public');
        $temp = [];
        $a = $this->core->get_one('pay_code', 'bank_online', ['id' => $data['bank_o_id']]);
        if (!empty($a)) {
            $temp = explode(',', $a['pay_code']);
        }
        $this->core->select_db('private');
        $this->check_online($miaoshu, $temp);

        $bool = $this->check_online($data, []);
        //验证支付域名是否在通过审核
        $domain = $this->core->get_one('', 'set_domain', ['domain' => $data['pay_domain'],'type'=>2]);
        if (empty($domain)) {
            $this->return_json(E_ARGS, '支付域名未绑定');
        }
        if ($bool) {
            if (!empty($temp)) {
                $data = array_merge($data, ['describe'=>json_encode($miaoshu, JSON_UNESCAPED_UNICODE)]);
            }

            if (empty($online_id)) {
                $where = [
                    'bank_o_id'=>$data['bank_o_id'],
                    'pay_id'=>$data['pay_id'],
                ];
                $x = $this->core->get_one('', 'bank_online_pay', $where);
                if ($x) {
                    //$this->return_json(E_ARGS, '你已经添加过了');
                }

                $data['status'] = 1;
                $a = $this->core->db->insert('bank_online_pay', $data);
                $insert_id = $this->core->db->insert_id();
            } else {
                $a = $this->core->db->update('bank_online_pay', $data, $online_id);
            }

            $this->core->select_db('public');
            $arr = $this->core->get_one('*', 'bank_online', ['id'=>$data['bank_o_id']]);
            $this->core->select_db('private');

            /***********日志*******/
            ($a)?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            if (empty($online_id)) {
                $logData['content'] = "添加第三方支付:{$arr['online_bank_name']}"."状态:$x 参数:".json_encode($data);//内容自己对应好
            } else {
                $logData['content'] = "更新第三方支付:{$arr['online_bank_name']}"."状态:$x 参数:".json_encode($data);//内容自己对应好
            }
            $this->Log_model->record($this->admin['id'], $logData);
            /***********日志*******/

            if ($a) {
                $this->return_json(OK, '操作成功');//返回错误信息
            } else {
                $this->return_json(E_OK, '操作失败');//返回错误信息
            }
        }
    }


    /**
     * 第三方自动出款添加  日志添加
    */
    public function out_add()
    {
        $id= $this->P('id');
        if ($id) {
            $online_id = ['id'=>(int)$id];
        } else {
            $online_id = [];
        }

        $data = [
            'o_id'       => $this->P('o_id')    ,//支付id
            'out_domain'      => trim($this->P('out_domain')) ,//支付域名
            'out_id'          => trim($this->P('out_id')), //商户id
            'out_key'         => trim($this->P('out_key')), //商户密钥
            'out_private_key' => trim($this->P('out_private_key')), //商户私钥
            'out_public_key'  => trim($this->P('out_public_key')), //商户公钥
            'out_server_key'  => trim($this->P('out_server_key')), //服务端公钥匙
            'out_server_num'  => trim($this->P('out_server_num')), //终端号
            'out_secret'  => trim($this->P('out_secret')), //商户数据签名加密盐值
            'max_amount'      => trim($this->P('max_amount')), //
            'min_amount'      => trim($this->P('min_amount')), //
            'total_amount'      => trim($this->P('total_amount'))
        ];

        if (empty($data['out_domain'])) {
            $arr = $this->core->get_gcset(['out_domain']);
            $data['out_domain'] = $arr['out_domain'];
        }
        /*$str1 =substr($data['pay_domain'],0,4);
        $str2 =substr($data['pay_return_url'],0,4);
        $str3 =substr($data['shopurl'],0,4);

        if ($str1 =='http'||$str2 =='http'||$str3 == 'http') {
            $this->return_json(E_ARGS,'域名请不要带上http头');
        }*/

        if (empty($data['o_id'])) {
            $this->return_json(E_ARGS, '支付平台id号错误');
        }
        foreach ($data as $k=>$v) {
            if (empty($v)&&$k!='status') {
                unset($data[$k]);
            }
        }
        $bool = $this->check_out($data, []);
        //验证出款域名是否在通过审核
        $domain = $this->core->get_one('', 'set_domain', ['domain' => $data['out_domain'],'type'=>2]);
        if (empty($domain)) {
            $this->return_json(E_ARGS, '代付域名未绑定');
        }
        if ($bool) {
            if (empty($online_id)) {
                $where = [
                    'o_id'=>$data['o_id'],
                    'out_id'=>$data['out_id'],
                ];
                $x = $this->core->get_one('', 'out_online_set', $where);
                if ($x) {
                    //$this->return_json(E_ARGS, '你已经添加过了');
                }

                $data['status'] = 1;
                $a = $this->core->db->insert('out_online_set', $data);
                $insert_id = $this->core->db->insert_id();
            } else {
                $a = $this->core->db->update('out_online_set', $data, $online_id);
            }

            $this->core->select_db('public');
            $arr = $this->core->get_one('*', 'out_online', ['id'=>$data['o_id']]);
            $this->core->select_db('private');

            /***********日志*******/
            ($a)?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            if (empty($online_id)) {
                $logData['content'] = "添加第三方出款:{$arr['out_online_name']}"."状态:$x 参数:".json_encode($data);//内容自己对应好
            } else {
                $logData['content'] = "更新第三方出款:{$arr['out_online_name']}"."状态:$x 参数:".json_encode($data);//内容自己对应好
            }
            $this->Log_model->record($this->admin['id'], $logData);
            /***********日志*******/

            if ($a) {
                $this->return_json(OK, '操作成功');//返回错误信息
            } else {
                $this->return_json(E_OK, '操作失败');//返回错误信息
            }
        }
    }

    /**
     * 修改状态
    */
    public function chang_status()
    {
        $id  =$this->P('id');
        if ($id) {
            $tb     = $this->P('type');
            $status = $this->P('status');
            if (!in_array($status, [1,2])) {
                $this->return_json(E_ARGS, 'status参数错误');
            }
            if ($status ==1) {
                $status =2;
            } else {
                $status =1;
            }

            if (!in_array($tb, [1,2,3])) {
                $this->return_json(E_ARGS, 'type参数错误');
            }
            $arr = [1=>'bank_card',2=>'bank_online_pay',3=>'out_online_set'];
            $tbname =$arr[$tb];
            $a=$this->core->write($tbname, ['status'=>$status], ['id'=>$id]);

            if ($a) {
                if ($status == 2 && $tb ==2) {
                    $key = "cash:count:online";
                    $online_id = $this->core->get_one('id', $tbname, ['id'=>$id]);
                    $this->core->redis_del($key, $online_id['id']);
                }
                $a = [];
                $a['status'] = OK;
                $a['msg']    = "操作成功";
            } else {
                $a = [];

                $a['status'] = E_OK;
                $a['msg']    = "操作失败";
            }

            /***********日志*******/
            ($a['status'] = OK)?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            ($status == 1)?$aa = "开启":$aa="关闭";
            $logData['content'] = "$aa 了线上支付:id:$id"."状态:$x";//内容自己对应好
            $this->Log_model->record($this->admin['id'], $logData);
            /***********日志*******/
            $this->return_json($a['status'], $a['msg']);//返回错误信息
        }
    }



    /**
     * 展示第三方支付
    */
    public function online_show($online_id=null)
    {

       // $online_id = $this->P('online_id');//支付方式id
        $status    = $this->G('status');   //状态
        $bank_o_id = $this->G('bank_o_id');//支付平台的id

        if ($online_id) {//查询单条
             $where =['id' => $online_id];
            $arr    = $this->core->get_one('*', 'bank_online_pay', $where);
            if (empty($arr)) {
                $this->return_json(E_ARGS, '没有该条记录');
            }
            $temp = json_decode($arr['describe'], true);
            unset($arr['describe']);
            if (empty($temp['title'])) {
                $temp = [
                    'title'   =>'扫码支付',
                    'prompt'  =>'扫码支付限额xxxx-xxxx',
                ];
            }
            $arr  = array_merge($arr, $temp);

            $this->core->select_db('public');
            $temp = $this->core->get_all('online_bank_name,is_box,id', 'bank_online');
            $this->core->select_db('private');
            $arrOnline=[];

            foreach ($temp as $item) {
                $arrOnline[$item['id']]['name']   = $item['online_bank_name'];
                $arrOnline[$item['id']]['is_box'] = $item['is_box'];
            }
           
            $arr['is_box'] = $arrOnline[$arr['bank_o_id']]['is_box'];
        } else {//查询所有

            $where = [] ;
            if ($status == '1') {
                $where['status'] = '1';
            } elseif ($status == '2') {
                $where['status'] = '2';
            }

            if ($bank_o_id) {
                $where['bank_o_id'] = $bank_o_id;
            }
            $order=$this->G('order')?$this->G('order'):'desc';
            $where2 = [
                'orderby'=>array('re_order'=>$order), // 排序
            ];
            $page =  array(
                'page'=>1,//当前页,
                'rows'=>100,//一页显示条数,
                'sort'=>'id',//排序字段,
                'order'=>'desc',//排序类型'desc',
                'total'=>-1//总数, 空为10000条  -1为数据库统计
                );
            //查询线上支付平台
            $this->core->select_db('public');
            $temp = $this->core->get_all('online_bank_name,is_box,id', 'bank_online');
            $this->core->select_db('private');
            $arrOnline=[];

            foreach ($temp as $item) {
                $arrOnline[$item['id']]['name']   = $item['online_bank_name'];
                $arrOnline[$item['id']]['is_box'] = $item['is_box'];
            }

            $arr    = $this->core->get_list('', 'bank_online_pay', $where, $where2, $page);
            foreach ($arr['rows'] as $k => $v) {
                $arr['rows'][$k]['online_name'] = $arrOnline[$v['bank_o_id']]['name'];
                $arr['rows'][$k]['is_box'] = $arrOnline[$v['bank_o_id']]['is_box'];
            }
        }

        if ($online_id) {
            $this->return_json(OK, $arr);
        } else {
            $this->return_json(OK, $arr);
        }
    }



     /**
     * 展示第三方自动出款信息
    */
    public function out_show($online_id=null)
    {

       // $online_id = $this->P('online_id');//支付方式id
        $status    = $this->G('status');   //状态
        $bank_o_id = $this->G('bank_o_id');//支付平台的id

        if ($online_id) {//查询单条
             $where =['id' => $online_id];
            $arr    = $this->core->get_one('*', 'out_online_set', $where);
            if (empty($arr)) {
                $this->return_json(E_ARGS, '没有该条记录');
            }

            $this->core->select_db('public');
            $temp = $this->core->get_all('out_online_name,is_box,id', 'out_online');
            $this->core->select_db('private');
            $arrOnline=[];

            foreach ($temp as $item) {
                $arrOnline[$item['id']]['name']   = $item['out_online_name'];
                $arrOnline[$item['id']]['is_box'] = $item['is_box'];
            }
           
            $arr['is_box'] = $arrOnline[$arr['o_id']]['is_box'];
        } else {//查询所有

            $where = [] ;
            if ($status == '1') {
                $where['status'] = '1';
            } elseif ($status == '2') {
                $where['status'] = '2';
            }

            if ($bank_o_id) {
                $where['o_id'] = $bank_o_id;
            }

            $where2 = [
                'order'=>'id desc', // 排序
            ];
            $page =  array(
                'page'=>1,//当前页,
                'rows'=>100,//一页显示条数,
                'sort'=>'id',//排序字段,
                'order'=>'asc',//排序类型'desc',
                'total'=>-1//总数, 空为10000条  -1为数据库统计
                );
            //查询线上支付平台
            $this->core->select_db('public');
            $temp = $this->core->get_all('out_online_name,is_box,id', 'out_online');
            $this->core->select_db('private');
            $arrOnline=[];

            foreach ($temp as $item) {
                $arrOnline[$item['id']]['name']   = $item['out_online_name'];
                $arrOnline[$item['id']]['is_box'] = $item['is_box'];
            }


            $arr    = $this->core->get_list('', 'out_online_set', $where, $where2, $page);
            foreach ($arr['rows'] as $k => $v) {
                $arr['rows'][$k]['online_name'] = $arrOnline[$v['o_id']]['name'];
                $arr['rows'][$k]['is_box'] = $arrOnline[$v['o_id']]['is_box'];
            }
        }

        if ($online_id) {
            $this->return_json(OK, $arr);
        } else {
            $this->return_json(OK, $arr);
        }
    }

    //验证第三方支付的数据
    private function check_online($data, $temp)
    {
        $rule = [
            'bank_o_id'      => 'require|number',//支付id
            'pay_id'         => 'require', //商户id  20171124出现了有横杠的商户id
            'pay_server_num' => 'alphaNum', //终端号
            'max_amount'     => 'require|number|gt:0', //最大收款额度（支付限额）
            're_order'     => 'require|number|lt:128', //排序权重
            'is_card'        => 'status', //是否为点卡支付@1
            'title'          => 'require|length:4,20',
            'prompt'         => 'require|length:4,25',
            'pay_domain'     => 'require|url',
            'pay_return_url' => 'require|url',
            'shopurl'        => 'require|url',
        ];

        $msg = [

            'bank_o_id'       => '支付平台id只能为数字',//支付id
            'pay_id'          => '商户出错', //商户id
            'pay_server_num'  => '终端号只能为数字和字母', //终端号
            'max_amount'      => '最大限额只能为数字且大于0', //最大收款额度（支付限额）
            're_order'      => '排序为小于128的整数', //最大收款额度（支付限额）
            'is_card'         => '是否为点卡 1:开启,0:关闭', //是否为点卡支付@1
            'title.require'   => 'app显示标题不能为空',
            'prompt.require'  => 'app显示描述不能为空',
            'title.chsDash'   => 'app显示标题能是汉字、字母、数字和下划线_及破折号-',
            'prompt.chsDash'  => 'app显示描述能是汉字、字母、数字和下划线_及破折号-',
            'title.length'    => 'app显示标题长度为4-20',
            'prompt.length'   => 'app显示描述长度为4-25',
            'pay_domain'      => '支付域名请填写完整的url带上http头',
            'pay_return_url'  => '返回地址请填写完整的url带上http头',
            'shopurl'         => '商城域名请填写完整的url带上http头',
        ];
        $a1 = ['bank_o_id','pay_domain','shopurl','pay_return_url','pay_id','pay_server_num','max_amount','is_card','re_order'];
        $a2 = [];//['wx_title','zfb_title','wx_Prompt','zfb_Prompt'];
        if (empty($temp)) {
            $str = 'a1';
        } else {
            $str = 'a2';
            $d   = array_diff($temp, [7]);

            if (!empty($d)) {
                $a2  = array_merge($a2, ['title','prompt']);
            } else {
                return true;
            }
        }

        $this->validate->rule($rule, $msg);
        $this->validate->scene('a1', $a1);
        if ($a2) {
            $this->validate->scene('a2', $a2);
        }
        $result   = $this->validate->scene($str)->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }

    //验证第三方代付的数据
    private function check_out($data, $temp)
    {

        $rule = [
            'o_id'      => 'require|number',//支付id
            'out_domain'         => 'require|url', //。代付域名
            'max_amount'         => 'require|number|gt:0',//.最大限额
            'min_amount'     => 'require|number|gt:0' //。最小限额
            //'total_amount'     => 'require|number|gt:0',//。总限额
        ];

        $msg = [

            'o_id'       => '代付平台id只能为数字',//支付id
            'out_domain'          => '代付域名请填写完整的url带上http头', //商户id
            'max_amount'      => '最大限额只能为数字且大于0', //最大收款额度（支付限额）
            'min_amount'      => '最小限额只能为数字且大于0'  //最小收款额度（支付限额）
            //'total_amount'      => '总限额只能为数字且大于0', //总收款额度（支付限额）
        ];
        $a1 = ['o_id','out_domain','out_id','max_amount','min_amount','total_amount'];
        $a2 = [];//['wx_title','zfb_title','wx_Prompt','zfb_Prompt'];
        if (empty($temp)) {
            $str = 'a1';
        } else {
            $str = 'a2';
            $d   = array_diff($temp, [7]);
        }

        $this->validate->rule($rule, $msg);
        $this->validate->scene('a1', $a1);
        if ($a2) {
            $this->validate->scene('a2', $a2);
        }
        $result   = $this->validate->scene($str)->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }




    /**
     * 验证银行卡的参数
     */
    private function check_card($data, $scene)
    {
        $rule =[

            'card_id'       => 'number|egt:0',       //银行id
            'bank_id'       => 'require|number|egt:0',       //银行id
            'card_num'      => 'require', //银行卡号
            'card_username' => 'require|chsAlpha',  //姓名
            'card_address'  => 'require|chsAlpha',  //开户行
            'max_amount'    => 'require|number',   //停用额度
            're_order'    => 'require|number|lt:128',   //停用额度
            'remark'        => 'chsDash',  //备注
            'status'        => 'require|between:1,2' ,     //状态
            'title'        => 'require|length:4,20',//显示标题
            'prompt'       => 'require|length:4,25',//显示描述

        ];

        $msg  =[

            'bank_id.require'       => '请选择银行',       //银行id
            'card_num.require'      => '请输入银行卡号', //银行卡号
            'card_username.require' => '请输入姓名',  //姓名
            'card_address.require'  => '请输入开户行',  //开户行
            'max_amount.require'    => '请输入停用额度',   //停用额度
            'status.require'        => '请选择银行卡的状态' ,     //状态
            're_order'      => '排序为小于128的整数',
            'card_id.number'         => 'card_id只能为数字',       //银行id
            'bank_id.number'         => 'bank_id只能为数字',
            'card_id.egt'            => 'card_id必须大于0',       //银行id
            'bank_id.egt'            => 'bank_id必须大于0',       //银行id
            'card_username.chsAlpha' => '持卡者姓名只能为汉字和字母',  //姓名
            'card_address.chsAlpha'       => '请输入开户行只能为汉字和字母',  //开户行
            'max_amount.number'      => '停用额度只能为数字',   //停用额度
            'remark.chsDash'         => '备注不能有特殊符号',  //备注
            'status'                 => '状态值为1:开启,2:关闭'  ,    //状态

            'title.require'        => '显示标题不能为空',
            'prompt.require'       => '显示描述不能为空',
            'title.chsDash'        => '显示标题能是汉字、字母、数字和下划线_及破折号-',
            'prompt.chsDash'       => '显示描述能是汉字、字母、数字和下划线_及破折号-',
            'title.length'         => '显示标题长度为4-20',
            'prompt.length'        => '显示描述长度为4-25',

        ];

        $this->validate->rule($rule, $msg);
        $this->validate->scene('miaosu', ['title', 'prompt']);
        $a  = ['card_id', 'bank_id', 'card_username', 'card_address','re_order', 'max_amount','remark','status'];
        if (!in_array($this->bank_id, $this->bankArr)) {
            $a[]= 'card_num';
        }
        $this->validate->scene('card', $a);
        $result   = $this->validate->scene($scene)->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }
    private function check_miao($data)
    {
        $rule =[
            'title'        => 'chsDash|length:4,20',//显示标题
            'prompt'       => 'chsDash|length:4,25',//显示描述
        ];

        $msg  =[
            'title.require'        => '显示标题不能为空',
            'prompt.require'       => '显示描述不能为空',
            'title.chsDash'        => '显示标题能是汉字、字母、数字和下划线_及破折号-',
            'prompt.chsDash'       => '显示描述能是汉字、字母、数字和下划线_及破折号-',
            'title.length'         => '显示标题长度为4-20',
            'prompt.length'        => '显示描述长度为4-25',
        ];

        $this->validate->rule($rule, $msg);
        $this->validate->scene('edit', ['title', 'prompt']);
        $result   = $this->validate->scene('edit')->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }
}
