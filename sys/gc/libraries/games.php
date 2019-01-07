<?php
/**
 * @file games.php
 * @brief
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/03/18 19:24
 *
 * $Id$
 */
define('LIBRARIES_GAMES', true);

/**
 * @brief 根据$contents 内容，分析出各位所选的球号
 * @param   string $contents 所选球号内容："1,2,3|4|5,6||8,9"
 * @param   array $chk 检测(false 则不检测)允许的球号：array(1, 2, 3, 4, 5, 6)
 * @return  array   各位上所选球号，如：
 *          array(array(1, 2, 3), array(4), array(5, 6), array(), array(8, 9);
 *          false   下注球号无效: 球号非法/球号重复等
 */
function get_balls($contents = '', $chk = false) /* {{{ */
{
    $balls = explode('|', $contents);
    $balls_count = count($balls);
    for ($i = 0; $i < $balls_count; $i++) {
        if ($balls[$i] == '') {
            $ball = array();
        } else {
            $ball = explode(',', $balls[$i]);
        }
        $balls[$i] = $ball;
        if ($chk == false) {
            continue;
        }
        /* 同一位置不可以有重复球出现 */
        $ball_count = count($ball);
        if ($ball_count != count(array_unique($ball, SORT_NUMERIC))) {
            return false;
        }
        for ($j = 0; $j < $ball_count; $j++) {
            //if (!empty($ball[$j]) && !in_array($ball[$j], $chk)) {}
            if (!is_numeric($ball[$j])) {
                return false;
            }
            if ($ball[$j] != '' && !in_array($ball[$j], $chk)) {
                return false;
            }
        }
    }
    return $balls;
} /* }}} */

/**
 * @brief 排列arrangement
 *      从n个不同元素中，任取m(m≤n,m与n均为自然数)个元素按照一定的顺序排成一列，叫做从n个不同元素中取出m个元素的一个排列；
 *      A(n, m) = n(n-1)(n-2)...(n-m+1) = n!/(n-m)!
 *      规定0! = 1, m <= n
 * @param
 * @return
 */
function arrangement($a, $m) /* {{{ */
{
    $r = array();
    $n = count($a);
    if ($m <= 0 || $m > $n) {
        return $r;
    }
    for ($i = 0; $i < $n; $i++) {
        $b = $a;
        // 从数组中移除选定的元素，并用新元素取代它。该函数也将返回包含被移除元素的数组
        $t = array_splice($b, $i, 1);
        if ($m == 1) {
            $r[] = $t;
        } else {
            $c = arrangement($b, $m - 1);
            foreach ($c as $v) {
                $r[] = array_merge($t, $v);
            }
        }
    }

    return $r;
} /* }}} */

/**
 * @brief 组合
 *      从n个不同元素中，任取m(m≤n）个元素并成一组，叫做从n个不同元素中取出m个元素的一个组合；
 *      C(n, m) = A(n, m)/m! = n!/(m!(n-m)!)
 *      C(n, m) = C(n, n-m);
 * @param array $a n个不同元素集合 n = count($a)
 * @param int $m 需要选取的个数 m <= n
 * @return
 */
function combination($a = array(), $m = 0) /* {{{ */
{
    $r = array();

    $n = count($a);
    if ($m <= 0 || $m > $n) {
        return $r;
    }

    for ($i = 0; $i < $n; $i++) {
        $t = array($a[$i]);
        if ($m == 1) {
            $r[] = $t;
        } else {
            // array_slice() 函数在数组中根据条件取出一段值，并返回。
            $b = array_slice($a, $i + 1);
            $c = combination($b, $m - 1);
            foreach ($c as $v) {
                $r[] = array_merge($t, $v);
            }
        }
    }

    return $r;
} /* }}} */

/**
 * @brief 阶乘
 * @param
 * @return
 */
function factorial($n) /* {{{ */
{
    if ($n < 1) {
        $n = 1;
    }
    // array_product 计算并返回数组的乘积
    // range 创建一个包含指定范围的元素的数组
    return array_product(range(1, $n));
} /* }}} */

/**
 * @brief 计算排列数
 * @param
 * @return
 */
function A($n, $m) /* {{{ */
{
    if ($n < $m || $n < 1 || $m < 1) {
        return 0;
    }
    return factorial($n) / factorial($n - $m);
} /* }}} */

/**
 * @brief 计算组合数
 * @param
 * @return
 */
function C($n, $m) /* {{{ */
{
    return A($n, $m) / factorial($m);
} /* }}} */

/**
 * @brief 组合
 *      从n个不同数组中，任取m个元素并成一组，叫做从n个不同数组中取出m个元素的一个组合；
 * @param array $arr 要处理的多维数组 [[2,3,4],[4,5,6],[1,3,9]]
 * @param int $m 要生成多少位的数组 3
 * @param bool $is_same 数组元素是否可以相同,默认不相同
 * @param array $sum 是否需要计算和值
 * @return mixed     返回的数组  ['total' => 36, 'data' => [[2,4,1],[2,4,3],[2,4,9],[2,5,1],[2,5,3],[2,5,9]....[4,6,9]]]
 */
function CC($arr, $m = 0, $is_same = false, $sum = array()) /* {{{ */
{
    // 初始化返回函数
    static $rs = array();

    if (count($arr) >= 2) {
        $tmpArr = array();
        $arr1 = array_shift($arr);
        $arr2 = array_shift($arr);
        foreach ($arr1 as $k1 => $v1) {
            foreach ($arr2 as $k2 => $v2) {
                if (!$is_same && ($v1 == $v2 || (is_array($v1) && in_array($v2, $v1)))) {
                    // 满足这些条件则表示不能有相同元素，过滤掉数据
                    continue;
                }
                if (is_array($v1)) {
                    if(count($v1)>count($arr1[0])){
                        array_pop($v1);
                    }
                    array_push($v1, $v2);
                    if (!empty($sum) && count($v1) == $m) {
                        // 过滤掉和值不想等的
                        if (!in_array(array_sum($v1), $sum)) continue;
                    }
                    $tmpArr[] = $v1;
                } else {
                    if (!empty($sum) && $m == 2) {
                        if (!in_array(($v1 + $v2), $sum)) continue;
                    }
                    $tmpArr[] = [$v1, $v2];
                }
            }
        }
        array_unshift($arr, $tmpArr);
        $rs = $arr[0];
        CC($arr, $m, $is_same, $sum);
    }
    return [
        'total' => count($rs),
        'data' => $rs
    ];
} /* }}} */

/**
 * @brief 通用组选计算方法
 *     组选x玩法公式 = C(M,N)x不相同的球数 + C(M-1,N)相同的球数
 * @param array $arr 要处理的二维数组 [[2,3,4],[4,5,6]]
 * @param int $n N选球数量
 * @param bool $setMain 设置主数组,false默认从第一个数组中做循环，true，从第二个数组中做循环
 * @return int 下注数量
 */
function CountGroupSelect($arr, $n, $setMain = false) /* {{{ */
{
    if (empty($arr) || (!$n)) {
        return 0;
    }

    $countDif = 0;
    $countSame = 0;
    $result = 0;

    if (count($arr) > 1) {
        if ($setMain) {

            foreach ($arr[1] as $value) {
                $flag = in_array($value, $arr[0]);
                if ($flag) {
                    $countSame++;
                } else {
                    $countDif++;
                }
            }

            $result = C(count($arr[0]), $n) * $countDif + C(count($arr[0]) - 1, $n) * $countSame;

        } else {

            foreach ($arr[0] as $value) {
                $flag = in_array($value, $arr[1]);
                if ($flag) {
                    $countSame++;
                } else {
                    $countDif++;
                }
            }

            $result = C(count($arr[1]), $n) * $countDif + C(count($arr[1]) - 1, $n) * $countSame;

        }
    } else {
        $result = C(count($arr[0]), $n);
    }

    return $result;
} /* }}} */

/**
 * @brief 剔除数组元素为空的元素，并返回新的非空元素数组
 *
 * @param array $arr 要处理的数组 [[],[1，2]，[1，2，3]，[]，[1，2，3，4,5]]
 * @return array 处理后的非空元素数组
 */

function filterEmptyCell($balls) /* {{{ */
{
    foreach ($balls as $key => $value) {
        if (empty($value)) {
            unset($balls[$key]);
        }
    }
    return $balls;
} /* }}} */

/**
 * @brief 任选x 通用计算函数
 *     例子：任选二 每行的选中球的数量分别为：1，2，3，4，5
 *     结果 = 1x2 + 1x3 + 1x4 + 1x5 + 2x3 + 2x4 + 2x5 + 3x4 + 3x5 + 4x5
 *          任选三 每行的选中球的数量分别为：1，2，3，4，5
 *     结果 = 1x2x3 + 1x2x4 + 1x2x5 + 1x3x4 + 1x3x5 + 1x4x5 + 2x3x4 + 2x3x5+ 2x4x5 + 3x4x5
 *  *       任选四 每行的选中球的数量分别为：1，2，3，4，5
 *     结果 = 1x2x3x4 + 1x2x4x5 + 1x3x4x5 + 2x3x4x5
 *
 * 先将key值作为排列组合，得到位数排列组合玩法数组，便利每个数组元素，找到对应下标的balls数组元素，计算个数并相乘
 *
 * @param array $arr 要处理的数组 [[1],[1，2]，[1，2，3]，[1，2，3，4]，[1，2，3，4,5]]
 * @param int $num 任选的数量 任选2 num=2；任选3 num=3；任选4 num=4
 * @return int 下注数量
 */

function RxBetCount($balls, $num) /* {{{ */
{
    $keys = [];
    $c = 0;

    //过滤到球数组中的空元素并获得新的非空数组
    $arr = filterEmptyCell($balls);

    //计算位数的排列组合玩法并获得位数的玩法数组
    foreach ($arr as $key => $value) {
        $keys[] = $key;
    }
    $temp = combination($keys, $num);

    //计算每个位的球数并相乘，获得下注数
    foreach ($temp as $firstLevelValue) {
        $i = 1;
        foreach ($firstLevelValue as $value) {
            $i *= count($arr[$value]);
        }
        $c += $i;
    }

    return $c;
} /* }}} */

/**
 * @brief 返回一个一维数组中的最大值
 * @param array $arr 要处理的数组 [1，2，3，4,5]
 * @return int 最大值
 */

function arrayMaxValue($lottery) /* {{{ */
{

    if(is_array($lottery)){
        $pos=array_search(max($lottery),$lottery);
        return $lottery[$pos];
    }else{
      return $lottery;
    }
} /* }}} */


/**
 * @brief 返回一个一维数组中的最小值
 * @param array $arr 要处理的数组 [1，2，3，4,5]
 * @return int 最大值
 */

function arrayMinValue($lottery) /* {{{ */
{
    if(is_array($lottery)){
        $pos=array_search(min($lottery),$lottery);
        return $lottery[$pos];
    }else{
        return $lottery;
    }
} /* }}} */

/**
 * 斗牛算法
 *  $lottery = [1,2,3,4,5]
 * @return bull(1-10) or 0
 */
function bull($lottery = []) /* {{{ */
{
    $c = count($lottery);
    if ($c != 5) {
        return -1;
    }

    $sum = 0;
    $dict = [];
    for ($i = 0; $i < $c; $i++) {
        $v = $lottery[$i];
        $sum += $v;
        $dict[$v] = !isset($dict[$v]) ? 1 : $dict[$v] + 1;
    }
    $point = $sum % 10;

    $is_bull = false;
    foreach ($dict as $k => $v) {
        $o = (10 + $point - $k) % 10;
        if (!empty($dict[$o])) {
            if (($o == $k && $dict[$o] >= 2) || ($o != $k && $dict[$o] >= 1)) {
                $is_bull = true;
            }
        }
    }

    $point = ($point == 0) ? 10 : $point;
    return $is_bull ? $point : 0;
} /* }}} */

/**
 * 梭哈算法
 *  五条、四条、葫芦、三条、顺子、两对、一对、五杂
 *  $lottery = [1,2,3,4,5]
 * @return [0]189 [1]190 [2]191 [3]170 [4]192 [5]193 [6]194 [7]195
 */
function suoha($lottery = []) /* {{{ */
{
    $c = count($lottery);
    if ($c != 5) {
        return 7;
    }

    $pk = $lottery;
    /* 五条 */
    if ($pk[0] == $pk[1] && $pk[1] == $pk[2] && $pk[2] == $pk[3] && $pk[3] == $pk[4]) {
        return 0;
    }
    sort($pk);
    /* 四条 */
    if (($pk[0] == $pk[1] && $pk[1] == $pk[2] && $pk[2] == $pk[3]) ||
            ($pk[1] == $pk[2] && $pk[2] == $pk[3] && $pk[3] == $pk[4])) {
        return 1;
    }
    /* 葫芦 */
    if (($pk[0] == $pk[1] && $pk[1] == $pk[2] && $pk[3] == $pk[4]) ||
            ($pk[0] == $pk[1] && $pk[2] == $pk[3] && $pk[3] == $pk[4])) {
        return 2;
    }
    /* 顺子 01239 01289 01789 06789 01234 12345... */
    if (($pk[4] - $pk[3] == 1 && $pk[3] - $pk[2] == 1 && $pk[2] - $pk[1] == 1 && $pk[1] - $pk[0] == 1) ||
            ($pk[0] == 0 && $pk[4] == 9 && 
                (($pk[1] == 1 && $pk[2] == 2 && in_array($pk[3], [3,8])) ||
                 ($pk[2] == 7 && $pk[3] == 8 && in_array($pk[1], [1,6]))))) {
        return 3;
    }
    /* 三条 */
    if (($pk[0] == $pk[1] && $pk[1] == $pk[2]) ||
            ($pk[1] == $pk[2] && $pk[2] == $pk[3]) ||
            ($pk[2] == $pk[3] && $pk[3] == $pk[4])) {
        return 4;
    }
    /* 两对 11223 11233 12233 */
    if (($pk[0] == $pk[1] && $pk[2] == $pk[3]) ||
            ($pk[0] == $pk[1] && $pk[3] == $pk[4]) ||
            ($pk[1] == $pk[2] && $pk[3] == $pk[4])) {
        return 5;
    }
    /* 一对 11234 12234 12334 12344 */
    if ($pk[0] == $pk[1] || $pk[1] == $pk[2] || $pk[2] == $pk[3] || $pk[3] == $pk[4]) {
        return 6;
    }
    /* 五杂 */
    return 7;
} /* }}} */

/**
 * 3球算法
 *  豹子、顺子、对子、半顺、杂六
 *  $lottery = [1,2,3]
 * @return 111 112 123 124 135
 * @return [0]169 [1]170 [2]171 [3]172 [4]173
 */
function ball3($lottery = []) /* {{{ */
{
    $c = count($lottery);
    if ($c != 3) {
        return 4;
    }

    $pk = $lottery;
    /* 豹子 */
    if ($pk[0] == $pk[1] && $pk[1] == $pk[2]) {
        return 0;
    }
    sort($pk);
    /* 顺子 019 089 012 123 234... */
    if (($pk[2] - $pk[1] == 1 && $pk[1] - $pk[0] == 1) ||
            ($pk[0] == 0 && $pk[2] == 9 && in_array($pk[1], [1,8]))) {
        return 1;
    }
    /* 对子 112 122 */
    if ($pk[0] == $pk[1] || $pk[1] == $pk[2]) {
        return 2;
    }
    /* 半顺 029 013 023 */
    if ($pk[2] - $pk[1] == 1 || $pk[1] - $pk[0] == 1 || ($pk[0] == 0 && $pk[2] == 9)) {
        return 3;
    }
    /* 杂六 */
    return 4;
} /* }}} */

/* end file */
