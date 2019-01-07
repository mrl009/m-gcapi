<?php
/**
 * @模块   报表model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Report_model extends MY_Model 
{

	public function __construct()
    {
        parent::__construct();
        $this->select_db('public');
    }




    /******************公共方法*******************/
    /**
	 * 会员分析／下注分析
	 * 获取列表数据 
	 */
	public function report_list($basic, $senior, $page)
	{
		$this->select_db('private');
		/* 获取汇总数footer */
		$select = 'sum(a.price) as total_price, 
				   sum(a.valid_price) as valid_price,
				   sum(a.lucky_price) as lucky_price,
				   sum(bets_num) as total_num';
		$footer = $this->get_list($select, 'report', 
							$basic, $senior);

		/* 获取数据rows */
		$senior['groupby'] = array('a.uid');
		$select .= ',sum(num_win) as total_win_num,
					a.uid as user_id,
					FROM_UNIXTIME(b.addtime) as addtime,
					b.agent_id as agent_id';
		$result = $this->get_list($select, 'report', 
							$basic, $senior, $page);

		$result['rows'] = $this->_id_to_name($result['rows']);
		$result['footer'] = $footer[0];
		return $result;
	}

    /**
     * 报表 获取列表数据
     * @param int $type 1:会员报表2:彩票报表
     * @param array $basic 查询条件1
     * @param array $page 查询条件2
     * @param array $order 排序
     * @return array|mixed
     */
	public function form_list_bak($type = 2, $basic, $page, $order)
	{
        $senior = array();
        $this->select_db('private');
		if($type != 1) {
            // 彩票报表
            $table = 'bet_settlement';
            $select = 'sum(price) as total_price, 
                sum(valid_price) as valid_price,
                sum(win_price) as lucky_price,
                sum(counts) as total_num,
                sum(return_price) as return_price,
                sum(bets_counts) as bets_num';
            $basic = [
                'created >=' => strtotime($basic['a.report_date >=']. ' 00:00:00'),
                'created <=' => strtotime($basic['a.report_date <=']. ' 23:59:59'),
            ];
            $footer = $this->get_list($select, $table, $basic, $senior);
            $senior['groupby'] = array('gid');
            $select .= ',gid';
            if (array_key_exists('diff_price', $order)) {
                $select .= ',(valid_price-win_price-return_price) as diff_price';
            }
		} else {
		    // 会员报表
            $table = 'report';
            if(strpos($basic['b.username ='], ',')) {
                $senior['wherein'] = array('username'=>explode(',', $basic['b.username =']));
                unset($basic['b.username =']);
            }
            $senior['join'] = 'gc_user';
            $senior['on'] = 'b.id=a.uid';
            $select = 'sum(a.price) as total_price, 
                sum(a.valid_price) as valid_price,,
                sum(a.lucky_price) as lucky_price,
                sum(num) as total_num,
                sum(return_price) as return_price,
                sum(a.bets_num) as bets_num';
			$footer = $this->get_list($select, $table, $basic, $senior);
			$senior['groupby'] = array('a.uid');
			$select .= ',b.username as name,a.gid,a.uid';
            if (array_key_exists('diff_price', $order)) {
                $select .= ',(valid_price-lucky_price-return_price) as diff_price';
            }
		}
		$senior['orderby'] = $order;
		$data = $this->get_list($select, $table, $basic, $senior, $page);
        foreach ($footer[0] as $key => $value) {
            $footer[0][$key] = floatval($value);
        }
		$data['footer'] = $footer[0];

		// 其他的一些字段
		if($type != 1) {
			$data['rows'] =  $this->gid_to_name($data['rows']);
		}
		foreach ($data['rows'] as $k => $v) {
		    if ($type != 1 && $v['total_price'] <= 0) {
		        unset($data['rows'][$k]);
		        continue;
            }
			$data['rows'][$k]['diff_price'] = sprintf("%.3f",floatval($v['lucky_price'] - $v['valid_price']+$v['return_price']));
			$data['rows'][$k]['cor_valid_price'] = $v['valid_price'];
			$data['rows'][$k]['cor_lucky_price'] = $v['lucky_price'];
			$data['rows'][$k]['cor_return_price'] = 0-$v['return_price'];
			$data['rows'][$k]['cor_diff_price'] = sprintf("%.3f",floatval($v['valid_price'] - $v['lucky_price']+$data['rows'][$k]['cor_return_price']));
		}
		$data['footer']['diff_price'] = sprintf("%.3f",floatval($data['footer']['lucky_price'] - $data['footer']['valid_price']+$data['footer']['return_price']));
		$data['footer']['cor_valid_price'] = $data['footer']['valid_price'];
		$data['footer']['cor_lucky_price'] = $data['footer']['lucky_price'];
		$data['footer']['cor_return_price'] = 0-$data['footer']['return_price'];
		$data['footer']['cor_diff_price']  = sprintf("%.3f",floatval($data['footer']['valid_price'] - $data['footer']['lucky_price']+$data['footer']['cor_return_price']));
		if ($type == 2) {
            $data['rows'] = array_values($this->format_name($data['rows']));
            $data['total'] = count($data['rows']);
        }
		return $data;
	}

    /**
     * 报表,report表统计方法
     */
    public function form_list($type = 2, $basic, $page, $order)
    {
        // 获取级别对应的用户id
        $senior = array();
        if(strpos($basic['b.username ='], ',')) {
            $senior['wherein'] = array('username'=>explode(',', $basic['b.username =']));
            unset($basic['b.username =']);
        }
        $senior['join'] = 'gc_user';
        $senior['on'] = 'b.id=a.uid';

        $this->select_db('private');
        $select = 'sum(a.price) as total_price, 
						sum(a.valid_price) as valid_price,,
						sum(a.lucky_price) as lucky_price,
						sum(num) as total_num,
						sum(return_price) as return_price,
						sum(a.bets_num) as bets_num';
        // 分类查询
        if($type != 1) {
            // 获取汇总数据
            $footer = $this->get_list($select,
                'report', $basic, $senior);

            // 数据列表条件
            $senior['groupby'] = array('a.gid');
            $select .= ',count(a.uid) as total_users';
            /* 交收查询 */
        } else {
            /* 获取汇总数据 */
            $footer = $this->get_list($select,
                'report', $basic, $senior);
            /* 获取数据行数 */
            $senior['groupby'] = array('a.uid');
            $select .= ',b.username as name';
        }
        $select .= ',a.gid,a.uid';
        if (array_key_exists('diff_price', $order)) {
            //$select .= ',(valid_price-lucky_price-return_price) as diff_price';
            $select .= ',(SUM(lucky_price)-SUM(valid_price)) as diff_price';
        }
        $senior['orderby'] = $order;
        $data = $this->get_list($select, 'report',
            $basic, $senior, $page);
        foreach ($footer[0] as $key => $value) {
            $footer[0][$key] = floatval($value);
        }
        $data['footer'] = $footer[0];

        // 其他的一些字段
        if($type != 1) {
            $data['rows'] =  $this->gid_to_name($data['rows']);
        }
        foreach ($data['rows'] as $k => $v) {
            if (array_key_exists('diff_price', $order)) {
                $data['rows'][$k]['diff_price'] = $v['diff_price'];
                $data['rows'][$k]['cor_diff_price'] = sprintf("%.3f",(0-$v['diff_price']));
            }
            $data['rows'][$k]['diff_price'] = sprintf("%.3f",floatval($v['lucky_price'] - $v['valid_price']));
            $data['rows'][$k]['cor_valid_price'] = $v['valid_price'];
            $data['rows'][$k]['cor_lucky_price'] = $v['lucky_price'];
            $data['rows'][$k]['cor_return_price'] = 0-$v['return_price'];
            $data['rows'][$k]['cor_diff_price'] = sprintf("%.3f",floatval($v['valid_price'] - $v['lucky_price']));
        }
        $data['footer']['diff_price'] = sprintf("%.3f",floatval($data['footer']['lucky_price'] - $data['footer']['valid_price']));
        $data['footer']['cor_valid_price'] = $data['footer']['valid_price'];
        $data['footer']['cor_lucky_price'] = $data['footer']['lucky_price'];
        $data['footer']['cor_return_price'] = 0-$data['footer']['return_price'];
        $data['footer']['cor_diff_price']  = sprintf("%.3f",floatval($data['footer']['valid_price'] - $data['footer']['lucky_price']));
        $type == 2 && $data['rows'] = $this->format_name($data['rows']);
        return $data;
    }

	/**
     * 对操作进行记录
     */
    public function add_log($content)
    {
        $this->select_db('private');
        $this->load->model('log/Log_model');
        $data['content'] = $content;
        $this->Log_model->record($this->admin['id'], $data);
    }

	/**
	 * 报表
	 * 获取今天的赢亏
	 */
	public function date_result_price($startdate, $enddate)
	{
		$this->select_db('private');
		//$select = 'sum(valid_price) - sum(lucky_price) - sum(return_price) as diff_price';
//		$select = 'sum(valid_price) - sum(lucky_price) as diff_price';
//		$where = array(
//			'report_date >=' => $startdate,
//			'report_date <=' => $enddate
//			);
//        return $this->get_one($select, 'report', $where);
        // 除去 试玩用户的输赢 为实际站点的输赢金额
        $sql = "SELECT sum(valid_price) - sum(lucky_price) as diff_price FROM `gc_report` as a inner JOIN `gc_user` as `b` ON a.`uid`=`b`.`id` and `b`.`status`<4 WHERE a.`report_date` >= '{$startdate}' AND a.`report_date` <= '{$enddate}' LIMIT 1";
        return $this->db->query($sql)->row_array();

		//@modify mrl 2018-3-20 改用bet_settlement数据获取报表
        /*$this->select_db('private');
        $select = 'sum(valid_price) - sum(win_price) - sum(return_price) as diff_price';
        $where = array(
            'created >=' => strtotime($startdate. ' 00:00:00'),
            'created <=' => strtotime($enddate. ' 23:59:59')
        );
        return $this->get_one($select, 'bet_settlement', $where);*/
	}

	/**
	 * 报表
	 * 获取某个游戏的赢亏
	 */
	public function game_result_price($date)
	{
		$this->select_db('private');
		$select = 'sum(valid_price) - sum(lucky_price) - sum(return_price) as diff_price,gid';
		$where = array(
			'report_date >=' => $date,
			'report_date <=' => $date
			);
		$condition = array(
			'groupby' => array('gid'),
			'orderby' => array('diff_price'=>'desc'));
		$result = $this->get_list($select, 'report', $where, $condition);
		$result = $this->gid_to_name($result);
		$result = $this->format_name($result);
		return $result;
	}

	/**
	 * 报表
	 * 获取结算表最小日期的数据
	 */
	public function min_report_date()
	{
		$this->select_db('private');
		$select = 'min(report_date) as min';
		return $this->get_one($select, 'report');
	}

    public function total_report($start, $end)
    {
        // 报表信息
        $this->select_db('private');
        $report = $this->db->select('report_date,sum(valid_price) as valid_price,sum(lucky_price) as lucky_price,count(DISTINCT uid) as bet_num')
            ->where('report_date >=', $start)
            ->where('report_date <=', $end)
            ->from('report')
            ->group_by('report_date')
            ->get()
            ->result_array();
        if (empty($report)) {
            return [
                'rows' => [],
                'footer' => [
                    [
                        'report_date' => '',
                        'valid_price' => 0.000,
                        'lucky_price' => 0.000,
                        'bet_num' => 0,
                        'is_first' => 0,
                        'win' => 0.000,
                        'fee' => 0.000,
                        'wh_fee' => 0.000,
                        'all_fee' => 0.000,
                    ]
                ]
            ];
        }
        $cash_report = $this->db->select('report_date,count(uid) as is_first')
            ->where('report_date >=', $start)
            ->where('report_date <=', $end)
            ->where('is_one_pay', 1)
            ->from('cash_report')
            ->group_by('report_date')
            ->get()
            ->result_array();
        !empty($cash_report) && $cash_report = array_make_key($cash_report, 'report_date');
        $rs['rows'] = $this->format_total_report($report, $cash_report);
        $rs['footer'][] = $this->format_total_report_footer($rs['rows']);
        return $rs;
    }



	/*********************私有方法**********************/
	/**
	 * 报表
	 * 获取级别的用户ID集
	 */
	private function level_userids($id) 
	{
		$this->select_db('private');
		$where['a.id ='] = $id;
		$condition = array('join' => 'gc_user','on' => 'b.level_id=a.id');
		$userids = $this->get_list('b.id', 'level', $where, $condition);
		foreach ($userids as $k => $v) {
			$userids[$k] = (int)$v['id'];
		}
		return $userids;
	}

	/**
	 * 报表
	 * 将gid转换为游戏名字
	 */
	private function gid_to_name($result)
	{
		$games = $this->data_list('id, name', 'games', 'public');
		foreach ($result as $k => $v) {
			foreach ($games as $kk => $vv) {
				if($v['gid'] == $vv['id']) {
					$result[$k]['name'] = $vv['name'];
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * 报表
	 * 获取某个表的全部数据
	 */
	private function data_list($select, $table, $db = 'private') 
	{
		$this->select_db($db);
		$res = $this->db->select($select)->select_all($table);
		return $res;
	}


	/**
     * 将id等转换为名称
     *
     * @access private
     * @param Array $data   数据数组
     * @return $data        转换后结果
     */
    private function _id_to_name($data) {
        if(empty($data)) return $data;

        // 初始化0的值
        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($data as $k => $v) {
            $user_id = $v['user_id'];
            $agent_id = $v['agent_id'];

            if(empty($cache['user_id'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['user_id'][$user_id] = $user;
            }

            if(empty($cache['user_id'][$agent_id])) {
                $agent = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $agent;
            }

            $v['username'] = $cache['user_id'][$user_id]['username'];
            $v['agent_name'] = $cache['user_id'][$agent_id]['username'];
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * 报表输赢抽水计算中心
     *
     * @access public
     * @param Float $money 报表金额
     * @param Integer $type 计算类型
     * @return Integer|Falst
     */
    public function pumping($money)
	{
		if ($money<=6000000) {
			$divi = $this->_pumping1($money);
		} else {
			$divi = $this->_pumping2($money);
		}
		return $divi;
	}

    /**
     * 易彩彩票账单新算法
     * @param $money
     * @return float
     */
    public function new_pumping($money)
    {
        //初始化
        $level1 = 2000000;
        $level2 = 4000000;
        $level3 = 6000000;
        $level4 = 10000000;
        $rate1 = 0.07;
        $rate2 = 0.06;
        $rate3 = 0.05;
        $rate4 = 0.04;
        $rate5 = 0.03;
        $m1 = $this->pumping_format($money, 0, $level1);
        $m2 = $this->pumping_format($money, $level1, $level2);
        $m3 = $this->pumping_format($money, $level2, $level3);
        $m4 = $this->pumping_format($money, $level3, $level4);
        $m5 = $money > $level4 ? $money - $level4 : 0;
        return $m1 * $rate1 + $m2 * $rate2 + $m3 * $rate3 + $m4 * $rate4 + $m5 * $rate5;
    }

    /**
     * 根据金额和其实结束金额算出结算金额
     * @param float $money
     * @param float $from
     * @param float $to
     * @return float
     */
    private function pumping_format($money, $from = 0.00, $to = 10000000.00)
    {
        if ($money < $from || $money < 0) {
            return 0;
        }
        return $money > $to ? $to - $from : $money - $from;
    }

    /**
     * 游戏名添加私彩国彩标准
     * @param $data
     * @return array
     */
    private function format_name($data)
    {
        $gc = explode(',', GC);
        $sc = explode(',', SC);
        foreach ($data as $k => $v) {
            if (in_array($v['gid'], $gc) && $v['gid'] != '3' && $v['gid'] != 4) {
                $data[$k]['name'] = $data[$k]['name']. '[官]';
            } else if (in_array($v['gid'], $sc)) {
                $data[$k]['name'] = $data[$k]['name']. '[私]';
            }
        }
        return $data;
    }

	/**
	 * 分层算法一
	 *
     * @access private
     * @param Float $money 报表金额
     * @return Integer
	 */
	private function _pumping1($money)
	{
		// 1
		if ($money > 1000000) {
			$money -= 1000000;
			$m = 1000000;
		} else {
			$m = $money;
			$money = 0;
		}
		$first = $m*6/100;
		$m = 0;
		// 2
		if ($money > 2000000) {
			$money -= 2000000;
			$m = 2000000;
		} else {
			$m = $money;
			$money = 0;
		}
		$second = $m*5/100;
		$m = 0;
		// 3
		$m = $money;
		$third = $m*4.5/100;
		$m = 0;

		return $first + $second + $third;
	}

	/**
	 * 分层算法二
	 *
     * @access private
     * @param Float $money 报表金额
     * @return Integer
	 */
	private function _pumping2($money)
	{
		// 1
		if ($money > 1000000) {
			$money -= 1000000;
			$m = 1000000;
		} else {
			$m = $money;
			$money = 0;
		}
		$first = $m*6/100;
		$m = 0;
		// 2
		if ($money > 2000000) {
			$money -= 2000000;
			$m = 2000000;
		} else {
			$m = $money;
			$money = 0;
		}
		$second = $m*5/100;
		$m = 0;
		// 3
		if ($money > 3000000) {
			$money -= 3000000;
			$m = 3000000;
		} else {
			$m = $money;
			$money = 0;
		}
		$third = $m*4.5/100;
		$m = 0;
		// 4
		$m = $money;
		$fourth = $m*4/100;
		$m = 0;

		return $first + $second + $third + $fourth;
	}

    private function format_total_report($report, $cash_report)
    {
        $rs = [];
        foreach ($report as $v) {
            $win = sprintf('%.3f', $v['valid_price'] - $v['lucky_price']);
            $rs[] = array(
                'report_date' => $v['report_date'],
                'valid_price' => $v['valid_price'],
                'lucky_price' => $v['lucky_price'],
                'bet_num' => $v['bet_num'],
                'is_first' => isset($cash_report[$v['report_date']]) ? $cash_report[$v['report_date']]['is_first'] : 0,
                'win' => $win,
                'fee' => '-',
                'wh_fee' => '-',
                'all_fee' => '-',
            );
        }
        return $rs;
    }

    private function format_total_report_footer($rs)
    {
        $set = $this->get_gcset(['site_fee', 'site_rate']);
        $win = sprintf('%.3f', array_sum(array_column($rs, 'win')));
        $fee = $this->get_fee($win, json_decode($set['site_rate'], true));
        $wh_fee = isset($set['site_fee']) ? $set['site_fee'] : 10000;
        return [
            'report_date' => '',
            'valid_price' => sprintf('%.3f', array_sum(array_column($rs, 'valid_price'))),
            'lucky_price' => sprintf('%.3f', array_sum(array_column($rs, 'lucky_price'))),
            'bet_num' => (int)array_sum(array_column($rs, 'bet_num')),
            'is_first' => (int)array_sum(array_column($rs, 'is_first')),
            'win' => $win,
            'fee' => sprintf('%.3f', $fee),
            'wh_fee' => sprintf('%.3f', $wh_fee),
            'all_fee' => sprintf('%.3f', $fee + $wh_fee),
        ];
    }

    private function get_fee($win, $site_rate)
    {
        //初始化
        $level = isset($site_rate['level']) ? explode(',', $site_rate['level']) : [];
        $rate = isset($site_rate['rate']) ? explode(',', $site_rate['rate']) : [];
        $level1 = isset($level[0]) ? $level[0] : 0;
        $level2 = isset($level[1]) ? $level[1] : 1000000;
        $level3 = isset($level[2]) ? $level[2] : 3000000;
        $level4 = isset($level[3]) ? $level[3] : 6000000;
        $level5 = isset($level[4]) ? $level[4] : 10000000;
        $rate1 = isset($rate[0]) ? $rate[0] / 100 : 0.06;
        $rate2 = isset($rate[1]) ? $rate[1] / 100 : 0.05;
        $rate3 = isset($rate[2]) ? $rate[2] / 100 : 0.045;
        $rate4 = isset($rate[3]) ? $rate[3] / 100 : 0.04;
        $rate5 = isset($rate[4]) ? $rate[4] / 100 : 0.04;
        $m1 = $this->pumping_format($win, $level1, $level2);
        $m2 = $this->pumping_format($win, $level2, $level3);
        $m3 = $this->pumping_format($win, $level3, $level4);
        $m4 = $this->pumping_format($win, $level4, $level5);
        $m5 = $win > $level5 ? $win - $level5 : 0;
        return $m1 * $rate1 + $m2 * $rate2 + $m3 * $rate3 + $m4 * $rate4 + $m5 * $rate5;
    }
	/********************************************/
}
