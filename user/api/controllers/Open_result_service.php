<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Open_result_service extends GC_Controller
{
    private $secret = 's#d%f^l@';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }

    /**
     * 提供前50的最近开奖结果详情
     */
    public function index()
    {
        $random_str = $this->G('sn') ? trim($this->G('sn')) : '';
        $key = $this->G('key') ? trim($this->G('key')) : '';
        $gid = $this->G('code') ? (int)$this->G('code') : 27; // 默认27
        $gid > 50 && $gid = gid_tran($gid); // gid转换
        $b = strtoupper(md5($this->secret . $random_str)) === $key;
        if (!$b || empty($random_str) || empty($key)) {
            $this->return_json(E_ARGS, '参数错误,请连联系开发人员');
        }
        $sn = $this->verify_key($random_str, $key, $gid);
        $this->core->init($sn);
        $this->verify_request($gid);
        //获取数据
        if (!in_array($gid, explode(',', ZKC))) {
            $data = $this->get_kc_result($gid);
        } else {
            $data = $this->get_zkc_result($sn, $gid);
        }
        foreach ($data as $key => $value) {
            $num_arr = explode(',', $value['number']);
            $data[$key]['number'] = implode(',', array_map(function ($v) {
                return str_pad($v, 2, '0', STR_PAD_LEFT);
            }, $num_arr));
        }
        $this->return_json(OK, $data);
    }

    /**
     * 验证秘钥
     * @param $random_str
     * @param $key
     * @param $gid
     * @return string 站点sn
     */
    private function verify_key($random_str, $key, $gid)
    {
        // @todo 增加ip过滤功能
        $this->core->select_db('public');
        $data = $this->core->get_one('sn,code', 'open_key', ['random_str' => $random_str, 'key' => $key]);
        if (empty($data) || ($data['code'] !== '*' && !in_array($gid, explode(',', $data['code'])))) {
            $this->return_json(E_ARGS, '无效的参数,请连联系开发人员');
        }
        return $data['sn'];
    }

    /**
     * 验证请求，不超过1秒一次
     * @param $gid
     */
    private function verify_request($gid)
    {
        $this->core->redis_select(REDIS_DB);
        $keys = 'open_result_request:' . $gid;
        $r = $this->core->redis_setnx($keys, 1);
        if (!$r) {
            $this->return_json(E_ARGS, '接口请求过于频繁');
        }
        $this->core->redis_expire($keys, 1);
        $this->core->redisP_select(REDIS_PUBLIC);
    }

    /**
     * 获取自开彩开奖结果
     * @param $sn
     * @param $gid
     * @return array
     */
    private function get_zkc_result($sn, $gid)
    {
        $data = $t_data = [];
        $t_data = $this->core->redisP_hgetall('gcopen:' . $sn . ':' . $gid);
        if ($t_data) {
            $keys = array_keys($t_data);
            rsort($keys);
            count($keys) > 50 && $keys = array_slice($keys, 0, 50);
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'issue' => $key,
                    'number' => isset($i[0]) ? $i[0] : '',
                    'open_time' => isset($i[1]) ? $i[1] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $condition = [
                'wherein' => ['status' => array(2, 3)],
                'orderby' => ['id' => 'desc'],
                'limit' => 50
            ];
            $this->core->select_db('private');
            $field = 'issue,updated as open_time,lottery as number';
            $data = $this->core->get_list($field, 'bet_settlement', ['gid' => $gid], $condition);
            $this->core->select_db('public');
            if (!empty($data)) {
                foreach ($data as $k => $v) {
                    $data[$k]['open_time'] = date('Y-m-d H:i:s', $v['open_time']);
                }
            }
        }
        return $data;
    }

    /**
     * 获取开奖结果
     * @param $gid
     * @return array
     */
    private function get_kc_result($gid)
    {
        $data = $t_data = [];
        $t_data = $this->core->redisP_hgetall('gcopen:' . $gid);
        if (!empty($t_data)) {
            $keys = array_keys($t_data);
            rsort($keys);
            count($keys) > 50 && $keys = array_slice($keys, 0, 50);
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'issue' => $key,
                    'number' => isset($i[0]) ? $i[0] : '',
                    'open_time' => isset($i[1]) ? $i[1] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $condition = [
                'wherein' => ['status' => array(2, 3)],
                'orderby' => ['id' => 'desc'],
                'limit' => 50
            ];
            $field = 'kithe as issue,open_time,number';
            $this->core->select_db('public');
            $data = $this->core->get_list($field, 'open_num', ['gid' => $gid], $condition);
        }
        return $data;
    }
}
