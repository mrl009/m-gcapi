<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 红包模块
 *
 * @author      ssm
 * @package     controllers/red/Red_model
 * @version     v1.0 2017/09/04
 * @copyright
 * @link
 */
class Red_model extends MY_Model
{
    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->ci = get_instance();
    }

    /**
     * 获取活动设置列表
     *
     * @access public
     * @param Array $condit 条件['page'=>[],
                                'where'=>[],
                                'where2'=>[]]
     * @return Array
     */
    public function get_activity_list($condit=[], $is_del=0)
    {
        if ($is_del == 1) {
            $result1 = $this->get_list('*', 'red_activity',
                $condit['where'],$condit['where2'],$condit['page']);
            $result1['rows'] = $this->_data_format($result1['rows'], 'activity');
            return $result1;
        }
        $result1 = $this->get_list('*', 'red_activity',
                $condit['where'],$condit['where2'],$condit['page']);
        $field = 'a.*, count(b.id) as count';
        $condit['where2']['join'][] =
            ['table'=>'red_order as b', 'on'=>'b.bag_id = a.id'];
        $condit['where2']['groupby'] = ['bag_id'];
        $result2 = $this->get_list($field, 'red_activity',
                $condit['where'],$condit['where2'],$condit['page']);
        $result1['rows'] = array_make_key($result1['rows']);
        foreach ($result2['rows'] as $key => $value) {
            $result1['rows'][$value['id']] = $value;
        }
        $result1['rows'] = array_values($result1['rows']);
        $result1['rows'] = $this->_data_format($result1['rows'], 'activity');

        // 排序
        if (empty($result1['rows'])) {
            $result1['rows'] = [];
            return  $result1;
        }
        foreach ($result1['rows'] as $k=>$v) {
            $tag1[] = $v['start_time'];
        }
        array_multisort($tag1, SORT_DESC, $result1['rows']);
        return $result1;
    }

    /**
     * 获取活动设置单条
     *
     * @access public
     * @param Integer $id ID
     * @return Array
     */
    public function get_activity_one($id)
    {
        $field = '*';
        $result = $this->get_one($field, 'red_activity',['id'=>$id]);
        $result = $this->_data_format([$result], 'activity');
        return isset($result[0]) ? $result[0] : [];
    }


    /**
     * 针对添加的红包活动
     *
     * @access public
     * @param Integer $id 添加的ID
     * @param Array $data 添加的活动数据
     * @return Boolean
     */
    public function add_activity_redis($id_arr, $data_arr)
    {
        if (!is_array($id_arr)) {
            $id = $id_arr;
            $id_arr = [];
            $id_arr[] = $id;

            $data = $data_arr;
            $data_arr = [];
            $data_arr[] = $data;
        }
        for ($i=0; $i < count($id_arr); $i++) {
            $this->redis_zadd('red_bag:zadd',
                $data_arr[$i]['start_time'], $id_arr[$i]);
        }
        return ;
    }

    /**
     * 针对修改删除的红包活动
     *
     * @access public
     * @param Integer $id 添加的ID
     * @param Array $data 添加的活动数据
     * @return Boolean
     */
    public function del_activity_redis($id_arr)
    {
        if (is_int($id_arr)) {
            return $this->redis_hdel('red_bag:list', $id_arr);
        }

        $this->redis_pipeline();
        foreach ($id_arr as $id) {
            $this->redis_hdel('red_bag:list', $id);
        }
        return $this->redis_exec();
    }

    /**
     * 针对层级的红包活动
     *
     * @access public
     * @param Integer $id 添加的ID
     * @param Array $data 添加的层级数据
     * @return Boolean
     */
    public function add_level_redis($data_t)
    {
        $data = $this->redis_get('red_bag:set');
        $data = json_decode($data, true);
        $data[] = $data_t;
        $this->redis_set('red_bag:set', json_encode($data));
        return ;
    }

    /**
     * 针对层级的红包活动
     *
     * @access public
     * @param Array $data 添加的层级数据
     * @return Boolean
     */
    public function upd_level_redis($data_t)
    {
        $data = $this->redis_get('red_bag:set');
        $data = json_decode($data, true);
        foreach ($data as $key => $value) {
            if ($value['id'] == $data_t['id']) {
                unset($data[$key]);
            }
        }
        $data[] = $data_t;
        $this->redis_set('red_bag:set', json_encode($data));
        return ;
    }

    /**
     * 针对层级设置，离红包开始三十分钟内无法修改和删除
     *
     * @access public
     * @return Boolean TRUE：不能删除修改
     */
    public function level_ud_limit()
    {
        $time = time()+A_RED_UPD_TIME_LIMIT*60;
        $sql = "SELECT * FROM gc_red_activity WHERE start_time <= {$time} ORDER BY start_time desc LIMIT 1";
        $resu = $this->db->query($sql)->row_array();
        if (empty($resu)) {
            return false;
        }
        if ($resu['start_time'] > $time) {
            return false;
        }
        if ($resu['end_time'] < time()) {
            return false;
        }
        return true;
    }

    /**
     * 针对修改删除的层级活动
     *
     * @access public
     */
    public function del_level_redis()
    {
        $this->redis_del('red_bag:set');
    }

    /**
     * 获取等级设置单条
     *
     * @access public
     * @param Integer $id ID
     * @return Array
     */
    public function get_level_one($id)
    {
        $field = '*';
        $result = $this->get_one($field, 'red_set',['id'=>$id]);
        $result = $this->_data_format([$result], 'level');
        if (!empty($result[0])) {
            $result = $result[0];
        }
        return $result;
    }

    /**
     * 获取等级设置列表
     *
     * @access public
     * @param Array $condit 条件['page'=>[],
                                'where'=>[],
                                'where2'=>[]]
     * @return Array
     */
    public function get_level_list($condit=[])
    {
        $field = '*';
        $result = $this->get_list($field, 'red_set',
                $condit['where'],$condit['where2'],$condit['page']);
        $result['rows'] = $this->_data_format($result['rows']);
        return $result;
    }

    /**
     * 获取订单设置列表
     *
     * @access public
     * @param Array $condit 条件['page'=>[],
                                'where'=>[],
                                'where2'=>[]]
     * @return Array
     */
    public function get_order_list($condit=[])
    {
        $field = '*';
        $result = $this->get_list($field, 'red_order',
                $condit['where'],$condit['where2'],$condit['page']);
        $result['rows'] = $this->_data_format($result['rows'], 'order');
        $result['footer'][] = $this->_get_footer($result['rows'], 'order');
        return $result;
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
     * 获取尾部数据
     *
     * @access public
     * @param Array $result 数据
     * @param String $scene 场景
     * @return Array 修改后的数据
     */
    private function _get_footer($result=[], $scene='')
    {
        if (empty($result) || !is_array($result) ||
            empty($result[0])) {
            return [];
        }
        $total['total'] = 0;
        foreach ($result as $key => $value) {
            if ($scene == 'order') {
                $total['total'] += $value['total'];
            }
        }
        return stript_float($total);
    }

    /**
     * 对数据进行修改
     *
     * @access public
     * @param Array $result 数据
     * @param String $scene 场景
     * @return Array 修改后的数据
     */
    private function _data_format($result=[], $scene='')
    {
        if (empty($result) || !is_array($result) ||
            empty($result[0])) {
            return [];
        }
        $cache['username'][0] = ['username'=>'-'];
        foreach ($result as $key => $value) {
            if ($scene == 'activity') {
                $now_in_second = time();
                if ($now_in_second >= $value['end_time']) {
                    $value['status'] = 3;
                } elseif ($now_in_second <= $value['start_time']) {
                    $value['status'] = 1;
                }  else {
                    $value['status'] = 2;
                }
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
            }
            
            elseif ($scene == 'level') {
                $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
            }

            elseif ($scene == 'order') {
                $uid = $value['uid'];
                /****************************/
                if (empty($cache['username'][$uid])) {
                    $user = $this->user_cache($uid);
                    $cache['username'][$uid] = $user;
                }
                /****************************/
                $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
                $value['username'] = $cache['username'][$uid]['username'];
                $value['ip'] = $value['ip'];
            }

            if (empty($value['count'])) {
                $value['count'] = 0;
            }
            $result[$key] = $value;
        }
        return $result;
    }

}
