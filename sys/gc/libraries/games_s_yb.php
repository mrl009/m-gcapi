<?php
/**
 * @file games_s_yb.php
 * @brief ssc 私彩下注库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/11 10:59
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_s_yb
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        /* 第1～3球 */
        'd13' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 100, 101, 102, 103],
        /* 总和 龙虎 */
        'zhlh' => [196, 197, 198, 199, 133, 131, 200],
        /* 三连 豹子 顺子 对子 半顺 杂六 */
        '3l' => [169, 170, 171, 172, 173],
    ];
    
    /**
     * @brief 1-3球_第一球
     *      第一球、第二球、第三球：指下注的每一球与开出之号码其开奖顺序及开奖号码相同，视为中奖，
     *      如第一球开出号码 8，下注第一球为 8 者视为中奖，其余情形视为不中奖。
     *      大小：根据相应单项投注的第一球 ~ 第三球开出的球号大于或等于 5 为大，小于或等于 4 为小。
     *      单双：根据相应单项投注的第一球 ~ 第三球开出的球号为双数则为双，如 2、6；球号为单数则为单，如 1、3。
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
     *          bets=[{"gid":56,"tid":5103,"price":2,"counts":1,"price_sum":2,"rate":"9.86","rebate":0,"pids":"50400","contents":"0","names":"0"}]
     * @access public
     * @param
     * @return
     */
    public function bet_13q_d1(& $bet = [], $ball = 'd13') /* {{{ */
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
     * @brief 1-3球_第二球
     * @access public
     * @param
     * @return
     */
    public function bet_13q_d2(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, 'd13');
    } /* }}} */
    
    /**
     * @brief 1-3球_第三球
     * @access public
     * @param
     * @return
     */
    public function bet_13q_d3(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, 'd13');
    } /* }}} */
    
    /**
     * @brief 整合_独胆
     *      独胆：会员可以选择 0 ~ 9 的任意一个号码，只要开出的3球中有下注的号码，即为中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_zh_dd(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, 'base');
    } /* }}} */
    
    /**
     * @brief 整合_跨度
     *      跨度：以开奖三个号码的最大差距(跨度)，作为中奖的依据。会员可以选择 0 ~ 9 的任一跨度。
     * @access public
     * @param
     * @return
     */
    public function bet_zh_kd(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, 'base');
    } /* }}} */
    
    /**
     * @brief 整合_总和龙虎
     *      整合_总和
     *      大小：根据相应单项投注的第一球 ~ 第三球开出的球号数字总和值大于或等于 14 为总和大，小于或等于 13 为总和小。
     *      单双：根据相应单项投注的第一球 ~ 第三球开出的球号数字总和值是双数为总和双，数字总和值是单数为总和单。
     *      整合_龙虎
     *      龙：开奖第一球（百位）的号码 大于 第三球（个位）的号码。如：6X2、7X6、9X8...开奖为龙，投注龙者视为中奖，其它视为不中奖。
     *      虎：开奖第一球（百位）的号码 小于 第三球（个位）的号码。如：1X2、4X6、3X8...开奖为虎，投注虎者视为中奖，其它视为不中奖。
     *      和：开奖第一球（百位）的号码 等于 第三球（个位）的号码。如：2X2、6X6、8X8...开奖为和，投注和者视为中奖，其它视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_zh_zhlh(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, 'zhlh');
    } /* }}} */
    
    /**
     * @brief 整合_龙虎
     *      龙：开奖第一球（百位）的号码 大于 第三球（个位）的号码。如：6X2、7X6、9X8...开奖为龙，投注龙者视为中奖，其它视为不中奖。
     *      虎：开奖第一球（百位）的号码 小于 第三球（个位）的号码。如：1X2、4X6、3X8...开奖为虎，投注虎者视为中奖，其它视为不中奖。
     *      和：开奖第一球（百位）的号码 等于 第三球（个位）的号码。如：2X2、6X6、8X8...开奖为和，投注和者视为中奖，其它视为不中奖。 
     * @access public
     * @param
     * @return
     */
    public function bet_zh_lh(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, 'lh');
    } /* }}} */
    
    /**
     * @brief 整合_3连
     *      3连 特殊玩法： 豹子 > 顺子 > 对子 > 半顺 > 杂六
     *      豹子：开奖号码的百位十位个位数字都相同。如中奖号码为：222、666、888...开奖号码的百位十位个位数字相同，则投注三连豹子者视为中奖，其它视为不中奖。
     *      顺子：开奖号码的百位十位个位数字都相连，不分顺序（数字9、0、1相连）。如中奖号码为：123、901、321、798...
     *          开奖号码的百位十位个位数字相连，则投注三连顺子者视为中奖，其它视为不中奖。
     *      对子：开奖号码的百位十位个位任意两位数字相同（不包括豹子）。如中奖号码为：001，288、696...开奖号码的百位十位个位有两位数字相同，
     *          则投注三连对子者视为中奖，其它视为不中奖。如果开奖号码为三连豹子，则三连对子视为不中奖。
     *      半顺：开奖号码的百位十位个位任意两位数字相连，不分顺序（不包括顺子、对子）。如中奖号码为：125、540、390、160...
     *          开奖号码的百位十位个位有两位数字相连，则投注三连半顺者视为中奖，其它视为不中奖。
     *          如果开奖号码为三连顺子、三连对子，则三连半顺视为不中奖。如开奖号码为：123、901、556、233...视为不中奖。
     *      杂六：不包括豹子、对子、顺子、半顺的所有开奖号码。如开奖号码为：157、268...开奖号码位数之间无关联性，则投注三连杂六者视为中奖，其它视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_zh_3l(& $bet = []) /* {{{ */
    {
        return $this->bet_13q_d1($bet, '3l');
    } /* }}} */
}

/* end file */
