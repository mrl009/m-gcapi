<?php
/**
 * @模块   开奖结果
 * @版本   Version 1.0.0
 * @日期   2017-04-05
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Open_result extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (isset($_GET['t'])) {@wlog(APPPATH.'logs/model_res_'.date('Ym').'.log', __CLASS__.'::'.__METHOD__);}

        $this->load->model('MY_Model', 'open');
        $this->load->model('Open_result_model', 'orm');
        $this->open->select_db('public');
    }


    /**
     * 根据Gid获取该彩种的前20期的开奖详情列表
     */
    public function index()
    {
        $gid = (int)$this->G('gid');
        $gid = empty($gid) ? 1 : $gid;
        if ($gid > 50) {
            $gid = gid_tran($gid);
        }
        if (!in_array($gid, explode(',', ZKC))) {
            $data['rows'] = $this->get_kc($gid);
        } else {
            $data['rows'] = $this->get_zkc($gid);
        }
        if (empty($data['rows'])) {
            $this->return_json(E_DATA_EMPTY, '无数据！');
        }
        $data['top_issue'] = (string)($data['rows'][0]['kj_issue'] + 1);
        if ($gid == 3 || $gid == 4) {
            $data['rows'] = $this->orm->for_sx_color($data['rows']);
        } elseif ($gid == 24 || $gid == 25) {
            foreach ($data['rows'] as $kk => $vv) {
                if (empty($vv['code_str'])) {
                    $re = $this->orm->get_28_hecl(explode(',', $vv['number']));
                    $data['rows'][$kk]['code_str'] = (string)$re['code_str'];
                } else {
                    $re = $this->orm->get_28_hecl('', $data['rows'][$kk]['code_str']);
                }
                $data['rows'][$kk]['color'] = $re['color'];
            }
        } elseif ($gid == 26 || $gid == 27 || $gid == 29 || $gid == 30) {
            foreach ($data['rows'] as $kk => $vv) {
                $num_arr = explode(',', $vv['number']);
                $data['rows'][$kk]['number'] = implode(',', array_map(function ($v) {
                    return str_pad($v, 2, '0', STR_PAD_LEFT);//六合彩和北京PK10号码前面补零
                }, $num_arr));
            }
        }
        $this->return_json(OK, $data);
    }

    /**
     * 获取开彩数据
     * @param $gid
     * @return mixed
     */
    private function get_kc($gid)
    {
        $time = strtotime($this->G('time')) ? $this->G('time') : 0;
        $rows = $this->G('rows') ? $this->G('rows') : 300;
        // @modify 2018-04-09 改用redis
        $data = [];
        $r_gid = $gid > 50 ? gid_tran($gid) : $gid;
        $t_data = [];
        if (empty($time)) {
            $t_data = $this->open->redisP_hgetall('gcopen:' . $r_gid);
        }
        if (!empty($t_data)) {
            $keys = array_keys($t_data);
            rsort($keys);
            if($this->G('kithe')){
                    $one_kithe = $this->G('kithe');
                    $i = json_decode($t_data[$one_kithe], true);
                    $t = [
                        'gid' => $gid,
                        'kj_issue' => $key,
                        'kj_time' => isset($i[1]) ? $i[1] : '',
                        'number' => isset($i[0]) ? $i[0] : ''
                    ];
                    array_push($data, $t);
                    return $data;
            }
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'gid' => $gid,
                    'kj_issue' => $key,
                    'kj_time' => isset($i[1]) ? $i[1] : '',
                    'number' => isset($i[0]) ? $i[0] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $where = array(
                'gid' => $gid,
                'kithe' => (int)$this->G('kithe'),
            );
            $where['status >='] = 2;
            $where['status <='] = 3;

            if (!empty($time)) {
                $where['open_time >='] = $time . ' 00:00:00';
                $where['open_time <='] = $time . ' 23:59:59';
            }
            $condition = [
                'orderby' => array('id' => 'desc'),
                'limit' => $rows
            ];
            $field = 'id,gid,kithe as kj_issue,open_time as kj_time,number,status as kj_status,code_kithe,code_str';
            $data = $this->open->get_list($field, 'open_num', $where, $condition);
        }
        //.不管排序没 重新排序
        key_sort($data,'kj_issue');
        return $data;
    }

    /**
     * 获取自开彩数据
     * @param $gid
     * @return mixed
     */
    private function get_zkc($gid)
    {
        $time = strtotime($this->G('time')) ? $this->G('time') : 0;
        $rows = $this->G('rows') ? $this->G('rows') : 300;
        // @modify 2018-04-09 改用redis
        $data = [];
        $r_gid = $gid > 50 ? gid_tran($gid) : $gid;
        $t_data = [];
        if (empty($time)) {
            $t_data = $this->open->redisP_hgetall('gcopen:' . $this->_sn . ':' . $r_gid);
        }
        if (!empty($t_data)) {
            $keys = array_keys($t_data);
            rsort($keys);
            if($this->G('kithe')){
                    $one_kithe = $this->G('kithe');
                    $i = json_decode($t_data[$one_kithe], true);
                    $t = [
                        'gid' => $gid,
                        'kj_issue' => $key,
                        'kj_time' => isset($i[1]) ? $i[1] : '',
                        'number' => isset($i[0]) ? $i[0] : ''
                    ];
                    array_push($data, $t);
                    return $data;
            }
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'gid' => $gid,
                    'kj_issue' => $key,
                    'kj_time' => isset($i[1]) ? $i[1] : '',
                    'number' => isset($i[0]) ? $i[0] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $where = array(
                'gid' => $gid,
                'issue' => (int)$this->G('kithe'),
            );
            $where['status >='] = 2;
            $where['status <='] = 3;
            $time = strtotime($this->G('time')) ? strtotime($this->G('time')) : 0;
            if (!empty($time)) {
                $where['updated >='] = $time;
                $where['updated <='] = $time + 24 * 3600;
            }
            $condition = [
                'orderby' => array('id' => 'desc'),
                'limit' => $rows
            ];
            $this->open->select_db('private');
            $field = 'id,gid,issue as kj_issue,updated as kj_time,lottery as number,status as kj_status';
            $data = $this->open->get_list($field, 'bet_settlement', $where, $condition);
            $this->open->select_db('public');
            if (!empty($data)) {
                foreach ($data as $k => $v) {
                    $data[$k]['kj_time'] = date('Y-m-d H:i:s', $v['kj_time']);
                }
            }
        }
        //.不管排序没 重新排序
        key_sort($data,'kj_issue');
        return $data;
    }

    // 测试开奖redis @todo delete after test
    public function test()
    {
        if (isset($_GET['t'])) {@wlog(APPPATH.'logs/model_res_'.date('Ym').'.log', __CLASS__.'::'.__METHOD__);}
        $gid = $this->G('gid');
        if($gid>50){
            $gid = gid_tran($gid);
        }
        $data = [];
        if (in_array($gid, explode(',', ZKC))) {
            $k = 'gcopen:' . $this->_sn . ':' . $gid;
        } else {
            $k = 'gcopen:' . $gid;
        }
        $t_data = null;
        if (isset($_GET['redis'])) {
            $t_data = $this->open->redisP_hgetall($k);
        }
        if ($t_data) {
            $keys = array_keys($t_data);
            rsort($keys);
            foreach ($keys as $key) {
                $i = json_decode($t_data[$key], true);
                $t = [
                    'gid' => $gid,
                    'kj_issue' => $key,
                    'kj_time' => isset($i[1]) ? $i[1] : '',
                    'number' => isset($i[0]) ? $i[0] : ''
                ];
                array_push($data, $t);
            }
        } else {
            $where = array(
                'gid' => $gid,
                'kithe' => (int)$this->G('kithe'),
            );
            $where['status >='] = 2;
            $where['status <='] = 3;
            $time = strtotime($this->G('time')) ? $this->G('time') : 0;
            if (!empty($time)) {
                $where['open_time >='] = $time . ' 00:00:00';
                $where['open_time <='] = $time . ' 23:59:59';
            }
            $condition = [
                'orderby' => array('id' => 'desc'),
                'limit' => 20
            ];
            $field = 'id,gid,kithe as kj_issue,open_time as kj_time,number,status as kj_status,code_kithe,code_str';
            $data = $this->open->get_list($field, 'open_num', $where, $condition);
        }

        if (isset($_GET['t'])) {@wlog(APPPATH.'logs/model_res_'.date('Ym').'.log', __CLASS__.'::'.__METHOD__.' end');}
        $this->return_json(OK, ['k' => $k, 'data' => $data]);
    }
}
