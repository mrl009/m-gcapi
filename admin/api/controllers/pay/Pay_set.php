<?php
/**
 * @file pay_set.php
 * @brief  支付设定
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

class Pay_set extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Pay_set_model');
    }


    private $paydata=array(  //测试所用 的 数组
        //存款优惠
        'pay_name' => '默认的支付设定1',
        /*第三方支付*/
        'ol_deposit'      => 2,  //存款优惠 1 每次 2首次
        'ol_is_give_up' => 0,  //可放弃优惠  1 0

        'ol_discount_num' => 100000,  //优惠标注金额
        'ol_discount_per' => 1.1 ,  //优会百分比
        'ol_catm_max'   => 10, //存款最大金额
        'ol_catm_min'   => 0,     //存款最小金额
        'ol_discount_max' => 100  , //最大优惠

//        'ol_other_discount_num'    => 1000,  //其他优惠标注金额
//        'ol_other_discount_per'    => 1.1 ,  //其他优会百分比
//        'ol_other_discount_max'    => 100 ,  //其他优惠最大优惠
//        'ol_other_discount_max_24' => 0   , //24小时内最大其他优惠上限 0为没上线,
//        'ol_fc_audit'    => 10,    //彩票稽核倍数
//        'ol_is_fc_audit' => 1,    //彩票是否开启 0 1


        'ol_cz_audit'    => 10,    //常态稽核倍数
        'ol_is_cz_audit' => 1,     //常态是否开启 0 1

        'ol_ct_fk_audit' => 10 ,   //放宽额度
        'ol_ct_xz_audit' => 11 ,   //常态稽核行政费率

         /*公司入款*/
        'line_deposit'      => 1,  //存款优惠 1 每次 2首次
        'line_is_give_up'   => 0,  //可放弃优惠  1 0
        'line_discount_num' => 1000,  //优惠标注金额
        'line_discount_per' => 1.1 ,  //优会百分比
        'line_catm_max'     => 50000, //存款最大金额
        'line_catm_min'     => 0,     //存款最小金额
        'line_discount_max' => 100  , //单次最大优惠
        'line_discount_min' => 1  , //单次最大优惠
//
//        'line_other_discount_num'    => 1000,  //优惠标注金额
//        'line_other_discount_per'    => 1.1 ,  //优会百分比
//        'line_other_discount_max'    => 1000 ,  //单次最大优惠
//        'line_other_discount_max_24' => 0   ,  //24小时内最大其他优惠上限 0为没上线

//        'line_fc_audit'    => 10,    //彩票稽核倍数
//        'line_is_fc_audit' => 1,     //彩票是否开启 0 1


        'line_cz_audit'    => 10,    //常态稽核倍数
        'line_is_cz_audit' => 1,     //常态是否开启 0 1

        'line_ct_fk_audit' => 10 ,   //放宽额度
        'line_ct_xz_audit' => 20 ,   //常态稽核行政费率

        /*出款设置*/
        'out_max'          => 50000,   //出款手续费上线
        'out_min'          => 50,      //出款手续费下线
        'is_counter'       => 0,      //达到门槛是否收取手续费
        'counter_num'      => 1,      //免说手续费次数
        'counter_money'    => 500  ,    //出款手续费
        'online_risk'      => 3000      //第自动入款三方风控额度

    );
    public function index()
    {
    }

    /**
     * 添加支付设定
    */
    public function pay_add()
    {
        $pay_id = $this->P('id');
//        $pay_id = 11;//$this->P('id');

        if (!empty($pay_id)) {//判断是否为更新
            $data['pay_id'] = $pay_id =$pay_id;
        }
        //存款优惠
        $payname = $this->P('pay_name');
        /*第三方支付*/
        $data['ol_deposit']      = (int)$this->P('ol_deposit');  //存款优惠 1 每次 2首次
        $data['ol_is_give_up']   = (int)$this->P('ol_is_give_up');  //可放弃优惠  1 0

        $data['ol_discount_num'] = $this->P('ol_discount_num');  //优惠标注金额
        $data['ol_discount_per'] = $this->P('ol_discount_per');  //优会百分比
        $data['ol_catm_max']     = $this->P('ol_catm_max');//存款最大金额
        $data['ol_catm_min']     = $this->P('ol_catm_min');   //存款最小金额
        $data['ol_discount_max'] = $this->P('ol_discount_max') ; //最大优惠


        $data['ol_ct_audit']    = $this->P('ol_ct_audit');    //常态稽核倍数
        $data['ol_is_ct_audit'] = (int)$this->P('ol_is_ct_audit');     //常态是否开启 0 1

        $data['ol_ct_fk_audit'] = $this->P('ol_ct_fk_audit') ;   //放宽额度
        $data['ol_ct_xz_audit'] = $this->P('ol_ct_xz_audit') ;   //常态稽核行政费率

        /*公司入款*/
        $data['line_deposit']      = (int)$this->P('line_deposit');  //存款优惠 1 每次 2首次
        $data['line_is_give_up']   = (int)$this->P('line_is_give_up');  //可放弃优惠  1 0
        $data['line_discount_num'] = $this->P('line_discount_num');  //优惠标注金额
        $data['line_discount_per'] = $this->P('line_discount_per');  //优会百分比
        $data['line_catm_max']     = $this->P('line_catm_max'); //存款最大金额
        $data['line_catm_min']     = $this->P('line_catm_min') ;     //存款最小金额
        $data['line_discount_max'] = $this->P('line_discount_max') ; //单次最大优惠
        $data['line_discount_min'] = $this->P('line_discount_min') ; //单次最大优惠

        $data['line_ct_audit']    = $this->P('line_ct_audit');    //常态稽核倍数
        $data['line_is_ct_audit'] = (int)$this->P('line_is_ct_audit');     //常态是否开启 0 1

        $data['line_ct_fk_audit'] = $this->P('line_ct_fk_audit') ;   //放宽额度
        $data['line_ct_xz_audit'] = $this->P('line_ct_xz_audit') ;   //常态稽核行政费率

        /*出款设置*/
        $data['out_max']          = $this->P('out_max') ;   //出款手续费上线
        $data['out_min']          = $this->P('out_min') ;      //出款手续费下线

        $data['is_counter']       = (int)$this->P('is_counter');      //达到门槛是否收取手续费
        $data['counter_num']      = $this->P('counter_num');      //免说手续费次数
        $data['counter_money']    = $this->P('counter_money');      //出款手续费
        $data['online_risk']      = $this->P('online_risk');      //第三方自动入款风控额度
        $data['wx_max']     = (int)$this->P('wx_max');      //第三方自动入款风控额度
        $data['zfb_max']     = (int)$this->P('zfb_max');      //第三方自动入款风控额度

        /********** 日志详细修改数据记录 *************/
        $record = function () use ($pay_id, $payname, $data) {
            $old = $this->Pay_set_model->get_one(
                'pay_name,pay_set_content', 'pay_set', ['id'=>$pay_id]);
            $old['pay_set_content'] = json_decode($old['pay_set_content'], true);
            $log = '';
            if ($old['pay_name'] != $payname) {
                $log .= '支付名称：'.$payname.'('.$old['pay_name'].')-';
            }
            $format = [
                'ol_deposit'=>'存款优惠','ol_is_give_up'=>'可放弃优惠',
                'ol_discount_num'=>'优惠标注金额','ol_discount_per'=>'优会百分比',
                'ol_catm_max'=>'存款最大金额','ol_catm_min'=>'存款最小金额',
                'ol_discount_max'=>'最大优惠','ol_ct_audit'=>'常态稽核倍数',
                'ol_is_ct_audit'=>'常态是否开启','ol_ct_fk_audit'=>'放宽额度',
                'ol_ct_xz_audit'=>'常态稽核行政费率',

                'line_deposit'=>'存款优惠','line_is_give_up'=>'可放弃优惠',
                'line_discount_num'=>'优惠标注金额','line_discount_per'=>'优会百分比',
                'line_catm_max'=>'存款最大金额','line_catm_min'=>'存款最小金额',
                'line_discount_max'=>'单次最大优惠','line_discount_min'=>'单次最大优惠',
                'line_ct_audit'=>'常态稽核倍数','line_is_ct_audit'=>'常态是否开启',
                'line_ct_fk_audit'=>'放宽额度','line_ct_xz_audit'=>'常态稽核行政费率',

                'out_max'=>'出款手续费上线','out_min'=>'出款手续费下线',
                'is_counter'=>'达到门槛是否收取手续费','counter_num'=>'免收手续费次数',
                'counter_money'=>'出款手续费','online_risk'=>'第三方自动入款风控额度',
                'wx_max'=>'微信出款上限',
                'zfb_max'=>'支付宝出款上限'
            ];

            foreach ($format as $k => $v) {
                if (!array_key_exists($k,$old['pay_set_content']) ||
                    !array_key_exists($k,$data)) {
                } elseif ($old['pay_set_content'][$k] != $data[$k]) {
                    $log .= $v.'：'.$data[$k].'('.$old['pay_set_content'][$k].')-';
                }
            }
            return $log;
        };
        if (!empty($data['pay_id'])) {
            $otherLog=$record();
        }
        /********** 日志详细修改数据记录 *************/



//        $data    = $this->paydata;
//        $payname = "默认的支a11";
//        $pay_id  = 1;
        if ($this->check_pay($data)) {
            $where = [];
            if (empty($pay_id)) {
                $where = ['pay_name'=>$payname];
                $arr = $this->Pay_set_model->get_one('*', 'pay_set', $where);
                if ($arr) {
                    return $this->return_json(E_ARGS, '支付名称不能重复');
                }
                $where= [];
            } else {
                $where['id'] = $pay_id;
                $sql = "select * from gc_pay_set where pay_name = '{$payname}' and  id !=$pay_id";
                $arr = $this->Pay_set_model->db->query($sql)->row();
                if ($arr) {
                    return $this->return_json(E_ARGS, '支付名称不能重复');
                }
            }

            $paydata =array();
            $paydata['pay_name'] = $payname;
            $paydata['pay_set_content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            if (empty($pay_id)) {
                $arr    = $this->Pay_set_model->write('pay_set', $paydata, $where);
                $pay_id = $this->Pay_set_model->db->insert_id();

                if ($arr) {
                    $arr = [];
                    $arr['msg'] = "操作成功";
                    $this->Pay_set_model->update_keys($pay_id, ['id'=>$pay_id,'data'=>$paydata['pay_set_content']]);
                    $arr['status'] = OK;
                } else {
                    $arr = [];
                    $arr['msg'] = "操作失败";
                    $arr['status'] = E_OP_FAIL;
                }
                /***********日志*******/
                ($arr['status'] == OK)?$x="成功":$x="失败";
                $this->load->model('log/Log_model');
                $logData['content'] = "添加了支付设定:id:$pay_id"."状态:$x";//内容自己对应好
                $this->Log_model->record($this->admin['id'], $logData);
                /***********日志*******/

                $this->return_json($arr['status'], $arr['msg']);
            } else {
                $bool = $this->Pay_set_model->db->update('pay_set', $paydata, $where);

                /***********日志*******/
                ($bool)?$x="成功":$x="失败";
                $this->load->model('log/Log_model');
                if (!empty($data['pay_id'])) {
                    $logData['content'] = "更新了支付设定:id:$pay_id"."状态:$x".'---'.$otherLog;//内容自己对应好
                } else {
                    $logData['content'] = "更新了支付设定:id:$pay_id"."状态:$x";
                }
                $this->Log_model->record($this->admin['id'], $logData);
                /***********日志*******/

                if ($bool) {
                    $this->Pay_set_model->update_keys($pay_id, ['id'=>$pay_id,'data'=>$paydata['pay_set_content']]);
                    $this->return_json(OK, '更新成功');
                } else {
                    $this->return_json(E_OK, '更新失败');
                }
            }
        }
    }

    /**
     * 支付设定数据展示
     */
    public function pay_show($pay_id = null)
    {
        $arr = $this->Pay_set_model->show_data($pay_id);
        $this->return_json($arr['status'], $arr['data']);
    }

    /**
     * 删除支付设定
    */
    public function pay_del()
    {
        $pay_id  = $this->P('id');
        $pay_idx = explode(",", $pay_id);
        if (empty($pay_id)) {
            $this->return_json(E_ARGS, '参数错误');
        }

        $data = $this->Pay_set_model->db->where_in('pay_id', $pay_idx)->get('level')->result_array();
        $msg = "删除成功";
        $xx   = OK;

        if ($data) {
            $xx   = E_ARGS;
            $msg = "未使用中的支付设定已删除";
            $idArr = array_column($data, 'pay_id');
            $idArr = array_unique($idArr);

            foreach ($pay_idx as $k => $v) {
                if (in_array($v, $idArr)) {
                    unset($pay_idx[$k]);
                }
            }
        }

        foreach ($pay_idx as $v) {
            $this->Pay_set_model->update_keys($v, []);
        }
        if (empty($pay_idx)) {
            $this->return_json(E_ARGS, "选中的支付设定被使用中");
        }
        $bool = $this->Pay_set_model->db->where_in('id', $pay_idx)->delete('pay_set');

        /***********日志*******/
        ($bool)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $logData['content'] = "删除了支付:id:".implode(",", $pay_idx)."状态:$x";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);
        /***********日志*******/
        if ($bool) {
            $this->return_json($xx, $msg);
        } else {
            $this->return_json(E_ARGS, "删除失败");
        }
    }

    /**
     * 检查支付参数
     */
    public function check_pay($data)
    {
        $arr=array('ol'=>'第三方支付','line'=>'公司入款');
        $rule =array();
        $msg  =array();

        foreach ($arr as $key=>$value) {
            $rule[$key . '_deposit'] = 'require|between:1,2|number';
            $rule[$key . '_is_give_up'] = 'require|accepted';
            $rule[$key . '_discount_num'] = 'require|number|egt:0';
            $rule[$key . '_discount_per'] = 'require|number|egt:0';
            $rule[$key . '_catm_max'] = 'require|int|gt:'.$data[$key.'_catm_min'];
            $rule[$key . '_catm_min'] = 'require|int|gt:0';
            $rule[$key . '_discount_max'] = 'require|number|egt:0';

           /* $rule[$key . '_other_discount_num'] = 'number|egt:0';
            $rule[$key . '_other_discount_per'] = 'number|egt:0';
            $rule[$key . '_other_discount_max'] = 'number|egt:0';
            $rule[$key . '_other_discount_max_24'] = 'number|egt:0';*/
//
//            $rule[$key . '_fc_audit'] = 'number|egt:0';
//            $rule[$key . '_is_fc_audit'] = 'accepted';
//            $rule[$key . '_zh_audit'] = 'number|egt:0';
//            $rule[$key . '_zh_fc_audit'] = 'accepted';
            $rule[$key . '_ct_audit'] = 'require|number|egt:0';
            $rule[$key . '_ct_fc_audit'] = 'accepted';

            $rule[$key . '_ct_fk_audit'] = 'require|number|egt:0';
            $rule[$key . '_ct_xz_audit'] = 'require|number|egt:0';


            $msg[$key . '_deposit']         = $value . '存款优惠type只能为1（每次）或者2（首次）';
            $msg[$key . '_is_give_up']      = $value . '可弃优惠type只能为1或者0';
            $msg[$key . '_discount_num']    = $value . '优惠标注金额必须为数字';
            $msg[$key . '_discount_per']    = $value . '优惠比必须为数字';
            $msg[$key . '_catm_max.int'] = $value . '存款最大金额必须为整数';
            $msg[$key . '_catm_max.gt']        = $value .     '存款最大金额必须大于最小金额';
            $msg[$key . '_catm_min.gt']        = $value .     '存款最小金额必须大于0';
            $msg[$key . '_catm_min.int'] = $value . '存款最小金额必须为整数';
            $msg[$key . '_discount_max']    = $value . '单次最大优惠必须为数字';
            $msg[$key . '_ct_audit'] = $value . '常态稽核额度必须为数字';
            $msg[$key . '_ct_fc_audit'] = $value . '常态稽核开启1 关闭 0';

            $msg[$key . '_ct_fk_audit'] = $value . '放宽额度必须为数字';
            $msg[$key . '_ct_xz_audit'] = $value . '常态稽核费率必须为数字';
        }

        $rule['out_min'] = 'require|int';
        $msg['out_min']  = '出款下限必须为整数';
        $rule['is_counter'] = 'accepted';
        $msg['is_counter']  = '达到门槛是否收取手续费 1 0';
        $rule['counter_num'] = 'require|int';
        $msg['counter_num']  = '免手续费次数为整数';
        $rule['counter_money'] = 'require|int';
        $msg['counter_money']  = '出款手续费为整数';
        $rule['pay_id'] = 'number|egt:0';
        $msg['counter_money']  = '支付id错误';
        $rule['online_risk'] = 'require|int|egt:0';
        $msg['online_risk']  = '第三方风控额度必填,必须为整数';

        //出款限制
        $rule['out_max'] = 'require|int';
        $msg['out_max']  = '出款上限必须为整数';
        if ($data['out_max'] != 0) {
            $rule['out_max'] .= '|gt:'.$data['out_min'];
            $msg['out_max']  .= '且必须大于最小出款'.$data['out_min'].',0为关闭';
        }

        $rule['wx_max'] = 'require|int';
        $msg['wx_max']  = '微信出款度必填,必须为整数';
        if ($data['wx_max']  != 0) {
            $rule['wx_max'] .= '|gt:'.$data['out_min'];
            $msg['wx_max']  .= '且必须大于最小出款'.$data['out_min'].',0为关闭';
        }

        $rule['zfb_max'] = 'require|int';
        $msg['zfb_max']  = '支付宝出款度必填,必须为整数';
        if ($data['zfb_max']  != 0) {
            $rule['zfb_max'] .= '|gt:'.$data['out_min'];
            $msg['zfb_max']  .= '且必须大于最小出款'.$data['out_min'].',0为关闭';
        }

        $this->validate->rule($rule, $msg);//验证数据

        $result   = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return 1;
        }
    }
}
