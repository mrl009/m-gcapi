<?php
/**
 * @brief 长龙助手
 * Created by wuya.
 * Date: 2018/11/11
 * Time: 上午9:37
 */

defined('BASEPATH') OR exit('No direct script access allowed');
include_once BASEPATH.'gc/libraries/games_settlement_s_k3.php';
include_once BASEPATH.'gc/libraries/games_settlement_s_ssc.php';
include_once BASEPATH.'gc/libraries/games_settlement_s_pk10.php';


class Dragon_model extends MY_Model
{
    public $games = [
        's_k3'=>[
            'zh'=>['hz']
        ],
        's_ssc'=>[
            'lm'=>['d1','d2','d3','d4','d5','zh']
        ],
        's_pk10'=>[
            'lm'=>['12he','d1','d2','d3','d4','d5','d6','d7','d8','d9','d10']
        ]
    ];
    protected $dragon_name = [
        100 => '大', 101 => '小', 102 => '单', 103 => '双',
        196 => '大', 197 => '小', 198 => '单', 199 => '双',
        313 => '大', 314 => '小', 315 => '单', 316 => '双',
    ];
    protected $ball_name = [
        'd1' => '第一', 'd2' => '第二', 'd3' => '第三', 'd4' => '第四', 'd5' => '第五', 'd6' => '第六', 'd7' => '第七', 'd8' => '第八', 'd9' => '第九', 'd10' => '第十', 'zh' => '和值', '12he' => '冠亚和', 'hz' => '和值'
    ];
    protected $dragon_gids = [];
    protected $kj_issue = [];
    protected $dragon_data = [];
    protected $dragon_history = [];
    protected $gc_update = false;
    protected $zkc_update = false;
    protected $lottery_data = [];
    protected $plugins = [];

    /**
     *  获取长龙彩种数据
     */
    public function getDragonCP()
    {
        $gcset = $this->get_gcset(['cp','lottery_auth']);
        $lottery_auth = array_unique(explode(',',$gcset['lottery_auth']));
        if (!in_array(2,$lottery_auth)) {
            get_instance()->return_json(E_ARGS, '请先开通长龙彩种');
        }
        $cp = array_unique(explode(',',$gcset['cp']));
        $dragon_gids = array_merge(TMP_TO_GID['s_k3'], TMP_TO_GID['s_ssc'], TMP_TO_GID['s_pk10']);
        $dragon_gids = array_unique(array_intersect($cp,$dragon_gids));
        if (empty($dragon_gids)) {
            get_instance()->return_json(E_ARGS, '请先开通长龙彩种');
        }
        $dragon_gids = array_values($dragon_gids);
        // 过滤正在维护的彩种
        $this->select_db('public');
        $res = $this->db->select('id')
            ->where_in('id',$dragon_gids)
            ->where('status',0)
            ->get('games')->result_array();
        $dragon_gids = array_column($res,'id');
        return $dragon_gids;

    }

    protected function getLotteryZKC($gid,$issue = null)
    {
        $this->select_db('private');
        $res = $this->db->select('issue as kj_issue,lottery as number')
            ->from('bet_settlement')
            ->where(['status >=' => 2,'status <=' => 3,'gid'=>$gid])
            ->order_by('id','desc')
            ->limit(50)
            ->get()
            ->result_array();
        if ($issue) {
            $res = array_filter($res,function ($item) use ($issue) {
                return (int)$item['kj_issue'] > (int)$issue;
            });
        }
        sortArrByField($res,'kj_issue',true);
        return $res;
    }

    protected function getLottery($gid,$issue = null)
    {
        $res = [];
        $data = $this->redisP_hgetall('gcopen:' . $gid);
        if (!empty($data)) {
            foreach ($data as $key => $v) {
                $i = json_decode($v, true);
                $t = [
                    'kj_issue' => $key,
                    'number' => isset($i[0]) ? $i[0] : ''
                ];
                array_push($res, $t);
            }
        } else {
            $this->select_db('public');
            $res = $this->db->select('kithe as kj_issue,number')
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
                return (int)$item['kj_issue'] > (int)$issue;
            });
        }
        sortArrByField($res,'kj_issue',true);
        return $res;
    }

    /**
     * 设置长龙数据
     * @param array $gids
     * @return object | $this | Dragon_model
     */
    public function set_dragon(array $gids)
    {
        $this->set_dragon_gids($gids)
            ->get_set_dragon_history()
            ->get_new_kj_issue();
        $this->calculate();
        $this->get_set_dragon_history($this->dragon_data);
        return $this;
    }

    /**
     * @return array $dargon_data
     */
    public function get_dragon()
    {
        return $this->dragon_data;
    }

    /**
     * 设置需要计算长龙的gids
     *
     * @param array $gids
     * @return object | $this | Dragon_model
     */
    protected function set_dragon_gids(array $gids)
    {
        $this->dragon_gids = $gids;
        return $this;
    }

    /**
     * 获取redis中缓存的长龙历史数据
     * @param array $history
     * @return object | $this | Dragon_model
     */
    protected function get_set_dragon_history(array $history = [])
    {
        $zkc = explode(',', ZKC);
        $gc_data = $zkc_data = [];
        if (!empty($history)) {
            foreach ($history as $gid => $data) {
                $data = json_encode($data);
                if (in_array($gid,$zkc)) {
                    $zkc_data[$gid] = $data;
                } else {
                    $gc_data[$gid] = $data;
                }
            }
            if ($this->gc_update) {
                $this->redisP_hmset('dragon_history',$gc_data);
            }
            if ($this->zkc_update) {
                $this->redis_hmset('dragon_history',$zkc_data);
            }
        } else {
            $zkc_data = $this->redis_hgetall('dragon_history');
            $gc_data = $this->redisP_hgetall('dragon_history');
            $history = array_merge($zkc_data,$gc_data);
            foreach ($history as $gid => &$data) {
                $data = json_decode($data,true);
            }
            $this->dragon_history = $history;
        }
        return $this;
    }

    /**
     * 获取最新的开奖结果的期号
     * @return object | $this | Dragon_model
     */
    protected function get_new_kj_issue()
    {
        $new_open_num = $this->redisP_hgetall('new_open_num');
        foreach ($this->dragon_gids as $gid) {
            $t_gid = $gid > 50 ? gid_tran($gid) : $gid;
            if (isset($new_open_num[$t_gid])) {
                $result = json_decode($new_open_num[$t_gid],true);
                $this->kj_issue[$gid] = $result['kithe'];
            }
        }
        return $this;
    }

    /**
     * 计算长龙数据
     * @return object | $this | Dragon_model
     */
    protected function calculate()
    {
        foreach ($this->dragon_gids as $gid) {
            $issue = '';
            if (isset($this->dragon_history[$gid])) {
                $issue = $this->dragon_history[$gid]['kj_issue'];
            }
            if ($issue && isset($this->kj_issue[$gid]) && $issue == $this->kj_issue[$gid]) {
                // 沒有更新的开奖结果 则不计算
                $this->dragon_data[$gid] = $this->dragon_history[$gid];
            } else {
                $this->set_lottery_data($gid);
                $this->set_dragon_data($gid);
            }
        }
        return $this;
    }

    /**
     * 取彩种开奖结果
     * @param $gid int 游戏id
     */
    protected function set_lottery_data($gid)
    {
        $this->lottery_data = [];
        $t_gid = $gid > 50 ? gid_tran($gid) : $gid;
        if (in_array($t_gid,explode(',', ZKC))) {
            $this->zkc_update = true;
            $this->lottery_data = $this->getLotteryZKC($t_gid);
        } else {
            $this->gc_update = true;
            $this->lottery_data = $this->getLottery($t_gid);
        }
    }

    /**
     * 根据开奖结果及长龙历史数据，生成新的长龙数据并写入缓存
     * @param $gid int 游戏gid
     * @param $issue string 游戏期号
     *
     * @return bool
     */
    protected function set_dragon_data($gid)
    {
        if (empty($this->lottery_data)) {
            return false;
        }
        if (in_array($gid,TMP_TO_GID['s_k3'])) {
            $this->set_k3_dragon_data($gid);
        } else if (in_array($gid,TMP_TO_GID['s_ssc'])) {
            $this->set_ssc_dragon_data($gid);
        } else if (in_array($gid,TMP_TO_GID['s_pk10'])) {
            $this->set_pk10_dragon_data($gid);
        } else {
            return false;
        }
    }

    protected function set_k3_dragon_data($gid)
    {
        $plugin_name = 'games_settlement_s_k3';
        if (isset($this->plugins['s_k3'])) {
            $plugin = $this->plugins['s_k3'];
        } else {
            if (class_exists($plugin_name)) {
                $plugin = $this->plugins['s_k3'] = new $plugin_name;
            } else {
                get_instance()->return_json(E_OP_FAIL,'缺少K3文件');
            }
        }
        $dx = $ds = true;
        foreach ($this->lottery_data as $issue_info) {
            $lottery = ['base' => explode(',', $issue_info['number'])];
            $lottery = $plugin->wins_balls($lottery);
            if (!isset($this->dragon_data[$gid])) {
                $this->dragon_data[$gid] = [
                    'hz' => [
                        'dx' => ['code'=>$lottery['he'][1],'times' => 1],
                        'ds' => ['code'=>$lottery['he'][2],'times' => 1]
                    ],
                    'kj_issue' => $issue_info['kj_issue']
                ];
            } else {
                if ($dx == true) {
                    $lottery['he'][1] == $this->dragon_data[$gid]['hz']['dx']['code'] ? $this->dragon_data[$gid]['hz']['dx']['times']++ : $dx=false;
                }
                if ($ds == true) {
                    $lottery['he'][2] == $this->dragon_data[$gid]['hz']['ds']['code'] ? $this->dragon_data[$gid]['hz']['ds']['times']++ : $ds=false;
                }
                if (!$dx && !$ds) {
                    break;
                }
            }
            $this->dragon_data[$gid]['kj_issue'] = max($issue_info['kj_issue'],$this->dragon_data[$gid]['kj_issue']);
        }
        $this->dragon_data[$gid]['kj_issue'] = (string)$this->dragon_data[$gid]['kj_issue'];
    }

    protected function set_ssc_dragon_data($gid)
    {
        $plugin_name = 'games_settlement_s_ssc';
        if (isset($this->plugins['s_ssc'])) {
            $plugin = $this->plugins['s_ssc'];
        } else {
            if (class_exists($plugin_name)) {
                $plugin = $this->plugins['s_ssc'] = new $plugin_name;
            } else {
                get_instance()->return_json(E_OP_FAIL,'缺少SSC文件');
            }
        }
        $len = count($this->games['s_ssc']['lm']);
        $dx = $ds = [];
        for ($i=0;$i<$len;$i++){
            $dx[$this->games['s_ssc']['lm'][$i]] = true;
            $ds[$this->games['s_ssc']['lm'][$i]] = true;
        }
        foreach ($this->lottery_data as $issue_info) {
            $lottery = ['base' => explode(',', $issue_info['number'])];
            $lottery = $plugin->wins_balls($lottery);
            if (!isset($this->dragon_data[$gid])) {
                for ($i=0;$i<$len;$i++) {
                    $k = $this->games['s_ssc']['lm'][$i];
                    $m = 1;
                    $n = 2;
                    if ($k == 'zh') {
                        $m = 0;
                        $n = 1;
                    }
                    $this->dragon_data[$gid][$k]['dx'] = ['code'=>$lottery[$k][$m],'times' => 1];
                    $this->dragon_data[$gid][$k]['ds'] = ['code'=>$lottery[$k][$n],'times' => 1];
                }
                $this->dragon_data[$gid]['kj_issue'] = $issue_info['kj_issue'];
            } else {
                $stop = true;
                for ($i=0;$i<$len;$i++) {
                    $k = $this->games['s_ssc']['lm'][$i];
                    $m = 1;
                    $n = 2;
                    if ($k == 'zh') {
                        $m = 0;
                        $n = 1;
                    }
                    if ($dx[$k] == true) {
                        $lottery[$k][$m] == $this->dragon_data[$gid][$k]['dx']['code'] ? $this->dragon_data[$gid][$k]['dx']['times']++ : $dx[$k]=false;
                    }
                    if ($ds[$k] == true) {
                        $lottery[$k][$n] == $this->dragon_data[$gid][$k]['ds']['code'] ? $this->dragon_data[$gid][$k]['ds']['times']++ : $ds[$k]=false;
                    }
                    $stop = $stop && !$dx[$k] && !$ds[$k];
                }
                if ($stop) {
                    break;
                }
            }
            $this->dragon_data[$gid]['kj_issue'] = max($issue_info['kj_issue'],$this->dragon_data[$gid]['kj_issue']);
        }

        $this->dragon_data[$gid]['kj_issue'] = (string)$this->dragon_data[$gid]['kj_issue'];
    }

    protected function set_pk10_dragon_data($gid)
    {
        $plugin_name = 'games_settlement_s_pk10';
        if (isset($this->plugins['s_pk10'])) {
            $plugin = $this->plugins['s_pk10'];
        } else {
            if (class_exists($plugin_name)) {
                $plugin = $this->plugins['s_pk10'] = new $plugin_name;
            } else {
                get_instance()->return_json(E_OP_FAIL,'缺少PK10文件');
            }
        }
        $len = count($this->games['s_pk10']['lm']);
        $dx = $ds = [];
        for ($i=0;$i<$len;$i++){
            $dx[$this->games['s_pk10']['lm'][$i]] = true;
            $ds[$this->games['s_pk10']['lm'][$i]] = true;
        }
        foreach ($this->lottery_data as $issue_info) {
            $lottery = ['base' => explode(',', $issue_info['number'])];
            $lottery = $plugin->wins_balls($lottery);
            if (!isset($this->dragon_data[$gid])) {
                for ($i=0;$i<$len;$i++) {
                    $k = $this->games['s_pk10']['lm'][$i];
                    $this->dragon_data[$gid][$k]['dx'] = ['code'=>$lottery[$k][1],'times' => 1];
                    $this->dragon_data[$gid][$k]['ds'] = ['code'=>$lottery[$k][2],'times' => 1];
                }
                $this->dragon_data[$gid]['kj_issue'] = $issue_info['kj_issue'];
            } else {
                $stop = true;
                for ($i=0;$i<$len;$i++) {
                    $k = $this->games['s_pk10']['lm'][$i];
                    if ($dx[$k] == true) {
                        $lottery[$k][1] == $this->dragon_data[$gid][$k]['dx']['code'] ? $this->dragon_data[$gid][$k]['dx']['times']++ : $dx[$k]=false;
                    }
                    if ($ds[$k] == true) {
                        $lottery[$k][2] == $this->dragon_data[$gid][$k]['ds']['code'] ? $this->dragon_data[$gid][$k]['ds']['times']++ : $ds[$k]=false;
                    }
                    $stop = $stop && !$dx[$k] && !$ds[$k];
                }
                if ($stop) {
                    break;
                }
            }
            $this->dragon_data[$gid]['kj_issue'] = max($issue_info['kj_issue'],$this->dragon_data[$gid]['kj_issue']);
        }

        $this->dragon_data[$gid]['kj_issue'] = (string)$this->dragon_data[$gid]['kj_issue'];
    }


}
