<?php
/**
 * @file games_k3.php
 * @brief 快3下注库
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/03/25 15:43
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_k3
{
    public $config_balls = [
        /* 基本球 */
        'base'  => [1, 2, 3, 4, 5, 6],
        /* 和值 */
        'hz'    => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
    ];
    
    /**
     * @brief 二不同号_标准选号
     *      对三个号码中两个指定的不同号码和一个任意号码进行投注。
     *      选两个不相同的号为一组，开奖号中有任意两号与所选号相同为中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param 
     * @return 
     */
    public function bet_2bth_bzxh(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        // $r2 = combination($balls[0], 2);
        $c = C($n, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 二不同号_手动选号
     *      手动输入号码，至少输入1-6中两个不同的数字组成一注号码。
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1909
     * @param 
     * @return 
     */
    public function bet_2bth_sdxh(& $bet = array(), $settlement = false) /* {{{ */
    {
        return $this->bet_2bth_bzxh($bet, $settlement);
    } /* }}} */
    
    /**
     * @brief 二不同号_胆拖选号
     *      从1~6中，选取2个及以上的号码进行投注，每注需至少包括1个胆码及1个拖码。
     *      胆码选中后的球，拖码不能再选。
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1910
     * @param 
     * @return 
     */
    public function bet_2bth_tdxh(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || empty($balls[1]) || (!empty($balls[0][0]) && in_array($balls[0][0], $balls[1]))) {
            return false;
        }
        /* 组合下注量 */
        $s = count($balls[0]);
        $c = count($balls[1]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($s == 1 && $c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 二同号_二同单选_标准选号
     *      对三个号码中两个指定的相同号码与一个指定的不同号码进行投注。
     *      二同号 选中后的球，不同号 不能再选。
     * @access public
     * @param 
     * @return 
     */
    public function bet_2th_2tdx_bzxh($bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || empty($balls[1]) || (!empty($balls[0][0]) && in_array($balls[0][0], $balls[1]))) {
            return false;
        }
        /* 组合下注量 */
        $s = count($balls[0]);
        $c = count($balls[1]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($s == 1 && $c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 二同号_二同单选_手动选号
     *      对三个号码中两个指定的相同号码与一个指定的不同号码进行投注。
     *      二同号 选中后的球，不同号 不能再选。
     *      !手动输入号码，至少输入1个三位数（其中1个号码需相同）号码组成一注。
     * @access public
     * @param 
     * @return 
     */
    public function bet_2th_2tdx_sdxh($bet = array(), $settlement = false) /* {{{ */
    {
        return $this->bet_2th_2tdx_bzxh($bet, $settlement);
    } /* }}} */
    
    /**
     * @brief 二同号_二同复选
     *      对三个号码中两个指定的相同号码进行投注。
     * @access public
     * @param 
     * @return 
     */
    public function bet_2th_2tfx(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 三不同号_标准选号
     *      对三个各不相同的号码进行投注。
     * @access public
     * @param 
     * @return 
     */
    public function bet_3bth_bzxh(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        $c = C($n, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 三不同号_手动选号
     *      对三个各不相同的号码进行投注。
     * @access public
     * @param 
     * @return 
     */
    public function bet_3bth_sdxh(& $bet = array(), $settlement = false) /* {{{ */
    {
        return $this->bet_3bth_bzxh($bet, $settlement);
    } /* }}} */
    
    /**
     * @brief 三同号_三同单选
     *      对所有相同的三个号码：（111 222 333 444 555 666）中任意选择一组号码进行投注。
     * @access public
     * @param 
     * @return 
     */
    public function bet_3th_3tdx(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 三同号_三同通选
     *      对所有相同的三个号码：（111 222 333 444 555 666）进行投注。
     *      $bet['contents'] = '1,2,3,4,5,6'
     * @access public
     * @param 
     * @return 
     */
    public function bet_3th_3ttx(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false || $balls[0] != $this->config_balls['base']) {
            return false;
        }
        /* 组合下注量 */
        $c = 1;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 三连号_三连通选
     *      对所有三个相连的号码（仅限：123、234、345、456）进行投注。
     *      $bet['contents'] = '1,2,3,4'
     * @access public
     * @param 
     * @return 
     */
    public function bet_3lh_3ltx(& $bet = array(), $settlement = false) /* {{{ */
    {
        /* 检测球号 */
        if ($bet['contents'] != '1,2,3,4') {
            return false;
        }
        /* 组合下注量 */
        $c = 1;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 和值_和值
     *      从3-18中任意选择1个或1个以上号码。
     * @access public
     * @param 
     * @return 
     */
    public function bet_hz_hz($bet = array(), $settlement = false) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['hz']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        // $c = count($balls[0]);
        $c = 1;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
}

/* end file */
