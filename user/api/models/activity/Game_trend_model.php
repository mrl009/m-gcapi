<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/4/24
 * Time: 17:41
 */


if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Game_trend_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 走势图
     */
    public function game_trend_list($gid, $start, $end)
    {
        if (!in_array($gid, explode(',', ZKC))) {
            $result = $this->get_kc_trend($gid, $start, $end);
        } else {
            $result = $this->get_zkc_trend($gid, $start, $end);
        }
        $rs = array_reverse($result);
        return $rs;
    }

    protected function sequence_sort($arrayList, $field, $type)
    {
        $arrSort = array();
        foreach ($arrayList as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($type), $arrayList);
        return $arrayList;

    }

    /**
     * 开彩走势
     * @param $gid
     * @param $start
     * @param $end
     * @return array|mixed
     */
    private function get_kc_trend($gid, $start, $end)
    {
        $data = [];
        $t_data = $this->redisP_hgetall('gcopen:' . $gid);
        if (!empty($t_data)) {
            $keys = array_keys($t_data);
            rsort($keys);
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'gid' => $gid,
                    'kithe' => $key,
                    'open_time' => isset($i[1]) ? $i[1] : '',
                    'number' => isset($i[0]) ? $i[0] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $where = [
                'gid' => $gid,
                'actual_time >' => $start,
                'actual_time <' => $end,
            ];
            $condition = [
                'limit' => 50,
                'orderby' => array('kithe' => 'desc')
            ];
            $this->select_db('public');
            $data = $this->get_list('kithe,number,open_time', 'open_num', $where, $condition);
        }
        return $data;
    }

    /**
     * 自开彩走势
     * @param $gid
     * @param $start
     * @param $end
     * @return array|mixed
     */
    private function get_zkc_trend($gid, $start, $end)
    {
        $data = [];
        $t_data = $this->redisP_hgetall('gcopen:' . $this->sn . ':' . $gid);
        if (!empty($t_data)) {
            $keys = array_keys($t_data);
            rsort($keys);
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'gid' => $gid,
                    'kithe' => $key,
                    'open_time' => isset($i[1]) ? $i[1] : '',
                    'number' => isset($i[0]) ? $i[0] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $where = [
                'gid' => $gid,
                'updated >' => strtotime($start),
                'updated <' => strtotime($end) + 24*3600,
            ];
            $condition = [
                'limit' => 50,
                'orderby' => array('issue' => 'desc')
            ];
            $field = 'issue as kithe,lottery as number,updated as open_time';
            $data = $this->get_list($field, 'bet_settlement', $where, $condition);
            if (!empty($data)) {
                foreach ($data as $k => $v) {
                    $data[$k]['open_time'] = date('Y-m-d H:i:s', $v['open_time']);
                }
            }
        }
        return $data;
    }
}