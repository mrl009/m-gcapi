<?php
/**
 * @模块   会员中心／投注记录
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Bet_record extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Bet_record_model',
                            'core');
    }

    /* 筛选选项 */
    private $opts = array('rows'=>
            array(
                array('label'=>'全部订单', 'value'=>STATUS_All, 'show'=>1),
                array('label'=>'已中奖', 'value'=>STATUS_WIN,'show'=>1),
                array('label'=>'待开奖', 'value'=>STATUS_NOTOPEN,'show'=>1),
                array('label'=>'已撤单', 'value'=>STATUS_CANCEL,'show'=>1),
               array('label'=>'未中奖', 'value'=>STATUS_LOSE,'show'=>0),
               array('label'=>'已取消', 'value'=>STATUS_CANCELING,'show'=>0),
            ));
    /* type的限定值 */
    private $type_limit = array(STATUS_All, STATUS_WIN,
        STATUS_NOTOPEN, STATUS_CANCEL
       // ,STATUS_LOSE
        );

    /******************公共方法*******************/
    /**
     * 获取下注列表操作
     */
    public function get_list()
    {
        // 默认获取全部订单 0：全部订单,1:已中奖,3:已撤单,4:待开奖,5:未中奖
        $type = (int)$this->G('type');
        if (!in_array($type, $this->type_limit)) {
            $type = 0;
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }
        // 精确条件
        $basic['a.uid'] = $this->user['id'];
        $basic['b.status'] = $type;
        $basic['a.created >='] = time()-30*86440;

        // 排序分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $page   = array(
            'page'  => $page,
            'rows'  => 15,
        );
        $data = $this->core->get_bet_list($type, $basic, $page);
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }
    /**
     * 获取下注列表操作
     */
    public function get_list2()
    {
        // 默认获取全部订单 0：全部订单,1:已中奖,3:已撤单,4:待开奖,5:未中奖
        $type = (int)$this->G('type');
        if (!in_array($type, $this->type_limit)) {
            $type = 0;
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }
        // 精确条件
        $basic['a.uid'] = $this->user['id'];
        $basic['b.status'] = $type;
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $page   = array(
            'page'  => $page,
            'rows'  => 15,
        );
        $data = $this->core->get_bet_list($type, $basic, $page);
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }
    /**
     * 代理注单检索
     */
    public function get_agent_list()
    {
        $type = (int)$this->G('type');
        if (!in_array($type, $this->type_limit)) {
            $type = 0;
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }
        // 精确条件
        $basic['a.agent_id'] = $this->user['id'];
        $basic['b.status'] = $type;
        // 获取代理用户id
        $username = $this->G('username');
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', array('username' => $username));
            if (isset($uid['id'])) {
                $basic['a.uid'] = $uid['id'];
            } else {
                $this->return_json(OK, ['total' => 0, 'rows' => []]);
            }
        }
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $page   = array(
            'page'  => $page,
            'rows'  => 15,
        );
        $data = $this->core->get_bet_list($type, $basic, $page);
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }
    /**
     * 获取选项
     */
    public function get_type()
    {
        $this->return_json(OK, $this->opts);
    }
    /*******************************************/
}
