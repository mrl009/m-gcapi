<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Open_time_model extends MY_Model
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    public $all_is_open = 0;

    /**
     * 根据gid获取相应的期数
     * @param $gid 游戏ID
	 * @param $is_order 是否从订单函数来的请求，如果为1，则从数据库取出开奖时间
     * @return bool or array
     * super
     */
    public function get_kithe($gid,$is_order=0)
    {
        /* 获取期数数-据 */
        $table = 'open_time';
        $data_json = $this->redisP_hget('open_time', $gid);
        $data = json_decode($data_json, true);
		$this->select_db('public');
        if (empty($data)) {
            $data = $this->get_one('*', $table, array('gid' => $gid));
            $this->redisP_hset('open_time', $gid, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        if (!$data) {
            return false;
        }

        $res['kithe'] = 0;              //当前的期数
        $res['is_open'] = 0;            //是否封盘
        $res['every_time'] = $data['every_time']; //每期开奖时间间隔
        $res['up_close_time'] = 0;      //当期结束后距离开奖的缓冲时间
        $res['kithe_time_stamp'] = 0;   //本期结束的时间戳
        $res['current_time_stamp'] = 0; //现在时间戳
        $res['kithe_time_second'] =  0; //距离本期结束的秒数(kithe_time_stamp - current_time_stamp)

        switch ($data['type']) {
            case 1:
                /* 时时彩：重庆，新疆，天津... gid为6-28的彩种*/
                $res = $this->typeone($data, $gid, $res);
                break;
            case 2:
                /* 福彩3D，排列3 */
                $res = $this->typetwo($data, $gid, $res);
                break;
            case 3:
                /* 六合彩 */
                $res = $this->typethree($data, $gid, $res);
                break;
            default:
                break;
        }
        $res['kithe_time_second'] = $res['kithe_time_stamp'] - $res['current_time_stamp'];
        if ($res['kithe_time_second']<=0) {
            $res['kithe_time_second']=0;
            $res['is_open'] = 0;
        }
		//$condition['orderby']['id'] = 'desc';
        if($gid>50){
            $gid = gid_tran($gid);
        }
        $result = $this->get_one('number,id','open_num',array('gid'=>$gid,'code_kithe'=>$gid.'_'.$res['kithe']),array());
		//开奖异常检测1：提前開出了結果
        if(!empty($result['number'])){
			$res['is_open'] = 0;
            //.记录开奖内容和服务器时间 2010/10/20
            wlog(APPPATH.'logs/bets/'.$gid.'_'.date('Ym').'.log', $res['kithe'].':'.$_SERVER['REQUEST_TIME'].':'.json_encode($res));
		}

        //来自order函数的请求，kithe_time_stamp改为没有减去缓冲时间的开奖时间
        if ($is_order===1){
        	$kt = $this->get_one('open_time', 'open_num', array('gid' => $gid, 'kithe' => $res['kithe']));
        	if (!empty($kt['open_time'])){
				$res['kithe_time_stamp'] = strtotime($kt['open_time']);
			} else {
				$res['kithe_time_stamp'] = $res['kithe_time_stamp'] + $res['up_close_time'];
			}
		}
        return $res;
    }

    /**
     * 获取追号需要的期数和开奖时间列表
     * @param $gid 游戏id
     * @param $is_ko 是否用array(kithe=>open_time)的形式返回数组,false NO, true YES;
     * @return bool or array
     */
    public function get_zhkithe_list($gid, $is_ko = false)
    {
        if($gid>50){
            $gid = gid_tran($gid);
        }
        $type_list = $id_list = array();
        $this->select_db('public_w');
        //获取彩种列表
        $list = $this->get_list('id,type', 'games', array(), array());
        if (empty($list)) {
            return false;
        }
        foreach ($list as $key => $value) {
            //六合彩，PC蛋蛋不参与追号
            if ($value['type']!='lhc' && $value['type']!='pcdd') {
                $id_list[] = $value;
                $type_list[$value['id']] = $value['type'];
            }
        }
        unset($list);
        $id_list = array_column($id_list, 'id');
        if (!in_array($gid, $id_list)) {//参数验证
            return false;
        }
        $limit = 100;
        if ($type_list[$gid]=='yb') {//福彩3D和排列三最多追4期
            $limit = 4;
        } else {
            $where['open_time <='] = date('Y-m-d').' 23:59:59';//追号不跨天
        }
        $res = $this->get_kithe($gid);
        if(empty($res['kithe'])){
        	return '';
		}
        $where['kithe >='] = $res['kithe'];
        $where['gid'] = $gid;
        $this->db->select('kithe,open_time');
        $this->db->order_by('id');
        $data =$this->db->get_where('gc_open_num', $where, $limit)->result_array();
        if ($is_ko) {   //用array(kithe=>open_time)的形式返回数组
            $data2 = [];
            foreach ($data as $v) {
                $data2[$v['kithe']] = $v['open_time'];
            }
            return $data2;
        }
        return $data;
    }

    /** 
     * 时时彩、11选5、快3、PC蛋蛋、PK10
     * @param $data 对应gc_open_time数据
     * @param $gid  对应彩票类型id
     * @return array 返回数组
     */
    private function typeone($data, $gid, $res)
    {

        $res['gid'] = $gid;
        $kithenum = explode(',', $data['kithe_style']);
        $now_stamp = $_SERVER['REQUEST_TIME'];
        $jiange = $is_chunjie  = 0;//每一期的时间间隔，是否春节
        if (!empty($kithenum[2])) {//pcdd 根据北京快乐8的开奖结果来计算开奖的彩种
            $is_chunjie = get_chunjie($now_stamp);
            if ($is_chunjie===false) {
                $res['is_open'] = 0;
                return $res;
            }
        }
        $today_stamp = strtotime(date('Y-m-d', $now_stamp));
        $todayM = intval($now_stamp - $today_stamp) / 60;
        $openArr = explode(',', $data['open_time']);
        $stopArr = explode('-', $data['stop_time']);
        $stopArr[0] = $stopArr[0]*60 + $today_stamp;
        $stopArr[1] = $stopArr[1]*60 + $today_stamp;


        /* 未开盘 */
        if ($stopArr[0] > $stopArr[1]) {
            if ($now_stamp > $stopArr[0]) {
                $stopArr[1] += 86400;
            } else {
                $stopArr[0] -= 86400;
            }
        }
        $open_time_arr = explode(',', $data['open_time']);
        $open_time_count = count($open_time_arr);
        if ($now_stamp >= $stopArr[0] && $now_stamp < $stopArr[1]) {
            // 设置期数
            if (strstr($data['kithe_style'], ',')) {//期数一直累加的彩种
                $kithenum[0] = strtotime($kithenum[0]);
                $kithenum[1] += floor(($stopArr[1]-$kithenum[0])/86400) * $open_time_count;
                $res['kithe'] = (string)($kithenum[1] + 1);
                if (!empty($kithenum[2])) {//pcdd 根据北京快乐8的开奖结果来计算开奖的彩种
                    $yc = $is_chunjie * $open_time_count  * 7;
                    $res['kithe'] = (string)($res['kithe'] - $yc);//春节减去7天的期数
                }
            } else {
                $res['kithe'] = date($data['kithe_style'], $stopArr[1]) . str_pad(1, $data['bu0_num'], '0', STR_PAD_LEFT);
            }
            // 设置其他信息
            $res['is_open'] = 1;
            $res['every_time'] = $data['every_time'];
            $res['kithe_time_stamp'] = $stopArr[1];
            $res['current_time_stamp'] = $now_stamp;
        } else {        /* 已开盘 */
            if (!empty($stopArr[2])) {//当天的期号延续到第二天的彩种
                $stop_minute = floor(($stopArr[1] - strtotime(date('Ymd', $stopArr[1]))) / 60);
                foreach ($openArr as $k => $v) {
                    if ($v < $stop_minute) {
                        array_push($openArr, $v + 1440);
                        unset($openArr[$k]);
                    }
                }
                $openArr = array_values($openArr);
                if ($todayM < $stop_minute) {
                    $todayM += 1440;
                }
            }
            foreach ($openArr as $k => $v) {
                $v = $v-($data['up_close_time']/60);
                if ($todayM <= $v) {
                    if (empty($openArr[$k-1])) {//计算每一期的间隔
                        $jiange = $openArr[$k+1]-$openArr[$k];
                    } else {
                        $jiange = $openArr[$k]-$openArr[$k-1];
                    }
                    // 设置期数
                    if (strstr($data['kithe_style'], ',')) {
                        $kithenum = explode(',', $data['kithe_style']);
                        $kithenum[0] = strtotime($kithenum[0]);
                        $kithenum[1] += floor(($now_stamp-$kithenum[0])/86400)* $open_time_count;
                        $res['kithe'] = (string)($kithenum[1] + $k +1);
                        if (!empty($kithenum[2])) {//pcdd 根据北京快乐8的开奖结果来计算开奖的彩种
                            $yc = $is_chunjie * count($openArr)  * 7;
                            $res['kithe'] = (string)($res['kithe'] - $yc);//春节减去7天的期数
                        }
                    } else {
                        if ($todayM >= 1440) {
                            $date = $now_stamp - 86400;
                        } else {
                            $date = $now_stamp;
                        }
                        $res['kithe'] = date($data['kithe_style'], $date) . str_pad($k+1, $data['bu0_num'], '0', STR_PAD_LEFT);
                    }
                    break;
                }
            }
            // 设置其他信息
            $res['is_open']=1;
            $res['current_time_stamp'] = $now_stamp;
            $res['kithe_time_stamp'] = $today_stamp + ($v>1440 ? ($v-1440)*60 : $v*60);
        }
        $res['last_kithe'] =$this -> get_last_kithe($data,$today_stamp,$openArr,$todayM,$is_chunjie,$data['up_close_time']);
        $res['up_close_time'] = (int)$data['up_close_time'];
        return $res;
    }

    /** 
     * 福彩3D, 排列3 
     * @param $data 对应gc_open_time数据
     * @param $gid  对应彩票类型id
     * @return array 返回数组
     */
    private function typetwo($data, $gid, $res)
    {
        $res['gid'] = $gid;
        $nowstamp = $_SERVER['REQUEST_TIME'];//当前时间戳
        $is_chunjie = get_chunjie($nowstamp);
        if ($is_chunjie===false) {
            $res['is_open'] = 0;
            return $res;
        }
        $todaydate = date('Y-m-d', $nowstamp);//今天的日期
        $openArr = explode('-', $data['open_time']);
        $openArr[0] = strtotime($todaydate . $openArr[0]);
        $openArr[1] = strtotime($todaydate . $openArr[1]);
        if ($nowstamp < $openArr[0] || $nowstamp > $openArr[1]) {
            //当天的一期结束，至明天
            $res['is_open'] = 1;
            $day = date('z', $nowstamp)+1;
            if ($nowstamp > $openArr[1]) {
                $day += 1;
                $openArr[0] += 86400;
            }

            $res['kithe'] = date($data['kithe_style'], $nowstamp) . str_pad($day, $data['bu0_num'], '0', STR_PAD_LEFT);
            $res['kithe_time_stamp'] = $openArr[0];//无封盘时间
            $res['current_time_stamp'] = $nowstamp;
        } else {
            return $res;
        }

        $res['up_close_time'] = (int)$data['up_close_time'];
        if($this->is_chunjied( $nowstamp ) === true) {
            $yc = 7;
            $res['kithe'] = (string)($res['kithe'] - $yc);//春节减去7天的期数
        }

        return $res;
    }


    #判断给定时间是否过了当年春节
    private function is_chunjied($now_stamp_time)
    {
        $chunjie_rules = [
            2017 => '2017-02-03',
            2018 => '2018-02-15',
            2019 => '2019-02-03',
            2020 => '2020-01-24',
            2021 => '2021-02-11',
            2022 => '2022-01-31',
            2023 => '2023-01-21'
        ];

        $key = date('Y', $now_stamp_time);
        return $now_stamp_time > strtotime($chunjie_rules[$key]) ? true : false;
    }



    /**
     * 六合彩 
     * @param $data 对应gc_open_time数据
     * @param $gid  对应彩票类型id
     * @return array 返回数组
     */
    private function typethree($data, $gid, $res)
    {
        $res['is_open'] = 1;            //是否封盘
        $res['gid'] = $gid;
        $nowstamp = $_SERVER['REQUEST_TIME'];
        $openstamp = strtotime($data['open_time']);
        $res['up_close_time'] = (int)$data['up_close_time'];
        $res['kithe_time_stamp'] = $openstamp;
        $res['current_time_stamp'] = $nowstamp;
        //.判断跨年逻辑的 ,月份是12 并且当前kithe =1 
        $month = date('m');
        if($data['current_kithe'] ==1&&$month==12)
        {
            $res['kithe'] = (date($data['kithe_style'])+1).str_pad($data['current_kithe'], $data['bu0_num'], '0', STR_PAD_LEFT);
        }else{
            $res['kithe'] = date($data['kithe_style']).str_pad($data['current_kithe'], $data['bu0_num'], '0', STR_PAD_LEFT); 
        }
        return $res;
    }


    /**
     * 验证期数，如果有半小时以上还没开奖，则作一次记录
     * @param $checkarr
     * @return bool
     * super
     */
    public function check_issue($checkarr)
    {
        $kj_time_stamp = strtotime($checkarr['kj_time']);
        if (($checkarr['current_time_stamp']-$kj_time_stamp)>=1800) {
            $this->select_db('public_w');
            $outtime = $this->redisP_hget('log:kj_timeout_time', $checkarr['id']);
            if (empty($outtime)) {
                $content['gid'] = $checkarr['id'];
                $content['kj_time'] = $checkarr['kj_time'];
                $content['kj_issue'] = $checkarr['kj_issue'];
                $content['every_time'] = $checkarr['every_time'];
                $content['current_time'] = date('Y:m:d H:i:s', $checkarr['current_time_stamp']);
                $content['current_issue'] = $checkarr['kithe'];
                //$this->Open_time_model->write('op_log', $content);
                $this->redisP_hset('log:kj_timeout_time', $checkarr['id'], $kj_time_stamp);
                return true;
            } elseif ($outtime==$kj_time_stamp) {
                return false;
            } else {
                $this->redisP_hdel('log:kj_timeout_time', $checkarr['id']);
                return false;
            }
        } else {
            return false;
        }
    }

    public function get_set_games_plan()
    {
        $games_plan = $this->redisP_hGetAll('games_plan');
        if (empty($games_plan)) {
            $this->select_db('public');
            $data = $this->db->select('gid,open_time,up_close_time')
                ->where('type', 1)
                ->from('open_time')
                ->get()
                ->result_array();
            $games_plan = [];
            foreach ($data as $k => $v) {
                $open_time = explode(',', $v['open_time']);
                $up_close_time = $v['up_close_time'];
                array_walk($open_time, function (&$item) use ($up_close_time) {
                    if ($item == 1440) {
                        $item = 0;
                    }
                    $item = ($item * 60) - $up_close_time;
                });
                sort($open_time,SORT_NUMERIC);
                $games_plan[$v['gid']] = implode(',',$open_time);
            }
            $this->redisP_hmset('games_plan', $games_plan);
        }
        return $games_plan;
    }

    public function get_games_kithe_plan($gid = '')
    {
        $day = date('Y-m-d');
        $nextday = date('Y-m-d',strtotime($day . ' +1 day'));
        $games_plan = $this->redisP_hGetAll('games_plan');
        array_walk($games_plan, function (&$item) {
            $item = explode(',',$item);
            $item = (int)end($item);
        });
        if ($gid) {
            $gids = [$gid];
        } else {
            $gids = $this->redisP_hkeys('games_plan');
            $gc_set = $this->get_gcset(['cp']);
            $gids = array_intersect($gids,array_unique(explode(',',$gc_set['cp'])));
            $gids = array_values($gids);
            sort($gids);
        }
        $special_gids = [4,6,8,10,11,27,29,30,31,56,58,60,61,77,81,88];//期号连到第二天的
        $games_kithe = [];
        $now = time();
        $now = $now - strtotime(date('Y-m-d',$now));

        foreach ($gids as $gid) {
            if ($gid > 100) {
                continue;
            }
            $t_gid = gid_tran($gid);
            $key = 'kitheToDate:' . $day . ':';
            $key2 = 'timeToKithe:' . $day . ':';
            if ($now >= $games_plan[$t_gid]) {
                $key = 'kitheToDate:' . $nextday . ':';
                $key2 = 'timeToKithe:' . $nextday . ':';
            }
            if (isset($games_kithe[$t_gid])) {
                $games_kithe[$gid] = $games_kithe[$t_gid];
            } else {
                if (in_array($t_gid,$special_gids)) {
                    $kithes = $this->redisP_hvals($key2 . $t_gid);
                    sort($kithes,SORT_NUMERIC);
                    $games_kithe[$gid] = implode(',',$kithes);
                } else {
                    $kithes = $this->redisP_hkeys($key . $t_gid);
                    sort($kithes,SORT_NUMERIC);
                    $games_kithe[$gid] = implode(',',$kithes);
                }
            }
        }
        return $games_kithe;
    }

    /**
     * 获得最后一期的期号
     * @param $data,$now_stamp,$openArr,$todayM,$is_chunjie
     * @return str
     * super
     */
    private function get_last_kithe($data,$today_stamp,$openArr,$todayM,$is_chunjie,$up_close_time)
    {
       // var_dump($openArr[1]);

        // 设置期数
        $start_time = $openArr[0]*60 - $up_close_time;
        $now_time = time() - strtotime(date('Ymd'));
      //  var_dump($now_time , $start_time);
        if($now_time < $start_time) {
            $today_stamp = $today_stamp - 86400;
        }
        $last_kithe = '';
        $open_time_count = count($openArr);
        if (strstr($data['kithe_style'], ',')) {
            $kithenum = explode(',', $data['kithe_style']);
            $kithenum[0] = strtotime($kithenum[0]);
            $kithenum[1] += floor(($today_stamp - $kithenum[0]) / 86400) * $open_time_count;
            $last_kithe = (string)($kithenum[1] + $open_time_count);
            if (!empty($kithenum[2])) {//pcdd 根据北京快乐8的开奖结果来计算开奖的彩种
                $yc = $is_chunjie * count($openArr) * 7;
                $last_kithe = (string)($last_kithe - $yc);//春节减去7天的期数
            }
        } else {
            if ($todayM > 1440) {
                $date = $today_stamp - 86400;
            } else {
                $date = $today_stamp;
            }
            $last_kithe = date($data['kithe_style'], $date) . str_pad($open_time_count, $data['bu0_num'], '0', STR_PAD_LEFT);
        }
        return $last_kithe;
    }

}
