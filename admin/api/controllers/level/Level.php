<?php
/**
 * @file Level.php
 * @brief 层级相关接口  层级的添加 更改 删除
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

class Level extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->model('level/Level_model');
        $this->load->model('pay/Bank_model','BM');
        $this->load->model('pay/Fast_model','FM');
        $this->load->model('pay/Online_model','OM');
        $this->load->model('pay/My_level_model','LM');
    }

    public function index()
    {
        $this->Level_model->chushihua();
    }

    /**
     * @php 快速直通车支付通道新加方法
     * lqh  2018/12/31
     */
    public function level_add_new()
    {
        //接收參數
        $level_id = input('param.id',0,'intval');
        $level_name = input('param.level_name','','trim');
        $bank_id = input('param.bank_id','','trim');
        $online_id = input('param.online_id','','trim');
        $fast_id = input('param.fast_id','','trim');
        //構造參數
        $data = array(
           'level_name' => $level_name,
           'total_deposit' => input('param.total_deposit',0,'intval'),
           'total_num' => input('param.total_num',0,'intval'),
           'remark' => input('param.remark','','trim'),
           'pay_id' => input('param.pay_id',0,'intval')
        );
        //判断参数
        if (empty($level_name) || empty(array_filter($data)))
        {
            $this->return_json(E_ARGS, '缺少必要参数');
        }  
        //判斷層級是否已經存在    
        $verify_name = $this->LM->verify_name($level_name,$level_id);
        if (!empty($verify_name)) 
        {
            $this->return_json(E_ARGS, '层级名称不能重复'); 
        }  
        /**执行sql操作 更新层级数据**/
        //添加层级信息
        if (empty($level_id))
        {
            $is_default = $this->LM->get_is_default();
            $data['is_default'] = $is_default;
            //开启事物
            $this->LM->db->trans_start();
            //添加level数据
            $this->LM->db->insert('level', $data);
            $insert_id = $this->LM->db->insert_id();
            //添加银行数据
            if (!empty($bank_id)) 
            {
                $bank_id = explode(',',$bank_id);
                $this->BM->add_bank_data($bank_id,$insert_id);
            }
            //添加第三方支付数据
            if (!empty($online_id))
            {
                $online_id = json_decode($online_id,true);
                $this->OM->add_online_data($online_id,$insert_id);
            }
            //添加直通车数据
            if (!empty($fast_id))
            {
                $fast_id = json_decode($fast_id,true);
                $this->FM->add_fast_data($fast_id,$insert_id);
            }
            //更新redis缓存信息
            $this->Level_model->level_cache($insert_id, $level_name, false);
            $code = $this->LM->db->trans_complete();
        //更新层级信息
        } else {
            //开启事物
            $this->LM->db->trans_start();
            //更新level数据
            $this->LM->update_level_data($data,$level_id);
            //更新银行数据
            $bank_id = explode(',',$bank_id);
            $this->BM->update_bank_data($bank_id,$level_id);
            //更新第三方支付数据
            $online_id = json_decode($online_id,true);
            $this->OM->update_online_data($online_id,$level_id);
            //更新直通车数据
            $fast_id = json_decode($fast_id,true);
            $this->FM->update_fast_data($fast_id,$level_id);
            //更新redis缓存
            $this->Level_model->level_cache($level_id, $level_name, false);
            $code = $this->LM->db->trans_complete();
        }
        //加入操作日志信息
        $x = empty($code) ? '失败' :'成功';
        $a = empty($level_id) ? '添加' : '修改'; 
        $msg = "{$a}了层级：层级id{$level_name},状态：{$x}";
        $msg .= ",参数：" . json_encode($data);  
        $logData['content'] = $msg;
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], $logData);
        //返回信息
        if ($code)
        {
            $this->return_json(OK, '操作成功');
        } else {
            $this->return_json(E_OK, '操作失败');
        }
    }

    /**
     * 添加层级的时候数据展示
    */
    public function insert_show_new() 
    {
        //獲取支付設定數據
        $all_pay = $this->Level_model->get_all('id,pay_name', 'pay_set');
        foreach ($all_pay as $k => $v) 
        {
            $all_pay[$k]['is_check'] = 0;
        }
        $data['pay_move'] = $all_pay;
        //獲取公司入款、第三方支付、直通车數據
        $data['bank'] = $this->BM->get_level_a();
        $data['online'] = $this->OM->get_level_a();
        $data['fast'] = $this->FM->get_level_a();
        //返回构造數據
        $this->return_json(OK,$data);
    }

    /**
     *更新成层级的信息是显示的信息
     * 
    */
    public function updata_show_new($level_id=null)
    {
        if (empty($level_id) || (0 >= $level_id))
        {
            $this->return_json(OK, '层级id错误');
        }
        //獲取層級數據
        $data = $this->Level_model->level_addx($level_id);
        if (empty($data)) $this->return_json(OK, '没有该层级');
        //獲取公司入款、第三方支付、直通车數據
        $data['bank'] = $this->BM->get_level_e($level_id);
        $data['online'] = $this->OM->get_level_e($level_id);
        $data['fast'] = $this->FM->get_level_e($level_id);
        //返回構造數據
        $this->return_json(OK, $data);
    }

    /**
     * 添加层级  已添加日志信息
     *
    */
    public function level_add()
    {
        $leve_id    = $this->P('id');


        $data = array(
            'level_name'      => trim($this->P('level_name')),//层级名称
            'total_deposit'   => $this->P('total_deposit'),// 存款总额度
            'total_num'       => $this->P('total_num'),//存款次数
            'remark'          => (string)$this->P('remark'),//备注
            'pay_id'          => $this->P('pay_id'),//支付设定id
            'bank_id'         => explode(',', $this->P('bank_id')),   //银行卡id
            'online_id'       => json_decode($this->P('online_id'), true),   //第三方支付的id
            'level_id'        => $leve_id,
        );


        if (empty($this->P('bank_id'))) {
            $data['bank_id'] = [];
        }
        if (empty($this->P('max_total'))) {
            $data['max_total'] = 0;
        }

        if ($this->check_level($data)) {
            $arr = $this->Level_model->level_add($data, $leve_id);
            ($arr['status']==OK)?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            if ($leve_id) {
                $logData['content'] = "更改了层级:层级id".$data['level_name']."状态:$x 参数:".json_encode($data);//内容自己对应好
            } else {
                $logData['content'] = "添加了层级:层级名称".$data['level_name']."状态:$x 参数: ".json_encode($data);//内容自己对应好
            }
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json($arr['status'], $arr['txt']);//返回信息
        }
    }
    /**
     * 添加层级的时候数据展示
    */
    public function insert_show()
    {
        $arr  = $this->Level_model->get_level_o('z');
        $arrx = $this->Level_model->get_all('card_num,id,bank_id,card_username', 'bank_card', ['status'=>1]);
        $base = $this->Level_model->base_bank_online('bank');

        foreach ($arr as $k => $v) {
            $arr[$k]['is_check'] = 0;
            unset($arr[$k]['check']);
        }
        foreach ($arrx as $k => $v) {
            if ($base[$v['bank_id']]['is_qcode'] == 1) {
                $arrx[$k]['card_username'] .='/'.$base[$v['bank_id']]['bank_name'];
            }
            $arrx[$k]['is_check'] = 0;
        }

        $all_pay = $this->Level_model->get_all('id,pay_name', 'pay_set');
        foreach ($all_pay as $k => $v) {
            $all_pay[$k]['is_check'] = 0;
        }

        $temp['online']  = $arr;
        $temp['bank']    = $arrx;
        $temp['pay_move'] = $all_pay;

        $this->return_json(OK, $temp);
    }


    /**
     *  展示层级的信息
     */
    public function show_level()
    {
        $page   = array(
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->G('order'),
            'sort'  => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->Level_model->count_level($page);
        $this->return_json(OK, $arr);
    }


    /**
     * 移动层级
    */
    public function move_level()
    {
        $id     = $this->P('id');
        $new_id = $this->P('new_id');
        if ($id<=0||$new_id<=0) {
            $this->return_json(E_ARGS, 'id错误');
        }
        if ($id == $new_id) {
            $this->return_json(OK);
        }

        $user = $this->Level_model->is_chang_level($id,$new_id);
        if ($user !== true) {
            $this->return_json(E_ARGS,$user);
        }

        ($user)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $logData['content'] = "将层级id:{$id}的会员移动到层级id:{$new_id}"."状态:$x";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);
        if ($user) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK);
        }
    }

    /**
     * 更改支付设定 日志信息已添加
    */
    public function chang_pay($data)
    {
        $id     = $data[0];
        $pay_id = $data[1];
        if ($id <=0 || $pay_id <= 0) {
            $this->return_json(E_ARGS, 'id错误');
        }
        $arr = $this->Level_model->db->update('level', ['pay_id'=>$pay_id], ['id'=>$id]);

        ($arr)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $logData['content'] = "更改支付设定id:{$id}到支付设定id:{$pay_id}"."状态:$x";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);

        if ($arr) {
            $this->return_json(OK, '成功');
        } else {
            $this->return_json(E_OK, '失败');
        }
    }


    /**
     *更新成层级的信息是显示的信息
    */
    public function updata_show($level_id=null)
    {
        if ($level_id <=0) {
            $this->return_json(OK, '层级id错误');
        }
        $data = $this->Level_model->level_addx($level_id);
        if (empty($data)) {
            $this->return_json(OK, '没有该层级');
        }
        $this->return_json(OK, $data);
    }


    /**
     * 层级数据验证
     * @param array       $data       要验证的数据
     * @param bool        $only_pay   是否只验证pay_id
     * @return bool        数据验证成功返回true验证失败返回错误信息
     */
    public function check_level($data)
    {
        $rule = [
            'level_name'      => 'chsAlphaNum',//层级名称
            'total_deposit'       => 'require|number|egt:0'     ,//存款次数
            'total_num'       => 'require|int|egt:0'    ,//存款总额度
            'remark'          => 'chsAlphaNum'    ,//备注
            'pay_id'          => 'require|number|gt:0'      ,//支付设定id
            'bank_id'         => 'intGt0'  ,     //
//            'online_id'       => 'intGt0'    ,   //
            'level_id'        => 'intGt0'  ,     //
            'move_id'         => 'intGt0'       //
        ];

        $msg  = [
            'level_name'      => '层级名称只能是汉字、字母和数字',//层级名称
            'total_num'       => '存款次数只能为数字且大于等于0'     ,//存款次数
            'total_deposit'   => '存款额度只能为数字且大于等于0'    ,//存款总额度
            'remark'          => '备注只能是汉字'    ,//备注
            'pay_id'          => '支付设定id必须是数字且大于0'      ,//支付设定id
            'bank_id'         => '银行卡id错误'   ,    //银行卡id错误
//            'online_id'       => '第三方支付id错误'    ,   //第三方支付id错误
            'level_id'        => '层级id错误'    ,   //第三方支付id错误
            'move_id'         => '必须为数字且大于0新层级id'       //

        ];


        $this->validate->rule($rule, $msg);
        $result   = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }

    /**
     * 获取层级的id 和层级的名字
    */
    public function level_base()
    {
        $arr = $this->Level_model->get_all('id,level_name', 'level');
        $this->return_json(OK, ['rows'=>$arr]);
    }



}
