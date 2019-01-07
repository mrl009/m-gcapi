<?php
/**
 * @file games_settlement_s_pk10.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/12 18:52
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_settlement_s_pk10
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
        '12he' => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 100, 101, 102, 103],
        /* 1-10 大小单双 龙虎 */
        'd15' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 100, 101, 102, 103, 133, 131],
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：pk10 当期开奖: 1,2,3,4,5,6,7,8,9,10
     *          则计算出开奖冠亚和值: 3
     * @access public/protected 
     * @param 
     * @return 
     */
    public function wins_balls(& $wins_balls = []) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        /* NOTE: pk10 开奖号不可以有重复 */
        if (count(array_unique($wins_balls['base'])) != 10 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][5], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][6], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][7], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][8], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][9], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[s_pk10] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        //$wins_balls['12he'] = [code1, code2(大小) / null, code3(单双) / null];
        //$wins_balls['d1'] = [code1, code2(大小), code3(单双), code4(龙虎)];
        //$wins_balls['d2'] = [code1, code2, code3, code4];
        //$wins_balls['d3'] = [code1, code2, code3, code4];
        //$wins_balls['d4'] = [code1, code2, code3, code4];
        //$wins_balls['d5'] = [code1, code2, code3, code4];
        //$wins_balls['d6'] = [code1, code2, code3];
        //$wins_balls['d10'] = [code1, code2, code3];
    
        /* 冠亚和 和大小单双 */
        $wins_balls['12he'][0] = $wins_balls['base'][0] + $wins_balls['base'][1];
        $wins_balls['12he'][1] = $wins_balls['12he'][0] == 11 ? null : ($wins_balls['12he'][0] > 11 ? 313 : 314);
        $wins_balls['12he'][2] = $wins_balls['12he'][0] == 11 ? null : ($wins_balls['12he'][0] % 2 == 1 ? 315 : 316);
        /* 第一~十名 大小单双 龙虎 */
        $i = 1;
        foreach ($wins_balls['base'] as $v) {
            $wins_balls['d'.$i][0] = $v;
            $wins_balls['d'.$i][1] = $v >= 6 ? 100 : 101;
            $wins_balls['d'.$i][2] = ($v % 2) == 1 ? 102 : 103;
            /* 龙虎 */
            if ($i <= 5) {
                $wins_balls['d'.$i][3] = $wins_balls['base'][$i - 1] > $wins_balls['base'][10 - $i] ? 133 : 131;
            }
            $i++;
        }
    
        return $wins_balls;
    } /* }}} */
    
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
    public function settlement_lm_12he(& $bet = [], & $lottery = []) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 */
        if (in_array($v, $lottery['12he'])) {
            $ret['win_contents'][] = [$v];
        }
        /* 和 */
        if ($lottery['12he'][0] == 11 && in_array($v, $this->config_balls['gy_dxds'])) {
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
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 两面盘_亚军
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d2(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd2');
    } /* }}} */
    
    /**
     * @brief 两面盘_第三名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd3');
    } /* }}} */
    
    /**
     * @brief 两面盘_第四名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d4(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd4');
    } /* }}} */
    
    /**
     * @brief 两面盘_第五名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d5(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd5');
    } /* }}} */
    
    /**
     * @brief 两面盘_第六名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d6(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd6');
    } /* }}} */
    
    /**
     * @brief 两面盘_第七名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d7(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd7');
    } /* }}} */
    
    /**
     * @brief 两面盘_第八名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d8(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd8');
    } /* }}} */
    
    /**
     * @brief 两面盘_第九名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d9(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd9');
    } /* }}} */
    
    /**
     * @brief 两面盘_第十名
     * @access public
     * @param
     * @return
     */
    public function settlement_lm_d10(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd10');
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
    public function settlement_12he_12he(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_12he($bet, $lottery);
    } /* }}} */
    
    /**
     * @brief 1-5名_冠军
     *      第一名 ~ 第十名 车号指定，每一个车号为一投注组合，开奖结果"投注车号"对应所投名次视为中奖，其余情形视为不中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_d15_d1(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd1');
    } /* }}} */
    
    /**
     * @brief 1-5名_亚军
     * @access public
     * @param
     * @return
     */
    public function settlement_d15_d2(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd2');
    } /* }}} */
    
    /**
     * @brief 1-5名_第三名
     * @access public
     * @param
     * @return
     */
    public function settlement_d15_d3(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd3');
    } /* }}} */
    
    /**
     * @brief 1-5名_第四名
     * @access public
     * @param
     * @return
     */
    public function settlement_d15_d4(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd4');
    } /* }}} */
    
    /**
     * @brief 1-5名_第五名
     * @access public
     * @param
     * @return
     */
    public function settlement_d15_d5(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd5');
    } /* }}} */
    
    /**
     * @brief 6-10名_第六名
     * @access public
     * @param
     * @return
     */
    public function settlement_d610_d6(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd6');
    } /* }}} */
    
    /**
     * @brief 6-10名_第七名
     * @access public
     * @param
     * @return
     */
    public function settlement_d610_d7(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd7');
    } /* }}} */
    
    /**
     * @brief 6-10名_第八名
     * @access public
     * @param
     * @return
     */
    public function settlement_d610_d8(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd8');
    } /* }}} */
    
    /**
     * @brief 6-10名_第九名
     * @access public
     * @param
     * @return
     */
    public function settlement_d610_d9(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd9');
    } /* }}} */
    
    /**
     * @brief 6-10名_第十名
     * @access public
     * @param
     * @return
     */
    public function settlement_d610_d10(& $bet = [], & $lottery = []) /* {{{ */
    {
        return $this->settlement_lm_d1($bet, $lottery, 'd10');
    } /* }}} */
}

/* end file */
