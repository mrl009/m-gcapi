<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Open_time extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Open_time_model');
        $this->load->model('Open_result_model', 'orm');
    }

    /**
     * 游戏列表、期数、最新开奖结果三合一列表
     * @param $use 根据参数判断需要返回的请求结果：main-主页，kj-开奖列表页，all-右上角的全部彩种页面，空或不传-购彩列表页
     * @param $gid  对应彩票类型id  $hot 是否热门   $type 彩票类型
     * @return array
     */
    public function get_games_list()
    {
        $use = $this->G('use');
        $ctg = $this->G('ctg');
        $all_list = array();
        //首页
        if ($use=='main') {
            $all_list = $this->orm->get_main_games();
            $this->return_json(OK, $all_list);
        }
        //全部彩票
        elseif ($use=='all') {
            $all_list = $this->orm->get_all_games();
            $this->return_json(OK, array($all_list));
        }
        $type = $this->G('type');
        $type_list = array_keys($this->orm->type_list);
        if (!empty($type) && !in_array($type, $type_list)) {
            $this->return_json(E_ARGS, '参数错误！');
        }
        if (!empty($ctg) && !in_array($ctg, ['sc','gc','sx','dz'])) {
            $this->return_json(E_ARGS, '参数错误！');
        }
        $gid = (int)$this->G('gid');
        $hot = (int)$this->G('hot');
        $new_hot = (int)$this->G('new_hot');

        $where['uid'] = empty($this->user['id'])?0:$this->user['id'];
        $result_list = $this->orm->get_allGame_new_result($type, $gid, $hot, $new_hot, $ctg);//游戏列表与对应的最新开奖结果二合一列表
        if (empty($result_list)) {
            $this->return_json(E_DATA_EMPTY, '结果为空！');
        }


        //开奖
        if ($use=='kj') {
            foreach ($result_list as $key => $value) {
                if ($value['id'] > 50 && $value['id'] != 73 && $value['id'] != 74) {
                    unset($result_list[$key]);
                    continue;
                }
                if ($value['id'] == 3 || $value['id'] == 4) {
                    $sc = $this->orm->get_sx_color(explode(',', $value['number']));
                    $result_list[$key]['color'] = $sc['color'];
                    $result_list[$key]['shengxiao'] = $sc['shengxiao'];
                    $result_list[$key]['number']  = $sc['number'];
                } elseif ($value['id'] == 24 || $value['id'] == 25) {
                    if (empty($value['code_str'])) {
                        $re = $this->orm->get_28_hecl(explode(',', $value['number']));
                        $result_list[$key]['code_str'] = (string)$re['code_str'];
                    } else {
                        $re = $this->orm->get_28_hecl('', $result_list[$key]['code_str']);
                    }
                    $result_list[$key]['color'] = $re['color'];
                } elseif ($value['id'] == 26 || $value['id'] == 27 || $value['id'] == 29 || $value['id'] == 30) {//PK10号码补零
                    $num_arr = explode(',', $value['number']);
                    $result_list[$key]['number']= implode(',', array_map(function ($v) {
                        return str_pad($v, 2, '0', STR_PAD_LEFT);
                    }, $num_arr));
                }
                $result_list[$key]['gid'] = $value['id'];
            }

            $result_list = $this->orm->guolv($result_list);
            $this->return_json(OK, array_values($result_list));
        }

        //购彩
		$one_fav = array();
        if (!empty($where['uid'])) {//如果已登录，则判断是否有收藏
            $this->orm->select_db('private');
            $one_fav = $this->orm->get_one('favorite', 'user_detail', $where, array());
            if (!empty($one_fav['favorite'])) {
                $one_fav = explode(',', $one_fav['favorite']);
            }
        }
        $gid_arr = array(3,4,26,27,29,30,76,77);
        foreach ($result_list as $k2 => $v2) {
            if ($v2['id'] == 3 || $v2['id'] == 4) {//六合彩购彩页面加颜色
                $sc = $this->orm->get_sx_color(explode(',', $v2['number']));
                $result_list[$k2]['color'] = $sc['color'];
            }
            $issue_list[$k2] = $this->Open_time_model->get_kithe((int)$v2['id']);
            if (in_array($v2['id'],$gid_arr)) {
                $num_arr = explode(',', $v2['number']);
                $result_list[$k2]['number']= implode(',', array_map(function ($v) {
                    return str_pad($v, 2, '0', STR_PAD_LEFT);//六合彩，PK10号码前面补零
                }, $num_arr));
            }
            //此处删除了旧版本的开奖异常检查
            $all_list[$k2] = array_merge((array)$result_list[$k2], (array)$issue_list[$k2]);
            $all_list[$k2]['favorite'] = 0;
            if (!empty($one_fav) && !empty($all_list[$k2]['gid'])) {
                foreach ($one_fav as $v3) {
                    if ($all_list[$k2]['gid']==$v3) {
                        $all_list[$k2]['favorite']=1;
                        break;//已收藏的彩种
                    }
                }
            }
            if (!empty($all_list[$k2]['kj_time']) && !empty($all_list[$k2]['kithe']) && !empty($all_list[$k2]['kj_issue'])){
				if (($all_list[$k2]['kithe']-$all_list[$k2]['kj_issue'])>1) {
					$this->Open_time_model->check_issue($all_list[$k2]);//验证期数，如果有半小时以上还没开奖，则作一次记录
				}
            }
        }
        $this->return_json(OK, $all_list);
    }


    /**
     * 获取追号需要的期数和开奖时间列表
     */
    public function get_zhkithe_list()
    {
        $gid = (int)$this->G('gid');
        $data = $this->Open_time_model->get_zhkithe_list($gid);
        $data?$this->return_json(OK, $data):$this->return_json(E_OP_FAIL, '操作失败！');
    }


    //获取游戏类型列表
    public function get_type_list()
    {
        $ctg = $this->G('ctg');
        empty($ctg)?$ctg='gc':$ctg;
        $array = $this->orm->get_type_list($ctg);
        $this->return_json(OK, $array);
    }


    /*public function get_new_hot()
    {
        $array = $this->orm->get_new_hot();
        $this->return_json(OK, $array);
    }*/

    /*public function test()
	{
		$aa = $this->Open_time_model->get_kithe(6,1);
	}*/

    /**
     * 获取全彩种期数列表 带参数则获取单个彩种期数列表
     * 暂停使用
     */
    public function get_issue($gid = '', $sort='', $order='')
    {
        /* 获取单个期数数据 */
        $gid = empty($gid)?(int)$this->G('gid'):$gid;
        if (!empty($gid) && is_numeric($gid) && $gid > 0) {
            $res = $this->Open_time_model->get_kithe($gid);
            if ($res) {
                $this->return_json(OK, $res);
                exit();
            } else {
                $this->return_json(E_DATA_EMPTY);
            }
        }
        /* 获取多个期数数据 */
        $sort = empty($sort)?$this->G('sort'):$sort;
        $order = empty($order)?$this->G('order'):$order;
        $page = array(
            'sort'=>$sort?$sort:'id',
            'order'=>$order);
        $gid_arr =  $this->orm->get_games('id', $page);
        if (!empty($gid_arr)) {
            $gid_arr = array_column($gid_arr, 'id');
            foreach ($gid_arr as $key=>$v) {
                $issue_list[$key] = $this->Open_time_model->get_kithe((int)$v);
            }
            $this->return_json(OK, $issue_list);
        }
        $this->return_json(E_DATA_EMPTY);
    }

    /**
     * 定时记录开期期数，每60秒去更新
     * 暂停使用
     */
    private function timer_record_log()
    {
        header('Refresh: 60; http://127.0.0.1/www/gcapi/user/index.php/openTime/timer_record_log');
        $data[] = array();
        for ($i=1; $i < 21 ; $i++) {
            $pid = $i;
            if (isset($pid) && is_numeric($pid) && $pid > 0) {
                $res = $this->Open_time_model->get_kithe($pid);
                $this->Open_time_model->add_temp_search_log($res);
                $res['kithe_time_stamp'] = date('Y-m-d H:i:s', $res['kithe_time_stamp']);
                $res['current_time_stamp'] = date('Y-m-d H:i:s', $res['current_time_stamp']);
                $data[] = $res;
            }
        }
        $this->return_json(OK, $data);
    }

    /**
     * 定时判断是否需要更新日期和期数
     * 暂停使用
     */
    private function timer_update_kithe()
    {
        $now_stamp = $_SERVER['REQUEST_TIME'];
        $date_info = getdate($now_stamp);
        $second = mktime(0, 0, 0, $date_info['mon'], $date_info['mday']+1, $date_info['year']) - $now_stamp;
        header('Refresh: '.($second+10).'; http://127.0.0.1/www/gcapi/user/index.php/openTime/timer_record_log');
        $res = $this->Open_time_model->_update_open_time();
        unset($res[0]);
        foreach ($res as $k => $v) {
            if ($v['pid'] != 3) {
                unset($res[$k]['open_time']);
                unset($res[$k]['up_close_time']);
                unset($res[$k]['stop_time']);
                unset($res[$k]['bu0_num']);
                unset($res[$k]['current_kithe']);
            } else {
                unset($res[$k]['up_close_time']);
                unset($res[$k]['stop_time']);
                unset($res[$k]['bu0_num']);
            }
        }
        $this->return_json(OK, $res);
    }

    /*public function set_opentime()
    {//录入数据用
        //$now = $_SERVER['REQUEST_TIME']+12*3600;
        $this->load->database();
        $op = array('open_time'=>'');
        $a=$b=0;
        for($i=0;$i<1440;$i=$i+10) {
           if($i>360 && $i<541)
            {
                $a++;
            }
            else
            {
                $op['open_time'] .=$i.',';
                $b++;
            }
        }
        $op['open_time'] = substr($op['open_time'], 0, -1);//去掉最后一个逗号
        $array = explode(',',$op['open_time']);
        $data = $this->db->where('gid',4)->update('gc_open_time',$op);
    }*/
}

/* end file */
