<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Comm_model extends GC_Model {
	/**
	 * 获取一个用户的信息
	 * @auther frank
	 * @return array
	 **/
	public function get_one_user($username='',$field='*')
	{
		if(is_numeric($username)){
			$where['id'] = $username;
		}
		else{
			$where['username'] = $username;
		}
        return $this->db->select($field)->limit(1)->get_where('user', $where)->row_array();
		//return $this->get_one($field,'user',$where);
	}

    /**
     * 存款时，检测稽核
     * @param  $uid     int     用户ID
     * @return $bool
     */
    public function check_and_set_auth($uid)
    {
        $where['uid'] = $uid;
        $where['is_pass'] = 0;
        $field = 'sum(auth_dml) as auth_dml,sum(limit_dml) as limit_dml';
        $authData = $this->get_one($field, 'auth', $where);
        $is_pass = true;
        $this->redis_select(REDIS_LONG);
        $rkUserAuthDml = 'user:dml';
        if (empty($authData['auth_dml'])) {
            $this->redis_hset($rkUserAuthDml, $uid, 0);
            return $is_pass;
        }
        $dml = $this->redis_hget($rkUserAuthDml, $uid);
        $dml = sprintf('%.3f', $dml);
        if ($dml >= $authData['auth_dml'] - $authData['limit_dml']) {
            $updateData['dml'] = $dml;
            $updateData['is_pass'] = 1;
            $updateData['end_time'] = $_SERVER['REQUEST_TIME'];
            if ($this->write('auth', $updateData, $where)) {
                $this->redis_hset($rkUserAuthDml, $uid, 0);
            } else {
                $is_pass = false;
            }
        }
        $this->redis_select(REDIS_DB);
        return $is_pass;
    }

    /**
     * 会员增加额度，并写入日志
     * @param  $uid         int       用户ID
     * @param  $balance     float     会员当前额度（累加之前）
     * @param  $amount      float     会员增加或者减少额度
     * @param  $order_num   string    现金流订单号
     * @param  $type        int       现金流水类型
     * @param  $remark      string    备注
     * @param  $Prcie       float     实际金额 不含手续费和优惠费用
     * @return $bool
     */
    public function update_banlace_and_cash_list($uid=0,$amount=0,$order_num='',$type=6,$remark='',$price=0)
    {
        //计算用户最大最小出入款的cashtype'
        $cashType   = array(5,6,7,8,9,12,13);
        //$this->load->model('Comm_model','cm');
        $realAmount = $amount;
        $userData = $this->db->select('*')->limit(1)->get_where('user', ['id'=>$uid])->row_array();
        //$userData = $this->get_one_user($uid,'balance,max_income_price,max_out_price');
        $balance = $userData['balance'];
        $whereUser['id'] = $uid;
        if($amount>0){
            if ($price > $userData['max_income_price'] && in_array($type,[5,6,7,8,9,12])) {
                $this->db->set('max_income_price',$price);
            }
            $this->db->set('balance','balance+'.$amount,FALSE);
            $balance = $balance+$amount;
            $whereUser['balance>='] = 0;
        }
        else{
            if (abs($price) > $userData['max_out_price'] && in_array($type,$cashType)) {
                $this->db->set('max_out_price',abs($price));
            }
            $amount = $amount*-1;
            $this->db->set('balance','balance-'.$amount,FALSE);
            $whereUser['balance>='] = $amount;
            $balance = $balance-$amount;
        }
        $this->db->where($whereUser)->update('user');// 3、加钱
        if(!$this->db->affected_rows()){
            return false;
        }
        $cashData['uid'] = $uid;
        $cashData['order_num'] = $order_num;
        $cashData['type'] = $type;
        $cashData['before_balance'] = $userData['balance'];
        $cashData['amount'] = $realAmount;
        $cashData['balance'] = $balance;
        $cashData['remark'] = $remark;
        $cashData['agent_id'] = $userData['agent_id'];
        $cashData['addtime'] = $_SERVER['REQUEST_TIME'];

        $b5 = $this->write('cash_list',$cashData);// 4、加入现金记录
        @wlog(APPPATH.'logs/'.$this->sn.'_cash_list_'.date('Ym').'.log', "会员查询数据".json_encode($userData)."插入数据:".json_encode($cashData));

        if(!$b5){
            return false;
        }
        return true;
    }


    /**
     * 收集，汇总cash_report表数据
     * @param $uid  int 用户ID
     * @param $report_date  string 日期
     * @param $data  array 数据
     * @return bool
     */
    public function collect_cash_report($uid,$report_date,$data)
    {
        $where['uid'] = $uid;
        $where['report_date'] = $report_date;
        $if_one = $this->get_one('id','cash_report',$where);
        if(!$if_one){
            /*没有记录，则为插入*/
            $data['uid'] = $uid;
            $data['report_date'] = $report_date;
            $b = $this->write('cash_report',$data);

            if($b){
                return true;
            }
            else{
                return false;
            }
        }
        /*有记录，则为更新*/

        /*公司入款*/
        if(isset($data['in_company_total']) && isset($data['in_company_discount'])){
            $this->db->set('in_company_total','in_company_total+'.$data['in_company_total'],FALSE);
            $this->db->set('in_company_discount','in_company_discount+'.$data['in_company_discount'],FALSE);
            if ($data['in_company_discount'] > 0) {
                $this->db->set('in_company_discount_num','in_company_discount_num+1',FALSE);
            }
            $this->db->set('in_company_num','in_company_num+1',FALSE);
        }
        /*在线入款*/
        if(isset($data['in_online_total']) && isset($data['in_online_discount'])){
            $this->db->set('in_online_total','in_online_total+'.$data['in_online_total'],FALSE);
            $this->db->set('in_online_discount','in_online_discount+'.$data['in_online_discount'],FALSE);
            if ($data['in_online_discount'] > 0) {
                $this->db->set('in_online_discount_num','in_online_discount_num+1',FALSE);
            }
            $this->db->set('in_online_num','in_online_num+1',FALSE);
        }
        /*人工入款*/
        if(isset($data['in_people_total']) && isset($data['in_people_discount'])){
            $this->db->set('in_people_total','in_people_total+'.$data['in_people_total'],FALSE);
            $this->db->set('in_people_discount','in_people_discount+'.$data['in_people_discount'],FALSE);
            if ($data['in_people_discount'] > 0) {
                $this->db->set('in_people_discount_num','in_people_discount_num+1',FALSE);
            }
            $this->db->set('in_people_num','in_people_num+1',FALSE);
        }
        /*优惠卡充值*/
        if(isset($data['in_card_total'])){
            $this->db->set('in_card_total','in_card_total+'.$data['in_card_total'],FALSE);
            $this->db->set('in_card_num','in_card_num+1',FALSE);
        }
        /*会员出款被扣金额*/
        if(isset($data['in_member_out_deduction'])){
            $this->db->set('in_member_out_deduction','in_member_out_deduction+'.$data['in_member_out_deduction'],FALSE);
            $this->db->set('in_member_out_num','in_member_out_num+1',FALSE);
        }
        /*人工出款*/
        if(isset($data['out_people_total'])){
            $this->db->set('out_people_total','out_people_total+'.$data['out_people_total'],FALSE);
            $this->db->set('out_people_num','out_people_num+1',FALSE);
        }
        /*线上出款*/
        if(isset($data['out_company_total'])){
            $this->db->set('out_company_total','out_company_total+'.$data['out_company_total'],FALSE);
            $this->db->set('out_company_num','out_company_num+1',FALSE);
        }
        /*给予返水*/
        if(isset($data['out_return_water'])){
            $this->db->set('out_return_water','out_return_water+'.$data['out_return_water'],FALSE);
            $this->db->set('out_return_num','out_return_num+1',FALSE);
        }
        if(isset($data['collect_cash_report'])){
            $this->db->set('collect_cash_report','collect_cash_report+'.$data['out_return_water'],FALSE);
        }
        if(isset($data['in_register_discount'])){
            $this->db->set('in_register_discount','in_register_discount+'.$data['in_register_discount'],FALSE);
        }
        /*活动优惠*/
        if(isset($data['activity_total'])){
            $this->db->set('activity_total','activity_total+'.$data['activity_total'],FALSE);
            $this->db->set('activity_num','activity_num+1',FALSE);
        }
        //8.30 报表首存添加
        if (!empty($data['is_one_pay']) && $data['is_one_pay'] == 1) {
            $this->db->set('is_one_pay',1);
        }
        $b = $this->db->update('cash_report',[],$where);
        if($b){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * 自增长层级累计额度
     * @param $price float 金额
     * @param  $level_id  int  层级id
     * @return  bool
     */
    public function incre_level_use($price,$lvel_id)
    {
        $sql = "UPDATE gc_level SET use_times=use_times+1,use_total=use_total+{$price} WHERE id ={$lvel_id}";
        return $this->comm->db->query($sql);
    }

    /**
     * 写会员稽核
     * @param  $uid     int     用户ID
     * @param  $inData  array   入款总金额,入款优惠
     * @param  $type    int     1 为公司入款 2为线上入款
     * @param  $payData  array  用户的支付设定
     * @return array
     */
    public function set_user_auth($uid,$inData,$type,$payData=[])
    {
        if(empty($pay_set)){
            $this->load->model('pay/Pay_set_model','ps');
            $payData = $this->ps->get_pay_set($uid,'ps.pay_set_content');//获取一个会员的支付设定信息
        }
        if(empty($payData)){
            $res['status'] = false;
            $res['content'] = '更取不到支付设置，请联系管理员';
            return $res;
        }
        $auth_dml = "0";
        $limit    = "0";//放宽额度
        if($type == 1){
            if($payData['line_is_ct_audit']){
                $auth_dml = $inData['total_price'] * $payData['line_ct_audit']/100;
                $limit = $payData['line_ct_fk_audit'];
            }
        }else{
            if($payData['ol_is_ct_audit']){
                $auth_dml = $inData['total_price'] * $payData['ol_ct_audit']/100;
                $limit = $payData['ol_ct_fk_audit'];
            }
        }
        $is_pass = "0";
        if ($auth_dml == 0) {
            $is_pass =1 ;
        }
        $authData['uid']            = $uid;
        $authData['total_price']    = $inData['price'];
        $authData['discount_price'] = $inData['discount_price'];
        $authData['auth_dml']       = $auth_dml;
        $authData['start_time']     = $_SERVER['REQUEST_TIME'];
        $authData['is_pass']        = $is_pass;
        $authData['limit_dml']      = $limit;
        $authData['type']           = $type;
        $b4 = $this->write('auth',$authData);// 6、增加稽核
        wlog(APPPATH.'logs/auth_'.$this->sn.'_'.date('Ym').'.log',"会员id".$uid." {$inData['price']}元,优惠:{$inData['discount_price']}");
        if(!$b4){
            $res['status'] = false;
            $res['content'] = '写入稽核日志失败';
            return $res;
        }
        $res['status'] = true;
        $res['content'] = '成功';
        return $res;
    }

    public function out_user_time($id,$time)
    {
        $keys =  "temp:out_time:user_$id";
        $this->redis_set($keys,$time);
    }

    public function out_user_w_dml($id, $is_w_dml)
    {
        $keys = "temp:out_user_w_dml:user_$id";
        $this->redis_set($keys, $is_w_dml);
    }

    /**
     * 入款自动过期
     * 将状态=1并且addtime<30分钟之后 更新为 status=3
     * @param $type   int 1 为更新公司入款 2 更新线上入款
     * @param $uid   int  会员id
     * @param $is_agent boolean 是否代理
     */
    public function update_online_status($type,$uid=null,$is_agent = false)
    {
        $tb   = array('','gc_cash_in_company','gc_cash_in_online');
        if ($type == 1) {
            $gcSet = $this->get_gcset();
            $time = $_SERVER['REQUEST_TIME'] - $gcSet['incompany_timeout']*60;
        } else {
            $time = $_SERVER['REQUEST_TIME'] - CASH_AUTO_EXPIRATION*60;
        }
        $str  = "";
        if ($uid) {
            $str = $is_agent?" and agent_id = $uid":" and uid = $uid";
        }
        if ($type ==1) {
            //公司入款addtime 有会员填入时间 用update_time
            $sql = 'UPDATE '.$tb[$type].' set status=3 WHERE update_time < '.$time.' AND status=1'.$str;

        }else{
            $sql = 'UPDATE '.$tb[$type].' set status=3 WHERE addtime < '.$time.' AND status=1'.$str;
        }
        $this->db->query($sql);
    }

    /**
     * 缓存到公库所有的游戏
     */
    public function cache_all_games()
    {
        $this->select_db('public');
        $where['status <>'] = 2;
        $field = 'a.id,a.name,a.sname,a.type as cptype,a.img,a.hot,a.sort,a.show,a.wh_content,a.status,a.tmp,a.ctg,b.every_time';
        $this->db->from('games as a');
        $this->db->select($field);
        $this->db->join('open_time as b','a.id=b.gid','left');
        $this->db->order_by('a.sort', 'desc');
        $this->db->where($where);
        $redis_list = $this->db->get()->result_array();
        foreach ($redis_list as $jian => $zhi) {
            $this->redisP_hset('games', $zhi['id'], json_encode($zhi, JSON_UNESCAPED_UNICODE));
            $this->redisP_zadd('type:'.$zhi['cptype'], $zhi['sort'], $zhi['id']);//按照彩票类型分类
            $this->redisP_zadd('all', $zhi['sort'], $zhi['id']);//所有游戏
            $this->redisP_zadd('ctg:'.$zhi['ctg'], $zhi['sort'], $zhi['id']);//按照彩票ctg类型分类
            if(in_array($zhi['id'],[3,4,24,25])){
                $this->redisP_zadd('ctg:gc', $zhi['sort'], $zhi['id']);//3,4,24,25 官私同彩
            }
        }
        $this->select_db('privite');
    }
}
