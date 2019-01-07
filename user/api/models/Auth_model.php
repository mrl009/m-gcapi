<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Auth_model extends MY_Model
{

    function __construct()
    {
        parent::__construct();
    }
	
    /**
     * 获取一个用户的稽核日志
     * @param  $uid		int		用户ID
     * @return $data 	array 	稽核数据
    */
    public function get_auth($username)
    {

        $where['b.username']= $username;
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
        $authData = $this->get_list($field,'auth',$where,$condition,$page);
        $this->load->model('pay/Pay_set_model','ps');
        $psData = $this->ps->get_pay_set($this->user['id'],'ps.*');
        $out_fee = $psData['counter_money'];//出款手续费
        $rkUserAuthDml = 'user:dml';
        $this->redis_select(REDIS_LONG);
        $dml = $this->redis_hget($rkUserAuthDml,$this->user['id']);
        $dml = sprintf('%.2f', $dml);
        // 增加用户免费额度
        $w_dml = $this->redis_hget('user:win_dml', $this->user['id']);
        $w_dml = sprintf('%.3f', $w_dml);
        $this->redis_select(REDIS_DB);
        if(empty($dml)){
            $dml = 0;
        }
        if(empty($authData['rows'])){
            $rkUserCounterNum = 'user_count:counter_num:'.$this->user['id'];
            $counterNum = $this->redis_get($rkUserCounterNum);
            if(empty($counterNum)){
                $counterNum = 0;
            }
            if($counterNum<$psData['counter_num'] && $psData['is_counter']){
                /*免手出款续费的次数*/
                $out_fee = 0;
            }
            $authData['dml'] = $dml;
            $authData['is_pass'] = 1;
            $authData['total_ratio_price'] = 0;
            $authData['out_fee'] = $out_fee;
            $authData['all_fee'] = $out_fee;
            $authData['w_dml'] = empty($w_dml) ? 0 : $w_dml;
            return $authData;
        }
        $totalDml = 0;
        $totalLimitDml = 0;
        foreach ($authData['rows'] as $n => $auth) {
            $uid = $auth['uid'];
            if($auth['is_pass']==0){
                $totalDml +=$auth['auth_dml'];
                $totalLimitDml +=$auth['limit_dml'];
            }
            $authData['rows'][$n]['start_date'] = date('Y-m-d H:i:s',$auth['start_time']);
            if(empty($auth['end_time'])){
                $authData['rows'][$n]['end_date'] = date('Y-m-d H:i:s');
            }
            else{
                $authData['rows'][$n]['end_date'] = date('Y-m-d H:i:s',$auth['end_time']);
            }
            unset($authData['rows'][$n]['start_time']);
            unset($authData['rows'][$n]['end_time']);
        }
        $is_pass = 0;

        if( $dml>=$totalDml-$totalLimitDml){
                //超过打码量
                $is_pass = 1;
        }

        /*计算扣除行政比例*/

        $ratio[1]['ratio'] = $psData['line_ct_xz_audit']/100;//公司入款-行政费用比例
        $ratio[2]['ratio'] = $psData['ol_ct_xz_audit']/100;//线上入款-行政费用比例

        if($is_pass==1 && $psData['is_counter']){
            /*达到门槛是否收取手续费 1 或者0*/
            $rkUserCounterNum = 'user_count:counter_num:'.$uid;
            $counterNum = $this->redis_get($rkUserCounterNum);
            if(empty($counterNum)){
                $counterNum = 0;
            }
            if($counterNum<$psData['counter_num']){
                /*免手出款续费的次数*/
                $out_fee = 0;
            }
        }

        $totalRatioPrice = 0;
        $nodml = $dml;
        $auth_dml = $discount_price = $total_price = $limit_dml = $user_dml  =  0;
        $end_time = $start_date = '';
        $new_authData = array();
        foreach ($authData['rows'] as $n => $auth) {
            if($auth['is_pass']==0){
                $authData['rows'][$n]['dml'] = $dml;
                $authData['rows'][$n]['is_pass'] = $is_pass;
                $nodml += $auth['dml'];
                if($is_pass==0){
                    $oneRatioPrice = ($auth['total_price']+$auth['discount_price'])*$ratio[$auth['type']]['ratio'];
                    $authData['rows'][$n]['ratio_price'] = $oneRatioPrice;
                    $oneRatioPrice = floor($oneRatioPrice);//行政费 保留整数想下取整
                    $totalRatioPrice += $oneRatioPrice;
                }
                if($total_price==0) {
                    $start_date = $auth['start_date'];
                }
                $auth_dml += $auth['auth_dml'];
                $limit_dml += $auth['limit_dml'];
            }
            else{
                $authData['rows'][$n]['ratio_price'] = 0;
                //$user_dml = $auth['dml'];
            }
            $total_price += $auth['total_price'];
            $discount_price += $auth['discount_price'];
            $end_time = $auth['end_date'];
        }


        $new_authData['total_price'] = $total_price;//存款
        $new_authData['discount_price'] = $discount_price;//优惠
        $new_authData['auth_dml'] = $auth_dml-$limit_dml;//需达标的稽核打码量
        if ($new_authData['auth_dml'] < 0) {
            $new_authData['auth_dml'] = 0;
        }
        $new_authData['dml'] = $nodml;//用户目前的打码量
        $new_authData['total_ratio_price'] = $totalRatioPrice; //所有扣除的行政费用
        $new_authData['out_fee'] = (float)$out_fee;
        $new_authData['all_fee'] = $out_fee+$totalRatioPrice;
        $new_authData = array_map(function($v){ return sprintf('%01.2f',$v);},$new_authData);
        $new_authData['start_date'] = $start_date;
        $new_authData['end_date'] = $end_time;
        $new_authData['is_pass'] = $is_pass;
        $new_authData['w_dml'] = empty($w_dml) ? 0 : $w_dml;
        return $new_authData;
    }
}
