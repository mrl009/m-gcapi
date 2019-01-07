<?php
/**
 * @file games_28.php
 * @brief pcdd 下注库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/07 10:37
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_28
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        // 特码
        'tm' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        // 特码包3
        'tmb3' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        // 混合
        'hh' => [100, 101, 102, 103, 108, 109, 110, 111, 242, 243],
        // 波色
        'bs' => [124, 125, 126],
        // 豹子
        'bz' => [169]
    ];
    
    /**
     * @brief 特码
     *          所选特码与开奖的3个号码相加之和相同，即中奖。
     *          投注号码示例 5
     *          开奖号码示例 0 0 5（顺序不限）等
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
     * @param
     * @return
     */
    public function bet_tm(& $bet = array(), $settlement = false)
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['tm']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 特码包三
     *          投选三个特码，任意一个特码与开奖的3个号码相加之和相同，即中奖。
     *          投注号码示例 5 6 7
     *          开奖号码示例 0 0 5（顺序不限）等
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
     * @param
     * @return
     */
    public function bet_tmb3(& $bet = array(), $settlement = false)
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['tmb3']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        if (count($balls[0]) != 3) {
            return false;
        }
        /* 组合下注量 */
        $c = 1;//count($balls);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 混合
     *          玩法1：大小（对特码大（14~27），小（0~13）形态进行投注，所选号码的形态与开奖号码的形态相同，即为中奖。）
     *          玩法2：单双（对特码单（1，3，5~27），双（0，2，4~26）形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。）
     *          玩法3：组合（对特码大单（15，17，19，21，23，25，27），
     *                          小单（1，3，5，7，9，11，13），
     *                          大双（14，16，18，20，22，24，26），
     *                          小双（0，2，4，6，8.10，12）
     *                          形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。）
     *          玩法4：极值（对特码极大（22，23，24，25，26，27），
     *                          极小（0，1，2，3，4，5）
     *                          形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。）
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
     * @param
     * @return
     */
    public function bet_hh(& $bet = array(), $settlement = false)
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['hh']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 波色
     *          对绿波（1，4，7，10，16，19，22，25），
     *          蓝波（2，5，8，11，17，20，23，26），
     *          红波（3，6，9，12，15，18，21，24）
     *          形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。
     *          投注号码示例 125
     *          开奖号码示例 1 2 4（顺序不限）等
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
     * @param
     * @return
     */
    public function bet_bs(& $bet = array(), $settlement = false)
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['bs']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 豹子
     *          对所有的豹子（000，111，222，333，444，555，666，777，888，999）进行投注。当开奖号码为任意1个豹子时，即中奖。
     *          投注号码示例 169
     *          开奖号码示例 1 1 1（顺序不限）等
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
     * @param
     * @return
     */
    public function bet_bz(& $bet = array(), $settlement = false)
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['bz']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
}

/* end file */
