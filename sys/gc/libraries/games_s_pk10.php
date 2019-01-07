<?php
/**
 * @file games_s_pk10.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/11 20:49
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_s_pk10
{
    public $config_balls = [
        /* 基本球 */
        'base' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        /* 大小单双 冠亚大小单双 */
        'dxds' => [100, 101, 102, 103],
        'gy_dxds' => [313, 314, 315, 316],
        /* 大小单双龙虎 */
        'dxdslh' => [100, 101, 102, 103, 133, 131],
        /* 冠亚军和 */
        '12he' => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 313, 314, 315, 316],
        /* 1-10 大小单双 龙虎 */
        'd15' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 100, 101, 102, 103, 133, 131],
    ];
    
    /**
     * @brief 两面盘_冠亚军和
     *      冠军车号＋亚军车号＝冠亚和值：
     *      冠亚和大小：大于11时投注"大"的注单视为中奖，小于11时投注"小"的注单视为中奖，其余视为不中(如果开11打和)
     *      冠亚和单双：为单视为投注"单"的注单视为中奖，为双视为投注"双"的注单视为中奖，其余视为不中奖(如果开11打和)。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *      demo:
     *          POST http://www.gc360.com/orders/bet/76
     *          bets=[{"gid":56,"tid":5103,"price":2,"counts":1,"price_sum":2,"rate":"9.86","rebate":0,"pids":"50400","contents":"0","names":"0"}]
     * @access public
     * @param
     * @return
     */
    public function bet_lm_12he(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'gy_dxds');
    } /* }}} */
    
    /**
     * @brief 两面盘_冠军
     *      单、双：号码为双数叫双，如8、10；号码为单数叫单，如9、5。
     *      大、小：开出之号码大于或等于6为大，小于或等于5为小。
     *      冠　军 龙/虎："第一名"车号大于"第十名"车号视为【龙】中奖、反之小于视为【虎】中奖，其余情形视为不中奖。
     *      亚　军 龙/虎："第二名"车号大于"第九名"车号视为【龙】中奖、反之小于视为【虎】中奖，其余情形视为不中奖。
     *      第三名 龙/虎："第三名"车号大于"第八名"车号视为【龙】中奖、反之小于视为【虎】中奖，其余情形视为不中奖。
     *      第四名 龙/虎："第四名"车号大于"第七名"车号视为【龙】中奖、反之小于视为【虎】中奖，其余情形视为不中奖。
     *      第五名 龙/虎："第五名"车号大于"第六名"车号视为【龙】中奖、反之小于视为【虎】中奖，其余情形视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d1(& $bet = [], $ball = 'dxdslh') /* {{{ */
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
     * @brief 两面盘_亚军
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d2(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第三名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d3(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第四名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d4(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第五名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d5(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第六名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d6(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第七名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d7(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第八名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d8(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第九名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d9(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第十名
     * @access public
     * @param
     * @return
     */
    public function bet_lm_d10(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'dxdslh');
    } /* }}} */
    
    /**
     * @brief 冠亚军组合_冠亚军和
     *      冠军车号＋亚军车号＝冠亚和值：
     *      可能出现的结果为3～19， 投中对应"冠亚和值"数字的视为中奖，其余视为不中奖。
     *      冠亚和大小：大于11时投注"大"的注单视为中奖，小于11时投注"小"的注单视为中奖，其余视为不中(如果开11打和)
     *      冠亚和单双：为单视为投注"单"的注单视为中奖，为双视为投注"双"的注单视为中奖，其余视为不中奖(如果开11打和)。
     * @access public
     * @param
     * @return
     */
    public function bet_12he_12he(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, '12he');
    } /* }}} */
    
    /**
     * @brief 1-5名_冠军
     *      第一名 ~ 第十名 车号指定，每一个车号为一投注组合，开奖结果"投注车号"对应所投名次视为中奖，其余情形视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_d15_d1(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 1-5名_亚军
     * @access public
     * @param
     * @return
     */
    public function bet_d15_d2(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 1-5名_第三名
     * @access public
     * @param
     * @return
     */
    public function bet_d15_d3(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 1-5名_第四名
     * @access public
     * @param
     * @return
     */
    public function bet_d15_d4(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 1-5名_第五名
     * @access public
     * @param
     * @return
     */
    public function bet_d15_d5(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 6-10名_第六名
     * @access public
     * @param
     * @return
     */
    public function bet_d610_d6(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 6-10名_第七名
     * @access public
     * @param
     * @return
     */
    public function bet_d610_d7(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 6-10名_第八名
     * @access public
     * @param
     * @return
     */
    public function bet_d610_d8(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 6-10名_第九名
     * @access public
     * @param
     * @return
     */
    public function bet_d610_d9(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
    
    /**
     * @brief 6-10名_第十名
     * @access public
     * @param
     * @return
     */
    public function bet_d610_d10(& $bet = []) /* {{{ */
    {
        return $this->bet_lm_d1($bet, 'd15');
    } /* }}} */
}

/* end file */
