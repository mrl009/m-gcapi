<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Auth_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取稽核日志
     * @param  $start_date		string		开始日期戳
     * @param  $end_date		string		结束日期戳
     * @param  $uid		int		用户ID
     * @return $data 	array
     */
    public function get_log($start_time=0, $end_time=0, $uid=0)
    {
        $where = array();
        if (!empty($start_time) && !empty($end_time)) {
            $where['addtime >=']= $start_time;
            $where['addtime <=']= $end_time;
        }
        if (!empty($uid)) {
            $where['uid']= $uid;
        }
        $page=array(
            'page'  =>$this->G('page'),
            'rows'  =>$this->G('rows'),
        );
        $condition=array('join'=>'user','on'=>'a.uid=b.id');
        $field = 'a.*,b.username';
        $data = $this->get_list($field, 'auth_log', $where, $condition, $page);
        foreach ($data['rows'] as $k => $v) {
            $data['rows'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        return $data;
    }

    /**
     * 获取一个用户的稽核日志
     * @param  $uid		int		用户ID
     * @return $data 	array 	稽核数据
    */
    public function get_auth($uid)
    {
        $where['a.uid']= $uid;
        $page=array(
            'page'  =>1,
            'rows'  =>100,
            'total' =>-1,
        );
        $condition=array(
            'join'=>'user','on'=>'a.uid=b.id',
            'orderby'=>array('id'=>'asc')
        );
        $field = 'a.*';
        $authData = $this->get_list($field, 'auth', $where, $condition, $page);
        $this->redis_select(REDIS_LONG);
        $rkUserAuthDml = 'user:dml';
        $dml = $this->redis_hget($rkUserAuthDml, $uid);
        $dml = sprintf('%.3f', $dml);
        $this->redis_select(REDIS_DB);
        if (empty($dml)) {
            $dml = 0;
        }

        $this->load->model('pay/Pay_set_model', 'ps');
        $psData = $this->ps->get_pay_set($uid, 'ps.*');
        $out_fee = $psData['counter_money'];//出款手续费
        //为空稽核
        if (empty($authData['rows'])) {
            $rkUserCounterNum = 'user_count:counter_num:'.$uid;
            $counterNum = $this->redis_get($rkUserCounterNum);
            if ($counterNum<$psData['counter_num'] && $psData['is_counter']) {
                /*免手出款续费的次数*/
                $out_fee = 0;
            }

            $authData['dml'] = $dml;
            $authData['total_ratio_price'] = 0;
            $authData['out_fee'] = $out_fee;
            $authData['all_fee'] = $out_fee;
            return $authData;
        }
        $totalDml = 0;
        $totalLimitDml = 0;
        foreach ($authData['rows'] as $n => $auth) {
            $uid = $auth['uid'];
            if ($auth['is_pass']==0) {
                $totalDml +=$auth['auth_dml'];
                $totalLimitDml +=$auth['limit_dml'];
            }
            $authData['rows'][$n]['start_date'] = date('Y-m-d H:i:s', $auth['start_time']);
            if (empty($auth['end_time'])) {
                $authData['rows'][$n]['end_date'] = date('Y-m-d H:i:s');
            } else {
                $authData['rows'][$n]['end_date'] = date('Y-m-d H:i:s', $auth['end_time']);
            }
            unset($authData['rows'][$n]['start_time']);
            unset($authData['rows'][$n]['end_time']);
        }


        $is_pass = 0;
        if ($dml>=$totalDml-$totalLimitDml) {
            //超过打码量
            $is_pass = 1;
        }
        /*计算扣除行政比例*/

        $ratio[1]['ratio'] = $psData['line_ct_xz_audit']/100;//公司入款-行政费用比例
        $ratio[2]['ratio'] = $psData['ol_ct_xz_audit']/100;//线上入款-行政费用比例

        if ($is_pass==1 && $psData['is_counter']) {
            /*达到门槛是否收取手续费 1 或者0*/
            $rkUserCounterNum = 'user_count:counter_num:'.$uid;
            $counterNum = $this->redis_get($rkUserCounterNum);
            if ($counterNum<$psData['counter_num']) {
                /*免手出款续费的次数*/
                $out_fee = 0;
            }
        }
        $totalRatioPrice = 0;
        foreach ($authData['rows'] as $n => $auth) {
            if ($auth['is_pass']==0) {
                $authData['rows'][$n]['dml'] = $dml;
                $authData['rows'][$n]['is_pass'] = $is_pass;
                if ($is_pass==0) {
                    $oneRatioPrice = ($auth['total_price']+$auth['discount_price'])*$ratio[$auth['type']]['ratio'];
                    $oneRatioPrice = floor($oneRatioPrice);//行政费 保留整数想下取整
                    $authData['rows'][$n]['ratio_price'] = $oneRatioPrice;
                    $totalRatioPrice += $oneRatioPrice;
                }
            } else {
                $authData['rows'][$n]['ratio_price'] = 0;
            }
        }
        $authData['dml'] = $dml;
        $authData['total_ratio_price'] = $totalRatioPrice;
        $authData['out_fee'] = $out_fee;
        return $authData;
    }
}
