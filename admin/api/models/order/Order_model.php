<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/7
 * Time: 下午5:03
 */
class Order_model extends MY_Model
{
    private $games_model;

    private $fromType = [
        '1' => 'ios',
        '2' => 'android',
        '3' => 'PC',
        '4' => 'wap',
        '5' => '未知'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Games_model');
        $this->games_model = &get_instance();
    }

    /**
     * 获取来源
     * @return array
     */
    public function getFromType()
    {
        return $this->fromType;
    }

    /**
     * 获取搜索条件
     * @param $condition
     * @return array
     */
    public function getBasicAndSenior($condition)
    {
        //初始化条件
        $rs = array(
            'basic' => [],
            'senior' => [],
        );
        // 订单号
        if (!empty($condition['order_num'])) {
            $rs['basic']['order_num'] = $condition['order_num'];
        }
        // 订单类型
        if (!empty($condition['order_type'])) {
            $gid = $condition['order_type'] > 10 ? $condition['order_type'] : '0' . $condition['order_type'];
            if (empty($basic['order_num'])) {
                $rs['basic']['order_num like'] = '9' . $gid . '%';
            }
        }
        //  开始时间
        if (!empty($condition['from_time'])) {
            $rs['basic']['created >='] = strtotime($condition['from_time']);
        }
        // 结束时间
        if (!empty($condition['to_time'])) {
            $rs['basic']['created <'] = strtotime($condition['to_time']);
        }
        // 账号
        if (!empty($condition['account'])) {
            $id = $this->getUserId($condition['account']);
            $id = isset($id['id']) ? $id['id'] : -1;
            $rs['basic']['uid'] = $id;
        }
        if (!empty($condition['uid'])) {
            $rs['basic']['uid'] = $condition['uid'];
        }
        return $rs;
    }

    /**
     * 格式化数据
     * @param array $data
     * @param array $condition
     * @return array
     */
    public function formatData($data = array(), $condition)
    {
        // 初始化返回数据
        $rs = array('rows' => []);
        // 如果为空直接放回数据，不做其他处理
        if (empty($data['rows'])) {
            return $rs;
        }
        // 获取表名及对应的订单号
        $data = $this->getOrderNumAndTableName($data['rows']);
        // 到对应表名获取对应数据
        $tempData = [];
        if (!empty($data['table_name']) && !empty($data['order_num'])) {
            foreach ($data['table_name'] as $k => $table) {
                // 获取搜索条件
                isset($data['order_num'][$k]) && $param = $this->formatCondition($condition, $data['order_num'][$k]);
                // 获取数据
                isset($param) && $res = $this->getOrderData($table, $param);
                if (isset($res) && !empty($res)) {
                    $tempData = array_merge($tempData, $res);
                }
            }
        }
        rsort($tempData);
        // 额外数据处理
        if (!empty($tempData)) {
            // 获取用户名
            $userName = $this->getUsername($tempData);
            foreach ($tempData as $k => &$v) {
                $openNum = $this->getOpenNum(array('gid' => $v['gid'], 'issue' => $v['issue']));
                $issue = array_column($openNum, 'issue');
                // 赢、已结算、未结算：存在跨库操作，只能通过数据过滤
                if (isset($condition['status'])) {
                    if ($condition['status'] == '4' && in_array($v['issue'], $issue)) {
                        unset($tempData[$k]);
                        continue;
                    } elseif ($condition['status'] == '5' && !in_array($v['issue'], $issue)) {
                        unset($tempData[$k]);
                        continue;
                    } elseif ($condition['status'] == '6' && !empty($v['win_status'])) {
                        unset($tempData[$k]);
                        continue;
                    }
                }
                // 下注时间
                $v['bet_time'] = date('Y-m-d H:i:s', $v['bet_time']);
                // 玩法
                $v['play'] = $this->games_model->Games_model->sname($v['gid'], $v['tid'], true);
                // 可返水金额：和局或没有设置返水不返水
                if (!empty($v['rebate']) && $v['win_status'] != STATUS_HE) {
                    $v['rebate_num'] = $v['rebate'] * $v['price_sum'] * 0.01;
                } else {
                    $v['rebate_num'] = 0;
                }
                // 返水比例
                $v['rebate'] = $v['rebate'] * 0.01 . '%';

                // 添加用户名
                if (is_array($userName) && !empty($userName)) {
                    foreach ($userName as $item) {
                        isset($v['uid']) && $item['id'] == $v['uid'] && $v['username'] = $item['username'];
                    }
                }
                // 实际输赢
                $v['real_win'] = $v['win_price_sum'] - $v['price_sum'];
                // 添加来源
                $v['from_type'] = $this->fromType[$v['src']];
                // 追号
                $v['chase'] = '否';
            }
            $rs = array('rows' => array_values($tempData));
        }
        return $rs;
    }

    /**
     * 获取注单详情
     * @param $orderNum
     * @return array
     */
    public function getOrderDetail($orderNum)
    {
        $rs = [
            'account'   => '',  // 账号
            'issue'     => '',  // 期号
            'g_name'    => '',  // 彩种
            'g_play'    => '',  // 玩法
            'order_num' => $orderNum,   //注单号
            'bet_time'  => '',  //投注时间
            'rate'      => '',  //投注赔率
            'counts'    => '',  //投注数量
            'price'     => '',  //单注总额
            'price_sum' => '',  //投注总额
            'rebate'    => '',  //返水比率
            'rebate_num'=> '',  //返水金额
            'open_time' => '',  //开奖时间
            'win_num'   => '',  //中奖金额
            'contents'  => '',  //投注号码
            'number'    => '',  //开奖号码
        ];

        // 获取游戏对应的表名
        $gid = substr($orderNum, 1, 2);
        $s_name = $this->games_model->Games_model->sname($gid);
        $rs['g_name'] = $this->games_model->Games_model->sname($gid, 0, true);
        // 游戏信息
        $games = $this->get_one('*', 'bet_' . $s_name, array('order_num' => $orderNum));
        // 中奖信息
        $winInfo = $this->get_one('*', 'bet_wins', array('order_num' => $orderNum, 'status' => 1));
        // 开奖信息
        $openInfo = $this->getOpenNum(array('gid' => $games['gid'], 'issue' => $games['issue']));

        if (!empty($games)) {
            $rs['g_play'] = $this->games_model->Games_model->sname($games['gid'], $games['tid'], true);
            $rs['bet_time'] = date('Y-m-d H:i:s', $games['bet_time']);
            $rs['issue'] = $games['issue'];
            $rs['rate'] = $games['rate'];
            $rs['counts'] = $games['counts'];
            $rs['price'] = $games['price'];
            $rs['price_sum'] = $games['price_sum'];
            $rs['rebate'] = $games['rebate'] * 0.01;
            //$rs['rebate_num'] = $games['rebate'] * $games['price_sum'] * 0.01;
            $rs['names'] = $games['names'];
            $rs['info_status'] = $games['status'];
            $rs['end_time'] = empty($games['end_time'])?'-':date('Y-m-d H:i:s', $games['end_time']);
            if (!empty($openInfo)) {
                $rs['open_time'] = isset($openInfo[0]['created']) ? date('Y-m-d H:i:s', $openInfo[0]['created']) : '';
                $rs['number'] = isset($openInfo[0]['lottery']) ? $openInfo[0]['lottery'] : '';
            }
            if (!empty($winInfo)) {
                $rs['win_num'] = isset($winInfo['price_sum']) ? $winInfo['price_sum'] : '';
                $rs['win_contents'] = isset($winInfo['win_contents']) ? $winInfo['win_contents'] : '';
            }
        }
        if(!empty($rs['win_contents'])) {
            $rs['win_contents'] = $this->win_contents_format($rs['win_contents'], $gid, $games['tid']);
        }
        return $rs;
    }

    /**
     * 根据开奖号获取中奖内容
     * @param $orderNum
     * @return array
     */
    public function getWinContent($orderNum)
    {
        $rs = [];
        // 获取游戏对应的表名
        $gid = substr($orderNum, 1, 2);
        if (!in_array($gid, explode(',', SC)) || in_array($gid, [3,4,24,25])) {
            return $rs;
        }
        $s_name = $this->games_model->Games_model->sname($gid);
        if ($s_name) {
            // 游戏信息
            $games = $this->get_one('*', 'bet_' . $s_name, array('order_num' => $orderNum));
            if (!empty($games)) {
                // 开奖信息
                $openInfo = $this->getOpenNum(array('gid' => $games['gid'], 'issue' => $games['issue']));
                if ($openInfo) {
                    $plugin = null;
                    $plugin_name = 'games_settlement_'.$s_name;
                    $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
                    if (file_exists($plugin_file)) {
                        include_once($plugin_file);
                        if (class_exists($plugin_name)) {
                            $plugin = new $plugin_name;
                        }
                    }
                    /* 获取游戏当期开奖号 */
                    if (method_exists($plugin, 'wins_balls')) {
                        $lottery = ['base' => explode(',', $openInfo[0]['lottery'])];
                        $rs = $plugin->wins_balls($lottery);
                        unset($rs['base']);
                        $rs = $this->wins_balls_format($rs, $gid);
                    }
                }
            }
        }
        return $rs;
    }


    /**
     * 将中奖内容转换为文本
     */
    private function win_contents_format($cs, $gid, $tid)
    {
        $codes = [];
        $contents = json_decode($cs, true);
        if(empty($contents)) return $cs;
        foreach ($contents as $k => $v) {
            if(is_array($v)) {
                foreach ($v as $k1 => $v1) {
                    if(is_numeric($v1)) {
                        $codes[] = (int)$v1;
                    }
                }
            }
            else {
                $codes[] = (int)$v;
            }
        }
        if(empty($codes)) return '';
        $names = $this->get_list('code, name', 'games_products', ['gid' => $gid, 'tid' => $tid], ['wherein'=>['code'=>$codes], 'groupby'=>['code', 'name']]);
        foreach ($names as $k => $v) {
            $f[$v['code']] = $v['name'];
        }
        $cs = strtr($cs,$f);
        return $cs;
    }

    /**
     * 开奖球号转换
     * @param $balls
     * @param $gid
     * @return array
     */
    private function wins_balls_format($balls, $gid) {
        $rs = [];
        $code = [];
        $pl = [];
        if ($balls && is_array($balls)) {
            foreach ($balls as $k => $v) {
                if (is_array($v)) {
                    $code = array_merge($code, $v);
                } else {
                    array_push($code, $v);
                }
                array_push($pl, $k);
            }
            $this->select_db('public');
            $names = $this->get_list('code, name', 'games_products', ['gid' => $gid], ['wherein'=>['code'=>array_unique($code)], 'groupby'=>['code', 'name']]);
            $play = $this->get_list('sname, name', 'games_types', ['gid' => $gid], ['wherein'=>['sname'=>array_unique($pl)], 'groupby'=>['sname', 'name']]);
            $names = array_make_key($names, 'code');
            $play = array_make_key($play, 'sname');
            foreach ($balls as $k => $v) {
                if (is_array($v)) {
                    if (!empty($v)) {
                        foreach ($v as $kk => $vv) {
                            if (isset($play[$k])) {
                                $rs[$play[$k]['name']][$kk] = isset($names[$vv]) ? $names[$vv]['name'] : $vv;
                            } else {
                                $rs[$k][$kk] = isset($names[$vv]) ? $names[$vv]['name'] : $vv;
                            }
                        }
                    } else {
                        $rs[$play[$k]['name']] = '';
                    }
                } else {
                    if (isset($play[$k])) {
                        $rs[$play[$k]['name']] = isset($names[$v]) ? $names[$v]['name'] : $v;
                    } else {
                        $rs[$k] = isset($names[$v]) ? $names[$v]['name'] : $v;
                    }
                }
            }
        }
        return $rs;
    }

    /**
     * 获取表名及对应的订单号
     * @param $data
     * @return array
     */
    private function getOrderNumAndTableName($data)
    {
        $rs = [
            'table_name' => [], // 表名对应的订单号
            'order_num' => [],  // 表名
        ];
        if (is_array($data) && !empty($data)) {
            foreach ($data as $v) {
                $rs['order_num'][substr($v['order_num'], 1, 2)][] = $v;
            }
            // 获取对应的表名
            $tableKeys = array_keys($rs['order_num']);
            foreach ($tableKeys as $v) {
                $s_name = $this->games_model->Games_model->sname($v);
                !empty($s_name) && $rs['table_name'][$v] = 'bet_' . $s_name;
            }
        }
        return $rs;
    }

    /**
     * 获取注单数据
     * @param $table
     * @param $condition
     * @return array|mixed
     */
    private function getOrderData($table, $condition)
    {
        $rs = $this->get_list($condition['fields'], $table, $condition['where'], $condition['condition'], $condition['page']);
        return $rs;
    }

    /**
     * 初始化搜索条件
     * @param $condition
     * @param $order_num
     * @return array
     */
    private function formatCondition($condition, $order_num)
    {
        $rs = [
            'fields' => 'a.bet_time,a.issue,a.order_num,a.uid,a.gid,a.src,a.rebate,a.tid,
                         a.contents,a.price_sum,b.price_sum as win_price_sum,b.status as win_status',
            'where' => [],
            'condition' => [],
            'page' => [],
        ];
        // 订单号
        if (is_array($order_num) && !empty($order_num)) {
            $rs['condition'] = array(
                'wherein' => array('a.order_num' => array_column($order_num, 'order_num')),
                'join' => 'bet_wins',
                'on' => 'a.order_num = b.order_num'
            );
        }
        // 来源
        if (!empty($condition['src'])) {
            $rs['where']['a.src'] = $condition['src'];
        }
        // 状态
        if (in_array($condition['status'], array(1, 2, 3))) {
            $rs['where']['b.status'] = $condition['status'];
        }
        return $rs;
    }

    /**
     * 获取用户ID
     * @param $account
     * @return array
     */
    private function getUserId($account)
    {
        $rs = [];
        if (!empty($account)) {
            $rs = $this->get_one('id', 'user', array('username' => $account));
        }
        return $rs;
    }

    /**
     * 获取用户名
     * @param $data
     * @return array|mixed
     */
    private function getUsername($data)
    {
        $rs = [];
        if (is_array($data) && !empty($data)) {
            $ids = array_column($data, 'uid');
            if (is_array($ids) && !empty($ids)) {
                $condition = array(
                    'wherein' => array('id' => $ids)
                );
                $rs = $this->get_list('id,username', 'user', array(), $condition);
            }
        }
        return $rs;
    }

    /**
     * 获取开奖的信息
     * @param $condition
     * @return array|mixed
     */
    private function getOpenNum($condition)
    {
        $rs = $this->get_list('*', 'bet_settlement', $condition, array('status' => array(2, 3)));
        return $rs;
    }

    /**
     * @brief 取 一条 gc_bet_xxx 表数据
     * @access public
     * @param $gname    bet_xxx
     * @param $where    [''=>'']
     * @return 注单类表数据
     */
    public function get_bet($gname = '', $where = []) /* {{{ */
    {
        $res = $this->db->get_where('bet_'.$gname, $where, 1)->row_array();
        return $res;
    } /* }}} */

    /**
     * @brief
     * @access public
     * @param int   $order_num    注单号
     * @return 注单信息
     */
    public function info($order_num, $gname = '') /* {{{ */
    {
        $res = $this->db->get_where('bet_'.$gname, ['order_num' => (string) $order_num], 1)->row_array();
        return $res;
    } /* }}} */

    /**
     * @brief 撤单
     *      写 redis 退单 hash 表，写 bet_wins 退单记录，退钱
     * @access public
     * @return
     */
    public function cancel_order($dbn = '', $gname = '', $gid = 0, $uid = 0, $order_num = '') /* {{{ */
    {
        $cancel_hash = 'bets_cancel';
        $price_sum = 0;
        $counts = 0;
        if (empty($order_num)) {
            return false;
        }

        /* 检测订单有效性 */
        $order = $this->info($order_num, $gname);
        if (count($order) < 1) {
            return false;
        }
        $price_sum = $order['price_sum'];
        /* 检测私库是否有开奖记录 */
        $private_open = $this->get_bet('settlement', ['issue' => (string) $order['issue'], 'gid' => $gid]);
        if ($private_open && $private_open['status'] == STATUS_END) {
            wlog(APPPATH.'logs/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log',
                    $order_num.':already settlement.uid:'.$uid.':price:'.$price_sum.':issue:'.$order['issue'].':'.json_encode($order));
            return false;
        }
        $counts = $order['counts'];
        /* 检测是否有撤单或结算过 */
        $in_wins = $this->info($order_num, 'wins');
        if (count($in_wins)) {
            wlog(APPPATH.'logs/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log',
                    $order_num.':in bet_wins error.uid:'.$uid.':price:'.$price_sum.':status:'.$in_wins['status'].json_encode($in_wins));
            return false;
        }

        if ($this->db->insert('bet_wins', array('order_num' => $order_num, 'uid' => $uid, 'price_sum' => $price_sum, 'status' => STATUS_CANCEL, 'created' => time()))) {
            //$this->load->model('comm_model');
            if ($this->update_banlace($uid, $price_sum, $order_num, BALANCE_CANCEL, '撤单')) {
                /* 下注单放到持久库 */
                $this->select_redis_db(REDIS_LONG);
                $this->redis_hset($cancel_hash, $order_num, STATUS_CANCEL);
                wlog(APPPATH.'logs/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':cannel ok.uid:'.$uid.':price:'.$price_sum);
            } else {
                wlog(APPPATH.'logs/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':change_balance error.uid:'.$uid.':price:'.$price_sum);
            }
        } else {
            wlog(APPPATH.'logs/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':insert bet_wins error.uid:'.$uid.':price:'.$price_sum);
            return false;
        }
        return true;
    } /* }}} */
}
