<?php
/**
 * @file games_settlement_s_ssc.php
 * @brief ssc 私彩结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/08 15:10
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_settlement_s_ssc
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        /* 第1～5球 */
        'd15' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 100, 101, 102, 103],
        /* 大小单双 */
        'dxds' => [100, 101, 102, 103],
        /* 前/中/后三球 豹子 顺子 对子 半顺 杂六 */
        '3q' => [169, 170, 171, 172, 173],
        /* 总和 大小单双 */
        'zh_dxds' => [196, 197, 198, 199],
        /* 龙虎: 龙133, 虎131, 和200 */
        'lh' => [133, 131, 200],
        /* 斗牛 没牛 牛牛 牛1-牛9 牛大 牛小 牛单 牛双 */
        'dn' => [174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188],
        /* 梭哈 五条 四条 葫芦 顺子 三条 两对 一对 散号 */
        'sh' => [189, 190, 191, 170, 192, 193, 194, 195],
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：ssc 当期开奖: 2,6,6,1,1
     *          则计算出开奖和值: 16
     * @access public/protected 
     * @param 
     * @return 
     */
    public function wins_balls(& $wins_balls = []) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3', '4', '5'];
        if (count($wins_balls['base']) != 5 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[s_ssc] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        //$wins_balls['d1'] = [code1, code2(大小), code3(单双)];
        //$wins_balls['d2'] = [code1, code2, code3];
        //$wins_balls['d5'] = [code1, code2, code3];
        //$wins_balls['q3/z3/h3'] = [code];
        //$wins_balls['zh'] = [code1(总和大小), code2(总和单双)];
        //$wins_balls['lh'] = [code];
        //$wins_balls['dn'] = [code1, code2(牛大小) / null(牛单双), code3 / null];
        //$wins_balls['sh'] = [code];
    
        /* 第一~五球 大小单双 */
        $i = 1;
        $he = 0;
        foreach ($wins_balls['base'] as $v) {
            $he += $v;
            $wins_balls['d'.$i][0] = $v;
            $wins_balls['d'.$i][1] = $v >= 5 ? 100 : 101;
            $wins_balls['d'.$i][2] = ($v % 2) == 1 ? 102 : 103;
            $i++;
        }
        /* 前三球 中三球 后三球 */
        $wins_balls['q3'] = [$this->config_balls['3q'][ball3([$wins_balls['base'][0], $wins_balls['base'][1], $wins_balls['base'][2]])]];
        $wins_balls['z3'] = [$this->config_balls['3q'][ball3([$wins_balls['base'][1], $wins_balls['base'][2], $wins_balls['base'][3]])]];
        $wins_balls['h3'] = [$this->config_balls['3q'][ball3([$wins_balls['base'][2], $wins_balls['base'][3], $wins_balls['base'][4]])]];
        /* 总和 大小单双 */
        $wins_balls['zh'][0] = $he >= 23 ? 196 : 197;
        $wins_balls['zh'][1] = ($he % 2) == 1 ? 198 : 199;
        /* 1:5 龙虎和 */
        $wins_balls['lh'][0] = $wins_balls['base'][0] > $wins_balls['base'][4] ? 133 : 131;
        $wins_balls['lh'][0] = $wins_balls['base'][0] == $wins_balls['base'][4] ? 200 : $wins_balls['lh'][0];
        /* 斗牛 牛大牛小 牛单牛双 */
        $n = bull($wins_balls['base']);
        $wins_balls['dn'][0] = $this->config_balls['dn'][$n];
        if ($n != 0) {
            $wins_balls['dn'][1] = $n > 5 ? 185 : 186;
            $wins_balls['dn'][2] = ($n % 2) == 1 ? 187 : 188;
        }
        /* 梭哈 */
        $wins_balls['sh'][0] = $this->config_balls['sh'][suoha($wins_balls['base'])];
    
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 整合_第一球
     *      第一球、第二球、第三球、第四球、第五球：指下注的每一球与开出之号码其开奖顺序及开奖号码相同，视为中奖，
     *      如第一球开出号码 8，下注第一球为 8 者视为中奖，其余情形视为不中奖。
     *      大小：根据相应单项投注的第一球 ~ 第五球开出的球号大于或等于 5 为大，小于或等于 4 为小。
     *      单双：根据相应单项投注的第一球 ~ 第五球开出的球号为双数则为双，如 2、6；球号为单数则为单，如 1、3。
     *      deom:
     *          {"zh_d1":["956709082034024839",520,"0",2,1,2,"9.86",0,0]}
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_d1(& $bet = [], & $lottery = [], $ball = 'd1') /* {{{ */
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
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 整合_第二球
     *      第一球、第二球、第三球、第四球、第五球：指下注的每一球与开出之号码其开奖顺序及开奖号码相同，视为中奖，
     *      如第一球开出号码 8，下注第一球为 8 者视为中奖，其余情形视为不中奖。
     *      大小：根据相应单项投注的第一球 ~ 第五球开出的球号大于或等于 5 为大，小于或等于 4 为小。
     *      单双：根据相应单项投注的第一球 ~ 第五球开出的球号为双数则为双，如 2、6；球号为单数则为单，如 1、3。
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_d2(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd2');
    } /* }}} */
    
    /**
     * @brief 整合_第三球
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_d3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd3');
    } /* }}} */
    
    /**
     * @brief 整合_第四球
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_d4(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd4');
    } /* }}} */
    
    /**
     * @brief 整合_第五球
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_d5(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd5');
    } /* }}} */
    
    /**
     * @brief 整合_前三球
     *      前三/中三/后三 特殊玩法： 豹子 > 顺子 > 对子 > 半顺 > 杂六
     *      豹子：开奖号码的万位千位百位数字都相同。如中奖号码为：222XX、666XX、888XX...开奖号码的万位千位百位数字相同，则投注前三豹子者视为中奖，其它视为不中奖。
     *      顺子：开奖号码的万位千位百位数字都相连，不分顺序（数字9、0、1相连）。如中奖号码为：123XX、901XX、321XX、798XX...
     *          开奖号码的万位千位百位数字相连，则投注前三顺子者视为中奖，其它视为不中奖。
     *      对子：开奖号码的万位千位百位任意两位数字相同（不包括豹子）。如中奖号码为：001XX，288XX、696XX...开奖号码的万位千位百位有两位数字相同
     *          ，则投注前三对子者视为中奖，其它视为不中奖。如果开奖号码为前三豹子，则前三对子视为不中奖。
     *      半顺：开奖号码的万位千位百位任意两位数字相连，不分顺序（不包括顺子、对子）。
     *          如中奖号码为：125XX、540XX、390XX、160XX...开奖号码的万位千位百位有两位数字相连，则投注前三半顺者视为中奖，其它视为不中奖。
     *          如果开奖号码为前三顺子、前三对子，则前三半顺视为不中奖。如开奖号码为：123XX、901XX、556XX、233XX...视为不中奖。
     *      杂六：不包括豹子、对子、顺子、半顺的所有开奖号码。如开奖号码为：157XX、268XX...开奖号码位数之间无关联性，则投注前三杂六者视为中奖，其它视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_q3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'q3');
    } /* }}} */
    
    /**
     * @brief 整合_中三球
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_z3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'z3');
    } /* }}} */
    
    /**
     * @brief 整合_后三球
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_h3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'h3');
    } /* }}} */
    
    /**
     * @brief 整合_总和
     *      大小：根据相应单项投注的第一球 ~ 第五球开出的球号数字总和值大于或等于 23 为总和大，小于或等于 22 为总和小。
     *      单双：根据相应单项投注的第一球 ~ 第五球开出的球号数字总和值是双数为总和双，数字总和值是单数为总和单。
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_zh(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'zh');
    } /* }}} */
    
    /**
     * @brief 整合_龙虎
     *      龙：开奖第一球（万位）的号码 大于 第五球（个位）的号码。如：3XXX2、7XXX6、9XXX8...开奖为龙，投注龙者视为中奖，其它视为不中奖。
     *      虎：开奖第一球（万位）的号码 小于 第五球（个位）的号码。如：1XXX2、3XXX6、4XXX8..开奖为虎，投注虎者视为中奖，其它视为不中奖。
     *      和：开奖第一球（万位）的号码 等于 第五球（个位）的号码。如：2XXX2、6XXX6、8XXX8...开奖为和，投注和者视为中奖，其它视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_lh(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'lh');
    } /* }}} */
    
    /**
     * @brief 整合_斗牛
     *      斗牛：开奖号码不分顺序
     *      牛牛：根据开奖第一球 ~ 第五球开出的球号数字为基础，任意组合三个号码成0或10的倍数，取剩余两个号码之和为点数。
     *          （大于10时减去10后的数字作为对奖基数，如：00026为牛8，02818为牛9，68628、23500皆为牛10俗称牛牛；
     *          26378、15286因任意三个号码都无法组合成0或10的倍数，称为没牛，注：当五个号码相同时，只有00000视为牛牛，其它11111，66666等皆视为没牛）
     *      大小：牛大(牛6,牛7,牛8,牛9,牛牛)，牛小(牛1,牛2,牛3,牛4,牛5)，若开出斗牛结果为没牛，则投注牛大牛小皆为不中奖。
     *      单双：牛单(牛1,牛3,牛5,牛7,牛9)，牛双(牛2,牛4,牛6,牛8,牛牛)，若开出斗牛结果为没牛，则投注牛单牛双皆为不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_dn(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'dn');
    } /* }}} */
    
    /**
     * @brief 整合_梭哈
     *      梭哈：开奖号码不分顺序
     *      五条：开奖的五个号码全部相同，例如：22222、66666、88888 投注梭哈：五条 中奖，其它不中奖。 
     *      四条：开奖的五个号码中有四个号码相同，例如：22221、66663、88885 投注梭哈：四条 中奖，其它不中奖。 
     *      葫芦：开奖的五个号码中有三个号码相同(三条)另外两个号码也相同(一对)，例如：22211、66633 投注梭哈：葫芦 中奖，其它不中奖。 
     *      顺子：开奖的五个号码从小到大排列为顺序(号码9、0、1相连)，例如：23456、89012、90123 投注梭哈：顺子 中奖，其它不中奖。 
     *      三条：开奖的五个号码中有三个号码相同另外两个不相同，例如：22231、66623、88895 投注梭哈：三条 中奖，其它不中奖。 
     *      两对：开奖的五个号码中有两组号码相同，例如：22166、66355、82668 投注梭哈：两对 中奖，其它不中奖。 
     *      一对：开奖的五个号码中只有一组号码相同，例如：22168、66315、82968 投注梭哈：一对 中奖，其它不中奖。 
     *      散号：开奖号码不是五条、四条、葫芦、三条、顺子、两对、一对的其它所有开奖号码，例如：23186、13579、21968 投注梭哈：散号 中奖，其它不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_sh(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'sh');
    } /* }}} */
    
    /**
     * @brief 两面盘_第一球
     *      第一球、第二球、第三球、第四球、第五球：指下注的每一球与开出之号码其开奖顺序及开奖号码相同，视为中奖，
     *      如第一球开出号码 8，下注第一球为 8 者视为中奖，其余情形视为不中奖。
     *      大小：根据相应单项投注的第一球 ~ 第五球开出的球号大于或等于 5 为大，小于或等于 4 为小。
     *      单双：根据相应单项投注的第一球 ~ 第五球开出的球号为双数则为双，如 2、6；球号为单数则为单，如 1、3。
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d1(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd1');
    } /* }}} */
    
    /**
     * @brief 两面盘_第二球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d2(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd2');
    } /* }}} */
    
    /**
     * @brief 两面盘_第三球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd3');
    } /* }}} */
    
    /**
     * @brief 两面盘_第四球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d4(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd4');
    } /* }}} */
    
    /**
     * @brief 两面盘_第五球
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d5(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'd5');
    } /* }}} */
    
    /**
     * @brief 两面盘_总和
     *      大小：根据相应单项投注的第一球 ~ 第五球开出的球号数字总和值大于或等于 23 为总和大，小于或等于 22 为总和小。
     *      单双：根据相应单项投注的第一球 ~ 第五球开出的球号数字总和值是双数为总和双，数字总和值是单数为总和单。
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_zh(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'zh');
    } /* }}} */
    
    /**
     * @brief 两面盘_龙虎
     *      龙：开奖第一球（万位）的号码 大于 第五球（个位）的号码。如：3XXX2、7XXX6、9XXX8...开奖为龙，投注龙者视为中奖，其它视为不中奖。
     *      虎：开奖第一球（万位）的号码 小于 第五球（个位）的号码。如：1XXX2、3XXX6、4XXX8..开奖为虎，投注虎者视为中奖，其它视为不中奖。
     *      和：开奖第一球（万位）的号码 等于 第五球（个位）的号码。如：2XXX2、6XXX6、8XXX8...开奖为和，投注和者视为中奖，其它视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_lh(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_zh_d1($bet, $lottery, 'lh');
    } /* }}} */
}

/* end file */
