<?php
/**
 * @模块   会员中心／账户明细model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Income_record_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }


    // 对应类型表和字段
    private $table_arr = array(
            INCOME_OPT_COMPANY => 'gc_cash_in_company',
            INCOME_OPT_ONLINE => 'gc_cash_in_online',
            INCOME_OPT_CARD => 'gc_card_',
            INCOME_OPT_PEOPLE =>'gc_cash_in_people'
        );
    private $list_select_arr = array(
            INCOME_OPT_COMPANY => 'a.addtime      as addtime,
                    a.uid,
                    a.remark,
                    a.agent_id,
                    a.bank_style as style,
                    a.price      as price,
                    a.status     as status,
                    a.order_num  as order_num,
                    a.discount_price as discount_price,
                    1 type',
            INCOME_OPT_ONLINE => 'a.addtime      as addtime,
                    a.uid,
                    a.remark,
                    a.agent_id,
                    a.pay_id     as style,
                    a.price      as price,
                    a.status     as status,
                    a.order_num  as order_num,
                    a.discount_price as discount_price,
                    2 type',
            /*INCOME_OPT_CARD => 'a.use_time     as addtime,
                    a.price      as price,
                    a.is_used    as status,
                    a.order_num  as order_num,
                    0 discount_price,
                    3 type',*/
            //去除cash_list
            INCOME_OPT_CARD => 'a.addtime,
                    a.uid,
                    ""  remark,
                    a.agent_id,
                    0 as price,
                    1 as status,
                    a.order_num  as order_num,
                    a.amount      as  discount_price,
                    3 type',
            //.人工存入
            INCOME_OPT_PEOPLE => 'a.addtime      as addtime,
                    a.uid,
                    a.remark,
                    2 as status,
                    a.price      as price,
                    ""   order_num,
                    a.discount_price as discount_price,
                    a.auth_multiple,
                    4 type',
    );




    /******************公共方法********************/
    /**
     * 获取综合充值记录数据列表
     */
    public function get_record_all($uid, $page, $basic)
    {
        $rows = $page['rows'];
        $page = ($page['page']-1) * $rows;
        $time1and2 = 'and a.addtime between '.$basic['start'].' and '.$basic['end'];
        $time3 = 'and a.use_time between '.($basic['start']).' and '.($basic['end']);
        $sql = "
        (SELECT a.addtime   as addtime, 
                0 style,
                a.price      as price,
                a.remark,
                a.status     as status,
                ".INCOME_OPT_COMPANY." type,
                a.order_num  as order_num,
                a.discount_price as discount_price
                FROM ".$this->table_arr[INCOME_OPT_COMPANY]." AS a 
                    WHERE uid=".$uid.' '.
                (($basic['start']&&$basic['end'])?$time1and2:' ').
                " ORDER BY id DESC LIMIT {$rows} OFFSET {$page})
        UNION ALL
        (SELECT a.addtime   as addtime, 
                0 style,
                a.price      as price,
                a.remark,
                a.status     as status,
                ".INCOME_OPT_ONLINE." type,
                a.order_num  as order_num,
                a.discount_price as discount_price
                FROM ".$this->table_arr[INCOME_OPT_ONLINE]." AS a 
                    WHERE uid=".$uid.' '.
                (($basic['start']&&$basic['end'])?$time1and2:' ').
                " ORDER BY id DESC LIMIT {$rows} OFFSET {$page})
        UNION ALL
        (SELECT a.addtime   as addtime, 
                0 style,
                a.price      as price,
                a.remark,
                2 as status,
                4 as type,
                ''  as order_num,
                a.discount_price as discount_price
                FROM ".$this->table_arr[INCOME_OPT_PEOPLE]." AS a 
                    WHERE a.type=1 and uid=".$uid.' '.
                (($basic['start']&&$basic['end'])?$time1and2:' ').
                " ORDER BY id DESC LIMIT {$rows} OFFSET {$page})
      ";
      //.下面一个连表是家的人工存入

        //点卡查询改为cash_list
        $sql .= " UNION ALL
            (SELECT a.addtime   as addtime,
            0 style,
            0 as price,
            '' as remark,
            1   as status,
            ".INCOME_OPT_CARD." as type,
            a.order_num,
            a.amount      as  discount_price
            FROM gc_cash_list as a WHERE uid=". $uid." and a.type =15 
            ".
            (($basic['start']&&$basic['end'])?$time1and2:' ')."
            ORDER BY id DESC LIMIT {$rows} OFFSET {$page})
            ORDER BY addtime
        ";

        $data = $this->db->query($sql)->result_array();
        //$this->set_card_table();
        /*$sql = "SELECT a.use_time   as addtime,
                    0 style,
                    a.price      as price,
                    a.is_used    as status,
                    ".INCOME_OPT_CARD." type,
                    a.order_num  as order_num,
                    0 discount_price
                    FROM ".$this->table_arr[INCOME_OPT_CARD]." AS a
                    WHERE uid={$uid} ".
                    (($basic['start']&&$basic['end'])?$time3:' ').
                    " ORDER BY id DESC LIMIT {$rows} OFFSET {$page}";
        $this->select_db('card');
        $cards = $this->db->query($sql)->result_array();

        $data = array_merge($data, $cards);*/
        foreach ($data as $k => $v) {
            //if($v['type'] != INCOME_OPT_CARD)
            if (1) {
                $data[$k]['addtime'] = date('Y-m-d H:i:s',
                        $data[$k]['addtime']);
            }
            $data[$k]['price'] = (float)sprintf("%.3f",
                                $data[$k]['price']);
            $data[$k]['discount_price'] =  (float)sprintf("%.3f",
                                $data[$k]['discount_price']);
            switch ($v['type']) {
                case INCOME_OPT_COMPANY:
                    $name = '银行转账';
                    break;
                case INCOME_OPT_ONLINE:
                    $name = '线上入款';
                    break;
                case INCOME_OPT_CARD:
                    $name = '彩豆充值';
                    break;
                case INCOME_OPT_PEOPLE:
                    $name = '人工存入';
                    break;
            }
            $data[$k]['style'] = $name;
            $data[$k]['remark'] =preg_replace("/\(.*\)/","",$data[$k]['remark']);
        }
        return array('rows'=>$data);
    }

    /**
     * 获取筛选后的充值记录数据列表
     */
    public function get_record_list($type, $basic, $senior, $page)
    {
        if ($type == INCOME_OPT_CARD) {
            //$this->db->db_select('gc_card');
            //$this->set_card_table($basic);
            $this->table_arr[INCOME_OPT_CARD] = 'gc_cash_list';
        }
        $table = $this->table_arr[$type];
        $select = $this->list_select_arr[$type];
        // var_dump($senior);exit;
        $data = $this->get_list($select, $table,$basic , $senior, $page);
        if (empty($data)) {
            return false;
        }
        $data = $data['rows'];
        foreach ($data as $k => $v) {
            //if($type != INCOME_OPT_CARD)
            $data[$k]['addtime'] = date('Y-m-d H:i:s', $data[$k]['addtime']);
            $data[$k]['price'] = (float)sprintf("%.3f", $data[$k]['price']);
            $data[$k]['discount_price'] = (float)sprintf("%.3f", $data[$k]['discount_price']);
            switch ($type) {
                case INCOME_OPT_COMPANY:
                    $name = '银行转账';
                    break;
                case INCOME_OPT_ONLINE:
                    $name = '线上入款';
                    break;
                case INCOME_OPT_CARD:
                    $name = '彩豆充值';
                    break;
                case INCOME_OPT_PEOPLE:
                    $name = '人工存入';
                    break;
            }
            $data[$k]['style'] = $name;
            $data[$k]['remark'] =preg_replace("/\(.*\)/","",$data[$k]['remark']);
        }
        return array('rows'=>$data);
    }
    /********************************************/



    /******************私有方法*******************/
    /**
     * 设置优惠卡对应那张表
     */
    private function set_card_table($basic = array())
    {
        $tables = $this->db->query('SHOW tables')
                             ->result_array();
        foreach ($tables as $key => $value) {
            $tables[$key] = current($value);
        }
        /* 如果最小日期设置了，则定位到当前月的card表 */
        if (!empty($basic['a.use_time <='])) {
            $table = $this->table_arr[INCOME_OPT_CARD].date('ym',
                strtotime($basic['a.use_time <=']));
            if (in_array($table, $tables)) {
                $this->table_arr[INCOME_OPT_CARD] = $table;
                return;
            }
        }
        /* 如果最大日期设置了，则定位到当前月的card表 */
        if (!empty($basic['a.use_time >='])) {
            $table = $this->table_arr[INCOME_OPT_CARD].date('ym',
                strtotime($basic['a.use_time >=']));
            if (in_array($table, $tables)) {
                $this->table_arr[INCOME_OPT_CARD] = $table;
                return;
            }
        }
        /* 默认则是最新的card表 */
        $this->table_arr[INCOME_OPT_CARD] = $this->table_arr[INCOME_OPT_CARD].date('ym');
    }
    /********************************************/
}
