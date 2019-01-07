<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 红包模块---等级设置---控制器
 * @模块   红包中心／获取等级设置
 * @模块   红包中心／添加等级设置
 * @模块   红包中心／获取等级活动设置
 * @模块   红包中心／删除等级设置
 * @模块   红包中心／更新等级设置
 * 
 * @author      ssm
 * @package     controllers/red/Level
 * @version     v1.0 2017/09/04
 * @copyright
 * @link
 */
class Level extends MY_Controller
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
     * 获取等级设置列表
     *
     * @access public
     * @return json
     */
    public function all()
    {
        $page = $this->_get_page();
        $where = $this->_get_where();
        $where2 = $this->_get_where2();
        $data = $this->core->get_level_list([
                            'where'=>$where,
                            'where2'=>$where2,
                            'page'=>$page]);
        return $this->return_json(OK, $data);
    }

    /**
     * 获取等级设置单条
     *
     * @access public
     * @return json
     */
    public function one()
    {
    	$id = (int)$this->G('id');
    	$data = $this->core->get_level_one($id);
        return $this->return_json(OK, $data);
    }

    /**
     * 添加等级设置列表
     *
     * @access public
     * @return json
     */
    public function add()
    {
        $data = $this->_verify_data();
        $sql = "SELECT * FROM gc_red_set WHERE (start_recharge <= {$data['start_recharge']} and end_recharge >= {$data['start_recharge']}) or (start_recharge <= {$data['end_recharge']} and end_recharge >= {$data['end_recharge']}) LIMIT 1";
        if ($this->core->level_ud_limit()) {
            $this->return_json(E_ARGS,'活动正在进行或者活动开始不到30分钟不能修改');
        }
        $exsixt = $this->core->db->query($sql)->row_array();
        if (isset($exsixt)) {
            $this->return_json(E_ARGS,'起始或结束金额重复');
        }
        $flag = $this->core->write('red_set', $data);
        if (!$flag) {
            $content = "添加等级设置失败：".json_encode($data);
            $this->core->add_log($content);
            $this->return_json(E_ARGS,'添加失败');
        } else {
            $content = "添加等级设置成功：".json_encode($data);
            $this->core->add_log($content);
            $id = $this->core->db->insert_id();
            $data['id'] = $id;
            $this->core->del_level_redis();
            $this->return_json(OK,'添加成功');
        }
    }

    /**
     * 修改等级设置列表
     *
     * @access public
     * @return json
     */
    public function upd()
    {
    	$id = (int)$this->P('id');
        $data = $this->_verify_data();
        unset($data['add_time']);
        if ($this->core->level_ud_limit()) {
            $this->return_json(E_ARGS,'活动正在进行或者活动开始不到30分钟不能修改');
        }
        $sql = "SELECT * FROM gc_red_set WHERE id!={$id} AND ((start_recharge <= {$data['start_recharge']} and end_recharge >= {$data['start_recharge']}) or (start_recharge <= {$data['end_recharge']} and end_recharge >= {$data['end_recharge']})) LIMIT 1";
        $exsixt = $this->core->db->query($sql)->row_array();
        if (isset($exsixt)) {
            $this->return_json(E_ARGS,'起始或结束金额重复');
        }
        $flag = $this->core->write('red_set', $data, ['id'=>$id]);
        if (!$flag) {
            $content = "修改等级设置失败：id={$id}---".json_encode($data);
            $this->core->add_log($content);
            $this->return_json(E_ARGS,'修改失败');
        } else {
            $content = "修改等级设置成功：id={$id}---".json_encode($data);
            $this->core->add_log($content);
            $this->core->del_level_redis();
            $this->return_json(OK,'修改成功');
        }
    }

    /**
     * 删除等级设置列表
     *
     * @access public
     * @return json
     */
    public function del()
    {
    	$id_arr = explode(',',$this->P('id'));
        if ($this->core->level_ud_limit()) {
            $this->return_json(E_ARGS,'活动正在进行或者活动开始不到30分钟不能修改');
        }
        $flag = $this->core->delete('red_set', $id_arr);
        if (!$flag) {
            $content = "删除等级设置成功：".json_encode($this->P('id'));
            $this->core->add_log($content);
            $this->core->del_level_redis();
            $this->return_json(E_ARGS,'删除失败');
        } else {
            $content = "删除等级设置成功：".json_encode($this->P('id'));
            $this->core->add_log($content);
            $this->core->del_level_redis();
            $this->return_json(OK,'删除成功');
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

        $data['start_recharge'] = (float)$this->P('start_recharge');
        $data['end_recharge'] = (float)$this->P('end_recharge');
        $data['start_total'] = (float)$this->P('start_total');
        $data['end_total'] = (float)$this->P('end_total');
        $data['count'] = (int)$this->P('count');

        $rule = [
            'start_recharge'=> 'require|egt:0',
            'end_recharge'  => 'require|egt:0|gt:'.
                                $data['start_recharge'],
            'start_total'   => 'require|egt:0.01',
            'end_total'     => 'require|egt:0|gt:'.
                                $data['start_total'],
            'count'         => 'require|egt:1|',
        ];
        $msg  = [
            'start_recharge' => '起始充值金额必须大于等于0',
            'end_recharge'   => '充值金额必须大于起始充值金额',
            'start_total'    => '起始奖金从0.01开始',
            'end_total'      => '结束奖金必须大于起始奖金',
            'count'          => '抽奖必须大于0',
        ];
        $this->validate->rule($rule, $msg);
        $result = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());
        }
        $data['add_time'] = strtotime(date('Y-m-d H:i:s'));
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

        $count = (int)$this->G('count');
        /************************/
        $where['count ='] = $count;
        return $where;
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
        $where2['orderby'] = ['start_recharge'=>'desc'];
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
        return $rows;
    }
}