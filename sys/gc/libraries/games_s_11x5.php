<?php
/**
 * @file games_s_11x5.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/20 21:43
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_s_11x5
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
    public function bet_lm_zh(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'he');
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
     */
    public function bet_lm_d1(& $bet = [], $ball = 'lm') /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls[$ball]);
        /* 检测球号 */
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 两面盘_第二球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d2(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 两面盘_第三球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d3(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 两面盘_第四球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d4(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 两面盘_第五球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d5(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 第1-5球_第一球
     *      单码 - 指第一球、第二球、第三球、第四球、第五球出现的顺序与号码为派彩依据。
     *      单码：如现场滚球第一个开奖号码为10号，投注第一球为10号则视为中奖，其它号码视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_15q_d1(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'base');
    } /* }}} */
    
    /**
     * @brief 第1-5球_第二球
     * @access public
     * @param
     * @return
     */
    public function bet_15q_d2(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'base');
    } /* }}} */
    
    /**
     * @brief 第1-5球_第三球
     * @access public
     * @param
     * @return
     */
    public function bet_15q_d3(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'base');
    } /* }}} */
    
    /**
     * @brief 第1-5球_第四球
     * @access public
     * @param
     * @return
     */
    public function bet_15q_d4(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'base');
    } /* }}} */
    
    /**
     * @brief 第1-5球_第五球
     * @access public
     * @param
     * @return
     */
    public function bet_15q_d5(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'base');
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
     *      bets=[{"gid":78,"tid":6430,"price":2,"counts":3,"price_sum":6,"rate":"5","rebate":0,"pids":"65495,65496,65497","contents":"1,2,3","names":"01,02,03"}]
     * @access public
     * @param
     * @return
     */
    public function bet_rx_1z1(& $bet = [], $m = 1, $limit = 1) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        /* 组合下注量 */
        $n = count(array_unique($balls[0]));
        if ($n < $m || $n > $limit) {
            return false;
        }
        $c = 1;
        /*$c = C($n, $m);*/
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_二中二
     *      每单1注，每注2球
     * @access public
     * @param
     * @return
     */
    public function bet_rx_2z2(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 2, 2);
    } /* }}} */
    
    /**
     * @brief 任选_三中三
     *      每单1注，每注3球
     * @access public
     * @param
     * @return
     */
    public function bet_rx_3z3(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 3, 3);
    } /* }}} */
    
    /**
     * @brief 任选_四中四
     * @access public
     * @param
     * @return
     */
    public function bet_rx_4z4(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 4, 4);
    } /* }}} */
    
    /**
     * @brief 任选_五中五
     * @access public
     * @param
     * @return
     */
    public function bet_rx_5z5(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 5, 5);
    } /* }}} */
    
    /**
     * @brief 任选_六中五
     * @access public
     * @param
     * @return
     */
    public function bet_rx_6z5(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 6, 6);
    } /* }}} */
    
    /**
     * @brief 任选_七中五
     *      bets=[{"gid":72,"tid":6123,"price":2,"counts":1,"price_sum":2,"rate":"20","rebate":0,"pids":"63373","contents":"1,2,3,4,5,6,7","names":"01"}]
     * @access public
     * @param
     * @return
     */
    public function bet_rx_7z5(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 7, 7);
    } /* }}} */
    
    /**
     * @brief 任选_八中五
     * @access public
     * @param
     * @return
     */
    public function bet_rx_8z5(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 8, 8);
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
    public function bet_zx_q2(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 2, 2);
    } /* }}} */
    
    /**
     * @brief 组选_前三
     *      每单1注，每注3球
     * @access public
     * @param
     * @return
     */
    public function bet_zx_q3(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_1z1($bet, 3, 3);
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
    public function bet_zhx_q2(& $bet = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        /* 组合下注量 */
        if ($balls[0][0] == $balls[1][0]) {
            return false;
        }
        $c = 1;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 直选_前三
     *      每单1注，每注3球
     *      bets=[{"gid":72,"tid":6128,"price":2,"counts":1,"price_sum":2,"rate":"900","rebate":0,"pids":"63437,63438,63439","contents":"1|2|3","names":"01"}]
     * @access public
     * @param
     * @return
     */
    public function bet_zhx_q3(& $bet = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || count($balls) != 3) {
            return false;
        }
        /* 组合下注量 */
        if ($balls[0][0] == $balls[1][0] || $balls[1][0] == $balls[2][0] || $balls[0][0] == $balls[2][0]) {
            return false;
        }
        $c = 1;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
}

/* end file */
