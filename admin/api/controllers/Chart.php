<?php
/**
 * @模块   开始页图表数据
 * @版本   Version 1.0.0
 * @日期   2017-06-12
 * shensiming
 *
 * @版本   Version 2.0.0
 * @日期   2017-06-19
 * super
 */

defined('BASEPATH') OR exit('No direct script access allowed');
class Chart extends MY_Controller
{

	private $day = null;
	private $time_start = null;
	private $today = null;
	public function __construct()
	{
		parent::__construct();
		$this->day = (int)$this->G('day_num');
		$this->time_start = empty($this->G('time_start')) ? date('Y-m-d') : $this->G('time_start');
		$this->today = strtotime(date('Y-m-d'));
	}


	public function del_chart_redis(){
		$this->load->model('cash/Report_model','report');
		$this->report->redis_del('chart:in_out_price');
		$this->report->redis_del('chart:in_out_num');
		$this->report->redis_del('chart:in_out_peo');
		$this->report->redis_del('chart:valid_user');
		$this->report->redis_del('chart:valid_bet_price');
		$this->return_json(OK);
	}

	/**
	 * 出入款数据
	 * @return json类型
	 */
	public function in_out_price()
	{
		$this->load->model('cash/Report_model','report');
		$basic['a.report_date ='] = $this->time_start;
		$data = $resu = [];
        while ($this->day--) {
			$r_json = $this->report->redis_hget('chart:in_out_price',$basic['a.report_date =']);
			if(empty($r_json)){
				$data = $this->report->get_report($basic,2);
				if(empty($data['out_discount_total'])){
					$data['out_discount_total']=0;
				}
				// 公司入款
				$r['in_company_total'] = $data['in_company_total'];
				// 线上入款
				$r['in_online_total'] = $data['in_online_total'];
				// 优惠卡入款
				$r['in_card_total'] = $data['in_card_total'];
				// 人工入款
				$r['in_people_total'] = $data['in_people_total'];
				// 日总入款
				$r['in_price_total'] = $data['in_company_total'] + $data['in_online_total'] +
						$data['in_people_total']  + $data['in_member_out_deduction'];
				/*$r['out_discount_total'] =
						$data['in_company_discount'] +
						$data['in_online_discount'] +
						$data['in_people_discount'] +
						$data['in_register_discount'];*/
				//优惠总额
				$r['out_discount_total'] =
					$data['in_company_discount'] + $data['in_online_discount'] + $data['in_people_discount'] +
					$data['in_card_total'] + $data['in_register_discount'];

				// 日总出款
				$r['out_price_total'] = ($data['out_company_total'] + $data['out_people_total'] +
						$r['out_discount_total']  + $data['out_return_water']);

				// 日盈亏
				$r['win_lose'] = $r['in_price_total'] - $r['out_price_total'];
				$r = array_map(function($v){ return (float)sprintf("%.2f", $v);},$r);
				if(strtotime($basic['a.report_date ='])<$this->today) {//当天及之后的数据都不写入redis
					$this->report->redis_hset('chart:in_out_price', $basic['a.report_date ='], json_encode($r));
				}
			}else{
				$r = json_decode($r_json,true);
			}
        	$resu[$basic['a.report_date =']] = $r;
        	$basic['a.report_date ='] = date('Y-m-d', strtotime('-1 days', strtotime($basic['a.report_date ='])));
        }
		unset($basic,$r_json,$r);
		if(!empty($resu)){
			$title_arr = array('in_company_total'=>'公司入款','in_online_total'=>'线上入款',
					'in_card_total'=>'彩豆入款','in_people_total'=>'人工入款','in_price_total'=>'日总收入',
					'out_price_total'=>'日总支出','win_lose'=>'日盈亏');
			$data = $this->chuli($resu,$title_arr);//统一处理
		}
		$this->return_json(OK, ['categories'=>$data['categories'],'series'=>$data['series'],'sum'=>$data['sum']]);
	}

	/**
	 * 存/提款笔数
	 * @return json类型
	 */
	public function in_out_num()
	{
		$this->load->model('cash/Report_model','report');
		$basic['a.report_date ='] = $this->time_start;
		$data = $resu = [];
        while ($this->day--) {
			$r_json = $this->report->redis_hget('chart:in_out_num',$basic['a.report_date =']);
			if(empty($r_json))
			{
				$data = $this->report->get_report($basic,2);
				// 公司存款笔数
				$r['in_company_num'] = $data['in_company_num'];
				// 线上存款笔数
				$r['in_online_num'] = $data['in_online_num'];
				// 彩豆入款笔数
				$r['in_card_num'] = $data['in_card_num'];
				// 人工入款笔数
				$r['in_people_num'] = $data['in_people_num'];
				// 存款总笔数
				$r['in_total_num']= $data['in_company_num']+$data['in_online_num']+
						$data['in_people_num']+$data['in_card_num'];
				// 银行提款总笔数
				$r['out_company_num'] = $data['out_company_num'];
				// 人工提款总笔数
				$r['out_people_num'] = $data['out_people_num'];
				//总计
				$r['total_num'] = $r['in_total_num']+$data['out_company_num']+$data['out_people_num'];
				$r = array_map(function($v){ return (float)sprintf("%.2f", $v);},$r);
				if(strtotime($basic['a.report_date ='])<$this->today) {
					$this->report->redis_hset('chart:in_out_num', $basic['a.report_date ='], json_encode($r));
				}
			}else{
				$r = json_decode($r_json,true);
			}
			$resu[$basic['a.report_date =']] = $r;
        	$basic['a.report_date ='] = date('Y-m-d', strtotime('-1 days', strtotime($basic['a.report_date ='])));
		}
		unset($basic,$r_json,$r);
		if(!empty($resu)){
			$title_arr = array('in_company_num'=>'公司存款笔数','in_online_num'=>'线上存款笔数',
				 	'in_card_num'=>'彩豆入款笔数','in_people_num'=>'人工入款笔数','in_total_num'=>'日存款总笔数',
					'out_company_num'=>'银行提款总笔数','out_people_num'=>'人工提款总笔数','total_num'=>'日总计');
			$data = $this->chuli($resu,$title_arr);//统一处理
			//unset($data['series'][3],$data['series'][4],$data['series'][5]);
		}
		$this->return_json(OK, ['categories'=>$data['categories'],'series'=>$data['series'],'sum'=>$data['sum']]);
	}

	//将数组处理成前端需要的数据格式
	private function chuli($resu,$title_arr)
	{
		$resu = array_reverse($resu,true);//倒过来
		$data['categories'] = array_map(function($v){return substr($v,5);},array_keys($resu));//截取出日期数组
		$data['sum'] = $data['series'] = array();
		$i=0;
		foreach($title_arr as $k=>$v){
			$data['series'][$i]['name'] = $v;
			$data['series'][$i]['data'] = array_column($resu,$k);
			$data['sum'][$i] = (float)sprintf("%.2f",array_sum($data['series'][$i]['data']));
			$i++;
		}
		return $data;
	}

	/**
	 * 存/提款人数
	 * @return json类型
	 */
	public function in_out_peo()
	{
		$this->load->model('cash/Report_model','report');
		$basic['a.report_date ='] = $this->time_start;
		$data = $resu = [];
        while ($this->day--) {
			$r_json = $this->report->redis_hget('chart:in_out_peo',$basic['a.report_date =']);
			if(empty($r_json)){
				$data = $this->report->get_report($basic,2);
				// 公司入款人数
				$r['in_company_peo'] = $data['in_company_peo'];
				// 线上入款人数
				$r['in_online_peo'] = $data['in_online_peo'];
				// 优惠卡入款人数
				$r['in_card_peo'] = $data['in_card_peo'];
				// 人工入款人数
				$r['in_people_peo'] = $data['in_people_peo'];
				// 人工出款人数
				$r['out_people_peo'] = $data['out_people_peo'];
				// 日出款总人数
				$r['out_total_peo'] = $data['out_company_peo']+$data['out_people_peo'];
				$r = array_map(function($v){ return (float)sprintf("%.2f", $v);},$r);
				if(strtotime($basic['a.report_date ='])<$this->today) {
					$this->report->redis_hset('chart:in_out_peo', $basic['a.report_date ='], json_encode($r));
				}
			}else{
				$r = json_decode($r_json,true);
			}
			$resu[$basic['a.report_date =']] = $r;
        	$basic['a.report_date ='] = date('Y-m-d', strtotime('-1 days', strtotime($basic['a.report_date ='])));
		}
		unset($basic,$r_json,$r);
		if(!empty($resu)){
			$title_arr = array('in_company_peo'=>'公司入款人数','in_online_peo'=>'线上入款人数',
					'in_card_peo'=>'彩豆入款人数','in_people_peo'=>'人工入款人数','out_people_peo'=>'人工出款人数',
				    'out_total_peo'=>'日出款总人数');
			$data = $this->chuli($resu,$title_arr);//统一处理
		}
		$this->return_json(OK, ['categories'=>$data['categories'],'series'=>$data['series'],'sum'=>$data['sum']]);
	}

	/**
	 * 有效会员分析
	 * @return json
	 */
	public function valid_user()
	{
		$this->load->model('cash/Report_model','report');
		$basic['a.addtime >='] = strtotime($this->time_start);
		$basic['a.addtime <='] = $basic['a.addtime >=']+86400;
		$data = $resu = [];
		$select = 'count(if(b.in_company_num>0,"1",null)) as in_company_peo,
				    count(if(b.in_online_num>0,"1",null)) as in_online_peo,
				    count(if(b.in_card_num>0,"1",null)) as in_card_peo,
				    count(if(b.in_people_num>0,"1",null)) as in_people_peo,

				    count(if(b.out_company_num>0,"1",null)) as out_company_peo,
				    count(if(b.out_people_num>0,"1",null)) as out_people_peo,

				    sum(b.in_online_num) as in_online_num,
				    sum(b.in_company_num) as in_company_num,
				    sum(b.in_card_num) as in_card_num,
				    sum(b.in_people_num) as in_people_num,
				    
				    sum(b.out_company_num) as out_company_num,
				    sum(b.out_people_num) as out_people_num,
				    
				    sum(b.in_online_total) as in_online_total,
				    sum(b.in_company_total) as in_company_total,
				    sum(b.in_card_total) as in_card_total,
				    sum(b.in_people_total) as in_people_total,
				    
				    sum(b.out_company_total) as out_company_total,
				    sum(b.out_people_total) as out_people_total';

		//新增优惠卡入款相关的统计 super
		$join = ['join'=>'cash_report', 'on'=>'a.id=b.uid'];
        while ($this->day-->0) {
			$day = date('Y-m-d',$basic['a.addtime >=']);
			$r_json = $this->report->redis_hget('chart:valid_user',$day);
			if(empty($r_json)){
				$data = $this->report->get_list($select, 'user', $basic, $join);
				$user = $this->report->get_one('count(id)', 'user as a', $basic);
				$r['in_price_num'] = $data[0]['in_online_num'] + $data[0]['in_company_num'] + $data[0]['in_card_num'] + $data[0]['in_people_num'];
				$r['in_price_peo'] = $data[0]['in_company_peo'] + $data[0]['in_online_peo'] + $data[0]['in_card_peo'] + $data[0]['in_people_peo'];
				$r['out_price_num'] = $data[0]['out_company_num']+$data[0]['out_people_num'];
				$r['out_price_peo'] = $data[0]['out_company_peo']+$data[0]['out_people_peo'];
				$r['in_price_total'] = $data[0]['in_company_total'] + $data[0]['in_online_total'] + $data[0]['in_card_total'] + $data[0]['in_people_total'];
				$r['out_price_total'] = $data[0]['out_company_total']+$data[0]['out_people_total'];
				$r['reg_num'] = $user['count(id)'];
				$r = array_map(function($v){ return (float)sprintf("%.2f", $v);},$r);
				if($basic['a.addtime <=']<$this->today){
					$this->report->redis_hset('chart:valid_user',$day,json_encode($r));
				}
			}else{
				$r = json_decode($r_json,true);
			}
			$resu[$day] = $r;
        	$basic['a.addtime >='] = $basic['a.addtime >=']-86400;
			$basic['a.addtime <='] = $basic['a.addtime <=']-86400;
		}
		unset($basic,$r_json,$r);
		if(!empty($resu)){
			$title_arr = array('in_price_num'=>'当日新注册会员入款笔数','in_price_peo'=>'当日新注册会员入款人次',
					'out_price_num'=>'当日新注册会员出款笔数','out_price_peo'=>'当日新注册会员出款人次',
				    'in_price_total'=>'当日新注册会员入款金额','out_price_total'=>'当日新注册会员出款金额','reg_num'=>'日注册人数');
			$data = $this->chuli($resu,$title_arr);//统一处理
		}
		$this->return_json(OK, ['categories'=>$data['categories'],'series'=>$data['series'],'sum'=>$data['sum']]);
	}

	/**
	 * 有效总投注
	 * @return json
	 */
	public function valid_bet_price()
	{
		$this->load->model('cash/Report_model','report');
		$day = (int)$this->G('day_num');
        $basic['a.report_date ='] = empty($this->G('time_start'))
        						? date('Y-m-d') : $this->G('time_start');

        $select = 'count(DISTINCT uid) as total_peo,
        		   sum(valid_price) as total_valid_price,
        		   sum(return_price) as total_return_price';//uid需要去重
		$data = $resu = [];
		//$day_stamp = strtotime(date('Ymd'));
        while ($day--) {
			$r_json = $this->report->redis_hget('chart:valid_bet_price',$basic['a.report_date =']);
			if(empty($r_json)){
				$data = $this->report->get_list($select, 'report', $basic);
				if(strtotime($basic['a.report_date ='])<$this->today){
					$this->report->redis_hset('chart:valid_bet_price',$basic['a.report_date ='],json_encode($data[0]));
				}
			}else{
				$data[0] = json_decode($r_json,true);
			}
			$resu[$basic['a.report_date =']] = array_map(function($v){ return (float)sprintf("%.2f", $v);},$data[0]);
			$basic['a.report_date ='] = date('Y-m-d', strtotime('-1 days', strtotime($basic['a.report_date ='])));
        }
		unset($basic,$r_json);
		if(!empty($resu)){
			$title_arr = array('total_peo'=>'人数','total_valid_price'=>'打码量','total_return_price'=>'返水');
			$data = $this->chuli($resu,$title_arr);//统一处理
		}
		$this->return_json(OK, ['categories'=>$data['categories'],'series'=>$data['series'],'sum'=>$data['sum']]);
	}



	private function _format($d)
	{
		foreach ($d as $key => $value) {
			$d[$key] = (float)$value;
		}
		return $d;
	}
}