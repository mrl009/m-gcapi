<?php
/**
 * @file Orders.php
 * @brief
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package controllers
 * @author Langr <hua@langr.org> 2017/03/23 11:10
 *
 * $Id$
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Orders extends MY_Controller
{
    public function __construct() /* {{{ */
    {
        parent::__construct();
        $this->load->model('bets_model');
        $this->load->model('games_model');
    } /* }}} */

    /**
     * @brief 取用户游戏订单列表
     * @param int   $show   每次取列表条数
     * @param int   $page   显示第几页
     * @return 用户指定订单信息
     */
    public function index($show = 10, $page = 1) /* {{{ */
    {
        //$gid = $this->G('gid');
        $show = $show > 100 ? 10 : $show;
        $uid = $this->user['id'];
        $rows = $this->bets_model->getlist($uid, $show, $page);
        $this->return_json(OK, $rows);
    } /* }}} */

    /**
     * @brief 取用户指定游戏订单
     * @param int   $order_num    订单id
     *          为空则返回用户全部订单列表
     * @return 用户指定订单信息
     */
    public function info($order_num = 0) /* {{{ */
    {
        // $gid = $this->G('gid');
        $rows = $this->bets_model->info($order_num, 'index');
        if (count($rows) < 1) {
            $this->return_json(E_ARGS, '无效的order_num');
        }

        // $b 为列表数据，$c 为除了列表数据以外的值
        $this->return_json(OK, $rows);
    } /* }}} */

    /**
     * @brief 取消订单
     * @param int   $order_num    订单id
     *          取消订单
     * @return ok/false
     */
    public function cancel(/*$order_num = 0*/) /* {{{ */
    {
        $order_num = $this->P('order_num');
        if (empty($order_num) || strlen($order_num) < 10) {
            $this->return_json(E_ARGS, '无效的order_num');
        }
        $gid = (int) substr($order_num, 1, 2);
        $game = $this->games_model->info($gid);
        if (count($game) < 1) {
            $this->return_json(E_ARGS, '无效的gid');
        }
        /* 验证期号时间有效性 */
        $this->load->model('open_time_model');
        $issue_info = $this->open_time_model->get_kithe($gid);
        if (!empty($issue_info['is_open']) && $issue_info['kithe_time_second'] > 0) {
            $issue = $issue_info['kithe'];
        } else {
            $this->return_json(E_OP_FAIL, $issue_info['kithe'].'已封盘');
        }

        $order = $this->bets_model->info($order_num, 'index');
        if (count($order) < 1) {
            $this->return_json(E_ARGS, '无效的order_num');
        }
        /* 非本用户 */
        if ($order['uid'] != $this->user['id']) {
            $this->return_json(E_OP_FAIL, '非法访问');
        }
        /* 注单期号不等于当前可下注期号，则不可撤单 */
        if ($order['issue'] != $issue_info['kithe']) {
            $this->return_json(E_OP_FAIL, $order['issue'].'不可撤单');
        }
        $dbn = $this->games_model->sn;
        if ($this->bets_model->cancel_order($dbn, $game['sname'], $gid, $order['uid'], $order['order_num'])) {
            /* 每日结算/未结算笔数 */
            $this->bets_model->bet_counts_redis('cancel', 1);
            $this->return_json(OK);
        }

        $this->return_json(E_OP_FAIL, '无法撤单');
    } /* }}} */

    /**
     * @brief 用户下注
     *      验证期号时间有效性，
     *      验证计算下注，校验赔率和返水，检查余额额度相关，
     *      扣款，写下注表, 写下注 redis 队列，写日结 redis Hash。。。
     * @link http://api.101.com/index.php?
     * @method  POST
     * @param   $gid        game id
     * @param   $issue      期数号
     * @param   $bets = [{
     *              gid:游戏id,tid:玩法id,price:单注金额,counts:总组合注单量,price_sum:总组合金额,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }...]
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = [{
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }]
     * @param   $contents   pid_code_name,pid_code_name|pid_code_name|pid_code_name,pid_code_name
     * @param   $names      name,name|name|name,name
     * @return  下注状态
     */
    public function bet($gid = 0, $issue = '') /* {{{ */
    {
        $bets = $this->P('bets');
        //$bets = '[{"gid":3,"tid":206,"price":2,"counts":1,"price_sum":2,"rate":"1.97,1.97","rebate":0,"pids":"22420|22425,22427,22428","contents":"100||100||","names":"02,04,05"}]';
        //$bets = '[{"contents":"0|0|9|1|5","counts":5,"gid":6,"names":"0|2|9|3|5","pids":"3505|3517|3534|3538|3550","price":2.0,"price_sum":10.0,"rate":"98000,9800,980,98,9.8","rebate":"0.0","tid":557}]';

        if (empty($gid) || !is_numeric($gid) || empty($bets)) {
            $this->return_json(E_ARGS, '无效的gid!');
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_OP_FAIL, '无用户信息!');
        }
        $uid = $this->user['id'];
        /* 测试注单? */
        $is_test = ($this->user['status'] == USER_TEST) ? 1 : 0;

        $game = $this->games_model->info($gid);
        if (count($game) < 1) {
            $this->return_json(E_ARGS, '无效的gid');
        } elseif ($game['status'] == STATUS_OFF) {
            $this->return_json(E_OP_FAIL, '彩种维护中');
        }
        /* 获取当前正在下注的期号 */
        //if (empty($issue)) {
            $this->load->model('open_time_model');
            /* 验证期号时间有效性 */
            $issue_info = $this->open_time_model->get_kithe($gid);
            if (!empty($issue_info['is_open']) && $issue_info['kithe_time_second'] > 0) {
                $issue = $issue_info['kithe'];
            } else {
                $this->return_json(E_OP_FAIL, $issue_info['kithe'].'已封盘');
            }
        //}

        /* 游戏缩写/名 如: sfssc/三分时时彩 */
        $gname = $game['sname'];            // $this->games_model->sname($gid);
        $dbn = $this->games_model->sn;      // 代理标识/数据库名
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']).' '.$issue.':'.$uid.':'.$bets);

        /* 加载游戏下注插件 */
        $plugin = null;
        $plugin_name = 'games_'.$gname;
        $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
        if (file_exists($plugin_file)) {
            include_once($plugin_file);
            if (class_exists($plugin_name)) {
                $plugin = new $plugin_name;
            }
        }
        //$this->load->model('comm_model');

        /* 注单检测，支持下注购物车 */
        $bets = json_decode($bets, true);
        $money = 0;
        $bet_sum = 0;
        $c = count($bets);
        $orders = [];
        foreach ($bets as $_tmp) {
            if (empty($_tmp['gid']) || empty($_tmp['tid']) || empty($_tmp['price']) || empty($_tmp['counts'])
                    || empty($_tmp['price_sum']) || empty($_tmp['rate']) || !isset($_tmp['rebate']) || empty($_tmp['pids'])
                    || !isset($_tmp['contents'])) {
                $this->return_json(E_ARGS, '下注单内容错误');
            }
            $bet = ['gid' => $_tmp['gid'], 'tid' => $_tmp['tid'], 'price' => $_tmp['price'], 'counts' => $_tmp['counts'],
                'price_sum' => $_tmp['price_sum'], 'rate' => $_tmp['rate'], 'rebate' => $_tmp['rebate'], 'pids' => $_tmp['pids'],
                'contents' => $_tmp['contents'], 'names' => ''];
            if ($gid != $bet['gid']) {
                $this->return_json(E_ARGS, '下注单gid无效');
            }
            /* 完整玩法字母缩写, 完整中文玩法名 */
            $fn = $this->games_model->sname($gid, $bet['tid']);
            $names = $this->games_model->sname($gid, $bet['tid'], true);

            /* 验证赔率返水 */
            if (!$this->games_model->chk_rate($gname, $fn, $gid, $bet['tid'], $bet['pids'], $bet['contents'], $bet['rate'], $bet['rebate'], $dbn, $uid)) {
                $this->return_json(E_ARGS, '赔率验证错误,请退出并清空缓存');
            }
            $bet['names'] = $this->games_model->get_balls_names($gid, $bet['tid'], $bet['contents']);

            /* 计算校验下注 */
            $fn_bet = 'bet_'.$fn;
            $ret = false;
            if (method_exists($plugin, $fn_bet)) {
                $ret = $plugin->$fn_bet($bet);
            }
            if ($ret == false || $bet['price'] < 0.01) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':注单内容非法:'.$bet['names']);
                $this->return_json(E_ARGS, '注单内容非法！['.$names.':'.$bet['names'].']');
            }

            /* 处理下注数据：扣余额并记录流水，写下注数据库, 下注 redis 队列, 日结 redis 记录 */
            $bet['issue'] = $issue;
            $bet['uid'] = $uid;
            $bet['fn'] = $fn;
            $bet['src'] = $this->from_way;
            unset($bet['pids']);
            $money += $bet['price_sum'];
            $bet_sum += $bet['counts'];
            $orders[] = $bet;
        }
        /* 检查用户额度相关 */
        if ($money < 0.01 || $money > $game['max_money_stake']) {
            $this->return_json(E_OP_FAIL, '金额超过限制！'.$money);
        }
        $gid_uid_key = 'user:dml:'.$gid.':'.$issue;
        $gid_uid_field = $uid;
        $expire = EXPIRE_1 >> 2;        /* 当期15分钟限制 */
        if (in_array($gname, ['lhc', 'fc3d', 'pl3'])) {
            $expire = EXPIRE_48;        /* 2天限制 */
        }
        $t_money = $this->bets_model->redis_hget($gid_uid_key, $gid_uid_field);
        $t_money += $money;
        if (!empty($this->user['max_game_price']) && $t_money > $this->user['max_game_price']) {
            $this->return_json(E_OP_FAIL, '金额超过用户限制！'.$money);
        }
        if ($t_money > $game['max_money_play']) {
            $this->return_json(E_OP_FAIL, '金额超过当期游戏限制！'.$t_money);
        }
        $this->bets_model->redis_hincrbyfloat($gid_uid_key, $gid_uid_field, $money);
        $this->bets_model->redis_expire($gid_uid_key, $expire);

        /* 注单检测全部通过，生成注单号 */
        $orders_counts = count($orders);
        $order_num_str = '';
        $this->bets_model->db->trans_begin();
        for ($i = 0; $i < $orders_counts; $i++) {
            $orders[$i]['bet_time'] = time();
            $orders[$i]['agent_id'] = !empty($this->user['agent_id']) ? $this->user['agent_id'] : 0;
            $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
            $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                    'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
            if ($ret == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                        'uid:'.$orders[$i]['uid'].':order_num duplicate:'.$orders[$i]['order_num']);
                $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
                $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                        'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
                if ($ret == false) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                            'uid:'.$orders[$i]['uid'].':order_num duplicate 2:'.$orders[$i]['order_num']);
                    $this->bets_model->db->trans_rollback();
                    $this->return_json(E_OP_FAIL, '索引失败'.$i);
                }
            }
            if ($i == 0) {
                $order_num_str = $orders[$i]['order_num'];
            } else {
                $order_num_str .= ','.$orders[$i]['order_num'];
            }
        }

        /* 检查用户实时余额并扣款，写数据库，写队列 */
        if ($this->bets_model->update_banlace($uid, -$money, $order_num_str, BALANCE_ORDER, '下注:'.$c.'笔'.$gname.'订单'.$bet_sum.'注')) {
            $this->bets_model->db->trans_commit();
            foreach ($orders as $bet) {
                $fn = $bet['fn'];
                unset($bet['fn']);
                $this->bets_model->bet($dbn, $gname, $fn, $bet, $is_test);
            }
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':ok:'.$c.'笔'.$gname.'订单'.$bet_sum.'注:'.$order_num_str);
        } else {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':余额不足！'.$money);
            $this->bets_model->db->trans_rollback();
            $this->return_json(E_YEBZ, '余额不足！');
        }
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('bet', $c);

        $this->return_json(OK);
    } /* }}} */

    /**
     * @brief 用户追号下注
     *      验证期号时间有效性，
     *      验证计算下注，校验赔率和返水，检查余额额度相关，
     *      扣款，写下注表, 写下注 redis 队列，写日结 redis Hash。。。
     * @link http://api.101.com/index.php?
     * @method  POST
     * @param   $gid        game id
     * @param   $win_stop   中奖后停止追号
     * @param   $issue      期数号
     * @param   $bets = [{
     *              gid:游戏id,issue:期号,tid:玩法id,price:单注金额,counts:总组合注单量,price_sum:总组合金额,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }...]
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = [{
     *              gid:15,issue:201707200014,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }]
     * @param   $contents   pid_code_name,pid_code_name|pid_code_name|pid_code_name,pid_code_name
     * @param   $names      name,name|name|name,name
     * @return  下注状态
     */
    protected function _bets($gid = 0, $win_stop = 0) /* {{{ */
    {
        $bets = $this->P('bets');
        //$bets = '[{"issue":201707200014,"contents":"0|0|9|1|5","counts":5,"gid":6,"names":"0|2|9|3|5","pids":"3505|3517|3534|3538|3550","price":2.0,"price_sum":10.0,"rate":"98000,9800,980,98,9.8","rebate":"0.0","tid":557}]';

        $win_stop = $win_stop ? time() : 0;
        if (empty($gid) || !is_numeric($gid) || empty($bets)) {
            $this->return_json(E_ARGS, '无效的gid!');
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_OP_FAIL, '无用户信息!');
        }
        $uid = $this->user['id'];

        $game = $this->games_model->info($gid);
        if (count($game) < 1) {
            $this->return_json(E_ARGS, '无效的gid');
        } elseif ($game['status'] == STATUS_OFF) {
            $this->return_json(E_OP_FAIL, '彩种维护中');
        }
        /* 获取当前正在下注的期号, 追号期号不能小于当前期号 */
        $issue = '';
        $this->load->model('open_time_model');
        /* 验证期号时间有效性 */
        $issue_info = $this->open_time_model->get_kithe($gid);
        if (!empty($issue_info['is_open']) && $issue_info['kithe_time_second'] > 0) {
            $issue = $issue_info['kithe'];
        } else {
            $this->return_json(E_OP_FAIL, $issue_info['kithe'].'已封盘');
        }
        /* 本期开奖日期 */
        //$issue_day = date('Ymd', $issue_info['kithe_time_stamp'] + $issue_info['up_close_time']);

        /* 游戏缩写/名 如: sfssc/三分时时彩 */
        $gname = $game['sname'];            // $this->games_model->sname($gid);
        $dbn = $this->games_model->sn;      // 代理标识/数据库名
        if (in_array($gname, ['lhc', 'xjp28', 'bj28'])) {
            $this->return_json(E_OP_FAIL, '彩种不支持追号');
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']).' '.$issue.':chase:'.$uid.':'.$bets);

        /* 加载游戏下注插件 */
        $plugin = null;
        $plugin_name = 'games_'.$gname;
        $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
        if (file_exists($plugin_file)) {
            include_once($plugin_file);
            if (class_exists($plugin_name)) {
                $plugin = new $plugin_name;
            }
        }
        //$this->load->model('comm_model');

        /* 注单检测，支持下注购物车 */
        $bets = json_decode($bets, true);
        $money = 0;
        $bet_sum = 0;
        $c = count($bets);
        $orders = [];
        $chase_sum = ['fc3d' => 4, 'pl3' => 4, 'all' => 100];       /* 最大可追号期数 */
        $chase_sum_key = 'all';
        if (in_array($gname, ['fc3d', 'pl3'])) {
            $chase_sum_key = $gname;
        }
        if ($c > $chase_sum[$chase_sum_key]) {
            $this->return_json(E_ARGS, '追号超过期数:'.$c);
        }
        foreach ($bets as $_tmp) {
            if (empty($_tmp['gid']) || empty($_tmp['tid']) || empty($_tmp['price']) || empty($_tmp['counts'])
                    || empty($_tmp['price_sum']) || empty($_tmp['rate']) || !isset($_tmp['rebate']) || empty($_tmp['pids'])
                    || !isset($_tmp['contents']) || empty($_tmp['issue'])) {
                $this->return_json(E_ARGS, '追号单内容错误');
            }
            $bet = ['gid' => $_tmp['gid'], 'tid' => $_tmp['tid'], 'price' => $_tmp['price'], 'counts' => $_tmp['counts'],
                'price_sum' => $_tmp['price_sum'], 'rate' => $_tmp['rate'], 'rebate' => $_tmp['rebate'], 'pids' => $_tmp['pids'],
                'contents' => $_tmp['contents'], 'names' => '', 'issue' => $_tmp['issue']];
            if ($gid != $bet['gid']) {
                $this->return_json(E_ARGS, '追号单gid无效');
            }
            /* 追号期号不能小于当前期号 */
            if ($issue > $bet['issue']) {
                $this->return_json(E_ARGS, '追号单期号无效:'.$bet['issue']);
            }
            /* 完整玩法字母缩写, 完整中文玩法名 */
            $fn = $this->games_model->sname($gid, $bet['tid']);
            $names = $this->games_model->sname($gid, $bet['tid'], true);

            /* 验证赔率返水 */
            if (!$this->games_model->chk_rate($gname, $fn, $gid, $bet['tid'], $bet['pids'], $bet['contents'], $bet['rate'], $bet['rebate'], $dbn, $uid)) {
                $this->return_json(E_ARGS, '赔率验证错误,请退出并清空缓存');
            }
            $bet['names'] = $this->games_model->get_balls_names($gid, $bet['tid'], $bet['contents']);

            /* 计算校验下注 */
            $fn_bet = 'bet_'.$fn;
            $ret = false;
            if (method_exists($plugin, $fn_bet)) {
                $ret = $plugin->$fn_bet($bet);
            }
            if ($ret == false || $bet['price'] < 0.01) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $bet['issue'].':'.$uid.':注单内容非法:'.$bet['names']);
                $this->return_json(E_ARGS, '注单内容非法！['.$names.':'.$bet['names'].']');
            }

            /* 处理下注数据：扣余额并记录流水，写下注数据库, 下注 redis 队列, 日结 redis 记录 */
            $bet['status'] = STATUS_CHASE;
            $bet['end_time'] = $win_stop;
            $bet['uid'] = $uid;
            $bet['fn'] = $fn;
            $bet['src'] = $this->from_way;
            unset($bet['pids']);
            /* TODO: 优化 循环处理期号列表 */
            $money += $bet['price_sum'];
            $bet_sum += $bet['counts'];
            $orders[] = $bet;

            /* 检查用户额度相关 */
            $gid_uid_key = 'user:dml:'.$gid.':'.$bet['issue'];
            $gid_uid_field = $uid;
            $expire = EXPIRE_24;                /* 追号1天限制 */
            if (in_array($gname, ['fc3d', 'pl3'])) {
                $expire = EXPIRE_24 * 5;        /* 5天限制 */
            }
            $t_money = $this->bets_model->redis_hget($gid_uid_key, $gid_uid_field);
            $t_money += $bet['price_sum'];
            if (!empty($this->user['max_game_price']) && $t_money > $this->user['max_game_price']) {
                $this->return_json(E_OP_FAIL, '金额超过用户限制！'.$bet['price_sum']);
            }
            if ($t_money > $game['max_money_play']) {
                $this->return_json(E_OP_FAIL, '金额超过当期游戏限制！'.$bet['issue'].':'.$t_money);
            }
            $this->bets_model->redis_hincrbyfloat($gid_uid_key, $gid_uid_field, $bet['price_sum']);
            $this->bets_model->redis_expire($gid_uid_key, $expire);
        }
        /* 检查用户额度相关 */
        if ($money < 0.01 || $money > $game['max_money_stake']) {
            $this->return_json(E_OP_FAIL, '金额超过限制！'.$money);
        }
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $uid.':注单检测完成');

        /* 注单检测全部通过，生成注单号 */
        $orders_counts = count($orders);
        $order_num_str = '';
        $order_num_arr = [];                /* 追号订单列表，追号中奖后停止使用 */
        $this->bets_model->db->trans_begin();
        for ($i = 0; $i < $orders_counts; $i++) {
            $orders[$i]['bet_time'] = time();
            $orders[$i]['agent_id'] = !empty($this->user['agent_id']) ? $this->user['agent_id'] : 0;
            $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
            $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                    'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
            if ($ret == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                        'uid:'.$orders[$i]['uid'].':order_num duplicate:'.$orders[$i]['order_num']);
                $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
                $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                        'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
                if ($ret == false) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                            'uid:'.$orders[$i]['uid'].':order_num duplicate 2:'.$orders[$i]['order_num']);
                    $this->bets_model->db->trans_rollback();
                    $this->return_json(E_OP_FAIL, '索引失败'.$i);
                }
            }
            $order_num_arr[$orders[$i]['order_num']] = $orders[$i]['issue'];
            if ($i == 0) {
                $order_num_str = $orders[$i]['order_num'];
            } else {
                $order_num_str .= ','.$orders[$i]['order_num'];
            }
        }

        /* 检查用户实时余额并扣款，写数据库，写队列，写追号 hash 记录 */
        if ($this->bets_model->update_banlace($uid, -$money, $order_num_str, BALANCE_ORDER, '追号:'.$c.'笔'.$gname.'订单'.$bet_sum.'注')) {
            $this->bets_model->db->trans_commit();
            //$this->load->model('open_time_model');
            //$chase_info = $this->open_time_model->get_zhkithe_list($gid, true);
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $uid.':注单号写入完成');
            foreach ($orders as $bet) {
                $fn = $bet['fn'];
                unset($bet['fn']);
                //$_issue_day = !empty($chase_info[$bet['issue']]) ? date('Ymd', strtotime($chase_info[$bet['issue']])) : $issue_day;
                $this->bets_model->bet($dbn, $gname, $fn, $bet, $win_stop);
            }
            /* 追号中奖后停止? */
            if ($win_stop) {
                $chase_key = 'chase:'.$gid.':'.$uid.':'.$win_stop;
                $this->bets_model->redis_set($chase_key, json_encode($order_num_arr));
                $this->bets_model->redis_expire($chase_key, EXPIRE_48 << 1);
            }
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':ok:追号'.$c.'笔'.$gname.'订单'.$bet_sum.'注:'.$order_num_str);
        } else {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':余额不足！'.$money);
            $this->bets_model->db->trans_rollback();
            $this->return_json(E_OP_FAIL, '余额不足！');
        }
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('bet', $c);

        $this->return_json(OK);
    } /* }}} */

    /**
     * @brief 用户追号下注 优化算法
     *      验证期号时间有效性，
     *      验证计算下注，校验赔率和返水，检查余额额度相关，
     *      扣款，写下注表, 写下注 redis 队列，写日结 redis Hash。。。
     * @link http://api.101.com/index.php?
     * @method  POST
     * @param   $gid        game id
     * @param   $win_stop   中奖后停止追号
     * @param   $bets = [{
     *              gid:游戏id,tid:玩法id,price:单注金额,counts:总组合注单量,price_sum:总组合金额,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }...]
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = [{
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }]
     * @param   $issues = {期号:倍数,期号:倍数,期号:倍数...}
     * @param   $contents   pid_code_name,pid_code_name|pid_code_name|pid_code_name,pid_code_name
     * @param   $names      name,name|name|name,name
     * @return  下注状态
     */
    protected function _bets2($gid = 0, $win_stop = 0) /* {{{ */
    {
        $bets = $this->P('bets');
        $issues = $this->P('issues');
        //$bets = '[{"issue":201707200014,"contents":"0|0|9|1|5","counts":5,"gid":6,"names":"0|2|9|3|5","pids":"3505|3517|3534|3538|3550","price":2.0,"price_sum":10.0,"rate":"98000,9800,980,98,9.8","rebate":"0.0","tid":557}]';
        //$issues = '{"20170809060":2,"20170809062":2,"20170809065":2,"20170809066":2}';

        $win_stop = $win_stop ? time() : 0;
        if (empty($gid) || !is_numeric($gid) || empty($bets)) {
            $this->return_json(E_ARGS, '无效的gid!');
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_OP_FAIL, '无用户信息!');
        }
        $uid = $this->user['id'];

        $game = $this->games_model->info($gid);
        if (count($game) < 1) {
            $this->return_json(E_ARGS, '无效的gid');
        } elseif ($game['status'] == STATUS_OFF) {
            $this->return_json(E_OP_FAIL, '彩种维护中');
        }
        /* 获取当前正在下注的期号, 追号期号不能小于当前期号 */
        $issue = '';
        $this->load->model('open_time_model');
        /* 验证期号时间有效性 */
        $issue_info = $this->open_time_model->get_kithe($gid);
        if (!empty($issue_info['is_open']) && $issue_info['kithe_time_second'] > 0) {
            $issue = $issue_info['kithe'];
        } else {
            $this->return_json(E_OP_FAIL, $issue_info['kithe'].'已封盘');
        }

        /* 游戏缩写/名 如: sfssc/三分时时彩 */
        $gname = $game['sname'];            // $this->games_model->sname($gid);
        $dbn = $this->games_model->sn;      // 代理标识/数据库名
        if (in_array($gname, ['lhc', 'xjp28', 'bj28'])) {
            $this->return_json(E_OP_FAIL, '彩种不支持追号');
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']).' '.$issue.':chase:'.$uid.':'.$bets.':issues:'.$issues);

        /* 加载游戏下注插件 */
        $plugin = null;
        $plugin_name = 'games_'.$gname;
        $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
        if (file_exists($plugin_file)) {
            include_once($plugin_file);
            if (class_exists($plugin_name)) {
                $plugin = new $plugin_name;
            }
        }
        //$this->load->model('comm_model');

        /* 注单检测，支持下注购物车 */
        $bets = json_decode($bets, true);
        $issues = json_decode($issues, true);
        if (empty($bets) || empty($issues)) {
            $this->return_json(E_ARGS, '注单或期号无效');
        }
        $money = 0;
        $bet_sum = 0;
        $c = count($bets) * count($issues);
        $orders = [];
        $chase_sum = ['fc3d' => 4, 'pl3' => 4, 'all' => 100];       /* 最大可追号期数 */
        $chase_sum_key = 'all';
        if (in_array($gname, ['fc3d', 'pl3'])) {
            $chase_sum_key = $gname;
        }
        if ($c > $chase_sum[$chase_sum_key]) {
            $this->return_json(E_ARGS, '追号超过期数:'.$c);
        }
        foreach ($bets as $_tmp) {
            if (empty($_tmp['gid']) || empty($_tmp['tid']) || empty($_tmp['price']) || empty($_tmp['counts'])
                    || empty($_tmp['price_sum']) || empty($_tmp['rate']) || !isset($_tmp['rebate']) || empty($_tmp['pids'])
                    || !isset($_tmp['contents'])) {
                $this->return_json(E_ARGS, '追号单内容错误');
            }
            $bet = ['gid' => $_tmp['gid'], 'tid' => $_tmp['tid'], 'price' => $_tmp['price'], 'counts' => $_tmp['counts'],
                'price_sum' => $_tmp['price_sum'], 'rate' => $_tmp['rate'], 'rebate' => $_tmp['rebate'], 'pids' => $_tmp['pids'],
                'contents' => $_tmp['contents'], 'names' => ''];
            if ($gid != $bet['gid']) {
                $this->return_json(E_ARGS, '追号单gid无效');
            }
            /* 完整玩法字母缩写, 完整中文玩法名 */
            $fn = $this->games_model->sname($gid, $bet['tid']);
            $names = $this->games_model->sname($gid, $bet['tid'], true);

            /* 验证赔率返水 */
            if (!$this->games_model->chk_rate($gname, $fn, $gid, $bet['tid'], $bet['pids'], $bet['contents'], $bet['rate'], $bet['rebate'], $dbn, $uid)) {
                $this->return_json(E_ARGS, '赔率验证错误,请退出并清空缓存');
            }
            $bet['names'] = $this->games_model->get_balls_names($gid, $bet['tid'], $bet['contents']);

            /* 计算校验下注 */
            $fn_bet = 'bet_'.$fn;
            $ret = false;
            if (method_exists($plugin, $fn_bet)) {
                $ret = $plugin->$fn_bet($bet);
            }
            if ($ret == false || $bet['price'] < 0.01) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.'追号:'.$uid.':注单内容非法:'.$bet['names']);
                $this->return_json(E_ARGS, '注单内容非法！['.$names.':'.$bet['names'].']');
            }

            /* 处理下注数据：扣余额并记录流水，写下注数据库, 下注 redis 队列, 日结 redis 记录 */
            $bet['status'] = STATUS_CHASE;
            $bet['end_time'] = $win_stop;
            $bet['uid'] = $uid;
            $bet['fn'] = $fn;
            $bet['src'] = $this->from_way;
            $price = $bet['price'];
            unset($bet['pids']);
            /* 优化 循环处理期号列表：期号=>倍数 */
            foreach ($issues as $k => $v) {
                $bet['issue'] = $k;
                $v = (int) $v;
                /* 追号期号不能小于当前期号 */
                if ($issue > $bet['issue']) {
                    $this->return_json(E_ARGS, '追号单期号无效:'.$bet['issue']);
                }
                if ($v <= 0) {
                    $this->return_json(E_ARGS, '追号倍数错误:'.$v);
                }
                $bet['price'] = $price * $v;
                $bet['price_sum'] = $bet['price'] * $bet['counts'];
                $money += $bet['price_sum'];
                $bet_sum += $bet['counts'];
                $orders[] = $bet;

                /* 检查用户额度相关 */
                $gid_uid_key = 'user:dml:'.$gid.':'.$bet['issue'];
                $gid_uid_field = $uid;
                $expire = EXPIRE_24;                /* 追号1天限制 */
                if (in_array($gname, ['fc3d', 'pl3'])) {
                    $expire = EXPIRE_24 * 5;        /* 5天限制 */
                }
                $t_money = $this->bets_model->redis_hget($gid_uid_key, $gid_uid_field);
                $t_money += $bet['price_sum'];
                if (!empty($this->user['max_game_price']) && $t_money > $this->user['max_game_price']) {
                    $this->return_json(E_OP_FAIL, '金额超过用户限制！'.$bet['price_sum']);
                }
                if ($t_money > $game['max_money_play']) {
                    $this->return_json(E_OP_FAIL, '金额超过当期游戏限制！'.$bet['issue'].':'.$t_money);
                }
                $this->bets_model->redis_hincrbyfloat($gid_uid_key, $gid_uid_field, $bet['price_sum']);
                $this->bets_model->redis_expire($gid_uid_key, $expire);
            }
        }
        /* 检查用户额度相关 */
        if ($money < 0.01 || $money > $game['max_money_stake']) {
            $this->return_json(E_OP_FAIL, '金额超过限制！'.$money);
        }
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $uid.':注单检测完成');

        /* 注单检测全部通过，生成注单号 */
        $orders_counts = count($orders);
        $order_num_str = '';
        $order_num_arr = [];                /* 追号订单列表，追号中奖后停止使用 */
        $this->bets_model->db->trans_begin();
        for ($i = 0; $i < $orders_counts; $i++) {
            $orders[$i]['bet_time'] = time();
            $orders[$i]['agent_id'] = !empty($this->user['agent_id']) ? $this->user['agent_id'] : 0;
            $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
            $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                    'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
            if ($ret == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                        'uid:'.$orders[$i]['uid'].':order_num duplicate:'.$orders[$i]['order_num']);
                $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
                $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                        'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
                if ($ret == false) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                            'uid:'.$orders[$i]['uid'].':order_num duplicate 2:'.$orders[$i]['order_num']);
                    $this->bets_model->db->trans_rollback();
                    $this->return_json(E_OP_FAIL, '索引失败'.$i);
                }
            }
            $order_num_arr[$orders[$i]['order_num']] = $orders[$i]['issue'];
            if ($i == 0) {
                $order_num_str = $orders[$i]['order_num'];
            } else {
                $order_num_str .= ','.$orders[$i]['order_num'];
            }
        }

        /* 检查用户实时余额并扣款，写数据库，写队列，写追号 hash 记录 */
        if ($this->bets_model->update_banlace($uid, -$money, $order_num_str, BALANCE_ORDER, '追号2:'.$c.'笔'.$gname.'订单'.$bet_sum.'注')) {
            $this->bets_model->db->trans_commit();
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $uid.':注单号写入完成');
            foreach ($orders as $bet) {
                $fn = $bet['fn'];
                unset($bet['fn']);
                $this->bets_model->bet($dbn, $gname, $fn, $bet, $win_stop);
            }
            /* 追号中奖后停止? */
            if ($win_stop) {
                $chase_key = 'chase:'.$gid.':'.$uid.':'.$win_stop;
                $this->bets_model->redis_set($chase_key, json_encode($order_num_arr));
                $this->bets_model->redis_expire($chase_key, EXPIRE_48 << 1);
            }
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':ok:追号2:'.$c.'笔'.$gname.'订单'.$bet_sum.'注:'.$order_num_str);
        } else {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':余额不足！'.$money);
            $this->bets_model->db->trans_rollback();
            $this->return_json(E_OP_FAIL, '余额不足！');
        }
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('bet', $c);

        $this->return_json(OK);
    } /* }}} */

    /**
     * @brief 互动大厅跟投下注
     *      验证期号时间有效性，
     *      计算下注，赔率和返水，检查余额额度相关，
     *      扣款，写下注表, 写下注 redis 队列，写日结 redis Hash。。。
     * @link http://api.101.com/index.php?
     * @method  POST
     * @param   $gid        game id
     * @param   $issue      期数号
     * @param   $bets = [{
     *              gid:游戏id,tid:玩法id,price:单注金额,counts:总组合注单量,price_sum:总组合金额,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }...]
     *          demo:
     *          POST http://www.gc360.com/orders/bet3/15/20170327024/1908
     *          $bets = [{
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }]
     * @param   $contents   pid_code_name,pid_code_name|pid_code_name|pid_code_name,pid_code_name
     * @param   $names      name,name|name|name,name
     * @return  下注状态
     */
    public function bet3($gid = 0, $issue = '') /* {{{ */
    {
        $bets = $this->P('bets');
        //$bets = '[{"gid":3,"tid":206,"price":2,"counts":1,"price_sum":2,"rate":"1.97,1.97","rebate":0,"pids":"22420|22425,22427,22428","contents":"100||100||","names":"02,04,05"}]';
        //$bets = '[{"contents":"0|0|9|1|5","counts":5,"gid":6,"names":"0|2|9|3|5","pids":"3505|3517|3534|3538|3550","price":2.0,"price_sum":10.0,"rate":"98000,9800,980,98,9.8","rebate":"0.0","tid":557}]';

        if (empty($gid) || !is_numeric($gid) || empty($bets)) {
            $this->return_json(E_ARGS, '无效的gid!');
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_OP_FAIL, '无用户信息!');
        }
        $uid = $this->user['id'];
        /* 测试注单? */
        $is_test = ($this->user['status'] == USER_TEST) ? 1 : 0;

        $game = $this->games_model->info($gid);
        if (count($game) < 1) {
            $this->return_json(E_ARGS, '无效的gid');
        } elseif ($game['status'] == STATUS_OFF) {
            $this->return_json(E_OP_FAIL, '彩种维护中');
        }
        /* 获取当前正在下注的期号 */
        //if (empty($issue)) {
            $this->load->model('open_time_model');
            /* 验证期号时间有效性 */
            $issue_info = $this->open_time_model->get_kithe($gid);
            if (!empty($issue_info['is_open']) && $issue_info['kithe_time_second'] > 0) {
                if ($issue && $issue != $issue_info['kithe']) {
                    $this->return_json(E_OP_FAIL, $issue.'已封盘');
                } else {
                    $issue = $issue_info['kithe'];
                }
            } else {
                $this->return_json(E_OP_FAIL, $issue_info['kithe'].'已封盘');
            }
        //}

        /* 游戏缩写/名 如: sfssc/三分时时彩 */
        $gname = $game['sname'];            // $this->games_model->sname($gid);
        $dbn = $this->games_model->sn;      // 代理标识/数据库名
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']).' '.$issue.':'.$uid.':'.$bets);

        /* 加载游戏下注插件 */
        $plugin = null;
        $plugin_name = 'games_'.$gname;
        $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
        if (file_exists($plugin_file)) {
            include_once($plugin_file);
            if (class_exists($plugin_name)) {
                $plugin = new $plugin_name;
            }
        }
        //$this->load->model('comm_model');

        /* 注单检测，支持下注购物车 */
        $bets = json_decode($bets, true);
        $money = 0;
        $bet_sum = 0;
        $c = count($bets);
        $orders = [];
        foreach ($bets as $_tmp) {
            if (empty($_tmp['gid']) || empty($_tmp['tid']) || empty($_tmp['price']) || empty($_tmp['counts'])
                || empty($_tmp['price_sum']) || empty($_tmp['pids'])
                || !isset($_tmp['contents'])) {
                $this->return_json(E_ARGS, '下注单内容错误');
            }
            $bet = ['gid' => $_tmp['gid'], 'tid' => $_tmp['tid'], 'price' => $_tmp['price'], 'counts' => $_tmp['counts'],
                'price_sum' => $_tmp['price_sum'], 'pids' => $_tmp['pids'],
                'contents' => $_tmp['contents'], 'names' => ''];
            if ($gid != $bet['gid']) {
                $this->return_json(E_ARGS, '下注单gid无效');
            }
            /* 完整玩法字母缩写, 完整中文玩法名 */
            $fn = $this->games_model->sname($gid, $bet['tid']);
            $names = $this->games_model->sname($gid, $bet['tid'], true);

            /* 设置赔率返水 */
            if (!$this->games_model->set_rate($gname, $fn, $gid, $bet['tid'], $bet['pids'], $bet['contents'], $bet, $dbn, $uid)) {
                $this->return_json(E_ARGS, '赔率设置出错,请退出并清空缓存');
            }
            $bet['names'] = $this->games_model->get_balls_names($gid, $bet['tid'], $bet['contents']);

            /* 计算校验下注 */
            $fn_bet = 'bet_'.$fn;
            $ret = false;
            if (method_exists($plugin, $fn_bet)) {
                $ret = $plugin->$fn_bet($bet);
            }
            if ($ret == false || $bet['price'] < 0.01) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':注单内容非法:'.$bet['names']);
                $this->return_json(E_ARGS, '注单内容非法！['.$names.':'.$bet['names'].']');
            }

            /* 处理下注数据：扣余额并记录流水，写下注数据库, 下注 redis 队列, 日结 redis 记录 */
            $bet['issue'] = $issue;
            $bet['uid'] = $uid;
            $bet['fn'] = $fn;
            $bet['src'] = $this->from_way;
            unset($bet['pids']);
            $money += $bet['price_sum'];
            $bet_sum += $bet['counts'];
            $orders[] = $bet;
        }
        /* 检查用户额度相关 */
        if ($money < 0.01 || $money > $game['max_money_stake']) {
            $this->return_json(E_OP_FAIL, '金额超过限制！'.$money);
        }
        $gid_uid_key = 'user:dml:'.$gid.':'.$issue;
        $gid_uid_field = $uid;
        $expire = EXPIRE_1 >> 2;        /* 当期15分钟限制 */
        if (in_array($gname, ['lhc', 'fc3d', 'pl3'])) {
            $expire = EXPIRE_48;        /* 2天限制 */
        }
        $t_money = $this->bets_model->redis_hget($gid_uid_key, $gid_uid_field);
        $t_money += $money;
        if (!empty($this->user['max_game_price']) && $t_money > $this->user['max_game_price']) {
            $this->return_json(E_OP_FAIL, '金额超过用户限制！'.$money);
        }
        if ($t_money > $game['max_money_play']) {
            $this->return_json(E_OP_FAIL, '金额超过当期游戏限制！'.$t_money);
        }
        $this->bets_model->redis_hincrbyfloat($gid_uid_key, $gid_uid_field, $money);
        $this->bets_model->redis_expire($gid_uid_key, $expire);

        /* 注单检测全部通过，生成注单号 */
        $orders_counts = count($orders);
        $order_num_str = '';
        $this->bets_model->db->trans_begin();
        for ($i = 0; $i < $orders_counts; $i++) {
            $orders[$i]['bet_time'] = time();
            $orders[$i]['agent_id'] = !empty($this->user['agent_id']) ? $this->user['agent_id'] : 0;
            $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
            $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
            if ($ret == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                    'uid:'.$orders[$i]['uid'].':order_num duplicate:'.$orders[$i]['order_num']);
                $orders[$i]['order_num'] = $this->bets_model->order_num($orders[$i]['gid'], $orders[$i]['uid']);
                $ret = $this->bets_model->db->insert('bet_index', ['uid' => $orders[$i]['uid'], 'order_num' => $orders[$i]['order_num'],
                    'gid' => $orders[$i]['gid'], 'issue' => $orders[$i]['issue'], 'created' => $orders[$i]['bet_time'], 'agent_id' => $orders[$i]['agent_id']]);
                if ($ret == false) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_error_'.date('Y').'.log',
                        'uid:'.$orders[$i]['uid'].':order_num duplicate 2:'.$orders[$i]['order_num']);
                    $this->bets_model->db->trans_rollback();
                    $this->return_json(E_OP_FAIL, '索引失败'.$i);
                }
            }
            if ($i == 0) {
                $order_num_str = $orders[$i]['order_num'];
            } else {
                $order_num_str .= ','.$orders[$i]['order_num'];
            }
        }

        /* 检查用户实时余额并扣款，写数据库，写队列 */
        if ($this->bets_model->update_banlace($uid, -$money, $order_num_str, BALANCE_ORDER, '下注:'.$c.'笔'.$gname.'订单'.$bet_sum.'注')) {
            $this->bets_model->db->trans_commit();
            foreach ($orders as $bet) {
                $fn = $bet['fn'];
                unset($bet['fn']);
                $this->bets_model->bet($dbn, $gname, $fn, $bet, $is_test);
            }
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':ok:'.$c.'笔'.$gname.'订单'.$bet_sum.'注:'.$order_num_str);
        } else {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $issue.':'.$uid.':余额不足！'.$money);
            $this->bets_model->db->trans_rollback();
            $this->return_json(E_YEBZ, '余额不足！');
        }
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('bet', $c);

        $this->return_json(OK);
    } /* }}} */
}

/* end file */
