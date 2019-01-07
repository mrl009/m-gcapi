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
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @brief 排列permutation
 *      从n个不同元素中，任取m(m≤n,m与n均为自然数)个元素按照一定的顺序排成一列，叫做从n个不同元素中取出m个元素的一个排列；
 *      A(n, m) = n(n-1)(n-2)...(n-m+1) = n!/(n-m)!
 *      规定0! = 1, m <= n
 * @param 
 * @return 
 */
function arrangement($a, $m) /* {{{ */
{
	return ;
} /* }}} */

/**
 * @brief 组合
 *      从n个不同元素中，任取m(m≤n）个元素并成一组，叫做从n个不同元素中取出m个元素的一个组合；
 *      C(n, m) = A(n, m)/m! = n!/(m!(n-m)!)
 *      C(n, m) = C(n, n-m);
 * @param array $a  n个不同元素集合 n = count($a)
 * @param int   $m  需要选取的个数 m <= n
 * @return 
 */
function combination($a, $m) /* {{{ */
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
            $b = array_slice($a, $i + 1);
            $c = combination($b, $m - 1);
            foreach ($c as $v) {
                $r[] = array_merge($t, $v);
            }
        }
    }

    return $r;
} /* }}} */

/* end file */
