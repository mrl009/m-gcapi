<?php
/**
 * @file models/Games_model.php
 * @brief
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package model
 * @author Langr <hua@langr.org> 2017/03/14 16:55
 *
 * $Id$
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Bets_model extends MY_Model
{
    private $rds = null;
    private $win_rate = null;

    public function __construct() /* {{{ */
    {
        parent::__construct();
        // $this->db2 = $this->load->database('private', true);
        // $this->select_db('public');
    } /* }}} */

    /**
     * 系统彩全局沙 redis 初始化
     */
    public function init_rds($gid = 0) /* {{{ */
    {
        /**
         * win_rate:
         * demo: {"redis":"redis://192.168.8.207:6379/15","4":90,"10":90,"11":90,"24":90,"27":90,"29":90,"30":90,"win_rand":5,"min_price":500,"kill_uids":{"w05":[8,19]}}
         */
        if ($this->win_rate === null) {
            $win_set = $this->redisP_get('win_rate');
            if (!empty($win_set)) {
                $this->win_rate = json_decode($win_set, true);
            } else {
                /* 无全局开配置，不记录 */
                $this->win_rate = '';
            }
        }
        if ($this->win_rate == '') {
            return false;
        } elseif (empty($this->win_rate[$gid]) && empty($this->win_rate[$gid - 50])) {
            /* 是否关注: 游戏有设置，且单注金额不小于设置 */
            return false;
        } elseif (empty($this->win_rate['redis'])) {
            $this->rds = $this->redis_public;
            return $this->rds;
        }
        if (!empty($this->rds)) {
            return $this->rds;
        }
        $dsn = parse_url($this->win_rate['redis']);
        $dsn['host'] = isset($dsn['host']) ? ($dsn['host']) : '127.0.0.1';
        $dsn['port'] = isset($dsn['port']) ? ($dsn['port']) : '6379';
        $dsn['user'] = isset($dsn['user']) ? ($dsn['user']) : '';
        $dsn['path'] = isset($dsn['path']) ? (substr($dsn['path'], 1)) : '1';
        $this->rds = new Redis();
        $this->rds->connect($dsn['host'], $dsn['port']);
        $this->rds->auth($dsn['user']);
        $this->rds->select($dsn['path']);
        return $this->rds;
    } /* }}} */

    /**
     * 系统彩全局 添加参与计算的注单
     */
    public function set_bets_list($dbn, $gname, $gid = 0, $issue = '', $k = '', $bet = []) /* {{{ */
    {
        /* 无全局开配置，不记录 */
        if ($this->win_rate === '') {
            return false;
        }
        if ($dbn == 'w02') {
            return false;
        }
        if (empty($this->rds) && $this->init_rds($gid) == false) {
            return false;
        }
        if (isset($this->win_rate['min_price']) && $this->win_rate['min_price'] > $bet[key($bet)][5]) {
            return false;
        }

        $key = 'bets:'.$gname.':'.$issue;
        $this->rds->hset($key, $dbn.$k, json_encode($bet));
        $this->rds->expire($key, EXPIRE_24);

        return true;
    } /* }}} */

    /**
     * 系统彩全局 删除不计算的注单
     */
    public function del_bets_list($dbn, $gname, $gid = 0, $issue = '', $k = '') /* {{{ */
    {
        /* 无全局开配置，不记录 */
        if ($this->win_rate === '') {
            return false;
        }
        if (empty($this->rds) && $this->init_rds($gid) == false) {
            return false;
        }
        $key = 'bets:'.$gname.':'.$issue;
        $this->rds->hdel($key, $dbn.$k);

        return true;
    } /* }}} */

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
     * @brief 取 一条 (之前) 未结算 gc:bets:$gname:$issue 队列名
     * @access public
     * @param $gname    gc:bets:xxx:
     * @param $issue    当前期号
     * @return false/之前未结算期号
     */
    public function get_issue($gname = '', $issue = '') /* {{{ */
    {
        $key = 'bets:';
        if (!empty($gname)) {
            $key = $key.$gname.':';
        }
        $this->select_redis_db(REDIS_LONG);
        $v = $this->redis_keys($key.'*');
        $c = count($v);
        $issues = [];       /* 队列中所有的未结算期号 (包括追号) */
        $ret = false;
        /* 取期号 */
        if ($c) {
            foreach ($v as $value) {
                $issues[] = substr($value, strrpos($value, ':') + 1);
            }
        }
        /* 排序期号, 并找到当前指定期号的位置(有追号时用到), 随机返回当前期号之前的未结算期号 */
        if ($c > 1) {
            $c_i = $c - 1;
            sort($issues);
            if (empty($issue)) {
                return $issues[rand(0, $c_i)];
            }
            foreach ($issues as $i => $i_v) {
                if ($i_v >= $issue) {
                    $c_i = $i;
                    break;
                }
            }
            $ret = $issues[rand(0, $c_i)];
        } elseif ($c == 1 && $issues[0] <= $issue) {    /* 如果只剩一期，检测此期号是不是追号 */
            $ret = $issues[0];
        }

        return $ret;
    } /* }}} */

    /**
     * @brief 设置 一条 (之前) 开奖结果
     *  gcopen:$gid 官方彩队列名
     *  gc0:gcopen:$gid 系统自开队列名
     * @access public
     * @param $sn       dbn
     * @param $gid      gid
     * @param $issue    当前期号
     * @param $lottery  当前期号开奖结果
     * @return false/true
     */
    public function set_open_issue($sn = '', $gid = '', $issue = '', $lottery = '', $time = '') /* {{{ */
    {
        $key = 'gcopen:'.$gid;
        if (!empty($sn)) {
            $key = 'gcopen:'.$sn.':'.$gid;
        }
        if (empty($time)) {
            $time = date('Y-m-d H:i:s');
        }
        $len = $this->redisP_hlen($key);
        if ($len >= 50) {
            $fields = $this->redisP_hkeys($key);
            //sort($fields);
            $this->redisP_hdel($key, $fields[0]);
        }
        $ret = $this->redisP_hset($key, $issue, json_encode([$lottery, $time]));

        return $ret;
    } /* }}} */

    /**
     * @brief 获取 (之前) 开奖结果
     *  gcopen:$gid 官方彩队列名
     *  gc0:gcopen:$gid 系统自开队列名
     * @access public
     * @param $sn       dbn
     * @param $gid      gid
     * @param $count    取结果条数,倒序排列,最多50条
     * @return false/true
     */
    public function get_open_issue($sn = '', $gid = '', $count = 5) /* {{{ */
    {
        $key = 'gcopen:'.$gid;
        if (!empty($sn)) {
            $key = 'gcopen:'.$sn.':'.$gid;
        }
        $ret = $this->redisP_hgetall($key);
        krsort($ret);
        $c = count($ret);
        if ($count < $c) {
            $ret = array_slice($ret, 0, $count, true);
        }
        return $ret;
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
     * @brief 获取注单列表
     * @access public
     * @return 注单列表
     */
    public function getlist($uid, $show_num = 10, $page = 1) /* {{{ */
    {
        $page = $page < 1 ? 1 : $page;
        $res = $this->db->order_by('id desc')->limit($show_num, $show_num * ($page - 1))->get_where('bet_index', ['uid' => $uid]);

        return $res->result_array();
    } /* }}} */

    /**
     * @brief 下注单
     *      写下注数据库, 下注 redis 队列, 日结 redis 记录
     *      gc:bets:lhc:20170302
     *          NOTE: 无限代理分支已经不再支持追号
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,追号时间或编号[boyou分支追号中奖后停止追号使用]]}
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,是否测试注单[yicai无线分支用]]}
     *          {lm_z1:[order_num,uid,contents,price,counts,price_sum,rate,rebate,chase_time/is_test]}
     *          {"5x_5xzhx_zh":["909706092047226921","113","1,2|2|3,4|4|5","1",20,"20","98000,9800,980,98,9.8","0.0",0]}
     * @access public
     * @param string    $dbn    数据库名(站点id)
     * @param string    $gname  游戏简写代号
     * @param string    $play   游戏玩法代号
     * @param array     $bet    游戏下注内容
     * @param int       $win_stop 追号是否中奖后停止:0不停，timestamp停 / 是否测试注单:1测试，0正常
     * @return
     */
    public function bet($dbn = '', $gname = '', $play = '', $bet = [], $win_stop = 0) /* {{{ */
    {
        /*
        $bet['order_num'] = $this->order_num($bet['gid'], $bet['uid']);
        $bet['bet_time'] = time();

        $ret = $this->db->insert('bet_index', array('uid' => $bet['uid'], 'order_num' => $bet['order_num'], 'issue' => $bet['issue'], 'created' => $bet['bet_time']));
        if ($ret == false) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', 'uid:'.$bet['uid'].':order_num duplicate:'.$bet['order_num']);
            $bet['order_num'] = $this->order_num($bet['gid'], $bet['uid']);
            $ret = $this->db->insert('bet_index', array('uid' => $bet['uid'], 'order_num' => $bet['order_num'], 'issue' => $bet['issue'], 'created' => $bet['bet_time']));
            if ($ret == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', 'uid:'.$bet['uid'].':order_num duplicate 2:'.$bet['order_num']);
                $bet['order_num'] = $this->order_num($bet['gid'], $bet['uid']);
                $ret = $this->db->insert('bet_index', array('uid' => $bet['uid'], 'order_num' => $bet['order_num'], 'issue' => $bet['issue'], 'created' => $bet['bet_time']));
                if ($ret == false) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', 'uid:'.$bet['uid'].':order_num duplicate 3:'.$bet['order_num']);
                    return false;
                }
            }
        }
        */

        if (!$this->db->insert('bet_'.$gname, $bet)) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', $bet['issue'].':'.$bet['uid'].':bet1:insert_error:'.json_encode($bet));
            if (!$this->db->insert('bet_'.$gname, $bet)) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', $bet['issue'].':'.$bet['uid'].':bet2:insert_error:'.json_encode($bet));
                //return false;
            }
        }
        /* 下注队列 */
        $bets_queue = 'bets:'.$gname.':'.$bet['issue'];
        $redis_bet = [$play => [$bet['order_num'], $bet['uid'], $bet['contents'], $bet['price'], $bet['counts'], $bet['price_sum'], $bet['rate'], $bet['rebate'], $win_stop]];
        /* 下注单放到持久库 */
        $this->select_redis_db(REDIS_LONG);
        $flag = $this->redis_rpush($bets_queue, json_encode($redis_bet));
        if (!$flag) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', $bet['issue'].':'.$bet['uid'].':bet:redis_rpush_error:'.$flag.json_encode($bet));
        }
        $this->redis_expire($bets_queue, EXPIRE_24 * 10);
        /* 无线代理返水记录 */
        $this->agent_rebate($dbn, $gname, $bet);
        /* 测试注单不参与 */
        if ($win_stop != 1) {
            $this->set_bets_list($dbn, $gname, $bet['gid'], $bet['issue'], $bet['order_num'], $redis_bet);
        }

        return true;
    } /* }}} */

    /**
     * @brief 结算
     *      消费下注 redis 队列, 处理日结 redis 记录，生产中奖队列
     *      gc:bets:lhc:20170302
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,追号时间或编号[boyou分支追号中奖后停止追号使用]]}
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,是否测试注单[yicai无线分支用]]}
     *          {lm_z1:[order_num,uid,contents,price,counts,price_sum,rate,rebate,chase_time/is_test]}
     *          {"5x_5xzhx_zh":["909706092047226921","113","1,2|2|3,4|4|5","1",20,"20","98000,9800,980,98,9.8","0.0",1501156312]}
     *      x 中奖[和局]队列
     *      x gc:bets_wins:lhc:20170302
     *      x    {lm_z1:[order_num,uid,win_contents,win_counts,win_price,status]}
     * @access public
     * @param string    $dbn        数据库名(站点id)
     * @param string    $gname      游戏简写代号
     * @param string    $issue      期号
     * @return
     */
    public function settlement($dbn = '', $gname = '', $gid = 0, $issue = '', $lottery = []) /* {{{ */
    {
        /* 下注队列 */
        $bets_queue = 'bets:'.$gname.':'.$issue;
        $tmp_queue = 'bets_tmp:'.$gname.':'.$issue;
        /* 撤单 hash */
        $cancel_hash = 'bets_cancel';
        /* 追号中奖后停止期号队列 queue */
        $chase_queue = 'chase:cancel:'.$gid;
        /* 返回结算结果 */
        $result = ['counts'=>0, 'bets_counts'=>0, 'price'=>0, 'valid_price'=>0, 'win_counts'=>0, 'win_price'=>0, 'return_counts'=>0, 'return_price'=>0];

        /* 加载游戏结算插件 */
        $plugin = null;
        $plugin_name = 'games_settlement_'.$gname;
        $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
        if (file_exists($plugin_file)) {
            include_once($plugin_file);
            if (class_exists($plugin_name)) {
                $plugin = new $plugin_name;
            }
        }

        /* 下注单放到持久库 */
        $this->select_redis_db(REDIS_LONG);
        /* 获取游戏当期开奖号 */
        if (method_exists($plugin, 'wins_balls')) {
            $lottery = $plugin->wins_balls($lottery);
        }
        /**
         * 每个用户多个订单金额修改组织成一条记录：中，和，返水，
         * $balance_list [$uid][BALANCE_WIN] => array(counts, price_sum, 'order_num1,order_num2...', 'order1_price,order2_price...');
         * $balance_list [$uid][BALANCE_HE] => array(counts, price_sum, 'order_num1,order_num2...', 'order1_price,order2_price...');
         * $balance_list [$uid][BALANCE_RETURN] => array(counts, price_sum, 'order_num1,order_num2...', 'return_price1,return_price2...');
         */
        $balance_list = [];
        /**
         * 用户游戏日报表
         * $report_list [$uid] = [];
         * uid => [下注笔数，下注量，总下注额，有效下注额(和局时需要减下注量和有效下注额)，中奖注数，中奖额，返水注数，返水额]
         * ['num'=>0, 'bets_num'=>0, 'price'=>0, 'valid_price'=>0, 'num_win'=>0, 'lucky_price'=>0, 'num_return'=>0, 'return_price'=>0, 'report_time'=>0];
         */
        $report_list = [];

        //$bets_counts = 0;
        for ($i = 0; ; ) {
            $bet = $this->redis_lpop($bets_queue);
            //$bet = "{\"2th_2tdx_bzxh\":[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]}";
            //$bet = "{\"2th_2tfx\":[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]}";
            //$bet = "{\"q3_q3zx_zx3\":[\"1570329190206858\",1,\"0,1,2,3,4,5,6,7,8,9\",2,1,2,\"6.12\",13]}";
            //$bet = "{\"2bth_bzxh\":[\"1570405".date('is').substr(microtime(), 2, 7)."\",1,\"1,2,3,4,5,6\",2,15,30,\"6.12\",13]}";
            //$bet = "{\"z1_10z1\":[\"037".date('mdis').substr(microtime(), 2, 7)."\",1,\"1,2,3,33,4,15,6,7,8,9\",2,1,2,\"1.8\",0]}";
            //$bet = "{\"bz\":[\"1570329155205234\",1,\"169\",2,1,2,\"1.97\",0]}";
            if ($bet == false) {
                /* done */
                break;
            }
            $this->redis_rpush($tmp_queue, $bet);
            $bet = json_decode($bet, true);
            //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_bet_'.date('Ym').'.log', $issue.':'.$bet, false);
            $fn = key($bet);
            $bet = $bet[$fn];
            $fn_settlement = 'settlement_'.$fn;
            /**
             * 退款检测redis
             * 检测此注单是否已经取消/处理过
             */
            $ret = $this->redis_hget($cancel_hash, $bet[0]);
            if (!empty($ret)) {
                $this->redis_hdel($cancel_hash, $bet[0]);
                $this->redis_rpop($tmp_queue);
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':cancel_order.uid:'.$bet[1].':'.$bet[0].'='.$ret, false);
                continue;
            }
            /* 测试注单不计结算统计 */
            if ($bet[8] != 1) {
                $result['counts']++;                         // 总结算笔数
                $result['bets_counts'] += $bet[4];           // 总有效结算注数(减和局)
                $result['price'] += $bet[5];                 // 总金额
                $result['valid_price'] += $bet[5];           // 总有效金额(减和局)
            }
            /* 用户日报表 */
            if (isset($report_list[$bet[1]])) {
                $report_list[$bet[1]]['num']++;
                $report_list[$bet[1]]['bets_num'] += $bet[4];
                $report_list[$bet[1]]['price'] += $bet[5];
                $report_list[$bet[1]]['valid_price'] += $bet[5];
            } else {
                $report_list[$bet[1]] = ['num'=>1, 'bets_num'=>$bet[4], 'price'=>$bet[5], 'valid_price'=>$bet[5], 'num_win'=>0, 'lucky_price'=>0, 'num_return'=>0, 'return_price'=>0, 'report_time'=>0];
            }
            /**
             * 中奖，和局，未中奖
             * ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
             * ret = array('win_contents'=>null, 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
             * ret = null;
             */
            $ret = array();
            if (method_exists($plugin, $fn_settlement)) {
                $ret = $plugin->$fn_settlement($bet, $lottery);
            }
            /* 统计用户打码量 打和不算打码量 */
            if (!isset($ret['status']) || $ret['status'] != STATUS_HE) {
                $this->redis_hincrbyfloat('user:dml', $bet[1], $bet[5]);
            }
            /* 处理中奖[或和局]结果 */
            if (isset($ret['price_sum']) && $ret['price_sum'] > 0) {
                if ($ret['status'] == STATUS_WIN) {
                    /* 中奖 */
                    $ret['price_sum'] = round($ret['price_sum'], 3);
                    $ret['win_contents'] = json_encode($ret['win_contents']);
                    /* 测试注单不计结算统计 */
                    if ($bet[8] != 1) {
                        $result['win_counts'] += $ret['win_counts'];        // 中奖注数
                        $result['win_price'] += $ret['price_sum'];          // 中奖额
                    }
                    
                    $report_list[$bet[1]]['num_win'] += $ret['win_counts'];
                    $report_list[$bet[1]]['lucky_price'] += $ret['price_sum'];
                    /* 追号中奖后停止 [无线分支去掉] */
                    //if (!empty($bet[8]) && $bet[8] > 1) {
                    //    $this->redis_rpush($chase_queue, json_encode([$bet[1], $bet[8], $issue]));
                    //}
                    /* 统计用户 中奖时的中奖额 */
                    $this->redis_hincrbyfloat('user:win_dml', $bet[1], $ret['price_sum']);
                } else {
                    /* 打和 */
                    $ret['win_contents'] = '';
                    $ret['win_counts'] = $bet[4];
                    /* 测试注单不计结算统计 */
                    if ($bet[8] != 1) {
                        $result['bets_counts'] -= $bet[4];          // 总有效结算注数(减和局)
                        $result['valid_price'] -= $bet[5];          // 总有效金额(减和局)
                    }

                    $report_list[$bet[1]]['bets_num'] -= $bet[4];
                    $report_list[$bet[1]]['valid_price'] -= $bet[5];
                }
                $ret['order_num'] = $bet[0];
                $ret['uid'] = $bet[1];
                $ret['created'] = time();
                //$redis_bet = [$fn => [$ret['order_num'], $ret['uid'], $ret['win_contents'], $ret['win_counts'], $ret['price_sum'], $ret['status']]];
                /** TODO: 直接派奖？
                 * 如果为了减少更新用户余额记录(合并用户每期的中奖,和局记录),
                 * 可在此处统计BALANCE_WIN和BALANCE_HE 并稍后[for结构外]统计更新和记录。
                 */
                if (!$this->db->insert('bet_wins', $ret)) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                            $issue.':insert1 error.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'], true);
                    if (!$this->db->insert('bet_wins', $ret)) {
                        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                                $issue.':insert2 error.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'], true);
                        continue;
                    }
                }
                //{
                    $type = ($ret['status'] == STATUS_WIN) ? BALANCE_WIN : BALANCE_HE;
                    if (isset($balance_list[$ret['uid']][$type])) {
                        $balance_list[$ret['uid']][$type] = array($balance_list[$ret['uid']][$type][0] + $ret['win_counts'],
                            $balance_list[$ret['uid']][$type][1] + $ret['price_sum'], $balance_list[$ret['uid']][$type][2].','.$ret['order_num'],
                            $balance_list[$ret['uid']][$type][3].','.$ret['price_sum']);
                    } else {
                        $balance_list[$ret['uid']][$type] = array($ret['win_counts'], $ret['price_sum'], $ret['order_num'], $ret['price_sum']);
                    }
                    //if ($this->update_banlace($ret['uid'], $ret['price_sum'], $ret['order_num'], $type, '派彩/打和')) {
                        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                                $issue.':'.$fn.':wins ok.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'].' '.$type);
                    //} else {
                        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                        //    $issue.':change_balance error.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'].' '.$type);
                        //continue;
                    //}
                //}
            }
            /* 实时返水？ 如果要高效率处理分开返水，可以写入 redis 队列 */
            if (/* $real_time_rebate && */$bet[7] > 0) {
                /* 非和局，则返水 */
                if (empty($ret['price_sum']) || $ret['status'] == STATUS_WIN) {
                    // $return_price = $bet['price_sum'] * $bet['rebate'] * 0.01;
                    $return_price = round($bet[5] * $bet[7] * 0.01, 3);
                    /* 测试注单不计结算统计 */
                    if ($bet[8] != 1) {
                        $result['return_counts'] += $bet[4];                // 返水注数
                        $result['return_price'] += $return_price;           // 返水额
                    }
                    if (isset($balance_list[$bet[1]][BALANCE_RETURN])) {
                        $balance_list[$bet[1]][BALANCE_RETURN] = array($balance_list[$bet[1]][BALANCE_RETURN][0] + $bet[4],
                            $balance_list[$bet[1]][BALANCE_RETURN][1] + $return_price, $balance_list[$bet[1]][BALANCE_RETURN][2].",'".$bet[0]."'",
                            $balance_list[$bet[1]][BALANCE_RETURN][3].','.$return_price);
                    } else {
                        $balance_list[$bet[1]][BALANCE_RETURN] = array($bet[4], $return_price, "'".$bet[0]."'", $return_price);
                    }
                    //$report_list[$bet[1]]['num_return'] += $bet[4];
                    //$report_list[$bet[1]]['return_price'] += $return_price;
                    //if ($this->update_banlace($bet[1], $return_price, $bet[0], BALANCE_RETURN, '返水')) {
                    //}
                }
            }
            $this->redis_rpop($tmp_queue);

            //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':settlement '.$i);
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':settlement done:'.$result['bets_counts'], true);

        /* 先 派彩 do_win/do_he/do_return */
        foreach ($balance_list as $uid => $v) {
            if (empty($v[BALANCE_WIN])) {
                continue;
            }
            if ($this->update_banlace($uid, $v[BALANCE_WIN][1], $v[BALANCE_WIN][2], BALANCE_WIN, '派彩:'.$v[BALANCE_WIN][3])) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                    $v[BALANCE_WIN][2].':wins ok.uid:'.$uid.':price:'.$v[BALANCE_WIN][0].'='.$v[BALANCE_WIN][1].' BALANCE_WIN');
            } else {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                    $v[BALANCE_WIN][2].':change_balance error.uid:'.$uid.':price:'.$v[BALANCE_WIN][0].'='.$v[BALANCE_WIN][1].' BALANCE_WIN');
            }
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':do_wins done:'.$result['win_counts'], true);

        /* 再 打和，返水 do_he/do_return */
        foreach ($balance_list as $uid => $v) {
            /* 打和 */
            if (!empty($v[BALANCE_HE])) {
                if ($this->update_banlace($uid, $v[BALANCE_HE][1], $v[BALANCE_HE][2], BALANCE_HE, '打和:'.$v[BALANCE_HE][3])) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                        $v[BALANCE_HE][2].':he ok.uid:'.$uid.':price:'.$v[BALANCE_HE][0].'='.$v[BALANCE_HE][1].' BALANCE_HE');
                } else {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                        $v[BALANCE_HE][2].':change_balance error.uid:'.$uid.':price:'.$v[BALANCE_HE][0].'='.$v[BALANCE_HE][1].' BALANCE_HE');
                }
            }
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':v:'.json_encode($v));
            /* 返水 */
            if (!empty($v[BALANCE_RETURN]) && $v[BALANCE_RETURN][1] >= 0.001) {
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':v:RETURN:'.json_encode($v[BALANCE_RETURN]));
                @$this->agent_rebate_ok($dbn, $gname, $v[BALANCE_RETURN][2]);
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':v:RETURN ok:');
                /*
                if ($this->update_banlace($uid, $v[BALANCE_RETURN][1], $v[BALANCE_RETURN][2], BALANCE_RETURN, '返水:'.$v[BALANCE_RETURN][3])) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                        $v[BALANCE_RETURN][2].':rate ok.uid:'.$uid.':price:'.$v[BALANCE_RETURN][0].'='.$v[BALANCE_RETURN][1].' BALANCE_RETURN');
                } else {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                        $v[BALANCE_RETURN][2].':change_balance error.uid:'.$uid.':price:'.$v[BALANCE_RETURN][0].'='.$v[BALANCE_RETURN][1].' BALANCE_RETURN');
                }
                */
            }
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':he/rate done.', true);

        /* 追号中奖后停止处理 [无线分支去掉] */
        //@$this->do_chase_stop($dbn, $gname, $gid);
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':all done:'.$result['counts'], true);

        /* 用户日报表更新 */
        $this->refresh_statistics($dbn, $gid, $report_list);
        return $result;
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
            @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log',
                    $order_num.':already settlement.uid:'.$uid.':price:'.$price_sum.':issue:'.$order['issue'].':'.json_encode($order));
            return false;
        }
        $counts = $order['counts'];
        /* 检测是否有撤单或结算过 */
        $in_wins = $this->info($order_num, 'wins');
        if (count($in_wins)) {
            @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log',
                    $order_num.':in bet_wins error.uid:'.$uid.':price:'.$price_sum.':status:'.$in_wins['status'].json_encode($in_wins));
            return false;
        }

        if ($this->db->insert('bet_wins', array('order_num' => $order_num, 'uid' => $uid, 'price_sum' => $price_sum, 'status' => STATUS_CANCEL, 'created' => time()))) {
            if ($this->update_banlace($uid, $price_sum, $order_num, BALANCE_CANCEL, '撤单')) {
                /* 下注单放到持久库 */
                $this->select_redis_db(REDIS_LONG);
                $this->redis_hset($cancel_hash, $order_num, STATUS_CANCEL);
                @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':cannel ok.uid:'.$uid.':price:'.$price_sum);
                $this->del_bets_list($dbn, $gname, $gid, $order['issue'], $order_num);
            } else {
                @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':change_balance error.uid:'.$uid.':price:'.$price_sum);
            }
        } else {
            @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':insert bet_wins error.uid:'.$uid.':price:'.$price_sum);
            return false;
        }
        return true;
    } /* }}} */

    /**
     * @brief 产生唯一注单号
     * @access public
     * @return
     */
    public function order_num($gid, $uid) /* {{{ */
    {
        $micro = substr(microtime(), 2, 4);
        $order_num = ($gid < 10 ? '90' : '9').$gid.substr(date('ymdHis'), 1).$micro;
        return $order_num;
    } /* }}} */

    /**
     * @brief 处理追号中奖后停止设置
     *      从中奖后停止队列中取需要停止的期号数据
     *      [uid,chase_time,issue]
     *      cli 执行，model 需要 init()
     * @access  public
     * @param   $dbn    sn
     * @param   $gname  aj3fc
     * @param   $gid    gid
     * @return
     */
    public function do_chase_stop($dbn = '', $gname = '', $gid = '') /* {{{ */
    {
        /* 追号中奖后停止期号队列 queue */
        $chase_queue = 'chase:cancel:'.$gid;
        /* 取追号期号和时间 */
        //$this->load->model('open_time_model');
        //$this->open_time_model->init($dbn);
        //$chase_info = $this->open_time_model->get_zhkithe_list($gid, true);
        for ($i = 0; ;) {
            $bet = $this->redis_lpop($chase_queue);
            if ($bet == false) {
                /* done or last issue */
                break;
            }
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_chase_stop_'.date('Ym').'.log', $gname.':bet:'.$bet);
            //$this->redis_rpush($chase_queue, $bet);
            $bet = json_decode($bet, true);
            $chase_key = 'chase:'.$gid.':'.$bet[0].':'.$bet[1];
            $orders = $this->redis_get($chase_key);
            if ($orders == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_chase_stop_'.date('Ym').'.log', $chase_key.':empty.');
                continue;
            }
            $orders = json_decode($orders, true);
            foreach ($orders as $order => $issue) {
                if ($issue > $bet[2]) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_chase_stop_'.date('Ym').'.log', $chase_key.':cancel:'.$order.':'.$issue);
                    $this->cancel_order($dbn, $gname, $gid, $bet[0], $order);
                }
            }
            //$this->redis_del($chase_key);
        }
        return true;
    } /* }}} */

    /**
     * @brief 笔数每日已结/未结统计
     * @access public
     * @param   $act = bet, cancel, settlement;
     * @param   int $counts = 正/负
     *      redis:
     *          gc:report:bets:20170328  1
     *          gc:report:stts:20170328  1
     * @return
     */
    public function bet_counts_redis($act = '', $counts = 0) /* {{{ */
    {
        $day = date('Ymd');
        $bets_key = 'report:bets:'.$day;
        $stts_key = 'report:stts:'.$day;

        if ($act == 'bet') {
            $this->redis_incrby($bets_key, $counts);
        } elseif ($act == 'settlement') {
            $this->redis_incrby($stts_key, $counts);
        } elseif ($act == 'cancel') {
            $this->redis_decrby($bets_key, $counts);
        }
        $this->redis_expire($bets_key, EXPIRE_48);
        $this->redis_expire($stts_key, EXPIRE_48);
        return true;
    } /* }}} */

    /**
     * @brief 刷新统计
     *      >hget gc:report:3:20170503 1
     *      "[8,16,16,0,0,0,0]"
     *      redis:
     *          gc:report:3:20170328
     *          uid => [下注笔数，下注量，总下注额，有效下注额(和局时需要减下注量和有效下注额)，中奖注数，中奖额，返水注数，返水额]
     * @access public
     * @param string    $dbn    数据库名(站点id)
     * @param string    $gid    游戏id
     * @param array     $report_list    用户日报表记录
     * @return
     */
    public function refresh_statistics($dbn = '', $gid = 0, & $report_list = []) /* {{{ */
    {
        $day = date('Y-m-d');
        $now = time();

        //insert into gc_report (gid,uid,report_date,num,report_time) values (3,88,'20170504',2,UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE num=num+2,report_time=unix_timestamp();
        foreach ($report_list as $uid => $v) {
            $v['gid'] = $gid;
            $v['uid'] = $uid;
            $v['report_date'] = $day;
            $v['report_time'] = $now;
            //$v = ['gid' => $gid, 'uid' => $uid, 'report_date'=>$day, 'num' => $v['num'], 'bets_num' => $v['bets_num'], 'price' => $v['price'], 'valid_price' => $v['valid_price'],
            //    'num_win' => $v['num_win'], 'lucky_price' => $v['lucky_price'], 'num_return' => $v['num_return'], 'return_price' => $v['return_price'], 'report_time' => $now];
            $sql = $this->db->insert_string('report', $v);
            $sql .= " ON DUPLICATE KEY UPDATE num=num+{$v['num']},bets_num=bets_num+{$v['bets_num']},price=price+{$v['price']},valid_price=valid_price+{$v['valid_price']},num_win=num_win+{$v['num_win']},lucky_price=lucky_price+{$v['lucky_price']},num_return=num_return+{$v['num_return']},return_price=return_price+{$v['return_price']},report_time='{$v['report_time']}'";
            $this->db->query($sql);
            //@wlog(APPPATH.'logs/bets/'.$dbn.'_day_report_'.date('Ym').'.log', $uid.'-'.$sql);
        }
        return true;
    } /* }}} */

    /**
     * @brief 更新结算状态和记录
     *      settlement => [下注笔数，下注量，总下注额，有效下注额(和局时减下注量和有效下注额)，中奖注数，中奖额，返水注数，返水额]
     * @access public
     * @param string    $gid  游戏id
     * @param string    $issue  期号
     * @param string    $s[ettlement]    结算后总数据信息
     * @return
     */
    public function refresh_settlement($gid = 0, $issue = '', $lottery = '', $s = []) /* {{{ */
    {
        $now = time();
        $v = ['gid' => $gid, 'issue' => $issue, 'lottery' => $lottery];
        $v['counts'] = isset($s['counts']) ? $s['counts'] : 0;
        $v['bets_counts'] = isset($s['bets_counts']) ? $s['bets_counts'] : 0;
        $v['price'] = isset($s['price']) ? $s['price'] : 0;
        $v['valid_price'] = isset($s['valid_price']) ? $s['valid_price'] : 0;
        $v['win_counts'] = isset($s['win_counts']) ? $s['win_counts'] : 0;
        $v['win_price'] = isset($s['win_price']) ? $s['win_price'] : 0;
        $v['return_counts'] = isset($s['return_counts']) ? $s['return_counts'] : 0;
        $v['return_price'] = isset($s['return_price']) ? $s['return_price'] : 0;
        $v['status'] = STATUS_END;
        $v['created'] = isset($s['created']) ? $s['created'] : $now;    /* 第一个进程结算开始时间 */
        $v['updated'] = $now;                                           /* 最后一个进程结算完成时间 */
        if (empty($lottery)) {
            $v['process'] = 0;
        }

        //insert into gc_bet_settlement (gid,issue,counts,bets_counts,price,vaild_price,win_counts,win_price,return_counts,return_price,status,created) values () ON DUPLICATE KEY UPDATE counts=counts+2,bets_counts=bets_counts+2,updated=$now;
        $sql = $this->db->insert_string('bet_settlement', $v);
        $sql .= " ON DUPLICATE KEY UPDATE lottery='$lottery',counts=counts+{$v['counts']},bets_counts=bets_counts+{$v['bets_counts']},
                price=price+{$v['price']},valid_price=valid_price+{$v['valid_price']},
                win_counts=win_counts+{$v['win_counts']},win_price=win_price+{$v['win_price']},
                return_counts=return_counts+{$v['return_counts']},return_price=return_price+{$v['return_price']},status={$v['status']},process=process+1,updated='$now'";
        $this->db->query($sql);
        return true;
    } /* }}} */

    /**
     * @brief 代理待返水记录
     * @access public
     * @param string    $dbn    数据库名(站点id)
     * @param string    $gname  游戏简写代号
     * @param array     $bet[]  游戏下注内容
     * @return
     */
    public function agent_rebate($dbn = '', $gname = '', & $bet = []) /* {{{ */
    {
        $now = date('Y-m-d H:i:s');
        /* 检测游戏是否支持代理返水 */
        if (empty(AGENT_GAMES[$bet['gid']]) || $bet['price_sum'] < 0.2) {
            //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', '游戏不支持返水或下注金额过低:'.json_encode($bet));
            return false;
        }
        $games_type = AGENT_GAMES[$bet['gid']];
        /* 获取用户的代理线 */
        $key = TOKEN_CODE_AGENT.':line:'.$bet['uid'];
        $this->select_redis_db(REDIS_DB);
        $agent_list = $this->redis_get($key);
        //$agent_list = '{"20521":{"ssc":8,"k3":8,"11x5":8,"fc3d":8,"pl3":8,"bjkl8":8,"pk10":8,"lhc":8},"20526":{"ssc":7,"k3":6.5,"11x5":7,"fc3d":6,"pl3":6,"bjkl8":7,"pk10":5.6,"lhc":6.2},"20527":{"ssc":3,"k3":5.5,"11x5":6,"fc3d":4,"pl3":5,"bjkl8":6,"pk10":5,"lhc":5.2}}';
        if (!$agent_list) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $bet['uid'].'无代理线信息！');
            return false;
        }
        $agent_list = json_decode($agent_list, true);
        //ksort($agent_list);
        /**
         * 计算用户代理线的返水额
         * {"20521":{"ssc":8,"k3":8,"11x5":8,"fc3d":8,"pl3":8,"bjkl8":8,"pk10":8,"lhc":8},
         *  "20526":{"ssc":6,"k3":6.5,"11x5":7,"fc3d":6,"pl3":6,"bjkl8":7,"pk10":5.6,"lhc":6.2}}
         */
        $a_count = count($agent_list);
        if ($a_count <= 1) {
            /* 当前为总代理 或 没有代理线信息，不给当前下注用户返水 */
            return true;
        }
        $rebates = [];
        $i = 0;
        foreach ($agent_list as $_uid => $_v) {
            if (empty($_v[$games_type])) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Ym').'.log', $bet['uid'].'游戏返水类型错误:'.$games_type.':'.json_encode($_v));
                return false;
            }
            $rebates[$i] = ['order_num'=>$bet['order_num'],'uid'=>$_uid, 'ouid'=>$bet['uid'], 'gid'=>$bet['gid'], 'issue'=>$bet['issue'], 'price'=>$bet['price_sum'], 'rebate'=>$_v[$games_type], 'price_rebate'=>0, 'status'=>0, 'created'=>$now];
            if ($i > 0) {
                /* 上级代理的实际返水比率=上级代理总可返水比率-上级代理给当级代理的可返水比率 */
                $rebates[$i - 1]['rebate'] = $agent_list[$rebates[$i - 1]['uid']][$games_type] - $_v[$games_type];
                $rebates[$i - 1]['price_rebate'] = round($bet['price_sum'] * $rebates[$i - 1]['rebate'] / 100, 3);
                if ($rebates[$i - 1]['price_rebate'] < 0.01) {
                    //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Ym').'.log', '返水金额过低:'.json_encode($rebates[$i - 1]));
                    unset($rebates[$i - 1]);
                }
            }
            if ($i + 1 >= $a_count) {
                if ($bet['uid'] != $rebates[$i]['uid']) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Ym').'.log', $bet['uid'].'代理线信息错误:'.json_encode($agent_list));
                    return false;
                }
                /* 不给当前下注者返水 */
                unset($rebates[$i]);
            }
            $i++;
        }
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $bet['uid'].'写返水:'.json_encode($rebates));
        /* 记录用户代理线的待返水标记为暂不可返水 */
        if (count($rebates) == 0) {
            return true;
        }
        $ret = $this->db->insert_batch('agent_rebate', $rebates);
        if (!$ret) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Ym').'.log', $bet['uid'].'写返水记录错误:'.json_encode($rebates));
            return false;
        }

        return true;
    } /* }}} */

    /**
     * @brief 代理返水标记为正常可以返水
     *  修改代理待返水记录为可以返水，
     *  之后返水通过异步定时或者手动集中加到代理帐号中
     * @access public
     * @param string    $dbn    数据库名(站点id)
     * @param string    $gname  游戏简写代号
     * @param string    $order_num   订单号(带单引号),多个以逗号分隔 
     * @return
     */
    public function agent_rebate_ok($dbn = '', $gname = '', $order_num = '') /* {{{ */
    {
        $now = date('Y-m-d H:i:s');
        /* 记录用户代理线的待返水标记为可返水 */
        $ret = $this->db->update('agent_rebate', ['status'=>1,'updated'=>$now], 'order_num in ('.$order_num.') and status=0');
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $order_num.':update agent_rebate status.'.json_encode($ret));
        if (!$ret) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log', $order_num.':update agent_rebate status error.');
            //return false;
        }
        //return true;
    } /* }}} */
}

/* end file */
