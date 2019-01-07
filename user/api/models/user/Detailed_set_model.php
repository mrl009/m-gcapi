<?php
/**
 * @模块   会员中心／详细设定model
 * @模块   会员中心／奖金详情model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


include_once(BASEPATH.'gc/model/Games_model.php');
class Detailed_set_model extends Games_model
{
    public function __construct()
    {
        parent::__construct();
    }

    /******************公共方法*******************/
    /**
     * 详细设定列表
     */
    public function get_detailed_list($type)
    {
        /* 获取数据 */
        $this->select_db('public');
        $select    = 'a.id, a.name, a.pid, a.gid, 
                      b.max_money_play as max_play, 
                      b.max_money_stake as max_stake';
        $table     = 'games_types';
        $where     = array('tmp'=>$type);
        $condition = array('join'=>'games', 'on'=>'b.id = a.gid');
        $data = $this->get_list($select, $table, $where, $condition);
        if (empty($data)) {
            return false;
        }
        
        
        /**
         * 获取下注最高限额和玩法最高限额
         * 并删除没用的字段
         */
        $resu['vals'] = array_make_key($data, 'gid');
        foreach ($resu['vals'] as $k => $v) {
            unset($resu['vals'][$k]['name']);
            unset($resu['vals'][$k]['id']);
            unset($resu['vals'][$k]['pid']);
            unset($resu['vals'][$k]['gid']);
        }

        /* 组合玩法 */
        $data = level_tree($data)[0]['child'];
        $data = $this->_combina('', $data);
        $data = rtrim($data, '|');
        $resu['rows'] = explode('|', $data);

        /* 去除重复 */
        $repeat = array();
        foreach ($resu['rows'] as $key => $value) {
            $v = substr($value, 0, strrpos($value, ':'));
            $hash = crc32($v);
            if (empty($repeat[$hash])) {
                $repeat[$hash] = 1;
            } else {
                unset($resu['rows'][$key]);
            }
        }
        return $resu;
    }

    /**
     * 奖金详情列表
     */
    public function get_bonus_list($type)
    {
        // 获取游戏玩法
        $this->select_db('public');
        $select    = 'a.id, a.name, a.pid, a.gid';
        $table     = 'games_types';
        $where = [];
        $condition = array('join'=>'games', 'on'=>'b.id = a.gid');
        if ('s_' === substr($type,0,2)) {
            $s_type = $type;
            $type = substr($type,2);
        } else {
            $s_type = 's_' . $type;
        }
        $types = [$type,$s_type];
        $lottery_auth = $this->get_gcset(['lottery_auth']);
        $lottery_auth = explode(',',$lottery_auth['lottery_auth']);
        // 彩票权限：1官方 2博友 3电子游戏 4视讯
        if (!in_array('2',$lottery_auth)) {
            $types = array_values(array_diff($types, [$s_type]));
        }
        if (!in_array('1',$lottery_auth)) {
            $types = array_values(array_diff($types, [$type]));
        }
        if ($type == 'lhc' && in_array('2',$lottery_auth)) {
            $types = [$type];
        }
        if ($type == 'fc3d' || $type == 'pl3') {
            $condition ['wherein'] = ['b.sname'=>$types];
        } else {
            $condition ['wherein'] = ['tmp'=>$types];
        }
        $resu = $this->get_list($select, $table, $where, $condition);
        if (empty($resu)) {
            return false;
        }
        $wherein = array_make_key($resu, 'id');
        $resu['rows'] = level_tree($resu)[0]['child'];

        // 获取赔率和返水
        $this->select_db('private');
        $select    = 'id, tid, rate, rate_min, rebate';
        $table     = 'games_products';
        $where     = array('code'=>888);
        $condition = array('wherein'=>array('tid'=>array_keys($wherein)));
        $data = $this->get_list($select, $table, $where, $condition);
        if (!empty($data)) {
            $data = array_make_key($data, 'tid');
        }

        // 组合起来
        $resu['rows'] = $this->_add_rate($resu['rows'], $data);

        $this->select_db('private');
        $this->_add_balls($resu['rows']);

        foreach ($resu['rows'] as $key => $value) {
            if (empty($value['rebate'])) {
                $resu['rows'][$key]['rebate'] = 0;
            }
        }
        /* 去除重复 */
        $repeat = array();
        $temp = array();
        foreach ($resu['rows'] as $key => $value) {
            $hash = crc32($value['name']);
            if (empty($repeat[$hash])) {
                $repeat[$hash] = 1;
                array_push($temp, $value);
            }
        }

        return ['rows' => $temp];
    }

    /**
     * 赔率详情列表
     */
    public function get_rate_list($type)
    {
        // 获取游戏玩法
        $this->select_db('public');
        $select    = 'a.id, a.name, a.pid, a.gid';
        $table     = 'games_types';
        $where = [];
        $condition = array('join'=>'games', 'on'=>'b.id = a.gid');
        if ('s_' === substr($type,0,2)) {
            $s_type = $type;
            $type = substr($type,2);
        } else {
            $s_type = 's_' . $type;
        }
        $types = [$type,$s_type];
        $lottery_auth = $this->get_gcset(['lottery_auth']);
        $lottery_auth = explode(',',$lottery_auth['lottery_auth']);
        // 彩票权限：1官方 2博友 3电子游戏 4视讯
        if (!in_array('2',$lottery_auth)) {
            $types = array_values(array_diff($types, [$s_type]));
        }
        if (!in_array('1',$lottery_auth)) {
            $types = array_values(array_diff($types, [$type]));
        }
        if ($type == 'lhc' && in_array('2',$lottery_auth)) {
            $types = [$type];
        }
        if ($type == 'fc3d' || $type == 'pl3') {
            $condition ['wherein'] = ['b.sname'=>$types];
        } else {
            $condition ['wherein'] = ['tmp'=>$types];
        }
        $resu = $this->get_list($select, $table, $where, $condition);
        if (empty($resu)) {
            return false;
        }
        $resu['rows'] = level_tree($resu)[0]['child'];
        /* 去除重复 */
        $repeat = array();
        $temp = array();
        foreach ($resu['rows'] as $key => $value) {
            $hash = crc32($value['name']);
            if (empty($repeat[$hash])) {
                $repeat[$hash] = 1;
                array_push($temp, $value);
            }
        }
        $gids = array_values(array_unique(array_column($temp,'gid')));
        $resu['rows'] = $temp;
        //echo json_encode($resu['rows'],JSON_UNESCAPED_UNICODE);die;

        // 获取赔率和返水
        $this->select_db('private');
        $select    = 'id, tid, name, rate, rate_min, rebate';
        $table     = 'games_products';
        $where     = ['rate <> '=> 0];
        $data = $this->db->select($select)->from($table)->where($where)->where_in('gid',$gids)->get()->result_array();

        if (!empty($data)) {
            $this->_combine_rate($data);
        }
        // 组合起来
        $this->_combine_type_rate($resu['rows'], $data);

        return $resu;
    }

    private function _combine_type_rate(&$types,$data)
    {
        foreach ($types as $k => $v) {
            if (!empty($v['child'])) {
                $types[$k]['child'] = $this->_combine_type_rate($v['child'], $data);
            } else {
                if (!empty($data[$v['id']])) {
                    if (count($data[$v['id']]) == 1) {
                        $types[$k]['rate'] = $data[$v['id']][0]['rate'];
                        $types[$k]['rate_min'] = $data[$v['id']][0]['rate_min'];
                        $types[$k]['rebate'] = $data[$v['id']][0]['rebate'];
                    } else {
                        $types[$k]['child'] = $data[$v['id']];
                    }

                }
            }
        }
        return $types;
    }

    /**
     * 获取筛选选项
     */
    public function get_opts()
    {
        $this->select_db('public');
        $select    = 'id, type as value, tname as label, tmp';
        $table     = 'games';
        $where     = array();
        //$condition = array('groupby'=>array('type'));
        $condition = array('groupby'=>array('tmp'));
        $resu['rows'] = $this->get_list($select, $table, $where, $condition);
        $resu['rows'] = $this->format_name($resu['rows']);
        return $resu;
    }
    /********************************************/






    /******************私有方法*******************/

    /**
     * 游戏名添加私彩国彩标准
     * @param $data
     * @return array
     */
    private function format_name($data)
    {
        $gc = explode(',', GC);
        $sc = explode(',', SC);
        $lottery_auth = $this->get_gcset(['lottery_auth']);
        $lottery_auth = $lottery_auth['lottery_auth'];
        // 彩票权限：1官方 2博友 3电子游戏 4视讯
        foreach ($data as $k => $v) {
            if ($lottery_auth === '1') {
                if (in_array($v['id'], $sc)) {
                    unset($data[$k]);
                }
            } elseif ($lottery_auth === '2') {
                if (in_array($v['id'], $gc) && $v['id'] != '3' && $v['id'] != 4) {
                    unset($data[$k]);
                }
            } else {
                if (in_array($v['id'], $gc) && $v['id'] != '3' && $v['id'] != 4) {
                    $data[$k]['label'] = $data[$k]['label']. '[官]';
                } else if (in_array($v['id'], $sc)) {
                    $data[$k]['label'] = $data[$k]['label']. '[私]';
                }
            }

        }
        return $data;
    }

    /**
     * 把层叠树进行结合
     * 例如：根name-子name-子name（字符串）
     */
    private function _combina($str, $data)
    {
        $resu = '';
        foreach ($data as $k => $v) {
            if (empty($v['child'])) {
                $resu .= $str.$v['name'].':'.$v['gid'].'|';
            } else {
                $resu .= $this->_combina($str.$v['name'].'-', $v['child']);
            }
        }
        return $resu;
    }

    private function _combine_rate(&$data)
    {
        $arr = [];
        foreach ($data as $item) {
            if (isset($arr[$item['tid']])) {
                $arr[$item['tid']][] = $item;
            } else {
                $arr[$item['tid']] = [$item];
            }
        }
        $data = $arr;
    }


    /**
     * 把赔率和返回添加到玩法中
     */
    private function _add_rate($resu, $data)
    {
        foreach ($resu as $k => $v) {
            if (!empty($v['child'])) {
                $resu[$k]['child'] = $this->_add_rate($v['child'], $data);
            } else {
                if (!empty($data[$v['id']])) {
                    $resu[$k]['rate'] = $data[$v['id']]['rate'];
                    $resu[$k]['rate_min'] = $data[$v['id']]['rate_min'];
                    $resu[$k]['rebate'] = $data[$v['id']]['rebate'];
                }
            }
        }
        return $resu;
    }


    private function _add_balls(&$resu)
    {
        foreach ($resu as $key => $value) {
            if (empty($value['child']) && empty($value['rate'])) {
                $where = array(
                    'tid'=>$value['id'],
                    'code != '.G_PRO_MENU.' and code !='=>G_MAX_RATE);
                $resu[$key]['child'] = $this->get_list('id,name,rate,rate_min,rebate', 'games_products', $where);
            }
            if (!empty($value['child'])) {
                $this->_add_balls($resu[$key]['child']);
            }
        }
    }
    /********************************************/
}
