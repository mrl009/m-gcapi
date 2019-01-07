<?php
if (!defined('BASEPATH')) {exit('No direct access allowed.');}

class Agent_bet_detail extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('agent/Agent_bet_detail_model','core');
        $this->load->model('Agent_model');
    }
    /*奖金状态
      type限定值：
      0 全部 1 已中奖 2 和局 3 订单取消 4 待开奖 5 未中奖
    */
    private $type = array(STATUS_All,STATUS_WIN,STATUS_HE,STATUS_CANCEL,STATUS_NOTOPEN,STATUS_LOSE);

    public function get_bet_list()
    {
        $username = trim($this->P('username'));
        $gid = trim($this->P('gid'));
        $type = (int)($this->P('type'));
        if (!in_array($type,$this->type)) {
            $this->return_json(E_ARGS,'参数错误');
        }
        $son_ids = $this->core->get_children_uid($this->user['id']);
        if (empty($son_ids)) {
            $this->return_json(OK,[]);
        }
        if (!empty($username)) {
            if ($username == $this->user['username']) {
                $this->return_json(E_ARGS,'不能查询自己哦！');
            }
            $user = $this->core->get_one('id','user',['username'=>$username,'wherein'=>['id'=>$son_ids]]);
            if (empty($user)) {
                $this->return_json(E_ARGS,'查找的用户不存在');
            } else {
                $where['a.uid'] = $user['id'];
            }
        } else {
            $where['wherein'] = ['a.uid'=>$son_ids];
        }
        $between_day = empty((int)$this->P('between_day')) ? 0 :(int)$this->P('between_day');
        $today = strtotime(date('Y-m-d'));
        if ($between_day === 0){
            $where['a.created >='] = $today;
        } elseif ($between_day === 1){
            $where['a.created >='] = $today-3600*24;
            $where['a.created <'] = $today;
        } elseif ($between_day === 7){
            $where['a.created >='] = $today-3600*24*7;
        }else{
            $this->return_json(E_ARGS,'参数错误');
        }
        if ($gid) {
            $where['a.gid'] = $gid;
        }
        $num = (int)$this->P('num');
        $index = (int)$this->P('index');
        $num = $num ? $num : 10;
        $index = $index ? $index : 0;
        $condition= array($num,$index);
        $data = $this->core->get_bet_list($type,$where,$condition);
        if ($data) {
            $this->return_json(OK,$data);
        } else {
            $this->return_json(OK,[]);
        }

    }


    /**
     * 获取投注详情
     */
    public function get_game_bet_detail(){
        $order_num = trim($this->P('order_num'));
        if (empty($order_num)) $this->return_json(E_ARGS,'参数错误');
        $gid = (int)substr($order_num, 1, 2);
        $detail = $this->core->get_game_detail($order_num,$gid);
        if ($detail) $this->return_json(OK,$detail);
    }

    public function get_game_lists()
    {
        $games = $this->core->games_list();
        if ($games !== false) {
            $this->return_json(OK, $games);
        }else{
            $this->return_json(E_OP_FAIL);
        }
    }

}