<?php
/**
 * @模块   赔率model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */


if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


include_once(BASEPATH.'gc/model/Games_model.php');
class Rate_model extends MY_model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('public');
    }

    /******************公共方法*******************/
    /**
     * 获取赔率列表
     */
    public function getproducts($gid = 1)
    {

        // 获取赔率列表
        $this->select_db('private');
        $query = $this->get_list('id,gid,cid,tid,pid,name,code,rate,rate_min,rebate', 'games_products', array('gid' => $gid));
        if (empty($query)) {
            return $query;
        }
        $query = level_tree($query);
        $query = $query[0]['child'];
        foreach ($query as $k => $v) {
            $resu[$v['tid']]['balls'][] = $v;
        }
        $query = null;

        /* 把code=G_MAX_RATE的提取出来 */
        foreach ($resu as $k => $v) {
            foreach ($v['balls'] as $k1 => $v1) {
                if ($v1['pid'] == G_NO_PID &&
                    $v1['code'] == G_MAX_RATE) {
                    $lastd = $v['balls'][$k1];
                    unset($v['balls'][$k1]);
                    $resu[$k]['balls'] = $v['balls'];
                    $resu[$k]['id'] = $lastd['id'];
                    $resu[$k]['rate'] = $lastd['rate'];
                    $resu[$k]['rate_min'] = $lastd['rate_min'];
                    $resu[$k]['rebate'] = $lastd['rebate'];
                }
            }
        }

        // 六合彩添加玩法name
        $this->select_db('public');
        $games_types = $this->get_list('id,name,sname,pid',
            'games_types', array('gid'=>$gid));
        $games_types = array_make_key($games_types, 'id');
        $this->load->model('Games_model');
        $tree_resu = array();
        foreach ($resu as $k => $v) {
            /* 添加游戏玩法和名字 */
            if (empty($games_types[$k])) {
                continue;
            }
            $resu[$k]['name'] = $games_types[$k]['name'];
            $resu[$k]['sname'] = $games_types[$k]['sname'];
            
            /**
             * 把游戏赔率添加成一颗树
             * 1. 先找出它的所有父节点
             * 2. 如果父节点只有一个，那么我们在复制一个父节点
             * 3. 如果节点树不为0，则添加到$tree_resu结果里面
             * 4. 如果节点树为0：六合彩则添加两个父节点（3层），其他都为一级节点（）
             */
            $pid = $k;
            $tree_arr = array();
            while ($pid = $games_types[$pid]['pid']) {
                array_unshift($tree_arr,
                    $games_types[$pid]['name']);
                $ppp = $pid;
            }

            if (count($tree_arr) == 1) {
                $value = $tree_arr[0].'-'.$games_types[$ppp]['sname'];
                array_push($tree_arr, $value);
            }

            if (count($tree_arr) != 0) {
                $this->_tree($tree_resu, $tree_arr, $resu[$k]);
            } else {
                if ($gid == 3) {
                    array_unshift($tree_arr, $resu[$k]['name']);
                    array_unshift($tree_arr, $resu[$k]['name']);
                    $this->_tree($tree_resu, $tree_arr, $resu[$k]['balls']);
                } else {
                    $this->_tree($tree_resu, $tree_arr, $resu[$k]);
                }
            }
            /*********************************/
        }
        return $tree_resu;
    }

    /**
     * 根据赔率id获取彩种
     */
    public function get_game($pro_id, $tid = false)
    {
        $this->select_db('public');
        if ($tid) {
            $where = array('games_products.tid'=>$pro_id);
        } else {
            $where = array('games_products.id'=>$pro_id);
        }
        $condition = array(
            'join' => 'games',
            'on' => 'games_products.gid=b.id');
        $games = $this->get_one('b.name', 'games_products', $where, $condition);
        return $games['name'];
    }

    /**
     * 获取游戏赔率初始化列表
     */
    public function get_init_rate($id, $db = 'public')
    {
        $this->select_db($db);
        return $this->get_list('id,rate,rate_min,rebate', 'gc_games_products', array('gid ='=>$id));
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
    /*******************************************/





    /******************私有方法*******************/
    /**
     * $tree_arr：$games_type的父节点
     * $games_type：当前节点
     * $tree_resu：根据$tree_arr找到节点，然后把$games_type添加进去
     */
    private function _tree(&$tree_resu, &$tree_arr, $games_type)
    {
        $element = array_shift($tree_arr);
        if (empty($tree_resu[$element])) {
            $tree_resu[$element] = array();
        }
        if (count($tree_arr) > 0) {
            $this->_tree($tree_resu[$element], $tree_arr, $games_type);
        } else {
            if (!empty($games_type[0]) && is_array($games_type)) {
                foreach ($games_type as $key => $value) {
                    $tmp = $value['child'];
                    unset($value['child']);
                    $value['balls'] = $tmp;
                    $tree_resu[$element][] = $value;
                }
            } else {
                $tree_resu[$element][] = &$games_type;
            }
        }
    }
    
    /**
     * 对products数据进行层叠，就像一颗树
     */
    private function _products_tree($dz, &$auths)
    {
        if (empty($dz)) {
            $dz = array(array('id'=>0));
        }

        foreach ($dz as $k => $v) {
            foreach ($auths as $kk => $vv) {
                if ($v['id'] == $vv['pid']) {
                    $dz[$k]['child'][] = $vv;
                    unset($auths[$kk]);
                }
            }
        }
        if (isset($auths)) {
            foreach ($dz as $k => $v) {
                if (isset($v['child'])) {
                    $dz[$k]['child'] = $this->_products_tree($v['child'], $auths);
                }
            }
        }
        return $dz;
    }
    /*******************************************/
}
