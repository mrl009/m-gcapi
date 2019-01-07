<?php
/**
 * @模块   会员中心-提现记录-提现详情
 * @版本   Version 1.0.0
 * @日期   2017-04-21
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Payout_record extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
    }

    private $status = array('1'=>'审核中','2'=>'提现成功','4'=>'预备提现','5'=>'审核未通过');
    private $status_arr = array(
        array('label'=>'审核中','value'=>'1'),
        array('label'=>'提现成功','value'=>'2'),
        array('label'=>'预备提现','value'=>'4'),
        array('label'=>'审核未通过','value'=>'5'),
    );
    //获取提现列表
    public function get_payout_list()
    {
        $where['uid'] =$this->user['id'];
        //$where['uid'] = 117;
        $where['status'] = (int)$this->G('type');
        if ($where['status']==3) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $where['addtime >='] = strtotime($this->G('time_start'))?strtotime($this->G('time_start'). ' 00:00:00'):'';
        $where['addtime <='] = strtotime($this->G('time_end'))?strtotime($this->G('time_end'). ' 23:59:59'):'';
        $page  = array(
            'page'  => $this->G('page')?(int)$this->G('page'):1,
            'rows'  => $this->G('rows')?(int)$this->G('rows'):20,
        );

        if (empty($where['uid'])) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $where['status <>'] = 3;
        $list= $this->M->get_list('id,uid,price,addtime,status,order_num,remark', 'cash_out_manage', $where, array(), $page);
        $rows = $list['rows'];
        if (empty($rows)) {
            $this->return_json(OK, array('rows'=>array(),'status'=>$this->status_arr));
        }
        $ss  = $this->status;
        foreach ($rows as $key => $value) {
            $rows[$key]['content'] = '提现'.$value['price'].'元';
            @$rows[$key]['tips'] = $ss[$value['status']];
            $rows[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
        }
        $list['status'] = $this->status_arr;
        $list['rows'] = $rows;
        $this->return_json(OK, $list);
    }

    //获取提现详情
    public function get_payout_detail()
    {
        $uid =$this->user['id'];
        //$uid=117;
        $where['id'] =(int)$this->G('id');
        if (empty($uid) || empty($where['id'])) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $field = 'id,uid,order_num,price,hand_fee,admin_fee,actual_price,addtime,status,remark';
        $one = $this->M->get_one($field, 'cash_out_manage', $where, array());
        if (empty($one)) {
            $this->return_json(OK, array('rows'=>array()));
        }
        $one['content'] = '提现';
        $one['tips'] = $this->status[$one['status']];
        $one['addtime'] = date('Y-m-d H:i:s', $one['addtime']);
        $one['price'] = sprintf("%.2f", $one['price']);
        $one['hand_fee'] = sprintf("%.2f", $one['hand_fee']);
        $one['admin_fee'] = sprintf("%.2f", $one['admin_fee']);
        $one['actual_price'] = sprintf("%.2f", $one['actual_price']);
        $this->return_json(OK, $one);
    }
}
