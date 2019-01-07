<?php
/**
 * @brief 开奖计划
 * Created by PhpStorm.
 * Date: 2019/1/2
 * Time: 上午11:38
 */

defined('BASEPATH') OR exit('No direct script access allowed');
include_once BASEPATH.'gc/libraries/games_settlement_s_k3.php';
include_once BASEPATH.'gc/libraries/games_settlement_s_ssc.php';
include_once BASEPATH.'gc/libraries/games_settlement_s_pk10.php';


class Plan_model extends MY_Model
{
    // 重庆时时彩，分分时时彩，三分时时彩，江苏快三，北京pk拾，三分pk拾，易彩快三，5分快三，
    protected $plan_gids = [56,60,61,62,76,77,82,88];
    protected $plan_history = [];
    protected $plan_name = [
        100 => '大', 101 => '小', 102 => '单', 103 => '双',
        196 => '大', 197 => '小', 198 => '单', 199 => '双',
        313 => '大', 314 => '小', 315 => '单', 316 => '双',
    ];
    protected $kj_issue = [];//最新开奖的期号
    protected $cur_kithe = [];//当前待开奖的期号
    protected $plugins = [];
    protected $plugin_name = [
        'k3'    =>  'games_settlement_s_k3',
        'ssc'   =>  'games_settlement_s_ssc',
        'pk10'  =>  'games_settlement_s_pk10'
    ];
    protected $balls = [
        'k3'    =>  ['dx' => ['he',1], 'ds' => ['he',2]],
        'ssc'   =>  ['dx' => ['zh',0], 'ds' => ['zh',1]],
        'pk10'  =>  ['dx' => ['12he',1], 'ds' => ['12he',2]]
    ];
    protected $times_plan = [];
    protected $kithe_plan = [];
    protected $kj_history = [];

    /**
     *  获取计划彩种数据
     */
    public function set_plan_gids()
    {
        $gcset = $this->get_gcset(['cp','lottery_auth']);
        $lottery_auth = array_unique(explode(',',$gcset['lottery_auth']));
        if (!in_array(2,$lottery_auth)) {
            die('请先开通计划彩种');
        }
        $cp = array_unique(explode(',',$gcset['cp']));
        $plan_gids = array_unique(array_intersect($cp,$this->plan_gids));
        if (empty($plan_gids)) {
            die('请先开通计划彩种');
        }
        $this->plan_gids = array_values($plan_gids);
        return $this;
    }

    protected function getLottery($gid,$issue = null)
    {
        $res = [];
        $data = $this->redisP_hgetall('gcopen:' . $gid);
        if (!empty($data)) {
            foreach ($data as $key => $v) {
                $i = json_decode($v, true);
                $t = [
                    'kithe' => $key,
                    'number' => isset($i[0]) ? (is_array($i[0]) ? implode(',',$i[0]) : $i[0]) : '',
                    'open_time' => date('H:i',strtotime($i[1]))
                ];
                array_push($res, $t);
            }
        } else {
            $this->select_db('public');
            $res = $this->db->select('kithe,number,DATE_FORMAT(open_time,"%H:%i") as open_time')
                ->from('open_num')
                ->where(['status >=' => 2,'status <=' => 3,'gid'=>$gid])
                ->order_by('id','desc')
                ->limit(50)
                ->get()
                ->result_array();
            $this->select_db('private');
        }
        if ($issue) {
            $res = array_filter($res,function ($item) use ($issue) {
                return (int)$item['kithe'] >= (int)$issue;
            });
        }
        sortArrByField($res,'kithe');
        return $res;
    }

    protected function getLotteryZKC($gid,$issue = null)
    {
        $this->select_db('private');
        $res = $this->db->select('issue as kithe,lottery as number,FROM_UNIXTIME(created,"%H:%i") as open_time')
            ->from('bet_settlement')
            ->where(['status >=' => 2,'status <=' => 3,'gid'=>$gid])
            ->order_by('id','desc')
            ->limit(50)
            ->get()
            ->result_array();
        if ($issue) {
            $res = array_filter($res,function ($item) use ($issue) {
                return (int)$item['kithe'] >= (int)$issue;
            });
        }
        sortArrByField($res,'kithe');
        return $res;
    }

    /**
     * 设置最新开奖期号和开奖结果
     */
    protected function set_kj_issue()
    {
        $new_open_num = $this->redisP_hgetall('new_open_num');
        foreach ($this->plan_gids as $gid) {
            $t_gid = $gid > 50 ? gid_tran($gid) : $gid;
            if (isset($new_open_num[$t_gid])) {
                $result = json_decode($new_open_num[$t_gid],true);
                $result['open_time'] = date('H:i',strtotime($result['open_time']));
                $this->kj_issue[$gid] = $result;
            }
            if (in_array($gid,explode(',', ZKC))) {
                $this->select_db('private');
                $res = $this->db->select('issue as kithe,lottery as number,created as open_time')->from('bet_settlement')
                    ->where(['status >=' => 2,'status <=' => 3,'gid'=>$gid])
                    ->order_by('id','desc')
                    ->limit(1)->get()->row_array();
                if (isset($this->kj_issue[$gid])) {
                    $this->kj_issue[$gid]['number'] = $res['number'];
                } else {
                    $res['open_time'] = date('H:i',$res['open_time']);
                    $this->kj_issue[$gid] = $res;
                }
            }
        }
        return $this;
    }

    /**
     * 设置当前待开奖期号
     */
    protected function set_cur_kithe()
    {
        if (empty($this->times_plan)) {
            $times_plan = $this->redisP_hGetAll('games_plan');
            foreach ($this->plan_gids as $gid) {
                $this->times_plan[$gid] = explode(',',$times_plan[$gid]);
            }
        }
        if (empty($this->kithe_plan)) {
            $CI = get_instance();
            $CI->load->model('Open_time_model');
            $CI->Open_time_model->init($CI->_sn);
            $kithe_plan = $CI->Open_time_model->get_games_kithe_plan();
            foreach ($this->plan_gids as $gid) {
                $this->kithe_plan[$gid] = explode(',',$kithe_plan[$gid]);
            }
        }
        $now = time();
        $now = $now - strtotime(date('Y-m-d',$now));
        $this->select_db('public');
        foreach ($this->plan_gids as $gid) {
            $times_plan = $this->times_plan[$gid];
            array_push($times_plan,$now);
            sort($times_plan);
            $keys = array_keys($times_plan,$now);
            $index = end($keys);
            if ($index == count($this->times_plan[$gid])) {
                $index = 0;
            }
            $cur_kithe = $this->kithe_plan[$gid][$index];
            $res = $this->db->select('open_time')->from('open_num')
                ->where(['kithe =' => $cur_kithe,'gid'=>gid_tran($gid)])
                ->limit(1)->get()->row_array();
            $this->cur_kithe[$gid] = ['kithe'=>$cur_kithe,'open_time'=>date('H:i',strtotime($res['open_time']))];
        }
        $this->select_db('private');
    }

    /**
     * 获取开奖计划历史
     * @param array $plan 计划
     * @return object | $this | Plan_model
     */
    public function get_plan_history(&$plan = [])
    {
        $history = $this->redis_hgetall('plan_history');
        foreach ($this->plan_gids as $gid) {
            $this->plan_history[$gid] = [];
            if (isset($history[$gid])) {
                $this->plan_history[$gid] = json_decode($history[$gid],true);
            }
        }
        $plan = $this->plan_history;
        return $this;
    }

    protected function set_plan_history()
    {
        array_walk($this->plan_history,function (&$item) {
            $item = json_encode($item);
        });
        $this->redis_hmset('plan_history',$this->plan_history);
    }

    /**
     * 检测是否要重新开始一轮计划
     * @param int $gid
     * @param string $key e.g. dx ds
     * @return bool
     */
    protected function plan_over($gid,$key)
    {
        if (empty($this->plan_history[$gid])) {
            return true;
        }
        $newest = reset($this->plan_history[$gid]);
        if ($newest[$key]['win_lost'] <= -10000) {
            // 盈利亏损达到-10000【1万】
            return true;
        } elseif ($newest[$key]['total_cost'] >= 100000) {
            // 累计成本达100000【10万】
            return true;
        } else {
            // 今日期号结束
            if ($this->cur_kithe[$gid]['kithe'] - $this->kj_issue[$gid]['kithe'] > 2) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 根据历史计划 生成下一期计划
     */
    public function make_plan()
    {
        $this->set_plan_gids()
            ->get_plan_history()
            ->set_kj_issue()
            ->set_cur_kithe();
        foreach ($this->plan_gids as $gid) {
            if (empty($this->plan_history[$gid])) {
                // 计划为空 则初始化
                $this->init_plan($gid);
            } elseif ($this->plan_history[$gid][0]['kithe'] == $this->kj_issue[$gid]['kithe']) {
                // 计划最新期号和当前最新开彩期号相同 则赋值开奖号码，矫正盈利，生成新计划
                $this->plan_history[$gid][0]['number'] = $this->kj_issue[$gid]['number'];
                $this->calculte($gid,$this->plan_history[$gid][0]);
                $this->add_plan($gid);
            } else {
                if ($this->plan_history[$gid][0]['kithe'] == $this->cur_kithe[$gid]['kithe']) {
                    // 计划最新期号和当前待开期号相同 则不处理
                    continue;
                }
                elseif ($this->plan_history[$gid][0]['kithe'] < $this->kj_issue[$gid]['kithe'])
                {
                    // 计划最新期号小于当前最新开奖期号 则补上漏掉的计划
                    $this->repair_plan($gid);
                }
            }
        }
        $this->set_plan_history();
    }


    /**
     * 初始化计划
     * @param $gid
     */
    protected function init_plan($gid)
    {
        $this->repair_plan($gid,true);

    }

    /**
     * 修复计划
     * @param int  $gid
     * @param bool $init 是否为初始化
     */
    protected function repair_plan($gid,$init=false)
    {
        $issue = $init ? null : $this->plan_history[$gid][0]['kithe'];
        if (in_array($gid,explode(',',ZKC))) {
            $plans = $this->getLotteryZKC($gid,$issue);
        } else {
            $plans = $this->getLottery($gid,$issue);
        }
        if ($init) {
            $_plan = [
                'kithe' => (string)$plans[0]['kithe'],
                'open_time' => $plans[0]['open_time'],
                'plan_number' => $this->rand_number($gid),
                'number' => ''
            ];
            $this->calculte($gid,$_plan);
            $this->plan_history[$gid][] = $_plan;
        }
        $cur_kithe = $this->cur_kithe[$gid];
        $len = count($plans);
        foreach ($plans as $k => $plan) {
            $this->kj_issue[$gid] = $plan;
            $this->plan_history[$gid][0]['number'] = $this->kj_issue[$gid]['number'];
            $this->calculte($gid,$this->plan_history[$gid][0]);
            if ($k+1 == $len) {
                $this->cur_kithe[$gid] = $cur_kithe;
            } else {
                $this->cur_kithe[$gid] = ['kithe'=>$plans[$k+1]['kithe'],'open_time'=>$plans[$k+1]['open_time']];
            }
            $this->add_plan($gid);
        }
    }



    protected function add_plan($gid)
    {
        // 先矫正当前历史中最新计划 盈利亏损
        $_plan = [
            'kithe' => (string)$this->cur_kithe[$gid]['kithe'],
            'open_time' => $this->cur_kithe[$gid]['open_time'],
            'plan_number' => $this->rand_number($gid),
            'number' => ''
        ];
        $this->calculte($gid,$_plan);
        array_unshift($this->plan_history[$gid],$_plan);
        if (count($this->plan_history[$gid]) > 50) {
            array_pop($this->plan_history[$gid]);
        }
    }

    /**
     * 随机生成一个开奖结果
     * 从最近50期中随机取一个
     * @param int $gid
     * @return string $number
     */
    protected function rand_number($gid)
    {
        if (isset($this->kj_history[$gid])) {
            $data = $this->kj_history[$gid];
        } else {
            $data = $this->redisP_hgetall('gcopen:' . $gid);
            if (empty($data)) {
                $data = $this->redisP_hgetall('gcopen:' . gid_tran($gid));
            }
            array_walk($data,function (&$item) {
                $item = json_decode($item,true);
            });
            $this->kj_history[$gid] = $data;
        }
        $key = array_rand($data);
        $number = $data[$key][0];
        if (is_array($number)) {
            $number = implode(',',$number);
        }
        return $number;
    }

    protected function calculte($gid,&$plan)
    {
        if (in_array($gid,TMP_TO_GID['s_k3'])) {
            $this->_calculte('k3',$gid,$plan);
        } else if (in_array($gid,TMP_TO_GID['s_ssc'])) {
            $this->_calculte('ssc',$gid,$plan);
        } else if (in_array($gid,TMP_TO_GID['s_pk10'])) {
            $this->_calculte('pk10',$gid,$plan);
        } else {
            return false;
        }
    }

    protected function _calculte($type,$gid,&$plan)
    {
        if (isset($this->plugins[$type])) {
            $plugin = $this->plugins[$type];
        } else {
            if (isset($this->plugin_name[$type]) && class_exists($this->plugin_name[$type])) {
                $plugin = $this->plugins[$type] = new $this->plugin_name[$type];
            } else {
                if (is_cli()) {
                    die("缺少{$type}文件\r\n");
                } else {
                    get_instance()->return_json(E_OP_FAIL,"缺少{$type}文件");
                }

            }
        }
        $plan_balls = ['base' => explode(',', $plan['plan_number'])];
        $plugin->wins_balls($plan_balls);
        if ($plan['number']) {
            $win_balls = ['base' => explode(',', $plan['number'])];
            $plugin->wins_balls($win_balls);
        }
        foreach ($this->balls[$type] as $k => $v) {
            if ($plan['number']) {
                if (empty($win_balls[$v[0]][$v[1]])) {
                    $plan[$k]['ball'] = '和';
                    $plan[$k]['win'] = -1;
                } else {
                    $plan[$k]['ball'] = $this->plan_name[$win_balls[$v[0]][$v[1]]];
                    $plan[$k]['win'] = $plan[$k]['ball']===$plan[$k]['plan_ball']?1:-1;
                }
                if ($plan[$k]['win']>0) {
                    $plan[$k]['win_lost'] += 2*$plan[$k]['cur_cost'];
                }
            } else {
                $plan[$k]['plan_ball'] = $plan[$k]['ball'] = '';
                $plan[$k]['win'] = -1;
                $plan[$k]['plan_ball'] = empty($plan_balls[$v[0]][$v[1]])?'和':$this->plan_name[$plan_balls[$v[0]][$v[1]]];
                if ($this->plan_over($gid,$k)) {
                    $plan[$k]['cur_cost'] = 5;
                    $plan[$k]['total_cost'] = 5;
                    $plan[$k]['win_lost'] = -5;
                } else {
                    $prev = $this->plan_history[$gid][0];
                    $plan[$k]['cur_cost'] = $prev[$k]['win'] > 0 ? 5 : 3*$prev[$k]['cur_cost'];
                    $plan[$k]['total_cost'] = $prev[$k]['total_cost'] + $plan[$k]['cur_cost'];
                    $plan[$k]['win_lost'] = $prev[$k]['win_lost'] - $plan[$k]['cur_cost'];
                }
            }
        }
    }



}
