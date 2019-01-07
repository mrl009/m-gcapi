<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * User_info模块
 *
 * @author      ssm
 * @package     models
 * @version     v1.0 2017/11/3
 */
class User_info_model extends MY_Model
{
	/**
	 * 对应表
	 */
	private $table = 'user';


	/**
	 * 今日盈亏
	 *
	 * API
	 * @access public
	 * @param $id 用户ID
	 * @return Array
	 */
	public function profit($id)
	{
		// 1
		$where = ['uid'=>$id, 'report_date'=>date('Y-m-d')];
		$cash_report = $this->get_cash_report($where);
		$where = ['uid'=>$id, 'report_date'=>date('Y-m-d')];
		$bet_report = $this->get_bet_report($where);
		$where = ['uid'=>$id, 'report_date'=>date('Y-m-d')];
		$rate_report = $this->get_rate_report($where);

		// 下注金额
		$valid_price = $bet_report['valid_price'];

		// 中奖金额
		$lucky_price = $bet_report['lucky_price'];
		
		// 返点金额
		$return_price = $bet_report['return_price'];

		// 活动金额
		$activi_price = $cash_report['activity_total'] + 
						$cash_report['in_people_discount'] + 
						$cash_report['in_register_discount'] + 
						$cash_report['in_company_discount'] + 
						$cash_report['in_online_discount'] + 
						$cash_report['in_card_total'];

		// 提现金额
		$outflow_price = $cash_report['out_people_total'] + 
						$cash_report['out_company_total'];

		// 充值金额
		$inpour_price = $cash_report['in_company_total'] + 
						$cash_report['in_online_total'] + 
						$cash_report['in_people_total'];

		// 今日盈亏
		$profit = $lucky_price + $activi_price + 
				  $return_price + $rate_report['rate_price']
				  - $valid_price;

		return stript_float(array(
			'valid_price' => $valid_price,
			'lucky_price' => $lucky_price,
			'return_price' => $return_price,
			'activi_price' => $activi_price,
			'inpour_price' => $inpour_price,
			'outflow_price' => $outflow_price,
			'profit' => round($profit, 2)
		));
	}

	/**
	 * 个人信息
	 *
	 * API
	 * @access public
	 * @param $id 用户ID
	 * @return Array
	 */
	public function info($id)
	{
		$where = ['uid' => $id];
		$user_detail = $this->get_one('birthday, img, phone, nickname, sex, email, modify', 'user_detail', $where);
        $user_detail['birthday'] = $user_detail['birthday'] . '000';
		$where = ['id' => $id];
		$user = $this->get_one('username', $this->table, $where);
		return array_merge($user_detail, $user);
	}


    /**
     * 修改个人信息
     *
     * API
     * @access public
     * @param $id 用户ID
     * @return Array
     */
    public function update_info($data)
    {
        $where = ['uid' => $data['uid']];
        unset($data['uid']);
        $a = $this->write('user_detail', $data, $where);
        return  $a;
    }


    /**
	 * 个人中奖信息
	 *
	 * API
	 * @access public
	 * @param $id 用户ID
	 * @return Array
	 */
	public function win_info($id, $data)
	{
		$where = ['uid' => $id];
		$user_detail = $this->get_one('sex, nickname, img', 'user_detail', $where);
		$where = ['id' => $id];
		$user = $this->get_one('username', $this->table, $where);
		if(empty($user['username'])){
            return array();
        }

		$username = $user['username'];
		$user['username'] = $username{0}.$username{1}.
                                    str_repeat('*', strlen($username)-3).
                                    $username{strlen($username)-1};
        $user_detail['sex'] = $user_detail['sex'] == 1 ? '男' : '女';

		$nobility = $this->nobility($id);

		$where = ['uid'=>$id, 'report_date <='=>$data['front'],'report_date >='=>$data['back']];
		$bet_report = $this->get_bet_report($where);
		$game_info = $this->get_game_info($where);

		return array_merge($user_detail, $user, 
							['lucky_price' => $bet_report['lucky_price']],
							['VipID'=>$nobility['VipID'], 'VipName'=>$nobility['VipName']],
							['game_list' => $game_info]);
	}

	/**
	 * 等级头衔
	 *
	 * API
	 * @access public
	 * @param $id 用户ID
	 * @return Array
	 */
	public function nobility($id)
	{
		$where['user.id'] = $id;
        $condition['join'] = array(array('table'=>'user_detail as b','on'=>'user.id = b.uid'));
		$user = $this->get_one('username, integral, vip_id,b.img', $this->table, $where,$condition);
		$grades = $this->get_list('*', 'grade_mechanism');
		$grades = array_make_key($grades, 'id');
		$next = array_key_exists($user['vip_id']+1, $grades)
				? $grades[$user['vip_id']+1] : end($grades);

		$vip_id = $user['vip_id'] != 0 ? $user['vip_id'] : 1;

		$data =  [
			'username' => $user['username'],
			'integral' => $user['integral'],
			'VipID'    => 'VIP'.$vip_id,
			'VipName'  => $grades[$vip_id]['title'],
			'NextVipIP' => 'VIP'.$next['id'],
			'NextVipName' => 'VIP'.$next['title'],
			'NextVipIntegral' => $next['integral'],
            'img' => $user['img'],
            'juli' => (string)($next['integral']-$user['integral']),
            'Vip_list'=>$grades
		];
        return $data;
	}

	/**
	 * 获取现金报表
	 *
	 * @access public
	 * @param Array $where ['uid'=>用户ID, 'report_date'=>日期]
	 * @return Array
	 */
	private function get_cash_report($where)
	{
		$field = 'sum(a.in_company_total) as in_company_total,
		         sum(a.in_company_discount) as in_company_discount, 
		         sum(a.in_online_total) as in_online_total,
		         sum(a.in_online_discount) as in_online_discount, 
		         sum(a.in_people_total) as in_people_total,
		         sum(a.in_people_discount) as in_people_discount, 
		         sum(a.in_card_total) as in_card_total,
		         sum(a.in_member_out_deduction) as in_member_out_deduction, 
		         sum(a.out_people_total) as out_people_total,
		         sum(a.out_company_total) as out_company_total, 
		         sum(a.out_return_water) as out_return_water,
		         sum(a.in_register_discount) as in_register_discount, 
		         sum(a.activity_total) as activity_total';
		$data = $this->get_list($field, 'cash_report', $where);
		return array_map(function($val) {
			return is_null($val) ? 0 : $val;
		}, $data[0]);
	}

	/**
	 * 获取下注报表
	 *
	 * @access private
	 * @param Array $where ['uid'=>用户ID, 'report_date'=>日期]
	 * @return Array
	 */
	private function get_bet_report($where)
	{
		$field = 'sum(a.price) as price,
		         sum(a.valid_price) as valid_price, 
		         sum(a.lucky_price) as lucky_price,
		         sum(a.return_price) as return_price';
		$data = $this->get_list($field, 'report', $where);
		return array_map(function($val) {
			return is_null($val) ? 0 : $val;
		}, $data[0]);
	}

	/**
	 * 获取下注报表
	 *
	 * @access private
	 * @param Array $where ['uid'=>用户ID, 'report_date'=>日期]
	 * @return Array
	 */
	private function get_game_info($where)
	{

		$where2['group'] = ['gid'];
		$data = $this->get_list('gid', 'report', $where);
        if( empty($data) ) {
            return array();
        }
		$data = array_slice($data,0,8);

        $senior['wherein'] = array('id'=>array_column($data,'gid'));
        return $this->get_img_name($senior);
        /*$this->load->model('Games_model');
        return array_map(function($val) {
            return $this->Games_model->sname($val['gid'], 0, true);
        }, $data);*/
	}

    /**
     * 获取游戏名和图片
     *
     * @access private
     * @param Array
     * @return Array
     */
    private function get_img_name($senior){
        $this->select_db('public');
        $data = $this->get_list('img as game_img,name,id as gid', 'games', array(),$senior);
        $this->select_db('private');
        return $data;
    }
	
	/**
	 * 获取返佣报表
	 *
	 * @access private
	 * @param Array $where ['uid'=>用户ID, 'report_date'=>日期]
	 * @return Array
	 */
	private function get_rate_report($where)
	{
		$where['status'] = 1;
		$where['agent_id'] = $where['uid']; 
		$where['uid'] = null;
		$field = 'sum(a.rate_price) as rate_price';
		$data = $this->get_list($field, 'agent_report', $where);
		return array_map(function($val) {
			return is_null($val) ? 0 : $val;
		}, $data[0]);
	}
}
