<?php
/**
 * @file games_settlement_s_k3.php
 * @brief k3 私彩结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/09/06 15:10
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_settlement_s_k3
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
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值：'1','2','3'
     *      如：k3 170906049 当期开奖: 2,6,6
     *          则计算出开奖和值: 14
     * @access public/protected 
     * @param 
     * @return 
     */
    public function wins_balls(& $wins_balls = array()) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3'];
        if (count($wins_balls['base']) != 3 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[s_k3] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        //$wins_balls['he'] = [code1(和), code2(和大小) / null, code3(和单双) / null];
        //$wins_balls['2l'] = [code1, code2 / null, code3 / null];
        //$wins_balls['bz'] = [code1, code2(任意豹子)] / null;
        //$wins_balls['dz'] = code(对子) / null;
    
        /* 和值 */
        $wins_balls['he'][0] = $wins_balls['base'][0] + $wins_balls['base'][1] + $wins_balls['base'][2];
        /* 和 大小单双 */
        $wins_balls['he'][1] = $wins_balls['he'][0] >= 11 ? 100 : 101;
        $wins_balls['he'][2] = ($wins_balls['he'][0] % 2) == 1 ? 102 : 103;
        if ($wins_balls['base'][0] == $wins_balls['base'][1] && $wins_balls['base'][1] == $wins_balls['base'][2]) {
            //$wins_balls['he'][1] = null;
            //$wins_balls['he'][2] = null;
            /* 豹子 */
            $wins_balls['bz'] = [$wins_balls['base'][0], 169];
            /* 对子 */
            $wins_balls['dz'] = $wins_balls['base'][0];
            /* 两连 0个可中 */
            $wins_balls['2l'] = [];
        } else {
            $wins_balls['bz'] = [];
            /* 对子 两连 */
            if ($wins_balls['base'][0] == $wins_balls['base'][1]) {
                $wins_balls['dz'] = $wins_balls['base'][0];
                /* 两连 1个可中 */
                $wins_balls['2l'][] = $wins_balls['base'][0] > $wins_balls['base'][2] ?
                    $wins_balls['base'][2] * 10 + $wins_balls['base'][0] : $wins_balls['base'][0] * 10 + $wins_balls['base'][2];
            } elseif ($wins_balls['base'][0] == $wins_balls['base'][2]) {
                $wins_balls['dz'] = $wins_balls['base'][0];
                /* 两连 1个可中 */
                $wins_balls['2l'][] = $wins_balls['base'][0] > $wins_balls['base'][1] ?
                    $wins_balls['base'][1] * 10 + $wins_balls['base'][0] : $wins_balls['base'][0] * 10 + $wins_balls['base'][1];
            } elseif ($wins_balls['base'][1] == $wins_balls['base'][2]) {
                $wins_balls['dz'] = $wins_balls['base'][1];
                /* 两连 1个可中 */
                $wins_balls['2l'][] = $wins_balls['base'][0] > $wins_balls['base'][1] ?
                    $wins_balls['base'][1] * 10 + $wins_balls['base'][0] : $wins_balls['base'][0] * 10 + $wins_balls['base'][1];
            } else {
                $wins_balls['dz'] = null;
                /* 两连 3个可中 */
                $wins_balls['2l'][] = $wins_balls['base'][1] > $wins_balls['base'][2] ?
                    $wins_balls['base'][2] * 10 + $wins_balls['base'][1] : $wins_balls['base'][1] * 10 + $wins_balls['base'][2];
                $wins_balls['2l'][] = $wins_balls['base'][0] > $wins_balls['base'][2] ?
                    $wins_balls['base'][2] * 10 + $wins_balls['base'][0] : $wins_balls['base'][0] * 10 + $wins_balls['base'][2];
                $wins_balls['2l'][] = $wins_balls['base'][0] > $wins_balls['base'][1] ?
                    $wins_balls['base'][1] * 10 + $wins_balls['base'][0] : $wins_balls['base'][0] * 10 + $wins_balls['base'][1];
            }
        }
    
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 整合_和值
     *      以全部开出的三个号码、加起来的总和来判定。 
     *      大小：三个开奖号码总和值11~17 为大；总和值4~10 为小；若三个号码相同、则不算中奖。
     *      单双：三个开奖号码总和5、7、9、11、13、15、17为单；4、6、8、10、12、14、16为双；若三个号码相同、则不算中奖。
     *      开奖号码总和值为3、4、5、6、7、8、9、10、11、12、13、14、15、16、17 、18时，即为中奖； 
     *      举例：如开奖号码为1、2、3、总和值为6、则投注「6」即为中奖。
     *      demo:
     *          {"zh_hz":["962709052019251044",520,"3",2,1,2,"165",0,0]}
     *      
     *      NOTE: 按大牛要求，无限分支，开豹子，大小单双也要中，2018-06-21.
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_hz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 和值 和大和小 和单和双 */
        if (in_array($v, $lottery['he'])) {
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
     * @brief 整合_两连
     *      任选一长牌组合、当开奖结果任2码与所选组合相同时，即为中奖。 
     *      举例：如开奖号码为1、2、3、则投注两连12、两连23、两连13皆视为中奖。
     *      demo:
     *          {"zh_hz":["962709052019251044",520,"3",2,1,2,"165",0,0]}
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_2l(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 两连 */
        if (in_array($v, $lottery['2l'])) {
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
     * @brief 整合_独胆
     *      三个开奖号码其中一个与所选号码相同时、即为中奖。 
     *      举例：如开奖号码为1、1、3，则投注独胆1或独胆3皆视为中奖。
     *      备注：不论当局指定点数出现几次，仅派彩一次(不翻倍)。
     *      demo:
     *          {"zh_hz":["962709052019251044",520,"3",2,1,2,"165",0,0]}
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_dd(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 独胆 */
        if (in_array($v, $lottery['base'])) {
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
     * @brief 整合_豹子
     *      豹子：开奖号码三字同号、且与所选择的豹子组合相符时，即为中奖；和值大小、单双都不中奖。
     *      任意豹子：任意豹子组合、开奖号码三字同号，即为中奖。
     *      demo:
     *          {"zh_hz":["962709052019251044",520,"3",2,1,2,"165",0,0]}
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 豹子 */
        if (in_array($v, $lottery['bz'])) {
            if ($v == $this->config_balls['bz'][6]) {
                $ret['win_contents'][] = [$v];
            } else {
                $ret['win_contents'][] = [$v, $v, $v];
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
     * @brief 整合_对子
     *      开奖号码任两字同号、且与所选择的对子组合相符时，即为中奖。 
     *      举例：如开奖号码为1、1、3、则投注对子1、1，即为中奖。
     *      demo:
     *          {"zh_hz":["962709052019251044",520,"3",2,1,2,"165",0,0]}
     * @access public
     * @param
     * @return
     */
    public function settlement_zh_dz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 对子 */
        if ($v == $lottery['dz']) {
            $ret['win_contents'][] = [$v, $v];
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
