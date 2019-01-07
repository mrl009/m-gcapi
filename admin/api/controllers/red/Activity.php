<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 红包模块---活动设置---控制器
 * @模块   红包中心／获取活动设置
 * @模块   红包中心／添加活动设置
 * @模块   红包中心／批量添加活动设置
 * @模块   红包中心／获取单条活动设置
 * @模块   红包中心／删除活动设置
 * @模块   红包中心／更新活动设置
 * 
 * @author      ssm
 * @package     controllers/red/Activity
 * @version     v1.0 2017/09/04
 * @copyright
 * @link
 */
class Activity extends MY_Controller
{

	/**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('red/Red_model', 'core');
    }

    /**
     * 获取活动设置列表
     *
     * @access public
     * @return json
     */
    public function all()
    {
        $page = $this->_get_page();
        $where = $this->_get_where();
        $where2 = $this->_get_where2();
        $data = $this->core->get_activity_list([
                            'where'=>$where,
                            'where2'=>$where2,
                            'page'=>$page]);
        $data['rows'] = $this->_get_filter($data['rows']);
        $data['rows'] = array_values($data['rows']);
        return $this->return_json(OK, $data);
    }

    /**
     * 获取状态列表
     *
     * @access public
     * @return json
     */
    public function type()
    {
        $data['rows'] = [
            ['id'=>1, 'label'=>'未开始'],
            ['id'=>2, 'label'=>'正在进行'],
            ['id'=>3, 'label'=>'已结束'],
        ];
        return $this->return_json(OK, $data);
    }

    /**
     * 获取活动设置单条
     *
     * @access public
     * @return json
     */
    public function one()
    {
    	$id = (int)$this->G('id');
    	$data = $this->core->get_activity_one($id);
        return $this->return_json(OK, $data);
    }

    /**
     * 添加活动设置列表
     *
     * @access public
     * @return json
     */
    public function add()
    {
        $data = $this->_verify_data();
        $this->_verify_repetition($data['start_time'], $data['end_time']);
        $flag = $this->core->write('red_activity', $data);
        if (!$flag) {
            $content = "添加红包活动失败：".json_encode($data);
            $this->core->add_log($content);
            $this->return_json(E_ARGS,'添加失败');
        } else {
            $content = "添加红包活动成功：".json_encode($data);
            $this->core->add_log($content);
            $id = $this->core->db->insert_id();
            $this->core->add_activity_redis($id, $data);
            $this->return_json(OK,'添加成功');
        }
    }

    /**
     * 批量添加活动设置列表
     *
     * @access public
     * @return json
     */
    public function batch()
    {
    	$batch = json_decode($this->P('data'), true);
        if (empty($batch)) {
            $this->return_json(E_ARGS,'添加失败');
        }
        // 验证数据库的日期是否重复
    	foreach ($batch as $k1 => $v1) {
            foreach ($v1 as $key => $value) {
                $_POST[$key] = $value;
            }
    		$d = $this->_verify_data();
            $data[] = $d;
            $this->_verify_repetition($d['start_time'], $d['end_time']);
    	}
        // 验证数据的日期是否重复
        $this->_verify_date($data);
        // 添加数据
        $ids = [];
        foreach ($data as $key => $value) {
            $this->core->write('red_activity', $value);
            $ids[] = $this->core->db->insert_id();
        }
        if (empty($ids)) {
            $content = "添加红包活动批量失败：".json_encode($data);
            $this->core->add_log($content);
            $this->return_json(E_ARGS,'添加失败');
        } else {
            $content = "添加红包活动批量成功：".json_encode($data);
            $this->core->add_log($content);
            $this->core->add_activity_redis($ids, $data);
            $this->return_json(OK,'添加成功');
        }
    }

    /**
     * 修改活动设置列表
     *
     * @access public
     * @return json
     */
    public function upd()
    {
    	$id = (int)$this->P('id');
        $data = $this->_verify_data();
        unset($data['add_time']);
        $this->_verify_repetition($data['start_time'], $data['end_time'], $id);
        $now_in_second = time();
        $d = $this->core->get_activity_one($id);
        if (empty($d)) {
            $this->return_json(E_ARGS,'ID错误');
        }
        if (strtotime($d['end_time']) <= time()) {
            $this->return_json(E_ARGS,'已结束无法修改');
        }
        if (strtotime($d['start_time']) <=
            $now_in_second+(A_RED_UPD_TIME_LIMIT*60) ) {
            $this->return_json(E_ARGS,'开始时间前'.A_RED_UPD_TIME_LIMIT.'分钟无法修改');
        }
        $flag = $this->core->write('red_activity', $data, ['id'=>$id]);
        if (!$flag) {
            $content = "修改红包活动失败：id={$id}---".json_encode($data);
            $this->core->add_log($content);
            $this->return_json(E_ARGS,'修改失败');
        } else {
            $content = "修改红包活动成功：id={$id}---".json_encode($data);
            $this->core->add_log($content);
            $this->core->del_activity_redis($id);
            $this->return_json(OK,'修改成功');
        }
    }

    /**
     * 删除活动设置列表
     *
     * @access public
     * @return json
     */
    public function del()
    {
        $id_arr = explode(',',$this->P('id'));
        $where['start_time <='] = time()+A_RED_UPD_TIME_LIMIT*60;
        $where2['wherein'] = ['id'=>$id_arr];
        $page = $this->_get_page();
        $r = $this->core->get_activity_list(
            ['where'=>$where, 'where2'=>$where2, 'page'=>$page], 1);
        foreach ($r['rows'] as $k => $v) {
            if (in_array($v['id'], $id_arr)) {
                $this->return_json(E_ARGS,'删除失败,红包三十分钟前或开始或结束的红包不能删除');
            }
        }
        $flag = $this->core->delete('red_activity', $id_arr);
        if (!$flag) {
            $content = "删除红包活动失败：".json_encode($this->P('id'));
            $this->core->add_log($content);
            $this->return_json(E_ARGS,'删除失败');
        } else {
            $content = "删除红包活动成功：".json_encode($this->P('id'));
            $this->core->add_log($content);
            $this->core->del_activity_redis($id_arr);
            $this->return_json(OK,'删除成功');
        }
    }

    /**
     * 验证日期是否重复
     *
     * @access private
     * @param Integer $start 开始时间戳
     * @param Integer $end 结束时间戳
     * @param Integer $id 不等于id（这个是针对修改的）
     * @return Error
     */
    private function _verify_repetition($start, $end, $id=0) {
        $sql = "SELECT * FROM gc_red_activity WHERE 
                (start_time <= {$start} and end_time >= {$start}) or
                (start_time <= {$end} and end_time >= {$end}) or
                (start_time >= {$start} and end_time <= {$end}) LIMIT 1";
        $exsixt = $this->core->db->query($sql)->row_array();
        if ($id !== 0) {
            if ($exsixt['id'] == $id) {
                return;
            }
        }
        if (isset($exsixt)) {
            $this->return_json(E_ARGS,'起始或结束日期重复');
        }
    }

    /**
     * 针对批量增加的日期验证
     *
     * @access private
     * @param Array $data 数据
     * @return Error
     */
    private function _verify_date($data)
    {
        foreach ($data as $k1 => $v1) {
            foreach ($data as $k2 => $v2) {
                if ($k1 == $k2) {
                    continue;
                }
                if ($v1['start_time'] <= $v2['start_time'] &&
                    $v1['end_time'] >= $v2['start_time']) {
                    $this->return_json(E_ARGS,'起始或结束日期重复1');
                }
                if ($v1['start_time'] <= $v2['end_time'] &&
                    $v1['end_time'] >= $v2['end_time']) {
                    $this->return_json(E_ARGS,'起始或结束日期重复2');
                }
                if ($v1['start_time'] >= $v2['start_time'] &&
                    $v1['end_time'] <= $v2['end_time']) {
                    $this->return_json(E_ARGS,'起始或结束日期重复3');
                }
            }
        }
    }

    /**
     * 验证数据
     *
     * @access private
     * @param Array $data 为空则获取POST
     * @return Array
     */
    private function _verify_data($data=[])
    {

    	if (empty($data)) {
    		$data['start_time'] = $this->P('start_time');
        	$data['end_time'] = $this->P('end_time');
        	$data['total'] = (float)$this->P('price_total');
    	}
		$rule = [
            'start_time'    => 'require|date|after:'.
                                date('Y-m-d H:i:s', time()+A_RED_UPD_TIME_LIMIT*60).
                                '|before:'.$data['end_time'],
            'end_time'      => 'require|date',
            'total'         => 'require|number|egt:0',
        ];
        $msg  = [
            'start_time'    => '开始时间不能高于结束时间并且不能低于'.A_RED_UPD_TIME_LIMIT.'分钟',
            'end_time'      => '结束时间不能低于开始时间',
            'total'         => '金额格式错误3',
        ];
        $this->validate->rule($rule, $msg);
        $result = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());
        }
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['add_time'] = strtotime(date('Y-m-d H:i:s'));
        $data['current_total'] = 0;
        if ($data['start_time']+(A_RED_ADD_MAX_TIME_LIMIT*60) < $data['end_time']) {
            $this->return_json(E_ARGS, '红包跨度最大为'.A_RED_ADD_MAX_TIME_LIMIT.'分钟');
        }
        if ($data['start_time']+(A_RED_ADD_MIN_TIME_LIMIT*60) > $data['end_time']) {
            $this->return_json(E_ARGS, '红包跨度最小为'.A_RED_ADD_MIN_TIME_LIMIT.'分钟');
        }
        return $data;
    }

    /**
     * 获取where查询条件
     *
     * @access private
     * @return Array
     */
    private function _get_where()
    {

        $status = (int)$this->G('status');
        if (empty($this->G('start_time'))) {
            $start = null;
        } else {
            $start = strtotime($this->G('start_time').' 00:00:00');
        }
        if (empty($this->G('end_time'))) {
            $end = null;
        } else {
            $end = strtotime($this->G('end_time').' 23:59:59');
        }
        /************************/
            $where['start_time >='] = $start;
            $where['end_time <='] = $end;
        /************************/
        if (!empty($end) && !empty($start) &&
            ($end - $start) > ADMIN_QUERY_TIME_SPAN ){
            $this->return_json(E_ARGS,'查询时间跨度不能超过2个月');
        }
        return $where;
    }

    private function _get_filter($data)
    {
        $status = (int)$this->G('status');
        if ($status == 0)
            return $data;
        foreach ($data as $key => $value) {
            if ($value['status'] != $status) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * 获取where2查询条件
     *
     * @access private
     * @param String $scene 场景
     * @return Array
     */
    private function _get_where2($scene='')
    {
        $where2['orderby'] = ['start_time'=>'desc'];
        return $where2;
    }

    /**
     * 验证page分页条件
     *
     * @access private
     * @return Array
     */
    private function _get_page()
    {
        // 分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
                (int)$this->G('rows') : 50;
        if ($rows > 500) {
            $rows = 500;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
        );
        return $page;
    }
}