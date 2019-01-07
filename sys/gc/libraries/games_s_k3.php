<?php
/**
 * @file games_s_k3.php
 * @brief k3 私彩下注库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/08/28 20:57
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_s_k3
{
    public $config_balls = [
        /* 基本球 */
        'base' => [1, 2, 3, 4, 5, 6],
        /* 和值 */
        'hz'   => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 100, 101, 102, 103],
        /* 两连 */
        '2l' => [12, 13, 14, 15, 16, 23, 24, 25, 26, 34, 35, 36, 45, 46, 56],
        /* 豹子 */
        'bz' => [1, 2, 3, 4, 5, 6, 169]
    ];
    
    /**
     * @brief 整合_和值
     *      以全部开出的三个号码、加起来的总和来判定。 
     *      大小：三个开奖号码总和值11~17 为大；总和值4~10 为小；若三个号码相同、则不算中奖。
     *      单双：三个开奖号码总和5、7、9、11、13、15、17为单；4、6、8、10、12、14、16为双；若三个号码相同、则不算中奖。
     *      开奖号码总和值为3、4、5、6、7、8、9、10、11、12、13、14、15、16、17 、18时，即为中奖； 
     *      举例：如开奖号码为1、2、3、总和值为6、则投注「6」即为中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *      demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          bets=[{"gid":62,"tid":5603,"price":2,"counts":1,"price_sum":2,"rate":"165","rebate":0,"pids":"61001","contents":"3","names":"3"}]
     * @access public
     * @param
     * @return
     */
    public function bet_zh_hz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['hz']);
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
     * @brief 整合_两连
     *      任选一长牌组合、当开奖结果任2码与所选组合相同时，即为中奖。 
     *      举例：如开奖号码为1、2、3、则投注两连12、两连23、两连13皆视为中奖。
     *      demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          bets=[{"gid":62,"tid":5603,"price":2,"counts":1,"price_sum":2,"rate":"165","rebate":0,"pids":"61001","contents":"3","names":"3"}]
     * @access public
     * @param
     * @return
     */
    public function bet_zh_2l(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['2l']);
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
     * @brief 整合_独胆
     *      三个开奖号码其中一个与所选号码相同时、即为中奖。 
     *      举例：如开奖号码为1、1、3，则投注独胆1或独胆3皆视为中奖。
     *      备注：不论当局指定点数出现几次，仅派彩一次(不翻倍)。
     *      demo:
     *          POST http://www.gc360.com/orders/bet/62/20170905024/5603
     *          bets=[{"gid":62,"tid":5603,"price":2,"counts":1,"price_sum":1,"rate":"165","rebate":0,"pids":"61001","contents":"3","names":"3"}]
     * @access public
     * @param
     * @return
     */
    public function bet_zh_dd(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
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
     * @brief 整合_豹子
     *      对所有的豹子（000，111，222，333，444，555，666，777，888，999）进行投注。当开奖号码为任意1个豹子时，即中奖。
     *      投注号码示例 169
     *      开奖号码示例 1 1 1（顺序不限）等
     *      demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          bets=[{"gid":62,"tid":5603,"price":2,"counts":1,"price_sum":1,"rate":"165","rebate":0,"pids":"61001","contents":"3","names":"3"}]
     * @access public
     * @param
     * @return
     */
    public function bet_zh_bz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['bz']);
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
     * @brief 整合_对子
     *      开奖号码任两字同号、且与所选择的对子组合相符时，即为中奖。 
     *      举例：如开奖号码为1、1、3、则投注对子1、1，即为中奖。
     *      demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          bets=[{"gid":62,"tid":5603,"price":2,"counts":1,"price_sum":1,"rate":"165","rebate":0,"pids":"61001","contents":"3","names":"3"}]
     * @access public
     * @param
     * @return
     */
    public function bet_zh_dz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
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
}

/* end file */
