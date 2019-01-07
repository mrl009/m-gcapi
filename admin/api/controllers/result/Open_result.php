<?php
/**
 * @模块   开奖结果
 * @版本   Version 1.0.0
 * @日期   2017-04-03
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Open_result extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('result/OpenResult_model','open');
        $this->load->model('MY_Model', 'core');
    }

    /*
     * 要求和值的彩种
     */
    private $games_type = [
        'k3'    =>[12,13,15,16,17,33,34,35,36,37],
        's_k3'  =>[62,63,65,66,67,82,83,84,85,86,87,88],
        'yb'    =>[1,2,51,52],
        'pcdd'  =>[24,25]
    ];
    public function index()
    {
        $gid = empty($this->G('gid'))?1:(int)$this->G('gid');
        if (!in_array($gid, explode(',', ZKC))) {
            $data = $this->get_kc($gid);
        } else {
            $data = $this->get_zkc($gid);
        }
        if (empty($data['rows'])) {
            $this->return_json(E_DATA_EMPTY, '无数据！');
        }
        if (!empty($data)) {
            foreach ($data['rows'] as $key => $value) {
                $b = $i = 1;
                $number_arr = explode(',', $value['number']);
                $data['rows'][$key]['sum_num'] =0;
                foreach ($number_arr as $v2) {
                    $data['rows'][$key]['num' . $i] = $v2;
                    if (in_array($gid,$this->games_type['k3']) || in_array($gid,$this->games_type['s_k3']) || in_array($gid,$this->games_type['yb']) || in_array($gid,$this->games_type['pcdd'])) {
                        $data['rows'][$key]['k1'] = $data['rows'][$key]['k1'] + $v2;
                    }else{
                        $data['rows'][$key]['k1'] = '';
                    }
                    $i++;
                }
                if (!empty($value['code_str'])) {
                    $code_arr = explode(',', $value['code_str']);
                    foreach ($code_arr as $v3) {
                        $data['rows'][$key]['k'.$b] = $v3;
                        $b++;
                    }
                }
            }
        } else {
            $this->return_json(E_DATA_EMPTY, $data);
        }
        $rs  = array('total'=>$data['total'],'rows'=>$data['rows']);
        $this->return_json(OK, $rs);
    }

    //根据gid获取下一个要开奖的开奖信息
    public function get_next_open()
    {
        if(!is_numeric($this->G('gid')) ||  empty($this->G('gid'))){
            $this->return_json(E_ARGS,'参数错误');
        }
        $where['gid'] = $this->G('gid');
        $this->core->select_db('public');
        if ($where['gid']==3){
            $one = $this->core->get_one('open_time,current_kithe', 'open_time', $where);
            if(empty($one['open_time']) || empty($one['current_kithe'])){
                $this->return_json(E_OP_FAIL,'获取开奖信息失败！');
            }
            $one['kithe'] = date('Y').$one['current_kithe'];
            unset($one['current_kithe']);
            $this->return_json(OK,$one);
        }
        $condition['orderby'] = array('code_kithe'=>'asc');
        $where['status'] = 1;
        $one = $this->core->get_one('open_time,kithe', 'open_num', $where, $condition);
        if(empty($one)){
            $this->return_json(E_OP_FAIL,'获取开奖信息失败！');
        }
        $this->return_json(OK,$one);
    }

	/**
	 * 人工开奖
	 */
    public function artificial()
	{
		if(!is_numeric($this->P('gid')) ||  empty($this->P('gid')) || empty($this->P('kithe')) || !is_numeric($this->P('kithe')) || empty($this->P('number')) ){
			$this->return_json(E_ARGS,'参数错误');
		}
		$gid  = $this->P('gid');
		$kithe = $this->P('kithe');
		$code_kithe = $where['code_kithe'] = $gid.'_'.$kithe;
		$data  = array(
			'number'    => $this->P('number'),
			'actual_time' => date('Y-m-d H:i:s'),
			'status' => 2
		);
		$this->core->select_db('public');
		$test_arr = explode(',',$data['number']);
		foreach($test_arr as $value) {
			if(!is_numeric($value)) {
				$this->return_json(E_OP_FAIL,'开奖号码输入错误，请重新输入！');
			}
		}
		$one = $this->core->get_one('id,status,open_time', 'open_num', $where);
		if( !empty($one['id']) ){
			if( $one['status'] == 2 ){
				$this->return_json(E_OP_FAIL,'该期已经开奖！');
			}
		}else{
			//插入
			$data['open_time'] = date('Y-m-d H:i:s');
			$data['gid'] = $gid;
			$data['kithe'] = $kithe;
			$data['code_kithe'] = $where['code_kithe'];
			$where = array();
		}

		$where2['gid'] = $gid;
		$where2['status'] = 2;
		$this->core->db->select('number');
		$this->core->db->limit(1);
		$test_result2 = $this->core->db->get_where('open_num',$where2)->result_array();
		if(empty($test_result2[0]['number'])){
			$this->return_json(E_OP_FAIL,'操作失败！');
		}
		if(count($test_arr)!=count(explode(',',$test_result2[0]['number']))){
			$this->return_json(E_OP_FAIL,'输入的开奖号码数量不正确！');
		}
        $this->core->select_db('public_w');
		$is = $this->core->write('open_num', $data, $where);
        $redis = $data;
		$redis['gid'] = $gid;
        $redis['kithe'] = $kithe;
        $redis['code_kithe'] = $code_kithe;
        $redis['open_time'] = isset($one['open_time']) ? $one['open_time'] : date('Y-m-d H:i:s');
		$this->core->redisP_hset('new_open_num',$gid,json_encode($redis,JSON_UNESCAPED_UNICODE));
		$this->set_open_redis($redis);
		if($is){
			$this->return_json(OK, '添加成功！');exit;
		}else{
			$this->return_json(E_OP_FAIL, '添加失败！');exit;
		}
	}

    private function set_open_redis($data)
    {
        $gid = $data['gid'] > 50 ? gid_tran($data['gid']) : $data['gid'];
        if (in_array($gid, explode(',', ZKC))) {
            $key = 'gcopen:' . $this->_sn . ':' . $gid;
        } else {
            $key = 'gcopen:' . $gid;
        }
        $this->core->redisP_hset($key, $data['kithe'], json_encode(array($data['number'], $data['open_time'])));
    }

    /**
     * 获取开彩数据
     * @param $gid
     * @return mixed
     */
    private function get_kc($gid)
    {
        $basic  = array(
            'gid'      => $gid,
            'kithe'    => $this->G('kithe'),
        ); //精确条件
        $time = $this->G('time');
        if (!empty($time)) {
            $basic['open_time >='] =$time.' 00:00:00';
            $basic['open_time <='] = $time.' 23:59:59';
        } else {
            if ($basic['gid'] ==1 || $basic['gid'] ==2 || $basic['gid'] ==3) {
                $basic['open_time >='] ='2017-01-01 00:00:00';
                $basic['open_time <='] =date('Y-m-d H:i:s');
            } else {
                $basic['open_time >='] =date('Y-m-d 00:00:00');
                $basic['open_time <='] = date('Y-m-d H:i:s');
            }
        }
        $senior = array(); //高级搜索
        $page   = array(
            'page'  => $this->G('page')?(int)$this->G('page'):1,
            'rows'  => $this->G('rows')?(int)$this->G('rows'):50,
            'order' => $this->G('order'),//排序方式
            'sort'  => $this->G('sort'),//排序字段
            'total' => -1,
        );
        $this->core->select_db('public');
        $rs = $this->core->get_list('id,gid,kithe,open_time,number,status,code_kithe,code_str', 'open_num', $basic, $senior, $page);
        return $rs;
    }

    /**
     * 获取自开彩数据
     * @param $gid
     * @return mixed
     */
    private function get_zkc($gid)
    {
        $where = array(
            'gid' => $gid,
            'issue' => (int)$this->G('kithe'),
        );
        $time = strtotime($this->G('time')) ? strtotime($this->G('time')) : 0;
        if (!empty($time)) {
            $where['updated >='] = $time;
            $where['updated <='] = $time + 24*3600;
        }
        $page = array(
            'page' => $this->G('page') ? (int)$this->G('page') : 1,
            'rows' => $this->G('rows') ? (int)$this->G('rows') : 50,
            'order' => 'desc',
            'sort' => 'id',
            'total' => -1,
        );
        $this->core->select_db('private');
        $field = 'id,gid,issue as kithe,updated as open_time,lottery as number,status';
        $data = $this->core->get_list($field, 'bet_settlement', $where, [], $page);
        if (!empty($data['rows'])) {
            foreach ($data['rows'] as $k => $v) {
                $data['rows'][$k]['open_time'] = date('Y-m-d H:i:s', $v['open_time']);
            }
        }
        return $data;
    }
}
