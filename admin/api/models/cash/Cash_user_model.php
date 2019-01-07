<?php
/**
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/4/4
 * Time: 15:12
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cash_user_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取更多信息
     */
    public function cash_x($uid=[])
    {
        $where  = [] ;
        $where2 = [
            'wherein' => ['uid'=> $uid]
        ];
        $arr = $this->get_all('uid,bank_name', 'user_detail', $where, $where2);
        $temp = [];
        foreach ($arr as $k => $v) {
            $temp[$v['uid']]['bank_name'] = $v['bank_name'];
        }
        return $temp;
    }

    /**
     * 将层级id，管理员id，支付id等转换为名称
     *
     * @access public
     * @param Array $data   数据数组
     * @return $data        转换后结果
     */
    public function _id_to_name($data)
    {
        if (empty($data)) {
            return $data;
        }

        // 初始化0的值
        $cache['leve_id'][0] = '-';
        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($data as $k => $v) {
            $user_id  = $v['user_id'];
            $agent_id = $v['agent_id'];

            if (empty($cache['user_id'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['user_id'][$user_id] = $user;
            }
            $leve_id = $cache['user_id'][$user_id]['level_id'];
            $v['level_id'] = $leve_id;

            if (empty($cache['user_id'][$agent_id])) {
                $user = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $user;
            }

            if (empty($cache['leve_id'][$leve_id])) {
                $leve = $this->level_cache($leve_id);
                $cache['leve_id'][$leve_id] = $leve;
            }

            $v['level_name'] = $cache['leve_id'][$leve_id];
            $v['username'] = $cache['user_id'][$user_id]['username'];
            if (isset($cache['leve_id'][$leve_id])) {
                $v['level_name'] = $cache['leve_id'][$leve_id];
            } else {
                $v['level_name'] = $cache['leve_id'][0];

            }
            if (isset( $cache['user_id'][$agent_id]['username'])) {
                $v['agent_name'] = $cache['user_id'][$agent_id]['username'];

            } else {
                $v['agent_name'] = $cache['user_id'][0]['username'];

            }
            $data[$k] = $v;
        }
        return $data;
    }
    /**
     * 将层级id，管理员id，支付id等转换为名称
     *
     * @access public
     * @param Array $data   数据数组
     * @return $data        转换后结果
     */
    public function _agent_to_name($data)
    {
        if (empty($data)) {
            return $data;
        }

        // 初始化0的值

        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($data as $k => $v) {
            $agent_id = $v['agent_id'];
            if (empty($cache['user_id'][$agent_id])) {
                $user = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $user;
            }
            if (isset($cache['user_id'][$agent_id]['username'])) {
                $v['agent_name'] = $cache['user_id'][$agent_id]['username'];
            }else{
                $v['agent_name'] = $cache['user_id'][0]['username'];
            }
            $data[$k] = $v;
        }
        return $data;
    }
    /********************************************/
}
