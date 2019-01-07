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

class Betstest_model extends MY_Model
{
    public function __construct() /* {{{ */
    {
        parent::__construct();
        // $this->db2 = $this->load->database('private', true);
        // $this->select_db('public');
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
        $res = $this->db->order_by('id desc')->limit($show_num, $show_num * ($page - 1))->get_where('bet_index', array('uid' => $uid));

        return $res->result_array();
    } /* }}} */

    /**
     * @brief 下注单
     *      写下注数据库, 下注 redis 队列, 日结 redis 记录
     *      gc:bets:lhc:20170302
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,追号时间或编号[追号中奖后停止追号使用]]}
	 *          {lm_z1:[order_num,uid,contents,price,counts,price_sum,rate,rebate,chase_time]}
	 *          {"5x_5xzhx_zh":["909706092047226921","113","1,2|2|3,4|4|5","1",20,"20","98000,9800,980,98,9.8","0.0",0]}
     * @access public
     * @param string    $dbn    数据库名(站点id)
     * @param string    $gname  游戏简写代号
     * @param string    $play   游戏玩法代号
     * @param array     $bet    游戏下注内容
     * @param string    $issue_day 彩票开奖日期
     * @param int       $win_stop 追号是否中奖后停止
     * @return
     */
    public function bet($dbn = '', $gname = '', $play = '', $bet = array(), $issue_day = '', $win_stop = 0) /* {{{ */
    {
        /*
        $bet['order_num'] = $this->order_num($bet['gid'], $bet['uid']);
        $bet['bet_time'] = time();

        $ret = $this->db->insert('bet_index', array('uid' => $bet['uid'], 'order_num' => $bet['order_num'], 'issue' => $bet['issue'], 'created' => $bet['bet_time']));
        if ($ret == false) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', 'uid:'.$bet['uid'].':order_num duplicate:'.$bet['order_num']);
            $bet['order_num'] = $this->order_num($bet['gid'], $bet['uid']);
            $ret = $this->db->insert('bet_index', array('uid' => $bet['uid'], 'order_num' => $bet['order_num'], 'issue' => $bet['issue'], 'created' => $bet['bet_time']));
            if ($ret == false) {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', 'uid:'.$bet['uid'].':order_num duplicate 2:'.$bet['order_num']);
                $bet['order_num'] = $this->order_num($bet['gid'], $bet['uid']);
                $ret = $this->db->insert('bet_index', array('uid' => $bet['uid'], 'order_num' => $bet['order_num'], 'issue' => $bet['issue'], 'created' => $bet['bet_time']));
                if ($ret == false) {
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', 'uid:'.$bet['uid'].':order_num duplicate 3:'.$bet['order_num']);
                    return false;
                }
            }
        }
        */
        /* 下注单放到持久库 */
        $this->select_redis_db(REDIS_LONG);

        $ret = $this->db->insert('bet_'.$gname, $bet);
        if ($ret == false) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log', $bet['issue'].':'.$bet['uid'].':insert_error:'.json_encode($bet));
            //return false;
        }
        /* 下注队列 */
        $bets_queue = 'bets:'.$gname.':'.$bet['issue'];
        $redis_bet = array($play => array($bet['order_num'], $bet['uid'], $bet['contents'], $bet['price'], $bet['counts'], $bet['price_sum'], $bet['rate'], $bet['rebate'], $win_stop));
        $this->redis_rpush($bets_queue, json_encode($redis_bet));

        /* 日结 hash 统计 */
        $this->bet_day_redis('bet', $dbn, $bet['gid'], $bet['uid'], $bet['price_sum'], $bet['counts'], $issue_day);
        
        return true;
    } /* }}} */

    /**
     * @brief 结算
     *      消费下注 redis 队列, 处理日结 redis 记录，生产中奖队列
     *      gc:bets:lhc:20170302
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,追号时间或编号[追号中奖后停止追号使用]]}
	 *          {lm_z1:[order_num,uid,contents,price,counts,price_sum,rate,rebate,chase_time]}
	 *          {"5x_5xzhx_zh":["909706092047226921","113","1,2|2|3,4|4|5","1",20,"20","98000,9800,980,98,9.8","0.0",1501156312]}
     *      x 中奖[和局]队列
     *      x gc:bets_wins:lhc:20170302
	 *      x    {lm_z1:[order_num,uid,win_contents,win_counts,win_price,status]}
     * @access public
     * @param string    $dbn        数据库名(站点id)
     * @param string    $gname      游戏简写代号
     * @param string    $issue      期号
     * @param string    $issue_day 彩票开奖日期
     * @return
     */
    public function settlement($dbn = '', $gname = '', $gid = 0, $issue = '', $lottery = array(), $issue_day = '') /* {{{ */
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
        //$this->load->model('comm_model');
        //$this->comm_model->init($dbn);

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
        $balance_list = array();

        $bets_counts = 0;
        $llen = $this->redis_llen($bets_queue);
        //$llen = 10000;
        for ($i = 0; $i < $llen; $i++) {
            $bet = $this->redis_lpop($bets_queue);
            //$bet = "{\"2th_2tdx_bzxh\":[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]}";
            //$bet = "{\"2th_2tfx\":[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]}";
            //$bet = "{\"q3_q3zx_zx3\":[\"1570329190206858\",1,\"0,1,2,3,4,5,6,7,8,9\",2,1,2,\"6.12\",13]}";
            //$bet = "{\"2bth_bzxh\":[\"1570405".date('is').substr(microtime(), 2, 7)."\",1,\"1,2,3,4,5,6\",2,15,30,\"6.12\",13]}";
            //$bet = "{\"z1_10z1\":[\"037".date('mdis').substr(microtime(), 2, 7)."\",1,\"1,2,3,33,4,15,6,7,8,9\",2,1,2,\"1.8\",0]}";
            //$bet = "{\"bz\":[\"1570329155205234\",1,\"169\",2,1,2,\"1.97\",0]}";
            //$bet = '{"2m_qezhx_zxhz":["901705261440002777","42","0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18","2",100,"200","85.0","10.0"]}';
            //$bet = '{"2m_hezhx_zxhz":["902705292138264666","42","0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18","2",100,"200","95.0","0.0"]}';
            //$bet = '{"5x_5xzhx_fs":["9117060311302357","250","0|1|2|0|0","1",2,"2","98000","0.0"]}';
            //$bet = '{"5x_5xzhx_zh":["909706092047226921","113","1,2|2|3,4|4|5","1",20,"20","98000,9800,980,98,9.8","0.0"]}';

            if ($bet == false) {
                /* done */
                break;
            }
            $this->redis_rpush($bets_queue, $bet);

            //$this->redis_rpush($tmp_queue, $bet);
            //wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_bet_'.date('Ym').'.log', $issue.':'.$bet, false);
            $bet = json_decode($bet, true);
            $fn = key($bet);
            $bet = $bet[$fn];
            $fn_settlement = 'settlement_'.$fn;
            /**
             * 退款检测redis
             * 检测此注单是否已经取消/处理过
             */
            $ret = $this->redis_hget($cancel_hash, $bet[0]);
            if (!empty($ret)) {
                //$this->redis_hdel($cancel_hash, $bet[0]);
                $this->redis_rpop($tmp_queue);
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':cancel_order.uid:'.$bet[1].':'.$bet[0].'='.$ret, false);
                continue;
            }
            $result['counts']++;                         // 总结算笔数
            $result['bets_counts'] += $bet[4];           // 总有效结算注数(减和局)
            $result['price'] += $bet[5];                 // 总金额
            $result['valid_price'] += $bet[5];           // 总有效金额(减和局)
            /* 统计用户打码量 */
            //$this->redis_hincrby('user:dml', $bet[1], $bet[4]);
            //$this->redis_hincrbyfloat('user:dml', $bet[1], $bet[5]);
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
            // 统计用户打码量 打和不算打码量
            if (!isset($ret['status']) || $ret['status'] != STATUS_HE) {
                $this->redis_hincrbyfloat('user:dml', $bet[1], $bet[5]);
            }
            /* 处理中奖[或和局]结果 */
            if (isset($ret['price_sum']) && $ret['price_sum'] > 0) {
                if ($ret['status'] == STATUS_WIN) {
                    /* 中奖 */
                    $ret['price_sum'] = round($ret['price_sum'], 3);
                    $ret['win_contents'] = json_encode($ret['win_contents']);
                    $result['win_counts'] += $ret['win_counts'];        // 中奖注数
                    $result['win_price'] += $ret['price_sum'];          // 中奖额
                    /* 追号中奖后停止 */
                    if (!empty($bet[8])) {
                        $this->redis_rpush($chase_queue, json_encode([$bet[1], $bet[8], $issue]));
                    }
                } else {
                    /* 打和 */
                    $ret['win_contents'] = '';
                    $ret['win_counts'] = $bet[4];
                    $result['bets_counts'] -= $bet[4];          // 总有效结算注数(减和局)
                    $result['valid_price'] -= $bet[5];          // 总有效金额(减和局)
                }
                $ret['order_num'] = $bet[0];
                $ret['uid'] = $bet[1];
                $ret['created'] = time();
                $redis_bet = array($fn => array($ret['order_num'], $ret['uid'], $ret['win_contents'], $ret['win_counts'], $ret['price_sum'], $ret['status']));
                /** TODO: 直接派奖？
                 * 如果为了减少更新用户余额记录(合并用户每期的中奖,和局记录),
                 * 可在此处统计BALANCE_WIN和BALANCE_HE 并稍后[for结构外]统计更新和记录。
                 */
                if ($this->db->insert('bet_wins_copy', $ret)) {
                    $type = ($ret['status'] == STATUS_WIN) ? BALANCE_WIN : BALANCE_HE;
                    if (isset($balance_list[$ret['uid']][$type])) {
                        $balance_list[$ret['uid']][$type] = array($balance_list[$ret['uid']][$type][0] + $ret['win_counts'],
                            $balance_list[$ret['uid']][$type][1] + $ret['price_sum'], $balance_list[$ret['uid']][$type][2].','.$ret['order_num'],
                            $balance_list[$ret['uid']][$type][3].','.$ret['price_sum']);
                    } else {
                        $balance_list[$ret['uid']][$type] = array($ret['win_counts'], $ret['price_sum'], $ret['order_num'], $ret['price_sum']);
                    }
                    //if ($this->update_banlace($ret['uid'], $ret['price_sum'], $ret['order_num'], $type, '派彩/打和')) {
                        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                                $issue.':'.$fn.':wins ok.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'].' '.$type);
                        /* 更新日结 hash 统计 */
                        //$act = ($ret['status'] == STATUS_WIN) ? 'win' : 'he';
                        //$this->bet_day_redis($act, $dbn, $gid, $ret['uid'], $ret['price_sum'], $ret['win_counts'], $issue_day);
                    //} else {
                        //wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                        //    $issue.':change_balance error.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'].' '.$type);
                        //continue;
                    //}
                } else {
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                            $issue.':insert error.uid:'.$ret['uid'].':'.$ret['order_num'].'='.$ret['price_sum'], true);
                    continue;
                }
            }
            /* 实时返水？ 如果要高效率处理分开返水，可以写入 redis 队列 */
            if (/* $real_time_rebate && */$bet[7] > 0) {
                /* 非和局，则返水 */
                if (empty($ret['price_sum']) || $ret['status'] == STATUS_WIN) {
                    // $return_price = $bet['price_sum'] * $bet['rebate'] * 0.01;
                    $return_price = round($bet[5] * $bet[7] * 0.01, 3);
                    $result['return_counts'] += $bet[4];                // 返水注数
                    $result['return_price'] += $return_price;           // 返水额
                    if (isset($balance_list[$bet[1]][BALANCE_RETURN])) {
                        $balance_list[$bet[1]][BALANCE_RETURN] = array($balance_list[$bet[1]][BALANCE_RETURN][0] + $bet[4],
                            $balance_list[$bet[1]][BALANCE_RETURN][1] + $return_price, $balance_list[$bet[1]][BALANCE_RETURN][2].','.$bet[0],
                            $balance_list[$bet[1]][BALANCE_RETURN][3].','.$return_price);
                    } else {
                        $balance_list[$bet[1]][BALANCE_RETURN] = array($bet[4], $return_price, $bet[0], $return_price);
                    }
                    //if ($this->update_banlace($bet[1], $return_price, $bet[0], BALANCE_RETURN, '返水')) {
                        /* 更新日结 hash 统计 */
                        //$this->bet_day_redis('return', $dbn, $gid, $bet[1], $return_price, $bet[4], $issue_day);
                    //}
                }
            }
            $this->redis_rpop($tmp_queue);

            //wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':settlement '.$i);
        }
        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':settlement done:'.$result['bets_counts'], true);

        /* 先 派彩 do_win/do_he/do_return */
        foreach ($balance_list as $uid => $v) {
            if (empty($v[BALANCE_WIN])) {
                continue;
            }
            if ($this->update_banlace($uid, $v[BALANCE_WIN][1], $v[BALANCE_WIN][2], BALANCE_WIN, '派彩:'.$v[BALANCE_WIN][3])) {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                    $v[BALANCE_WIN][2].':wins ok.uid:'.$uid.':price:'.$v[BALANCE_WIN][0].'='.$v[BALANCE_WIN][1].' BALANCE_WIN');
            } else {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                    $v[BALANCE_WIN][2].':change_balance error.uid:'.$uid.':price:'.$v[BALANCE_WIN][0].'='.$v[BALANCE_WIN][1].' BALANCE_WIN');
            }
            /* 更新日结 hash 统计 */
            $this->bet_day_redis('win', $dbn, $gid, $uid, $v[BALANCE_WIN][1], $v[BALANCE_WIN][0], $issue_day);
        }
        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':do_wins done:'.$result['win_counts'], true);

        /* 再 打和，返水 do_he/do_return */
        foreach ($balance_list as $uid => $v) {
            /* 打和 */
            if (!empty($v[BALANCE_HE])) {
                if ($this->update_banlace($uid, $v[BALANCE_HE][1], $v[BALANCE_HE][2], BALANCE_HE, '打和:'.$v[BALANCE_HE][3])) {
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                        $v[BALANCE_HE][2].':he ok.uid:'.$uid.':price:'.$v[BALANCE_HE][0].'='.$v[BALANCE_HE][1].' BALANCE_HE');
                } else {
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                        $v[BALANCE_HE][2].':change_balance error.uid:'.$uid.':price:'.$v[BALANCE_HE][0].'='.$v[BALANCE_HE][1].' BALANCE_HE');
                }
                /* 更新日结 hash 统计 */
                $this->bet_day_redis('he', $dbn, $gid, $uid, $v[BALANCE_HE][1], $v[BALANCE_HE][0], $issue_day);
            }
            /* 返水 */
            if (!empty($v[BALANCE_RETURN])) {
                if ($this->update_banlace($uid, $v[BALANCE_RETURN][1], $v[BALANCE_RETURN][2], BALANCE_RETURN, '返水:'.$v[BALANCE_RETURN][3])) {
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_'.date('Ym').'.log',
                        $v[BALANCE_RETURN][2].':rate ok.uid:'.$uid.':price:'.$v[BALANCE_RETURN][0].'='.$v[BALANCE_RETURN][1].' BALANCE_RETURN');
                } else {
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_wins_error_'.date('Ym').'.log',
                        $v[BALANCE_RETURN][2].':change_balance error.uid:'.$uid.':price:'.$v[BALANCE_RETURN][0].'='.$v[BALANCE_RETURN][1].' BALANCE_RETURN');
                }
                /* 更新日结 hash 统计 */
                $this->bet_day_redis('return', $dbn, $gid, $uid, $v[BALANCE_RETURN][1], $v[BALANCE_RETURN][0], $issue_day);
            }
        }
        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':he/rate done.', true);

        /* 追号中奖后停止处理 */
        $this->do_chase_stop($dbn, $gname, $gid);
        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':all done:'.$result['counts'], true);

        return $result;
    } /* }}} */

    /**
     * @brief 撤单
     *      写 redis 退单 hash 表，写 bet_wins 退单记录，退钱
     * @access public
     * @param string    $issue_day 彩票开奖日期
     * @return
     */
    public function cancel_order($dbn = '', $gname = '', $gid = 0, $uid = 0, $order_num = '', $issue_day = '') /* {{{ */
    {
        $cancel_hash = 'bets_cancel';
        $price_sum = 0;
        $counts = 0;
        if (empty($order_num)) {
            return false;
        }

        /* 检测订单有效性 */
        $order = $this->info($order_num, $gname);
        return false;
        if (count($order) < 1) {
            return false;
        }
        $price_sum = $order['price_sum'];
        /* 检测私库是否有开奖记录 */
        $private_open = $this->get_bet('settlement', ['issue' => (string) $order['issue'], 'gid' => $gid]);
        if ($private_open && $private_open['status'] == STATUS_END) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log',
                    $order_num.':already settlement.uid:'.$uid.':price:'.$price_sum.':issue:'.$order['issue'].':'.json_encode($order));
            return false;
        }
        $counts = $order['counts'];
        /* 检测是否有撤单或结算过 */
        $in_wins = $this->info($order_num, 'wins');
        if (count($in_wins)) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log',
                    $order_num.':in bet_wins error.uid:'.$uid.':price:'.$price_sum.':status:'.$in_wins['status'].json_encode($in_wins));
            return false;
        }

        if ($this->db->insert('bet_wins', array('order_num' => $order_num, 'uid' => $uid, 'price_sum' => $price_sum, 'status' => STATUS_CANCEL, 'created' => time()))) {
            if ($this->update_banlace($uid, $price_sum, $order_num, BALANCE_CANCEL, '撤单')) {
                /* 下注单放到持久库 */
                $this->select_redis_db(REDIS_LONG);
                $this->redis_hset($cancel_hash, $order_num, STATUS_CANCEL);
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':cannel ok.uid:'.$uid.':price:'.$price_sum);
            } else {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':change_balance error.uid:'.$uid.':price:'.$price_sum);
            }
            $this->bet_day_redis('cancel', $dbn, $gid, $uid, $price_sum, $counts, $issue_day);
        } else {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_cancel_'.date('Ym').'.log', $order_num.':insert bet_wins error.uid:'.$uid.':price:'.$price_sum);
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
        $this->load->model('open_time_model');
        $this->open_time_model->init($dbn);
        $chase_info = $this->open_time_model->get_zhkithe_list($gid, true);
        for ($i = 0; ;) {
            $bet = $this->redis_lpop($chase_queue);
            if ($bet == false || empty($chase_info)) {
                /* done or last issue */
                break;
            }
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_chase_stop_'.date('Ym').'.log', $gname.':bet:'.$bet);
            //$this->redis_rpush($chase_queue, $bet);
            $bet = json_decode($bet, true);
            $chase_key = 'chase:'.$gid.':'.$bet[0].':'.$bet[1];
            $orders = $this->redis_get($chase_key);
            if ($orders == false) {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_chase_stop_'.date('Ym').'.log', $chase_key.':empty.');
                continue;
            }
            $orders = json_decode($orders, true);
            foreach ($orders as $order => $issue) {
                if ($issue > $bet[2]) {
                    $issue_day = !empty($chase_info[$issue]) ? date('Ymd', strtotime($chase_info[$issue])) : '';
                    wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_chase_stop_'.date('Ym').'.log', $chase_key.':cancel:'.$order.':'.$issue.':'.$issue_day);
                    $this->cancel_order($dbn, $gname, $gid, $bet[0], $order, $issue_day);
                }
            }
            //$this->redis_del($chase_key);
        }
        return true;
    } /* }}} */

    /**
     * @brief 注单日结统计
     *      处理每笔订单时调用一次
     * @access public
     * @param   $act = 'bet' 下注,'win' 中奖,'he' 打和,'return' 返水,'cancel' 退单
     * @param   bet = array(gid, uid, price, counts, price_sum);
     *      redis:
     *          gc:report:3:20170328
	 *          uid => [下注笔数，下注量，总下注额，有效下注额(和局时需要减下注量和有效下注额)，中奖注数，中奖额，返水注数，返水额]
     * @param   $counts 注数
     * @param   $day    issue_day  彩票开奖日期
     * @return
     */
    public function bet_day_redis($act = 'bet', $dbn = '', $gid = 0, $uid = 0, $price_sum = 0, $counts = 0, $day = '') /* {{{ */
    {
        /* 六合彩等低频彩，需要向数据库取期数对应的开奖日期 */
        if (empty($day)) {
            $day = date('Ymd');
        }
        $bets_uid_day_hash = 'report:'.$gid.':'.$day;
        //$bets_gid_day_hash = 'report:'.$day;
        $key = $uid;

        $bet = ['all_num' => 0, 'all_counts' => 0, 'all_price' => 0, 'all_price_ok' => 0, 'win_counts' => 0, 'win_price' => 0, 'return_counts' => 0, 'return_price' => 0];
        if ($act == 'bet') {
            $bet['all_num'] = 1;
            $bet['all_counts'] = $counts;
            $bet['all_price'] = $price_sum;
            $bet['all_price_ok'] = $price_sum;
        } elseif ($act == 'win') {
            $bet['win_counts'] = $counts;
            $bet['win_price'] = $price_sum;
        } elseif ($act == 'he') {
            $bet['all_num'] = -1;
            $bet['all_counts'] = -$counts;
            $bet['all_price_ok'] = -$price_sum;
        } elseif ($act == 'return') {
            $bet['return_counts'] = $counts;
            $bet['return_price'] = $price_sum;
        } elseif ($act == 'cancel') {
            $bet['all_num'] = -1;
            $bet['all_counts'] = -$counts;
            //$bet['all_price'] = -$price_sum;
            $bet['all_price_ok'] = -$price_sum;
        }
        $v = $this->redis_hget($bets_uid_day_hash, $key);
        if ($v) {
            $v = json_decode($v, true);
            $day_v = [$bet['all_num'] + $v[0], $bet['all_counts'] + $v[1], $bet['all_price'] + $v[2], $bet['all_price_ok'] + $v[3], $bet['win_counts'] + $v[4], $bet['win_price'] + $v[5], $bet['return_counts'] + $v[6], $bet['return_price'] + $v[7]];
        } else {
            $day_v = [$bet['all_num'], $bet['all_counts'], $bet['all_price'], $bet['all_price_ok'], $bet['win_counts'], $bet['win_price'], $bet['return_counts'], $bet['return_price']];
        }
        $this->redis_hset($bets_uid_day_hash, $key, json_encode($day_v));
        $this->redis_expire($bets_uid_day_hash, EXPIRE_48 << 1);
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
     * @param string    $gname  游戏简写代号
     * @param string    $issue  期号
     * @param string    $day    issue_day  彩票开奖日期
     * @return
     */
    public function refresh_statistics($dbn = '', $gid = 0, $day = '') /* {{{ */
    {
        /* 六合彩等低频彩，需要向数据库取期数对应的开奖日期 */
        if (empty($day)) {
            $day = date('Ymd');
        }
        $bets_uid_day_hash = 'report:'.$gid.':'.$day;
        //$bets_gid_day_hash = 'report:'.$day;
        $keys = $this->redis_hkeys($bets_uid_day_hash);
        $now = time();

        //insert into gc_report (gid,uid,report_date,num,report_time) values (3,88,'20170504',2,UNIX_TIMESTAMP()) ON DUPLICATE KEY UPDATE num=num+2,report_time=unix_timestamp();
        foreach ($keys as $key) {
            $v = $this->redis_hget($bets_uid_day_hash, $key);
            $v = json_decode($v, true);
            $v = ['gid' => $gid, 'uid' => $key, 'report_date'=>$day, 'num' => $v[0], 'bets_num' => $v[1], 'price' => $v[2], 'valid_price' => $v[3],
                'num_win' => $v[4], 'lucky_price' => $v[5], 'num_return' => $v[6], 'return_price' => $v[7], 'report_time' => $now];
            $sql = $this->db->insert_string('report', $v);
            $sql .= " ON DUPLICATE KEY UPDATE num='{$v['num']}',bets_num='{$v['bets_num']}',price='{$v['price']}',valid_price='{$v['valid_price']}',
                num_win='{$v['num_win']}',lucky_price='{$v['lucky_price']}',num_return='{$v['num_return']}',return_price='{$v['return_price']}',
                report_time='{$v['report_time']}'";
            $this->db->query($sql);

            /*if ($v['return_price'] > 0) {
                $report_date = date('Y-m-d');
                $cashData['out_return_water'] = $v['return_price']; //反水金额
                $cashData['out_return_num'] = $v['num_return'];     //反水笔数
                $is = $this->collect_cash_report($key, $report_date, $cashData);    // 汇总返水到现金报表
                if ($is) {
                    //wlog(APPPATH.'logs/bets/'.$dbn.'_report_return_'.date('Ym').'.log', 'gid:'.$gid.':uid:'.$key.':return money:'.$cashData['out_return_water']);
                } else {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_report_return_error_'.date('Ym').'.log', 'gid:'.$gid.':uid:'.$key.':return money:'.$cashData['out_return_water']);
                }
            }*/
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
     * @brief 汇总返水到现金报表
     * @access public
     * @param string    $uid  用户id
     * @param string    $report_date  报表日期
     * @param string    $data    数据
     * @return
     */
    public function collect_cash_report($uid, $report_date, $data) /* {{{ */
    {
        $where['uid'] = $uid;
        $where['report_date'] = $report_date;
        $if_one = $this->get_one('id,out_return_water', 'cash_report', $where);
        $b = array();
        /* 没有记录，则为插入 */
        if (!$if_one) {
            $data['uid'] = $uid;
            $data['report_date'] = $report_date;
            $b = $this->write('cash_report', $data);

            if ($b) {
                return true;
            } else {
                return false;
            }
        }

        /* 有记录，则为更新 */
        /* 给予返水 */
        if (isset($data['out_return_water'])) {
            $data['out_return_water'] = $if_one['out_return_water']+$data['out_return_water'];
            $b = $this->db->update('cash_report', $data, $where);
        }
        if ($b) {
            return true;
        } else {
            return false;
        }
    } /* }}} */
}

/* end file */
