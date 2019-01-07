<?php
/**
 * @file games_s_kl10.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/15 16:36
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_s_kl10
{
    public $config_balls = [
        /* 基本球 */
        'base' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
        /* 总和大小单双 总和尾大尾小 总和龙虎 */
        'zh' => [196, 197, 198, 199, 225, 226, 133, 131],
        /* 大小单双 尾大尾小合单合双 东南西北中发白 */
        'lm' => [100, 101, 102, 103, 112, 113, 106, 107, 218, 219, 220, 221, 222, 223, 224],
        /* 第1-8 */
        'd18' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 100, 101, 102, 103, 112, 113, 106, 107, 218, 219, 220, 221, 222, 223, 224],
    ];
    
    /**
     * @brief 两面盘_总和
     *      总和单双：所有8个开奖号码的数字总和值是单数为总和单，如数字总和值是37、51；
     *          所有8个开奖号码的数字总和值是双数为总和双，如数字总和是42、80；假如投注组合符合中奖结果，视为中奖。
     *      总和大小：所有8个开奖号码的数字总和值85到132为总大；所有8个开奖号码的数字总和值37到83为总分小；
     *          所有8个开奖号码的数字总和值为84打和；如开奖号码为01、20、02、08、17、09、11，数字总和是68，则总分小。假如投注组合符合中奖结果，视为中奖。
     *      总尾大小：所有8个开奖号码的数字总和数值的个位数大于或等于5为总尾大，小于或等于4为总尾小；假如投注组合符合中奖结果，视为中奖。
     *      龙：开出之号码第一球的中奖号码大于第八球的中奖号码。如 14XXXXXX09,16XXXXXX10,17XXXXXX08...中奖为龙。
     *      虎：开出之号码第一球的中奖号码小于第八球的中奖号码。如 14XXXXXX19,16XXXXXX17,17XXXXXX18...中奖为虎。
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
     *          bets=[{"gid":78,"tid":6412,"price":2,"counts":1,"price_sum":2,"rate":"1.96","rebate":0,"pids":"65001","contents":"196","names":"总和大"}]
     * @access public
     * @param
     * @return
     */
    public function bet_lm_zh(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'zh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第一球
     *      两面：指：单双、大小、尾大尾小。
     *      单双：号码为双数叫双，如8、16；号码为单数叫单，如19、5。
     *      大小：开出之号码大于或等于11为大，小于或等于10为小。
     *      尾大尾小：开出之尾数大于或等于5为尾大，小于或等于4为尾小。
     *      每一个号码为一投注组合，假如投注号码为开奖号码并在所投的球位置，视为中奖，其余情形视为不中奖。
     *      中发白：
     *      中：开出之号码为01、02、03、04、05、06、07
     *      发：开出之号码为08、09、10、11、12、13、14
     *      白：开出之号码为15、16、17、18、19、20
     *      方位：
     *      东：开出之号码为01、05、09、13、17
     *      南：开出之号码为02、06、10、14、18
     *      西：开出之号码为03、07、11、15、19
     *      北：开出之号码为04、08、12、16、20
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
     * @brief 两面盘_第六球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d6(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 两面盘_第七球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d7(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 两面盘_第八球
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d8(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'lm');
    } /* }}} */
    
    /**
     * @brief 第一球_第一球
     *      第一球~第八球：第一球、第二球、第三球、第四球、第五球、第六球、第七球、第八球：
     *      指下注的每一球与开出之号码其开奖顺序及开奖号码相同，视为中奖，如第一球开出号码 8，下注第一球为 8 者视为中奖，其余情形视为不中奖。
     *      单双：号码为双数叫双，如8、16；号码为单数叫单，如19、5。
     *      大小：开出之号码大于或等于11为大，小于或等于10为小。
     *      尾大尾小：开出之尾数大于或等于5为尾大，小于或等于4为尾小。
     *      每一个号码为一投注组合，假如投注号码为开奖号码并在所投的球位置，视为中奖，其余情形视为不中奖。
     *      中发白：
     *      中：开出之号码为01、02、03、04、05、06、07
     *      发：开出之号码为08、09、10、11、12、13、14
     *      白：开出之号码为15、16、17、18、19、20
     *      方位：
     *      东：开出之号码为01、05、09、13、17
     *      南：开出之号码为02、06、10、14、18
     *      西：开出之号码为03、07、11、15、19
     *      北：开出之号码为04、08、12、16、20
     * @access public
     * @param
     * @return
     */
    public function bet_d1_d1(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第二球_第二球
     * @access public
     * @param
     * @return
     */
    public function bet_d2_d2(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第三球_第三球
     * @access public
     * @param
     * @return
     */
    public function bet_d3_d3(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第四球_第四球
     * @access public
     * @param
     * @return
     */
    public function bet_d4_d4(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第五球_第五球
     * @access public
     * @param
     * @return
     */
    public function bet_d5_d5(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第六球_第六球
     * @access public
     * @param
     * @return
     */
    public function bet_d6_d6(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第七球_第七球
     * @access public
     * @param
     * @return
     */
    public function bet_d7_d7(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 第八球_第八球
     * @access public
     * @param
     * @return
     */
    public function bet_d8_d8(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd18');
    } /* }}} */
    
    /**
     * @brief 总和_总和
     * @access public
     * @param
     * @return
     */
    public function bet_zh_zh(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'zh');
    } /* }}} */
    
    /**
     * @brief 连码_任选二
     *      任选二：指从01至20中任意选择2个号码对开奖号码中任意2个位置的投注。 投注号码与开奖号码中任意2个位置的号码相符，即中奖。
     *      任选二组：指从01至20中任意选择2个号码对开奖号码中按开奖顺序出现的2个连续位置的投注。
     *          投注号码与开奖号码中按开奖顺序出现的2个连续位置的号码相符（顺序不限），即中奖。
     *      任选三：指从01至20中任意选择3个号码对开奖号码中任意3个位置的投注。 投注号码与开奖号码中任意3个位置的号码相符，即中奖。
     *      任选四：指从01至20中任意选择4个号码，对开奖号码中任意4个位置的投注。投注号码与开奖号码中任意4个位置的号码相符，即中奖。
     *      任选五：指从01至20中任意选择5个号码，对开奖号码中任意5个位置的投注。投注号码与开奖号码中任意5个位置的号码相符，即中奖。
     *      bets=[{"gid":78,"tid":6430,"price":2,"counts":3,"price_sum":6,"rate":"5","rebate":0,"pids":"65495,65496,65497","contents":"1,2,3","names":"01,02,03"}]
     * @access public
     * @param
     * @return
     */
    public function bet_lma_rx2(& $bet = [], $m = 2, $limit = 10) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < $m || $n > $limit) {
            return false;
        }
        $c = C($n, $m);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连码_任选二组
     * @access public
     * @param
     * @return
     */
    public function bet_lma_rx2z(& $bet = []) /* {{{ */
    {
        return $this->bet_lma_rx2($bet, 2, 6);
    } /* }}} */
    
    /**
     * @brief 连码_任选三
     * @access public
     * @param
     * @return
     */
    public function bet_lma_rx3(& $bet = []) /* {{{ */
    {
        return $this->bet_lma_rx2($bet, 3, 6);
    } /* }}} */
    
    /**
     * @brief 连码_任选四
     * @access public
     * @param
     * @return
     */
    public function bet_lma_rx4(& $bet = []) /* {{{ */
    {
        return $this->bet_lma_rx2($bet, 4, 7);
    } /* }}} */
    
    /**
     * @brief 连码_任选五
     * @access public
     * @param
     * @return
     */
    public function bet_lma_rx5(& $bet = []) /* {{{ */
    {
        return $this->bet_lma_rx2($bet, 5, 8);
    } /* }}} */
}

/* end file */
