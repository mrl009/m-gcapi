<?php
/**
 * @file games_settlement_s_11x5.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/21 19:58
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_settlement_s_11x5
{
    public $config_balls = [
        /* 基本球 */
        'base' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        /* 和大小单双 尾大尾小 龙虎 */
        'he' => [104, 105, 106, 107, 112, 113, 133, 131],
        /* 大小单双 */
        'lm' => [100, 101, 102, 103],
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：11x5 当期开奖: 1,2,3,4,5
     *          则计算出开奖总和: 15
     * @access public/protected 
     * @param 
     * @return 
     */
    public function wins_balls(& $wins_balls = []) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3', '4', '5'];
        /* NOTE: 11x5 开奖号不可以有重复 */
        if (count(array_unique($wins_balls['base'])) != 5 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[s_11x5] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        //$wins_balls['zh'] = [code0, code1(大小), code2(单双), code3(尾大小), code4(龙虎)];
        //$wins_balls['d1'] = [code0, code1(大小) / null, code2(单双) / null];
        //$wins_balls['d2'] = [code0, code1, code2];
        //$wins_balls['d3'] = [code0, code1, code2];
        //$wins_balls['d5'] = [code0, code1, code2];
    
        /* 第一~五名 大小单双 */
        $i = 1;
        $zh = 0;
        foreach ($wins_balls['base'] as $v) {
            $zh += $v;
            $wins_balls['d'.$i][0] = $v;
            $wins_balls['d'.$i][1] = ($v == 11) ? null : ($v >= 6 ? 100 : 101);
            $wins_balls['d'.$i][2] = ($v == 11) ? null : (($v % 2) == 1 ? 102 : 103);
            $i++;
        }
        /* 总和 大小单双 尾大尾小 龙虎 */
        $wins_balls['zh'][0] = $zh;
        $wins_balls['zh'][1] = $wins_balls['zh'][0] == 30 ? null : ($wins_balls['zh'][0] > 30 ? 104 : 105);
        //$wins_balls['zh'][2] = $wins_balls['zh'][0] == 30 ? null : ($wins_balls['zh'][0] % 2 == 1 ? 106 : 107);
        $wins_balls['zh'][2] = $wins_balls['zh'][0] % 2 == 1 ? 106 : 107;
        $wins_balls['zh'][3] = ($wins_balls['zh'][0] % 10) > 4 ? 112 : 113;
        $wins_balls['zh'][4] = $wins_balls['base'][0] > $wins_balls['base'][4] ? 133 : 131;
    
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 两面盘_总和
     *      总和 - 以全部开出的5个号码，加起来的总和来判定。
     *      总和大小: 所有开奖号码数字加总值大于30为和大；总和值小于30为和小；若总和值等于30为和 (不计算输赢)。 
     *      总和单双: 所有开奖号码数字加总值为单数叫和单，如11、31；加总值为双数叫和双，如42、30。
     *      总和尾数大小: 所有开奖号码数字加总值的尾数，大于或等于5为尾大，小于或等于4为尾小。
     *      龙虎:
     *      龙：第一球开奖号码大于第五球开奖号码，如第一球开出10，第五球开出7。
     *      虎：第一球开奖号码小于第五球开奖号码，如第一球开出3，第五球开出7。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *      demo:
     *          POST http://www.gc360.com/orders/bet/78
     *          bets=[{"gid":68,"tid":5706,"price":2,"counts":1,"price_sum":2,"rate":"1.97","rebate":0,"pids":"62002","contents":"105","names":"和小"}]
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_zh(& $bet = [], & $lottery = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 */
        if (in_array($v, $lottery['zh'])) {
            $ret['win_contents'][] = [$v];
        }
        /* 和 */
        if ($lottery['zh'][0] == 30 && in_array($v, [104, 105])) {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
        } elseif (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 两面盘_第一球
     *      单码 - 指第一球、第二球、第三球、第四球、第五球出现的顺序与号码为派彩依据。
     *      单码：如现场滚球第一个开奖号码为10号，投注第一球为10号则视为中奖，其它号码视为不中奖。
     *      大小：开出的号码大于或等于6为大，小于或等于5为小，开出11为和 (不计算输赢)。
     *      单双：号码为双数叫双，如2、8；号码为单数叫单，如5、9；开出11为和 (不计算输赢)。
     * @access public
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_lm_d1(& $bet = [], & $lottery = [], $ball = 'd1') /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 */
        if (in_array($v, $lottery[$ball])) {
            $ret['win_contents'][] = [$v];
        }
        /* 和 */
        if ($lottery[$ball][0] == 11 && in_array($v, [100, 101, 102, 103])) {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
        } elseif (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 两面盘_第二球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d2(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd2');
    } /* }}} */
    
    /**
     * @brief 两面盘_第三球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd3');
    } /* }}} */
    
    /**
     * @brief 两面盘_第四球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d4(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd4');
    } /* }}} */
    
    /**
     * @brief 两面盘_第五球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d5(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd5');
    } /* }}} */
    
    /**
     * @brief 第1-5球_第一球
     *      单码 - 指第一球、第二球、第三球、第四球、第五球出现的顺序与号码为派彩依据。
     *      单码：如现场滚球第一个开奖号码为10号，投注第一球为10号则视为中奖，其它号码视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_15q_d1(& $bet = [], & $lottery = [], $i = 0) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。但结算可以支持1注多球 */
        //$v = $balls[0][0];
        /* 中 */
        if (in_array($lottery['base'][$i], $balls[0])) {
            $ret['win_contents'][] = [$lottery['base'][$i]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 第1-5球_第二球
     * @access public
     * @param
     * @return
     */
    public function settlement_15q_d2(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_15q_d1($bet, $lottery, 1);
    } /* }}} */
    
    /**
     * @brief 第1-5球_第三球
     * @access public
     * @param
     * @return
     */
    public function settlement_15q_d3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_15q_d1($bet, $lottery, 2);
    } /* }}} */
    
    /**
     * @brief 第1-5球_第四球
     * @access public
     * @param
     * @return
     */
    public function settlement_15q_d4(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_15q_d1($bet, $lottery, 3);
    } /* }}} */
    
    /**
     * @brief 第1-5球_第五球
     * @access public
     * @param
     * @return
     */
    public function settlement_15q_d5(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_15q_d1($bet, $lottery, 4);
    } /* }}} */
    
    /**
     * @brief 任选_一中一
     *      选号 - 选号玩法是由1~11号中，选出1~5个号码为一投注组合来进行投注。
     *      任选一中一: 投注1个号码与当期开奖的5个号码中任1个号码相同，视为中奖。
     *      任选二中二: 投注2个号码与当期开奖的5个号码中任2个号码相同(顺序不限)，视为中奖。
     *      任选三中三: 投注3个号码与当期开奖的5个号码中任3个号码相同(顺序不限)，视为中奖。
     *      任选四中四: 投注4个号码与当期开奖的5个号码中任4个号码相同(顺序不限)，视为中奖。
     *      任选五中五: 投注5个号码与当期开奖的5个号码中5个号码相同(顺序不限)，视为中奖。
     *      任选六中五: 投注6个号码中任5个号码与当期开奖的5个号码相同(顺序不限)，视为中奖。
     *      任选七中五: 投注7个号码中任5个号码与当期开奖的5个号码相同(顺序不限)，视为中奖。
     *      任选八中五: 投注8个号码中任5个号码与当期开奖的5个号码相同(顺序不限)，视为中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_1z1(& $bet = [], & $lottery = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注m球。 */
        /* 中 */
        foreach ($balls[0] as $v) {
            if (in_array($v, $lottery['base'])) {
                $ret['win_contents'][] = [$v];
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_二中二
     *      每单1注，每注2球
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_2z2(& $bet = [], & $lottery = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 2);
        foreach ($r as $v) {
            if (in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base'])) {
                $ret['win_contents'][] = [$v];
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_三中三
     *      每单1注，每注3球
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_3z3(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 3);
        foreach ($r as $v) {
            if (in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base']) && in_array($v[2], $lottery['base'])) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_四中四
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_4z4(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 4);
        foreach ($r as $v) {
            if (in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base'])
                && in_array($v[2], $lottery['base']) && in_array($v[3], $lottery['base'])
            ) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_五中五
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_5z5(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 5);
        foreach ($r as $k => $v) {
            if (empty($ret['win_contents']) && in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base'])
                && in_array($v[2], $lottery['base']) && in_array($v[3], $lottery['base']) && in_array($v[4], $lottery['base'])
            ) {
                $ret['win_contents'][] = $v;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_六中五
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_6z5(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 6);
        foreach ($r as $v) {
            if (in_array($lottery['base'][0], $v) && in_array($lottery['base'][1], $v) && in_array($lottery['base'][2], $v)
                && in_array($lottery['base'][3], $v) && in_array($lottery['base'][4], $v)
            ) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_七中五
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_7z5(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 7);
        foreach ($r as $v) {
            if (in_array($lottery['base'][0], $v) && in_array($lottery['base'][1], $v) && in_array($lottery['base'][2], $v)
                && in_array($lottery['base'][3], $v) && in_array($lottery['base'][4], $v)
            ) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 任选_八中五
     * @access public
     * @param
     * @return
     */
    public function settlement_rx_8z5(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 8);
        foreach ($r as $v) {
            if (in_array($lottery['base'][0], $v) && in_array($lottery['base'][1], $v) && in_array($lottery['base'][2], $v)
                && in_array($lottery['base'][3], $v) && in_array($lottery['base'][4], $v)
            ) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 组选_前二
     *      每单1注，每注2球
     *      组选前二: 投注的2个号码与当期顺序开出的5个号码中的前2个号码相同，视为中奖。
     *      组选前三: 投注的3个号码与当期顺序开出的5个号码中的前3个号码相同，视为中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_zx_q2(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 获取组数
        $r = combination($balls[0], 2);
        foreach ($r as $v) {
            if (in_array($v[0], [$lottery['base'][0], $lottery['base'][1]]) && in_array($v[1], [$lottery['base'][0], $lottery['base'][1]])) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 组选_前三
     *      每单1注，每注3球
     * @access public
     * @param
     * @return
     */
    public function settlement_zx_q3(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 获取组数
        $r = combination($balls[0], 3);
        foreach ($r as $v) {
            if (in_array($v[0], [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2]])
                && in_array($v[1], [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2]])
                && in_array($v[2], [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2]])
            ) {
                $ret['win_contents'][] = $v;
            }
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 直选_前二
     *      每单1注，每注2球
     *      直选前二: 投注的2个号码与当期顺序开出的5个号码中的前2个号码相同且顺序一致，视为中奖。
     *      直选前三: 投注的3个号码与当期顺序开出的5个号码中的前3个号码相同且顺序一致，视为中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_zhx_q2(& $bet = [], & $lottery = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        // 两个同时中奖才能中,而且只能中一注
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1])) {
            $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 直选_前三
     *      每单1注，每注3球
     * @access public
     * @param
     * @return
     */
    public function settlement_zhx_q3(& $bet = [], & $lottery = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        // 三个同时中奖才能中,而且只能中一注
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1]) && in_array($lottery['base'][2], $balls[2])) {
            $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
}

/* end file */
