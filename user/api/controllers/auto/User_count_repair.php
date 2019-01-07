<?php

class User_count_repair extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Home_model', 'core');
    }

    public function repair($sn, $uid)
    {
        if (empty($sn)) {
            exit('参数错误');
        }
        $this->core->init($sn);
        if ($uid) {
            $d_sql = "select uid,sum(amount) as discount from gc_cash_list where type in(3,11,20,22) and uid = $uid";//优惠数据
            $o_sql = "select uid,sum(-amount) as out_t_total,COUNT(1) as out_t_num from gc_cash_list where type in(13,14) and uid = $uid";//出款数据
            $i_sql = "select uid,sum(amount) as in_t_total,COUNT(1) as in_t_num from gc_cash_list where type in(5,6,7,8,12) and uid = $uid";//入款数据
        } else {
            $d_sql = "select uid,sum(amount) as discount from gc_cash_list where type in(3,11,20,22) group by uid";
            $o_sql = "select uid,sum(-amount) as out_t_total,COUNT(1) as out_t_num from gc_cash_list where type in(13,14) group by uid";
            $i_sql = "select uid,sum(amount) as in_t_total,COUNT(1) as in_t_num from gc_cash_list where type in(5,6,7,8,12) group by uid";
        }
        // 优惠数据
        $discount = $this->core->db->query($d_sql)->result_array();
        $discount = array_make_key($discount, 'uid');
        // 出款数据
        $outData = $this->core->db->query($o_sql)->result_array();
        $outData = array_make_key($outData, 'uid');
        // 入款数据
        $inData = $this->core->db->query($i_sql)->result_array();
        $inData = array_make_key($inData, 'uid');
        if ($uid) {
            $uids = [$uid];
        } else {
            $uids = array_unique(array_merge(array_column($discount, 'uid'), array_column($outData, 'uid'), array_column($inData, 'uid')));
            sort($uids);
        }
        $rs = [];
        foreach ($uids as $id) {
            $tmp = [
                'id' => $id,
                'discount' => isset($discount[$id]) ? $discount[$id]['discount'] : '0.000',
                'in_t_num' => isset($inData[$id]) ? $inData[$id]['in_t_num'] : 0,
                'in_t_total' => isset($inData[$id]) ? $inData[$id]['in_t_total'] : '0.000',
                'out_t_num' => isset($outData[$id]) ? $outData[$id]['out_t_num'] : 0,
                'out_t_total' => isset($outData[$id]) ? $outData[$id]['out_t_total'] : '0.000'
            ];
            $rs[] = $tmp;
        }
        wlog(APPPATH . 'logs/' . $this->core->sn . '_user_repair.log', json_encode($rs));
        $flag = $this->core->db->update_batch('user', $rs, 'id');
        if ($flag) {
            wlog(APPPATH . 'logs/' . $this->core->sn . '_user_repair.log', '更新user成功');
            exit('更新user成功');
        } else {
            wlog(APPPATH . 'logs/' . $this->core->sn . '_user_repair.log', '更新user失败');
            exit('更新user失败');
        }
    }

    public function repair_by_cash_report($sn, $uid_from, $uid_to)
    {
        if (empty($sn)) {
            exit('参数错误');
        }
        $this->core->init($sn);
        if ($uid_from && $uid_to) {
            $sql = "SELECT uid as id , SUM( in_company_num + in_online_num + in_people_num) AS in_t_num , 
                    SUM( in_company_total + in_online_total + in_people_total) AS in_t_total , 
                    SUM(out_people_num + out_company_num) AS out_t_num , 
                    SUM( out_people_total + out_company_total) AS out_t_total , 
                    SUM( in_company_discount + in_online_discount + in_people_discount + in_card_total + in_register_discount + activity_total) AS discount 
                    FROM `gc_cash_report` WHERE  uid >= $uid_from AND uid <= $uid_to GROUP BY uid";
        } else {
            $sql = "SELECT uid as id , SUM( in_company_num + in_online_num + in_people_num) AS in_t_num , 
                    SUM( in_company_total + in_online_total + in_people_total) AS in_t_total , 
                    SUM(out_people_num + out_company_num) AS out_t_num , 
                    SUM( out_people_total + out_company_total) AS out_t_total , 
                    SUM( in_company_discount + in_online_discount + in_people_discount + in_card_total + in_register_discount + activity_total) AS discount 
                    FROM `gc_cash_report` GROUP BY uid";
        }
        $rs = $this->core->db->query($sql)->result_array();
        wlog(APPPATH . 'logs/' . $this->core->sn . '_repair_by_cash_report.log', json_encode($rs));
        $flag = $this->core->db->update_batch('user', $rs, 'id');
        $sql = $this->core->db->last_query();
        wlog(APPPATH . 'logs/' . $this->core->sn . '_repair_by_cash_report.log', $sql);
        if ($flag) {
            wlog(APPPATH . 'logs/' . $this->core->sn . '_user_repair.log', '更新user成功');
            exit('更新user成功');
        } else {
            wlog(APPPATH . 'logs/' . $this->core->sn . '_user_repair.log', '更新user失败');
            exit('更新user失败');
        }
    }

    // 现金报表数据修复
    public function repair_cash_report($sn, $uid, $day)
    {
        if (empty($sn)) {
            exit('参数错误');
        }
        $this->core->init($sn);
        if (empty($day)) {
            $days = $this->getDays();
        } else {
            $days = [$day];
        }
        foreach ($days as $day) {
            strtotime($day) > strtotime('2018-12-07') && $day = '2018-12-07';
            $ifExit = $this->core->get_one('*', 'cash_report', ['report_date' => $day]);
            if (!empty($ifExit)) {
                echo $day . '数据已经存在';
                continue;
            }
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 23:59:59');
            if (!empty($uid)) {

            } else {
                // 公司入款
//            $in_company_sql = "SELECT uid , SUM(amount) AS in_company_total , COUNT(1) AS in_company_num
//                              FROM gc_cash_list WHERE type IN(6 , 8) AND addtime >= $start AND addtime <= $end";
                $in_company_sql = "SELECT uid , SUM(price) in_company_total , SUM(discount_price) in_company_discount , COUNT(1) in_company_num , COUNT(discount_price > 0 or null) in_company_discount_num
                                FROM `gc_cash_in_company` 
                                WHERE `addtime` >= $start AND `addtime` <= $end AND `status` = 2 GROUP BY uid";
                $in_company = $this->core->db->query($in_company_sql)->result_array();
                $in_company = array_make_key($in_company, 'uid');
                // 线上入款
//            $in_online_sql = "SELECT uid , SUM(amount) AS in_online_total , COUNT(1) AS in_online_num
//                              FROM gc_cash_list WHERE type IN(5 , 7) AND addtime >= $start AND addtime <= $end";
                $in_online_sql = "SELECT uid , SUM(price) AS in_online_total , COUNT(*) AS in_online_num , SUM(discount_price) AS in_online_discount , COUNT(discount_price > 0 or null) in_online_discount_num
                              FROM `gc_cash_in_online` 
                              WHERE `addtime` >= $start AND `addtime` <= $end AND `status` = 2 GROUP BY uid";
                $in_online = $this->core->db->query($in_online_sql)->result_array();
                $in_online = array_make_key($in_online, 'uid');
                // 人工入款
//            $in_people_sql = "SELECT uid , SUM(amount) AS in_people_total , COUNT(1) AS in_people_num
//                              FROM gc_cash_list WHERE type IN(12) AND addtime >= $start AND addtime <= $end";
                $in_people_sql = "SELECT uid , SUM(price) in_people_total , SUM(discount_price) in_people_discount , COUNT(1) in_people_num , COUNT(discount_price > 0 or null) in_people_discount_num
                              FROM `gc_cash_in_people` 
                              WHERE `addtime` >= $start AND `addtime` <= $end AND price > 0 GROUP BY uid";
                $in_people = $this->core->db->query($in_people_sql)->result_array();
                $in_people = array_make_key($in_people, 'uid');
                // 人工出款
//            $out_people_sql = "SELECT uid , SUM(-amount) AS out_people_total , COUNT(1) AS out_people_num
//                              FROM gc_cash_list WHERE type IN(13) AND addtime >= $start AND addtime <= $end";
                $out_people_sql = "SELECT uid , SUM(price) out_people_total , COUNT(1) out_people_num 
                              FROM `gc_cash_out_people` 
                              WHERE `addtime` >= $start AND `addtime` <= $end GROUP BY uid";
                $out_people = $this->core->db->query($out_people_sql)->result_array();
                $out_people = array_make_key($out_people, 'uid');
                //会员出款
                $out_company_sql = "SELECT uid , SUM(actual_price) out_company_total , COUNT(1) out_company_num 
                              FROM `gc_cash_out_manage` 
                              WHERE `addtime` >= $start AND `addtime` <= $end AND `status` = 2 GROUP BY uid";
                $out_company = $this->core->db->query($out_company_sql)->result_array();
                $out_company = array_make_key($out_company, 'uid');
                // 优惠退水
//            $out_return_sql = "SELECT uid , SUM(amount) AS out_return_water , COUNT(1) AS out_return_num
//                              FROM gc_cash_list WHERE type IN(3) AND addtime >= $start AND addtime <= $end";
                $out_return_sql = "SELECT uid , sum(num_return) AS out_return_num , sum(return_price) AS out_return_water 
                              FROM gc_report 
                              WHERE report_date = $day AND num_return > 0 GROUP BY uid";
                $out_return = $this->core->db->query($out_return_sql)->result_array();
                $out_return = array_make_key($out_return, 'uid');
                // 优惠活动
//            $activity_sql = "SELECT uid , SUM(amount) AS activity_total , COUNT(1) AS activity_num
//                              FROM gc_cash_list
//                              WHERE type = 11 AND addtime >= $start AND addtime <= $end";
//            $activity = $this->core->db->query($activity_sql)->result_array();
//            $activity = array_make_key($activity, 'uid');
                // 出款被扣
                $member_out_sql = "SELECT uid , COUNT(1) in_member_out_num , SUM( IF(STATUS = 2 , hand_fee + admin_fee , 0)) in_member_out_deduction1 ,
                              SUM(IF(STATUS = 3 , price , 0)) in_member_out_deduction2 
                              FROM `gc_cash_out_manage` 
                              WHERE `addtime` >= $start AND `addtime` <= $end AND( `status` = 3 OR( `status` = 2 AND price != actual_price)) GROUP BY uid";
                $member_out = $this->core->db->query($member_out_sql)->result_array();
                $member_out = array_make_key($member_out, 'uid');

                $uids = array_unique(
                    array_merge(
                        array_column($in_company, 'uid'),
                        array_column($in_online, 'uid'),
                        array_column($in_people, 'uid'),
                        array_column($out_people, 'uid'),
                        array_column($out_company, 'uid'),
                        array_column($out_return, 'uid'),
//                    array_column($activity, 'uid'),
                        array_column($member_out, 'uid')
                    )
                );
                $rs = [];
                foreach ($uids as $id) {
                    $tmp = [
                        'uid' => $id,
                        'report_date' => $day,
                        'in_company_total' => isset($in_company[$id]) ? $in_company[$id]['in_company_total'] : '0.000',
                        'in_company_num' => isset($in_company[$id]) ? $in_company[$id]['in_company_num'] : 0,
                        'in_company_discount' => isset($in_company[$id]) ? $in_company[$id]['in_company_discount'] : '0.000',
                        'in_company_discount_num' => isset($in_company[$id]) ? $in_company[$id]['in_company_discount_num'] : 0,
                        'in_online_total' => isset($in_online[$id]) ? $in_online[$id]['in_online_total'] : '0.000',
                        'in_online_num' => isset($in_online[$id]) ? $in_online[$id]['in_online_num'] : 0,
                        'in_online_discount' => isset($in_online[$id]) ? $in_online[$id]['in_online_discount'] : '0.000',
                        'in_online_discount_num' => isset($in_online[$id]) ? $in_online[$id]['in_online_discount_num'] : 0,
                        'in_people_total' => isset($in_people[$id]) ? $in_people[$id]['in_people_total'] : '0.000',
                        'in_people_num' => isset($in_people[$id]) ? $in_people[$id]['in_people_num'] : 0,
                        'in_people_discount' => isset($in_people[$id]) ? $in_people[$id]['in_people_discount'] : '0.000',
                        'in_people_discount_num' => isset($in_people[$id]) ? $in_people[$id]['in_people_discount_num'] : 0,
                        'out_people_total' => isset($out_people[$id]) ? $out_people[$id]['out_people_total'] : '0.000',
                        'out_people_num' => isset($out_people[$id]) ? $out_people[$id]['out_people_num'] : 0,
                        'out_company_total' => isset($out_company[$id]) ? $out_company[$id]['out_company_total'] : '0.000',
                        'out_company_num' => isset($out_company[$id]) ? $out_company[$id]['out_company_num'] : 0,
                        'out_return_water' => isset($out_return[$id]) ? $out_return[$id]['out_return_water'] : '0.000',
                        'out_return_num' => isset($out_return[$id]) ? $out_return[$id]['out_return_num'] : 0,
                        //'activity_total' => isset($activity[$id]) ? $activity[$id]['activity_total'] : '0.000',
                        //'activity_num' => isset($activity[$id]) ? $activity[$id]['activity_num'] : 0,
                        'in_member_out_deduction' => isset($member_out[$id]) ? $member_out[$id]['in_member_out_deduction1'] + $member_out[$id]['in_member_out_deduction2'] : '0.000',
                        'in_member_out_num' => isset($member_out[$id]) ? $member_out[$id]['in_member_out_num'] : 0,
                    ];
                    $rs[] = $tmp;
                }
                wlog(APPPATH . 'logs/' . $this->core->sn . '_repair_cash_report.log', json_encode($rs));
                //$flag = $this->core->db->insert_batch('user', $rs, 'uid');
                $flag = $this->core->db->insert_batch('cash_report', $rs);
                if ($flag) {
                    wlog(APPPATH . 'logs/' . $this->core->sn . '_repair_cash_report.log', '更新user成功');
                    exit('更新 ' . $day . ' cash_report成功');
                } else {
                    wlog(APPPATH . 'logs/' . $this->core->sn . '_repair_cash_report.log', '更新user失败');
                    exit('更新 ' . $day . ' cash_report失败');
                }
            }
        }
    }

    private function getDays()
    {
        return [
            '2018-11-01',
            '2018-11-02',
            '2018-11-03',
            '2018-11-04',
            '2018-11-05',
            '2018-11-06',
            '2018-11-07',
            '2018-11-08',
            '2018-11-09',
            '2018-11-10',
            '2018-11-11',
            '2018-11-12',
            '2018-11-13',
            '2018-11-14',
            '2018-11-15',
            '2018-11-16',
            '2018-11-17',
            '2018-11-18',
            '2018-11-19',
            '2018-11-20',
            '2018-11-21',
            '2018-11-22',
            '2018-11-23',
            '2018-11-24',
            '2018-11-25',
            '2018-11-26',
            '2018-11-27',
            '2018-11-28',
            '2018-11-29',
            '2018-11-30',
            '2018-12-01',
            '2018-12-02',
            '2018-12-03',
            '2018-12-04',
            '2018-12-05',
            '2018-12-06',
        ];
    }
}