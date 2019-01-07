<?php
/**
 * @模块   视讯
 * @版本   Version 1.0.0
 * @日期   2017-09-11
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Shixun extends MY_Controller
{
    private $game_list = array('ag','mg','dg','lebo','pt');//游戏
    private $tiaojiao = array();//查询条件
    private $sort_field = array();//可排序字段列表
    private $table = '';
    private $field = '';//查询字段
    private $select = '';//统计查询字段
    private $moon = '';//月份
    private $sort_sn = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('sx/Shixun_model', 'sx');
        $this->load->library('BaseApi');
        $this->sort_sn = $this->get_sn();
    }

	/**
	 * 获取视讯报表
	 * @接收参数：game，sn，username，game_type，start_time，end_time，page，rows，sort，order
	 * @param
	 * @return json
	 */
	public function get_sx_report()
	{
		//精确条件
		$game = $this->G('game')?$this->G('game'):'ag';
		$basic = array(
			'sn'   => $this->G('sn'),
			'username' => $this->G('username'),
			'game_type' => $this->G('game_type')?(int)$this->G('game_type'):1,//ag：1真人视讯，2电子 3打鱼，其他没有打鱼。
			'bet_time >=' => strtotime($this->G('start_time'))?$this->G('start_time'):date('Y-m-d'),//报表开始日期
			'bet_time <=' => strtotime($this->G('end_time'))?$this->G('end_time'):date('Y-m-d'),//报表结束日期
		);

		//参数检测
		if (!in_array($game,$this->game_list)){
			$this->return_json(E_ARGS,'参数错误:game');
		}
		if (!in_array($basic['game_type'],array(1,2,3))|| empty($basic['game_type'])){
			$this->return_json(E_ARGS,'参数错误:game_type');
		}
		if ($game == 'dg' || $game=='lebo'){
			unset($basic['game_type']);//dg和lebo没有电子
		}

		//查询时间跨度不能超过两个月
		$start_time = strtotime($basic['bet_time >=']);
		$end_time = strtotime($basic['bet_time <=']);
		$diff_time = $end_time - $start_time;
		if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
			//$basic['bet_time <='] = date('Y-m-d',$start_time+ADMIN_QUERY_TIME_SPAN);
			$this->return_json(E_ARGS,'查询时间跨度不能超过62天！');
		}
		//分页
		$page   = array(
			'page'  => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
			'rows'  => (int)$this->G('rows') > 0 ? (int)$this->G('rows') : 50
		);
		if ($page['rows'] > 500 || $page['rows'] < 1) {
			$page['rows'] = 50;
		}

		//排序
		$order = array();
		//下注金额，下注有效金额，派彩金额，输赢，赢的注单数，总注单数，报表日期，更新时间
		$sort_field = array('id','total_bet', 'total_v_bet', 'payout', 'win_or_lose', 'total_win_count','total_count', 'bet_time', 'update_time');
		$sort = $this->G('sort')?$this->G('sort'):'id';
		if (in_array($sort, $sort_field)) {
			$order = array($sort => $this->G('order')?$this->G('order'):'desc');
		}

		//查询
		if($game=='ag' || $game=='mg' || $game=='pt'){
			$field = 'id,snuid,username,total_bet,total_v_bet,payout,win_or_lose,cal_type,game_type,
			total_win_count,total_count,bet_time,is_fs';
		}else{
			$field = 'id,snuid,username,total_bet,total_v_bet,payout,win_or_lose,cal_type,
			total_win_count,total_count,bet_time,is_fs';
		}
		$select = 'sum(total_bet) as total_bet_sum,sum(total_v_bet) as total_v_bet_sum,sum(payout) as payout_sum,
		sum(win_or_lose) as win_or_lose_sum,sum(total_win_count) as total_win_count_sum,sum(total_count) as total_count_sum';
		$data = $this->sx->all_find($field, $select, $game.'_bet_report', $basic, $order, $page);
		//$data = $this->sx->find_bet_report($game, $basic, $order, $page);
		/**** 格式化小数点 ****/
		// $history['rows'] = stript_float($history['rows']);
		//$data = stript_float($data);
		$this->return_json(OK, $data);
	}


	/**
	 * 获取视讯现金流水
	 * @接收参数：game，sn，username，type，transfer_id，start_time，end_time，page，rows，sort，order
	 * @param
	 * @return json
	 */
	public function get_sx_fund()
	{
		//精确条件
		$game = $this->G('game')?$this->G('game'):'ag';
		$basic = array(
			'sn'   =>$this->get_sn(),
			'gc_username' => $this->G('username'),
			'type' => (int)$this->G('type'),//操作类型1存款2出款
			'transfer_id'=>$this->G('transfer_id'),
			'add_time >=' => strtotime($this->G('start_time'))?strtotime($this->G('start_time')):strtotime(date('Y-m-d')),//操作时间 start
			'add_time <=' => strtotime($this->G('end_time'))?strtotime($this->G('end_time')):strtotime(date('Y-m-d').' 23:59:59'),//操作时间 end
		);

		//参数检测
		if (!in_array($game,array('ag','dg','mg'))){
			$this->return_json(E_ARGS,'参数错误:game');
		}
		if(empty($basic['type'])){
			unset($basic['type']);
		}

		//查询时间跨度不能超过两个月
		$diff_time = $basic['add_time <='] - $basic['add_time >='];
		if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
			$this->return_json(E_ARGS,'查询时间跨度不能超过62天！');
		}

		//分页
		$page   = array(
			'page'  => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
			'rows'  => (int)$this->G('rows') > 0 ? (int)$this->G('rows') : 50
		);
		if ($page['rows'] > 500 || $page['rows'] < 1) {
			$page['rows'] = 50;
		}

		//排序
		$order = array();
		$sort_field = array('id', 'add_time', 'transfer_id', 'amount', 'free_balance');
		$sort = $this->G('sort')?$this->G('sort'):'id';
		if (in_array($sort, $sort_field)) {
			$order = array($sort => $this->G('order')?$this->G('order'):'desc');
		}

		//查询
		$field = 'id,gc_username,type,add_time,transfer_id,amount,free_balance';
		$select = 'sum(amount) as amount_sum,sum(free_balance) as free_balance_sum';
		$data = $this->sx->all_find($field, $select, $game.'_fund', $basic, $order, $page);

		//$data = $this->sx->find_fund($game, $basic, $order, $page);
		$this->return_json(OK, $data);
	}


	/**
	 * 获取视讯用户列表
	 * @接收参数：game，sn，username，type，transfer_id，start_time，end_time，page，rows，sort，order
	 * @param
	 * @return json
	 */
	public function get_sx_user()
	{
		//精确条件
		$game = $this->G('game')?$this->G('game'):'ag';
		$basic = array(
			'sn'   => $this->G('sn'),
			'g_username' => $this->G('username'),
			'status' => (int)$this->G('status'),//1:可用,0:停用
			'actype'=>(int)$this->G('actype'),//1:真钱帐号,0测试帐号
			'oddtype' => (int)$this->G('oddtype'),//限红组
			'createtime >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//操作时间 start
			'createtime <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//操作时间 end
		);

		//参数检测
		if (!in_array($game,$this->game_list)){
			$this->return_json(E_ARGS,'参数错误:game');
		}
		if ($basic['status']!=0 && $basic['status']!=1){
			unset($basic['status']);
		}
		if ($game!='ag' && $game != 'dg'){//有限红组的游戏
			unset($basic['oddtype']);
		}
		if ($game!='ag' && $game != 'mg'){//有区分真钱帐号or测试帐号的游戏
			unset($basic['actype']);
		}
		//查询时间跨度不能超过两个月
		$diff_time = strtotime($basic['createtime <=']) - strtotime($basic['createtime >=']);
		if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
			$this->return_json(E_ARGS,'查询时间跨度不能超过62天！');
		}

		//分页
		$page   = array(
			'page'  => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
			'rows'  => (int)$this->G('rows') > 0 ? (int)$this->G('rows') : 50
		);
		if ($page['rows'] > 500 || $page['rows'] < 1) {
			$page['rows'] = 50;
		}

		//排序
		$order = array();
		$sort_field = array('id', 'balance', 'createtime', 'amount', 'free_balance');
		$sort = $this->G('sort')?$this->G('sort'):'id';
		if (in_array($sort, $sort_field)) {
			$order = array($sort => ($this->G('order')?$this->G('order'):'desc'));
		}
		//查询
		switch ($game)
		{
			case 'ag':$field = 'id,snuid,g_username,balance,currency,createtime,status,oddtype,actype';break;
			case 'dg':$field = 'id,snuid,g_username,balance,currency,createtime,status,oddtype,win_limit';break;
			case 'lebo':$field = 'id,snuid,g_username,balance,currency,createtime,status';break;
			case 'pt':$field = 'id,snuid,g_username,balance,currency,createtime,status';break;
			case 'mg':$field = 'id,snuid,g_username,balance,currency,createtime,status,actype,permission';break;
			default:return false;
		}
		$select = 'sum(balance) as balance_sum';
		$data = $this->sx->all_find($field, $select, $game.'_user', $basic, $order, $page);
		//$data = $this->sx->find_fund($game, $basic, $order, $page);
		$this->return_json(OK, $data);
	}


	/**
	 * 获取视讯出入款(额度转换)记录
	 * @接收参数：game，sn，username，type，transfer_id，start_time，end_time，page，rows，sort，order
	 * @param
	 * @return json
	 */
	public function get_sx_cash_record()
	{
		//精确条件
		$basic = array(
			'sn'   => $this->G('sn'),
			'username' => $this->G('username'),
			'status' => (int)$this->G('status'),//状态，1成功，0失败
			'platform'=> $this->G('platform'),//交易平台
			'cash_type' => (int)$this->G('cash_type'),//是入款还是，出款（ 1是向平台打款，2用户从平台取款到本地）
			'create_time >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//操作时间 start
			'create_time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//操作时间 end
		);

		//参数检测
		if ($basic['status']!=0 && $basic['status']!=1){
			unset($basic['status']);
		}
		//查询时间跨度不能超过两个月
		$diff_time = strtotime($basic['create_time <=']) - strtotime($basic['create_time >=']);
		if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
			$this->return_json(E_ARGS,'查询时间跨度不能超过62天！');
		}

		//分页
		$page   = array(
			'page'  => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
			'rows'  => (int)$this->G('rows') > 0 ? (int)$this->G('rows') : 50
		);
		if ($page['rows'] > 500 || $page['rows'] < 1) {
			$page['rows'] = 50;
		}

		//排序
		$order = array();
		$sort_field = array('id', 'credit', 'create_time', 'update_time');
		$sort = $this->G('sort')?$this->G('sort'):'id';
		if (in_array($sort, $sort_field)) {
			$order = array($sort => ($this->G('order')?$this->G('order'):'desc'));
		}
		//查询
		$field = 'id,trans_id,transaction_id,username,platform,cash_type,status,credit,create_time,update_time,info';
		$select = 'sum(credit) as credit_sum';
		$data = $this->sx->all_find($field, $select, 'cash_record', $basic, $order, $page);
		//$data = $this->sx->find_fund($game, $basic, $order, $page);
		$this->return_json(OK, $data);
	}


	/**
	 * 获取ag视讯注单
	 * @接收参数
	 * @param
	 */
	public function get_ag_order()
	{
		//精确条件
        $username = empty($this->G('username')) ? '' : $this->sort_sn. $this->G('username');
        $this->tiaojiao = array(
			'bill_no'   => $this->G('bill_no'),//注单流水号
			'sn' => $this->sort_sn,
			'username'  => $username,
			'agent_code'=> $this->G('agent_code'),//代理商编号
			'game_code' => $this->G('game_code'),//游戏局号
			'flag'      => (int)$this->G('flag'),//结算状态
			'play_type' => $this->G('play_type'),//游戏玩法
			'currency'  => $this->G('currency'),//货币类型
			'table_code'=> $this->G('table_code'),//桌子编号
			'login_ip' => $this->G('login_ip'),//玩家 IP
			'platform_type' => $this->G('platform_type'),//平台类型
			'round' => $this->G('round'),//平台内大厅类型
			'bet_time >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//操作时间 start
			'bet_time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//操作时间 end
		);
		$this->table = 'ag_game_order';
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');
		$this->sort_field = array('bill_no', 'netamount', 'bet_time', 'bet_amount', 'valid_betamount', 'recalcu_time', 'before_credit');
		$this->field = 'bill_no,data_type,game_type,username,agent_code,game_code,netamount,bet_time,
		bet_amount,valid_betamount,flag,play_type,currency,table_code,login_ip,recalcu_time,
		platform_type,remark,round,result,before_credit,odds,device_type';
		$this->select = 'sum(netamount) as netamount_sum,sum(bet_amount) as bet_amount_sum,
		sum(valid_betamount) as valid_betamount_sum,sum(before_credit) as before_credit_sum';
		$this->get_sx_order('bet_time','bet_time');
	}


	/**
	 * 获取ag视讯捕鱼达人注单
	 * @接收参数
	 * @param
	 */
	public function get_ag_buyu_order()
	{
		//精确条件
		$this->tiaojiao = array(
			'platform_type'   => $this->G('platform_type'),//游戏平台类型
			'sn' => $this->G('sn'),
			'username'  => $this->G('username'),
			'transfer_type'=> $this->G('transfer_type'),//转账类型
			'room_id' => $this->G('room_id'),//游戏房间号
			'flag'      => (int)$this->G('flag'),//结算状态
			'currency'  => $this->G('currency'),//货币类型
			'game_code'=> $this->G('game_code'),//游戏局号
			'creation_time >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//操作时间 start
			'creation_time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//操作时间 end
		);
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');
		$this->table = 'ag_game_orderh';
		$this->sort_field = array('id', 'room_bet', 'cost', 'earn', 'jackpotcomm', 'transfer_amount', 'previous_amount',
			'previous_amount', 'current_amount', 'creation_time', 'update_time');
		$this->field = 'id,trade_no,platform_type,username,scene_starttime,scene_endtime,room_id,room_bet,cost,
		earn,jackpotcomm,transfer_amount,previous_amount,current_amount,currency,exchange_rate,ip,flag,game_code,creation_time';
		$this->select = 'sum(room_bet) as room_bet_sum,sum(cost) as cost_sum,sum(earn) as earn_sum,sum(jackpotcomm) as jackpotcomm_sum,
		sum(transfer_amount) as transfer_amount_sum,sum(previous_amount) as previous_amount_sum,sum(current_amount) as current_amount_sum,';
		$this->get_sx_order('creation_time','id');
	}


	/**
	 * 获取dg视讯注单
	 * @接收参数
	 * @param
	 */
	public function get_dg_order()
	{

		//精确条件
        $username = empty($this->G('username')) ? '' : $this->sort_sn. $this->G('username');
        $suid=$this->sx->find_user(array('g_username'=>$username));
        if($username && !$suid ) {
            $data['total'] = 0;
            $data['sum'] = [];
            $data['rows'] = [];
            $this->return_json(OK, $data);
        }
		$this->tiaojiao = array(
			'sn' => $this->sort_sn,
			'sx_id' => $this->G('sx_id'),//视讯注单唯一Id
			'snuid'=>$suid['snuid'],
			'tableId' => (int)$this->G('tableId'),//游戏桌号
			'shoeId'  => (int)$this->G('shoeId'),//游戏靴号
			'playId'  => (int)$this->G('playId'),//游戏局号
			'lobbyId' => (int)$this->G('lobbyId'),//游戏大厅号 1:旗舰厅, 2:竞咪厅
			'GameType'=> (int)$this->G('GameType'),//游戏类型
			//'result'  => $this->G('result'),//游戏结果
			//'betDetail'=> $this->G('下注注单'),//下注注单
			'ip'      => $this->G('ip'),//ip
			'ext'     => $this->G('ext'),//游戏唯一ID
			'isRevocation'=> (int)$this->G('isRevocation'),//是否结算：0:未结算, 1:已结算, 2:已撤销(该注单为对冲注单)
			'deviceType'  => $this->G('deviceType'),//下注时客户端类型
			'betTime >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//下注时间 start
			'betTime <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//下注时间 end
		);
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');
		$this->table = 'dg_game_order';
		$this->sort_field = array('id', 'betTime', 'calTime', 'winOrLoss', 'balanceBefore', 'betPoints',
			'betPointsz', 'availableBet', 'update_time');
		$this->field = 'id,sn,guid,sx_id,userName,tableId,shoeId,playId,lobbyId,GameType,GameId,memberId,betTime,calTime,
		winOrLoss,balanceBefore,betPoints,betPointsz,availableBet,result,betDetail,ip,ext,isRevocation,deviceType,update_time';
		$this->select = 'sum(winOrLoss) as winOrLoss_sum,sum(balanceBefore) as balanceBefore_sum,sum(betPoints) as betPoints_sum,
		sum(betPointsz) as betPointsz_sum,sum(availableBet) as availableBet_sum';
		$this->get_sx_order('betTime','id');
	}
    /*获取ky视讯注单*/
    public function get_ky_order()
    {
        $username=$this->G('username')?$this->get_sn().$this->G('username'):'';
        //精确条件
        $this->tiaojiao = array(
            'sn' => $this->get_sn(),
            'username'  => $username,
            'table_code' => (int)$this->G('tableId'),//游戏桌号
            'game_code' => $this->G('bill_no'),
            'game_type'=> (int)$this->G('GameType'),//游戏类型
            'login_ip'      => $this->G('ip'),//ip
            'isRevocation'=> (int)$this->G('isRevocation'),
            'device_type'  => $this->G('deviceType'),//下注时客户端类型
            'bet_time >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//下注时间 start
            'bet_time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//下注时间 end
        );
        $this->table = 'ky_game_order';
        $this->moon = $this->G('moon')?$this->G('moon'):date('m');
        $this->sort_field = array('id', 'netamount', 'bet_time', 'bet_amount', 'valid_betamount', 'recalcu_time', 'renevue');
        $this->field = 'snuid,sn,username,agent_code,game_code,bet_amount,valid_betamount,netamount,renevue,bet_time,recalcu_time,game_type,flag,currency,table_code,login_ip,platform_type,remark,card_value,device_type';
        $this->select = 'sum(netamount) as netamount_sum,sum(bet_amount) as bet_amount_sum,
		sum(valid_betamount) as valid_betamount_sum';
        $this->get_sx_order('bet_time','bet_time');
    }
	/**
	 * 获取lebo视讯注单
	 * @接收参数
	 * @param
	 */
	public function get_lebo_order()
	{
		//精确条件
        $username = empty($this->G('username')) ? '' : $this->sort_sn. $this->G('username');

        $this->tiaojiao = array(
			'sn' => $this->get_sn(),
			'round_no' => $this->G('bill_no'),//视讯注单唯一Id
			'user_name'  => $username,
			'start_time >=' => strtotime($this->G('start_time'))?$this->G('start_time'):date('Y-m-d').' 00:00:00',//下注时间 start
			'start_time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//下注时间 end
		);
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');
		$this->table = 'lebo_game_order';
		$this->sort_field = array('game_id', 'start_time', 'total_bet_score', 'valid_bet_score_total');
		$this->field = '*';
		$this->select = 'sum(total_bet_score) as betamount_sum,sum(valid_bet_score_total) as valid_betamount_sum';
		$this->get_sx_order('start_time','game_id');
	}


	/**
	 * 获取pt视讯注单
	 * @接收参数
	 * @param
	 */
	public function get_pt_order()
	{
		//精确条件
        $username = empty($this->G('username')) ? '' : $this->sort_sn. $this->G('username');
        $this->tiaojiao = array(
			'sn' => $this->sort_sn,
			'game_code' => $this->G('game_code'),//订单／注单号
			'username'  => $username,
			'game_type'  => (int)$this->G('game_type'),//表示是哪种游戏
			'game_name'  => (int)$this->G('game_name'),//游戏名字
			'game_date >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//下注时间 start
			'game_date <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//下注时间 end
		);
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');
		$this->table = 'pt_game_order';
		$this->sort_field = array('id', 'game_code', 'bet', 'win', 'balance', 'game_date');
		$this->field = 'id,game_code,username,game_type,game_name,bet,win,balance,game_date,live_network';
		$this->select = 'sum(bet) as bet_sum,sum(win) as win_sum,sum(balance) as balance_sum';
		$this->get_sx_order('game_date','id');
	}


	/**
	 * 获取mg视讯注单
	 * @接收参数
	 * @param
	 */
	public function get_mg_order()
	{
		//精确条件
        $username = empty($this->G('username')) ? '' : $this->sort_sn. $this->G('username');
        $this->tiaojiao = array(
			'sn' => $this->sort_sn,
			'bill_no' => $this->G('bill_no'),//订单／注单号
			'username'  => $username,
			'bet_time >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//下注时间 start
			'bet_time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//下注时间 end
		);
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');
		$this->table = 'mg_game_order';
		$this->sort_field = array('bill_no', 'bet_time', 'bet_amount', 'valid_betamount', 'before_credit');
		$this->field = '*';
		$this->select = 'sum(bet_amount) as bet_amount_sum,sum(valid_betamount) as valid_betamount_sum,sum(before_credit) as before_credit_sum';
		$this->get_sx_order('bet_time','id');
	}

	/**
	 * 获取视讯注单列表
	 * @param $timename  查询条件-时间名
	 * @param $mr_sort   默认排序字段
	 */
	public function get_sx_order($timename,$mr_sort)
	{
		//参数检测
		for ($a=1; $a<=12; $a++) {
			$moomarr[$a] = str_pad($a,2,'0',STR_PAD_LEFT);//生成月份数组
		}
		if(!in_array($this->moon,$moomarr)){
			$this->return_json(E_ARGS,'参数错误：moon');
		}

		//查询时间跨度
		/*$diff_time = strtotime($this->tiaojiao[$timename.' <=']) - strtotime($this->tiaojiao[$timename.' >=']);
		if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
			$this->return_json(E_ARGS,'查询时间跨度不能超过31天！');
		}*/
        $this->moon = date('m', strtotime($this->tiaojiao[$timename.' <=']));
		if (date('m', strtotime($this->tiaojiao[$timename.' <='])) != date('m', strtotime($this->tiaojiao[$timename.' >=']))) {
            $this->return_json(E_ARGS,'请不要跨月查询！');
        }

		//分页
		$page   = array(
			'page'  => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
			'rows'  => (int)$this->G('rows') > 0 ? (int)$this->G('rows') : 50
		);
		if ($page['rows'] > 500 || $page['rows'] < 1) {
			$page['rows'] = 50;
		}

		//排序
		$order = array();
		//注单流水号，玩家输赢额度，投注时间，投注金额，有效投注金额，注单重新派彩时间，玩家下注前的剩余额度
		$order['orderby'] = array($mr_sort => 'desc');
		//查询
		$data = $this->sx->all_find($this->field, $this->select, $this->table.$this->moon, $this->tiaojiao, $order, $page);
		//$data = $this->sx->find_fund($game, $basic, $order, $page);
        //var_dump($data);exit();
		$this->return_json(OK, $data);
	}


	/**
	 * 获取视讯注单详情
	 * @param $timename  查询条件-时间名
	 * @param $mr_sort   默认排序字段
	 */
	public function get_order_detali()
	{
		$order_num  = $this->G('order_num');
		$this->moon = $this->G('moon')?$this->G('moon'):date('m');

	}
//	/*后台获取单个会员的各个额度*/
//	public function get_user_sx_credit()
//    {
//        $uid=$this->G('uid');
//        //系统额度
//        $userBalance = $this->M->get_one('balance', 'user', array('id' =>$uid));
//        $rs['user'] = isset($userBalance['balance']) ? $userBalance['balance'] : 0;
//        //获取站点设置
//        $set = $this->M->get_gcset();
//        $set = explode(',', $set['cp']);
//        if(in_array(1001, $set)){
//            $agUser=$this->sx->find_plat_user(array('snuid'=>$uid),'ag');
//            if(!empty($agUser)){
//                $agBalance=$agUser['balance'];
//            }else{
//                $agBalance=0;
//            }
//            $rs['sx']['ag'] = $agBalance;
//        }
//        if(in_array(1002, $set)){
//            $dgUser=$this->sx->find_plat_user(array('snuid'=>$uid),'dg');
//            if(!empty($dgUser)){
//                $dgBalance=$dgUser['balance'];
//            }else{
//                $dgBalance=0;
//            }
//            $rs['sx']['dg'] = $dgBalance;
//        }
//        if(in_array(1003, $set)){
//
//        }
//        if(in_array(1006, $set)){
//            $kyUser=$this->sx->find_plat_user(array('snuid'=>$uid),'ky');
//            if(!empty($kyUser)){
//                $kyBalance=$kyUser['balance'];
//            }else{
//                $kyBalance=0;
//            }
//            $rs['sx']['ky'] = $kyBalance;
//        }
//        $this->return_json(OK,$rs);
//    }
    /**获取视讯额度流水**/
    public function get_sx_credit()
    {
        $this->tiaojiao=array(
            'time >=' => strtotime($this->G('start_time'))?$this->G('start_time').' 00:00:00':date('Y-m-d').' 00:00:00',//时间 start
            'time <=' => strtotime($this->G('end_time'))?$this->G('end_time').' 23:59:59':date('Y-m-d').' 23:59:59',//时间 end
            'platform'=>$this->G('platform'),
            'sn'=>$this->get_sn()
        );
        $this->table = 'credit_record';
        $this->field = '*';
        //分页
        $page   = array(
            'page'  => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
            'rows'  => (int)$this->G('rows') > 0 ? (int)$this->G('rows') : 50
        );
        if ($page['rows'] > 500 || $page['rows'] < 1) {
            $page['rows'] = 50;
        }
        $order=array(
            'time'=>'desc'
        );
        $data = $this->sx->all_find($this->field, $this->select, $this->table, $this->tiaojiao, $order, $page);
        $this->return_json(OK, $data);
    }

    /**
     * 后台获取单个会员的各个额度
     */
    public function get_user_sx_credit()
    {
        $uid  = (int)$this->G('uid') > 0 ? (int)$this->G('uid') : 0;
        if($uid == 0) {
            $user  = $this->G('username') ? $this->G('username') : '';
            //系统额度
            $userInfo = $this->M->get_one('balance,username,id', 'user', array('username' => $user));
            if(!$userInfo['id']){
                return false;
            }else{
                $uid=$userInfo['id'];
            }
        }
        $rs = [
            'sx' => [],
            'user' => 0,
            'platform' => 0
        ];

        //系统额度
        $userBalance = $this->M->get_one('balance,username', 'user', array('id' => $uid));
        $rs['uid'] = $uid;
        $rs['user'] = isset($userBalance['balance']) ? $userBalance['balance'] : 0;
        $username = isset($userBalance['username']) ? $userBalance['username'] : '';
        if($username == '') {
            return false;
        }
        $username = $this->sort_sn.$username;
        //获取站点设置
        $set = $this->M->get_gcset();
        $set = explode(',', $set['cp']);
        //ag额度
        if (in_array(1001, $set)) {
            $agUser = $this->sx->user_info($username, 'balance,actype,g_password', 'ag');
            if (!empty($agUser)) {
                $this->ag_api = BaseApi::getinstance('ag', 'user', $this->sort_sn);
                $agBalance = $this->ag_api->get_balance($username, $agUser['actype'], $agUser['g_password']);
            } else {
                $agBalance = 0;
            }
            $rs['sx']['ag'] = isset($agBalance['info']) ? $agBalance['info'] : 0;
        }
        //dg额度
        if (in_array(1002, $set)) {
            $dgUser = $this->sx->user_info($username, 'balance', 'dg');
            if (!empty($dgUser)) {
                $this->dg_api = BaseApi::getinstance('dg', 'dgUser', $this->sort_sn);
                $dgBalance = $this->dg_api->updateBalance($username, 'dg');
            } else {
                $dgBalance = 0;
            }
            $rs['sx']['dg'] = isset($dgBalance['member']['balance']) ? $dgBalance['member']['balance'] : 0;
        }
        //lebo额度
        if (in_array(1003, $set)) {
            $lbUser = $this->sx->user_info($username, 'balance', 'lebo');
            $rs['sx']['lebo'] = isset($lbUser['balance']) ? $lbUser['balance'] : 0;
        }
        //pt额度
        if (in_array(1004, $set)) {
            $ptUser = $this->sx->user_info($username, 'balance', 'pt');
            $rs['sx']['pt'] = isset($ptUser['balance']) ? $ptUser['balance'] : 0;
        }
        if (in_array(1006, $set)){
            $this->load->library('ky/KyuserApi','','KyUser');
            $data = [];
            $data['s'] = 1;
            $data['account'] = $username;
            $res = $this->KyUser->get_api_data($data,1,$this->sort_sn);
            if(isset($res['d']['code'])&&$res['d']['code']==0){
                /*同步ky_user信息*/
                //$data = $this->update_balance($username,$rs['d']['money'],'ky');
                $this->sx->update_balance($username, $res['d']['money'], 'ky');
                $rs['sx']['ky'] =$res['d']['money']?$res['d']['money']:0;
            }else{
                $kyUser = $this->sx->user_info($username, 'balance', 'ky');
                $rs['sx']['ky'] = isset($kyUser['balance']) ? $kyUser['balance'] : 0;
            }
            //$kyUser = $this->dg_user->user_info($username, 'balance', 'ky');
            //$rs['sx']['ky'] = isset($kyUser['balance']) ? $kyUser['balance'] : 0;
        }
        //mg额度
        if (in_array(1005, $set)) {
            $rs['sx']['mg'] = 0;
        }
        if (!empty($rs['sx'])) {
            foreach ($rs['sx'] as $k => $v) {
                $rs['platform'] = isset($rs['platform']) ? $rs['platform'] + $v : $v;
            }
            $rs['platform']=sprintf("%.2f", $rs['platform']);
        }
        $this->return_json(OK, $rs);
    }

    /*************额度转换************/
}
