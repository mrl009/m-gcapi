<?php
/**
 * @模块   人工存取款
 * @版本   Version 1.0.0
 * @日期   2017-04-04
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Cash_people extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/Cash_common_model', 'ccm');
        $this->load->model('pay/Pay_set_model', 'ps');
        $this->load->model('cash/Cash_people_model', 'cpm');
    }

    private static $in_type = array(
        '1' => '人工存入',
        '2' => '取消出款',
        '3' => '存款优惠',
        '4' => '返水优惠',
        '5' => '活动优惠',
    );

    private static $cash_in_type = [
        '1' => '12',//人工存入
        '2' => '18',//取消出款
        '3' => '11',//优惠活动
        '4' => '3', //优惠退水
        '5' => '11' //优惠活动
    ];

    private static $out_type = array(
        '1'=>'重复出款',
        '2'=>'公司入款误存',
        '3'=>'公司负数回冲',
        '4'=>'手工申请出款',
        '5'=>'扣除非法下注派彩',
        '6'=>'放弃存款优惠',
        '7'=>'其它'
    );

    private $balance = 'user:balance';


    /**
     * 存取款历史查询
     */
    public function history()
    {
        $in_count=array();
        $out_count=array();
        $content['b.agent_id ='] = $this->G('agent_id');
        $content['a.addtime >='] = $this->G('time_start')?strtotime($this->G('time_start').' 00:00:00') : '';
        $content['a.addtime <='] = $this->G('time_end')?strtotime($this->G('time_end').' 23:59:59') : '';
        $content['b.username'] = $this->G('username');
        $content['type'] = $this->G('type');
        $big_type = $this->G('big_type');
        $table_key = $field = $field2 = '';
        $page   = array(
                'page'  => $this->G('page')?$this->G('page'):1,
                'rows'  => $this->G('rows')?$this->G('rows'):50,
                'order' => $this->G('order'),//排序方式
                'sort'  => $this->G('sort'),//排序字段
                'total' => -1,
        );

        $operator = trim($this->G('operator'));
        if ($operator) {
            $admin_info = $this->cpm->get_admin_info($operator);
            if (empty($admin_info)){
                $this->return_json(E_ARGS,'输入的操作员不存在');
            }
            $content['a.admin_id']=$admin_info['id'];
        }

        //区分入款表还是出款表
        if (!empty($content['type'])) {
            if ($content['type']<=7 && $content['type']>=0) {
                $table_key = 'in';
                $field = 'b.id as id,a.uid as uid,b.username as username,a.price as price,
				a.discount_price as discount_price,a.auth_multiple as auth_multiple,a.type as type,
				a.remark as remark,b.balance as balance,a.addtime as addtime';
                $field2 = 'sum(price) as price_all_count,sum(discount_price) as youhui_all_count';
            } elseif ($content['type']>7 && $content['type']<=14) {
                $table_key = 'out';
                $field = 'b.id as id,a.uid as uid,b.username as username,a.price as price,a.type as type,
				a.remark as remark,b.balance as balance,a.addtime as addtime';
                $field2 = 'sum(price) as price_all_count';
                $content['type'] = $content['type']-7;
            } else {
                $this->return_json(E_ARGS, '参数错误!');
            }
            $condition['join'] = 'user';
            $condition['on'] = 'a.uid=b.id';
            $history= $this->cpm->get_list($field, 'cash_'.$table_key.'_people', $content, $condition, $page);
            $inorout_count = $this->cpm->get_list($field2, 'cash_'.$table_key.'_people', array(), $condition);
        } else {
            $condition['join'] = 'user';
            $condition['on'] = 'a.uid=b.id';
            if ($big_type==1) {
                $field = 'b.id as id,a.uid as uid,b.username as username,a.price as price,
				a.discount_price as discount_price,a.auth_multiple as auth_multiple,a.type as type,
				a.remark as remark,b.balance as balance,a.addtime as addtime';
                $his_up= $this->cpm->get_list($field, 'cash_in_people', $content, $condition, $page);
                $in_count = $this->cpm->get_list('sum(price) as price_all_count,sum(discount_price) as youhui_all_count', 'cash_in_people', array(), $condition);
            } elseif ($big_type==2) {
                $field = 'b.id as id,a.uid as uid,b.username as username,a.price as price,a.type as type,
				a.remark as remark,b.balance as balance,a.addtime as addtime';
                $his_down= $this->cpm->get_list($field, 'cash_out_people', $content, $condition, $page);
                $out_count = $this->cpm->get_list('sum(price) as price_all_count', 'cash_out_people', array(), $condition);
            } else {
                $field = 'b.id as id,a.uid as uid,b.username as username,a.price as price,
				a.discount_price as discount_price,a.auth_multiple as auth_multiple,a.type as type,
				a.remark as remark,b.balance as balance,a.addtime as addtime';
                $his_up= $this->cpm->get_list($field, 'cash_in_people', $content, $condition, $page);
                $in_count = $this->cpm->get_list('sum(price) as price_all_count,sum(discount_price) as youhui_all_count', 'cash_in_people', array(), $condition);
                $field = 'b.id as id,a.uid as uid,b.username as username,a.price as price,a.type as type,
				a.remark as remark,b.balance as balance,a.addtime as addtime';
                $his_down= $this->cpm->get_list($field, 'cash_out_people', $content, $condition, $page);
                $out_count = $this->cpm->get_list('sum(price) as price_all_count', 'cash_out_people', array(), $condition);
            }
            if (empty($his_up['total'])) {
                $his_up['total']=0;
            }
            if (empty($his_down['total'])) {
                $his_down['total']=0;
            }
            if (empty($his_up['rows'])) {
                $his_up['rows']=array();
            }
            if (empty($his_down['rows'])) {
                $his_down['rows']=array();
            }
            $history['total'] = $his_up['total']+$his_down['total'];
            $history['rows'] = array_merge((array)$his_up['rows'], (array)$his_down['rows']);
        }

        if (empty($history)) {
            $this->return_json(E_DATA_EMPTY, '无数据!');
        } else {
            $it = self::$in_type;
            $ot = self::$out_type;
            $history['price_all_count'] = $history['youhui_all_count'] = $history['youhui_count'] = $history['price_count'] = 0;
            if (isset($in_count) || isset($out_count)) {
                $history['price_all_count'] = $in_count[0]['price_all_count']+$out_count[0]['price_all_count'];
                $history['youhui_all_count'] = $in_count[0]['youhui_all_count'];
            } elseif (!empty($inorout_count)) {
                $history['price_all_count'] = $inorout_count[0]['price_all_count'];
                $history['youhui_all_count'] = empty($inorout_count[0]['youhui_all_count'])?0:$inorout_count[0]['youhui_all_count'];
            }
            foreach ($history['rows'] as $kk => $vv) {
                $history['rows'][$kk]['addtime'] = date("Y-m-d H:i:s", $history['rows'][$kk]['addtime']);
                if (isset($vv['discount_price'])) {
                    $history['rows'][$kk]['type_name'] = $it[$vv['type']];
                    $history['rows'][$kk]['auth_dml'] =
                            ($history['rows'][$kk]['price']+$history['rows'][$kk]['discount_price'])
                            *$history['rows'][$kk]['auth_multiple'];
                    $history['youhui_count'] += $history['rows'][$kk]['discount_price'];
                } else {
                    $history['rows'][$kk]['type_name'] = $ot[$vv['type']];
                }
                $history['price_count'] += $history['rows'][$kk]['price'];
                unset($history['rows'][$kk]['type']);
            }
        }

        /**** 格式化小数点 ****/
        // $history['rows'] = stript_float($history['rows']);
        $history = stript_float($history);

        $this->return_json(OK, $history);
    }


    /**
     * 根据用户帐号查询该用户信息
     */
    public function get_user()
    {
        $content['username']=$this->P('username');
        if (empty($content['username'])) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $condition['join'] = array(array('table'=>'user_detail as l','on'=>'user.id = l.uid'));
        $user= $this->ccm->get_one('user.id as uid,username,balance,bank_name', 'user', $content, $condition);
        if(empty($user)){
            $this->return_json(E_DATA_EMPTY, '无数据!');
        }else{
            $key = $this->balance.':'.$user['uid'];
            $this->ccm->redis_set($key,$user['balance']);
            $this->ccm->redis_expire($key,7200);
            $this->return_json(OK, $user);
        }
    }

    /**
     * 获取层级列表
     */
    public function get_level_list()
    {
        $content = $condition = array();
        $level= $this->ccm->get_list('id,level_name', 'level', $content, $condition);
        empty($level)?$this->return_json(E_DATA_EMPTY, '无数据!'):$this->return_json(OK, $level);
    }

    /**
     * 人工存款 区分单个存款还是批量存款
     */
    public function cash_in_people()
    {
        $content['price']=$this->P('price')?abs($this->P('price')):0;
        //存款金额
        $content['discount_price']=abs($this->P('discount_price'));//存款优惠金额
        $zong = (float)$content['price']+(float)$content['discount_price'];
        /*if($admin['max_credit_in_people']!=0 && $zong>(float)$admin['max_credit_in_people']){
            $this->return_json(E_OP_FAIL,'操作失败，金额超出管理员人工存取款累计额度上限！');
        }*/
        if ($content['price']==0 && $content['discount_price']==0) {
            $this->return_json(E_OP_FAIL, '存款总金额不能为0！');
        }
        if ($this->admin['max_credit_in_people']>0) {
            if ($zong > (float)$this->admin['max_credit_in_people']) {
                $this->return_json(E_OP_FAIL, '操作失败，金额超出管理员人工存取款额度上限！');
            }
        }
        $level_id = $this->P('level_id');
        $username = $this->P('username');
        $user_arr = array();
        if (!empty($level_id)) {//根据层级批量
            $basic['level_id'] = $level_id;//根据层级ID查找对应的用户
            $condition = array();
            $user_arr = $this->ccm->get_list('id,username', 'user', $basic, $condition);
            if (empty($user_arr)) {
                $this->return_json(E_DATA_EMPTY, '该层级下暂无用户！');
            }
        } elseif (!empty($username)) {//多用户批量
            $basic = $condition = array();
            $username_arr = explode(',', $username);
            if (count($username_arr)>1) {
                $wheresql = '';
                foreach ($username_arr as $k => $v) {
                    $wheresql.="username='$v' OR ";
                }
                $condition['wheresql'] = array(substr($wheresql, 0, -3));
            } else {
                $basic['username'] = $username_arr[0];
            }
            $user_arr = $this->ccm->get_list('id,username', 'user', $basic, $condition);
            if (empty($user_arr)) {
                $this->return_json(E_DATA_EMPTY, '会员不存在!');
            }
        } else {//单用户
            $content['uid']=$this->P('uid'); //用户ID
        }

        $content['auth_multiple']=(float)$this->P('auth_multiple');//稽核倍数（常态性稽核）
        $content['type']=$this->P('type');//存款项目
        $content['auth_dml'] = $auth_dml= $this->P('auth_dml')*1;//综合打码量

        if ($content['type']!=3 && $content['type']!=4 && $content['type']!=5 && empty($content['price'])) {
            $this->return_json(E_ARGS, '输入的数据不合法');
        }

        if ($content['type']==3 || $content['type']==4 || $content['type']==5) {
            $content['price']=0;
        }
        //检测是否为空
        foreach ($content as $key=>$value) {
            if ($value===null) {
                $this->return_json(E_ARGS, '参数错误:'.$key);
            }
        }
        // //检测存款金额、存款优惠与打码量的合法性
        // if (($zong*$content['auth_multiple'])!=$content['auth_dml']) {
        //     $this->return_json(E_ARGS, '输入的数据不合法');
        // }
        array_pop($content);
        $content['addtime']=$_SERVER['REQUEST_TIME'];//存入时间
        $content['remark']=$this->P('remark').'(操作员:'.$this->admin["name"].'('.$this->admin["username"].'))';//备注
        $content['admin_id']=$this->admin['id'];
        //$remit=$this->P('remit')?$this->P('remit'):0;//汇款优惠
        $str = '';
        if (!empty($content['uid'])) {
            //单个用户存款
            if (empty($level_id)) {
                $where['id'] = $content['uid'];
                $level_id = $this->ccm->get_list('level_id', 'user', $where, array());//根据UID查找层级ID
            }
            $c = $this->cash_in_people_run($content, $auth_dml, $level_id[0]['level_id']);
            if($c){
                $this->return_json(OK);
            }else{
                $this->return_json(E_OP_FAIL, '操作失败！');
            }
        } else {
            //多个用户存款
            foreach ($user_arr as $key => $value) {
                $where['id'] = $content['uid']=$value['id'];
                if (empty($level_id)) {
                    $level_id = $this->ccm->get_list('level_id', 'user', $where, array());//根据UID查找层级ID
					$level_id = $level_id[0]['level_id'];
                }
                $is = $this->cash_in_people_run($content, $auth_dml, $level_id);
                //if(!$is)$this->return_json(E_OP_FAIL,'第'.($key+1).'个用户操作失败！用户名：'.$value['username'].',后面的操作已停止！');
                if (!$is) {
                    $str .= '第'.($key+1).'个用户操作失败！用户名：'.$value['username'].'；';
                }
            }
        }
        if (empty($str)) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OP_FAIL, $str);
        }
    }


    /**
     * 人工存款操作
     */
    private function cash_in_people_run($content, $auth_dml, $level_id)
    {
        $this->ccm->db->trans_begin();
        $this->load->model('log/Log_model', 'lo');
        $this->ccm->write('cash_in_people', $content);//写入人工入款信息
        $payData = $this->ps->get_pay_set($content['uid'], 'ps.pay_set_content');//获取一个会员的支付设定信息
        $order_num = date('YmdHis').mt_rand(1000, 9999);
        $all_price = $content['price']+$content['discount_price']; //最终存入的金额 = 存款金额+存款优惠金额
        $ta = self::$in_type;
        $cash_type = self::$cash_in_type[$content['type']]; //写入流水表type
        //11.21加钱前先算好等级和积分，增加存款用户积分及晋级等级信息
        $set = $this->ccm->get_gcset(['sys_activity']);
        if ($content['type'] == 1 && in_array(1, explode(',', $set['sys_activity']))) {
            $this->load->model('Grade_mechanism_model');
            $gradeInfo = $this->Grade_mechanism_model->grade_doing($content['uid'], $all_price);
            if (empty($gradeInfo['vip_id'])) {
                $this->ccm->db->trans_rollback();
                $logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 等级和积分计算失败';
                $this->lo->record($this->admin['id'], $logData);
                return false;
            }
        } else {
            $gradeInfo = ['integral' => 0, 'vip_id' => 0];
        }

        $user = $this->ccm->get_one('max_income_price,agent_id', 'user', array('id'=>$content['uid']));//加钱前获取会员信息
        //加钱，写入现金记录
        if ($content['type'] == 1 && $content['discount_price'] > 0) {
            // 人工存款-人工存入 优惠金额 > 0  时 写入两条记录到现金流水表
            $p1 = $this->ccm->update_banlace($content['uid'], $content['price'], $order_num, $cash_type, '人工存款-人工存入-'.$this->P('remark'), $content['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
            $_p1 = $this->ccm->update_banlace($content['uid'], $content['discount_price'], $order_num, 11, '人工存款-存款优惠');
            $p1 = $p1 && $_p1;
        } else {
            $p1 = $this->ccm->update_banlace($content['uid'], $all_price, $order_num, $cash_type, '人工存款-'.$ta[$content['type']].'-'.$this->P('remark'), $content['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        }

		if(!$p1){
			$this->ccm->db->trans_rollback();
			$logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 写入流水和更新余额失败';
			$this->lo->record($this->admin['id'], $logData);
			return false;
		}
		$p2 = $this->ccm->check_and_set_auth($content['uid']);//更新稽核
		if(!$p2){
			$this->ccm->db->trans_rollback();
			$logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 更新稽核失败';
			$this->lo->record($this->admin['id'], $logData);
			return false;
		}
        $authData['uid'] = $content['uid'];
        $authData['total_price'] = $content['price'];
        $authData['discount_price'] = $content['discount_price'];
        $authData['auth_dml'] = $auth_dml;
        $authData['limit_dml'] = $payData['line_ct_fk_audit'];
        $authData['start_time'] = $_SERVER['REQUEST_TIME'];
        $authData['is_pass'] = $auth_dml > 0 ? 0 : 1;
        $authData['type'] = 1;
		$p3 = $this->ccm->write('auth', $authData);//增加稽核
		if(!$p3){
			$this->ccm->db->trans_rollback();
			$logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 增加稽核失败';
			$this->lo->record($this->admin['id'], $logData);
			return false;
		}
        $cashData['in_people_total'] = $content['price'];
        $cashData['in_people_discount'] = $content['discount_price'];
        if ($content['discount_price']>0) {
            $cashData['in_people_discount_num'] = 1;
        } else {
            $cashData['in_people_discount_num'] = 0;
        }
        $cashData['in_people_num'] = 1;
		if($user['max_income_price']<=0 && $cashData['in_people_total']>0){ //判断是否首次存款，优惠不算首存
			$cashData['is_one_pay']=1;
		}
		$cashData['agent_id'] = (int)$user['agent_id'];
        $uid = $content['uid'];
        $report_date = date('Y-m-d');
        $this->load->model('cash/Report_model', 'report');
		$p4 = $this->report->collect_cash_report($uid, $report_date, $cashData);/*汇总现金报表，计算平台额度*/
		if(!$p4){
			$this->ccm->db->trans_rollback();
			$logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 汇总现金报表失败';
			$this->lo->record($this->admin['id'], $logData);
			return false;
		}
		$p5 = $this->ccm->incre_level_use((int)$content['price'], (int)$level_id); /*增加层级金额*/
		if(!$p5){
			$this->ccm->db->trans_rollback();
			$logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 增加层级金额失败';
			$this->lo->record($this->admin['id'], $logData);
			return false;
		}
		//最终检测
        $re = $this->last_check($uid);
        if(!$re){
            $this->ccm->db->trans_rollback();
            $logData['content'] = '人工存款-'.$ta[$content['type']].',uid:'.$content['uid'].' price:'.$all_price.' 最终检测失败';
            $this->lo->record($this->admin['id'], $logData);
            return false;
        }
        //会员增加额度，并写入日志
        /*if ($this->ccm->db->trans_status() === false) {
            $this->ccm->db->trans_rollback();
            $logData['content'] = '人工存款-'.$ta[$content['type']].' 失败，提交失败';
            $this->lo->record($this->admin['id'], $logData);
            return false;
        } else {
            $this->ccm->db->trans_commit();
            $logData['content'] = '人工存款-'.$ta[$content['type']].',order_num:'.$order_num.',插入内容：('.implode(',', $content).')';
            $this->lo->record($this->admin['id'], $logData);
            return true;
        }*/
        $this->ccm->db->trans_commit();
        $logData['content'] = '人工存款-'.$ta[$content['type']].',order_num:'.$order_num.',插入内容：('.implode(',', $content).')';
        $this->lo->record($this->admin['id'], $logData);
        return true;
    }

    //最终检测
    private function last_check($uid)
    {
        $old_balance = $this->ccm->redis_get($this->balance.':'.$uid);
        $userData = $this->ccm->db->select('balance')->limit(1)->get_where('user', ['id'=>$uid])->row_array();
        if(empty($userData['balance'])){
            return false;
        }
        if($old_balance == $userData['balance']){
            return false;
        }else{
            return true;
        }
    }


    /**
     * 人工取款操作
     */
    public function cash_out_people()
    {
        $content['uid']=$this->P('uuid');   //用户ID
        $content['price']=$this->P('price')?$this->P('price'):0; //出款金额
        //$balance = $this->P('balance')?(float)$this->P('balance'):0;
        $user = $this->ccm->get_one('balance,agent_id', 'user', array('id'=>$content['uid']));
        if (($user['balance']-$content['price'])<0) {
            $this->return_json(E_OP_FAIL, '余额不足');
        }
        if (!is_numeric($content['price']) || $content['price']<0) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $admin = $this->admin;
        if ($admin['max_credit_in_people']>0) {
            if ((float)$content['price'] > (float)$admin['max_credit_in_people']) {
                $this->return_json(E_OP_FAIL, '操作失败，金额超出管理员人工存取款额度上限！');
            }
        }
        $content['type']=$this->P('type');//出款项目
        if (empty($content['uid']) || empty($content['type'])) {
            $this->return_json(E_DATA_EMPTY, '参数不能为空');
        }
        $content['addtime']=$_SERVER['REQUEST_TIME'];//出款时间
        $content['remark']=$this->P('remark').'(操作员:'.$this->admin["name"].'('.$this->admin["username"].'))';//备注
        $content['admin_id']=$admin['id'];
        $this->load->model('cash/Out_manage_model', 'omm');
        $ot = self::$out_type;
        $order_num = date('YmdHis').mt_rand(1000, 9999);
        $this->ccm->db->trans_start();
        $flag = $this->omm->write('cash_out_people', $content);
        if (!$flag) {
            $this->ccm->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '人工取款-'.$ot[$content['type']].' 失败']);
            $this->return_json(E_OP_FAIL, '更新人工出款失败！');
        }
        $content['price'] = (float)('-'.$content['price']);
        $flag = $this->ccm->update_banlace($content['uid'], $content['price'], $order_num, 13, '人工取款-'.$ot[$content['type']], $content['price']);//减钱，写入现金记录
        if (!$flag) {
            $this->ccm->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '人工取款-'.$ot[$content['type']].' 失败']);
            $this->return_json(E_OP_FAIL, '更新余额失败！');
        }
        $flag = $this->omm->out_clear_auth($content['uid'], '人工取款-'.$ot[$content['type']].'清除稽核', $_SERVER['REQUEST_TIME']);
        if (!$flag) {
            $this->ccm->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '人工取款-'.$ot[$content['type']].' 失败']);
            $this->return_json(E_OP_FAIL, '清除稽核失败！');
        }
        $cashData['out_people_total'] = abs($content['price']);
        $uid = $content['uid'];
		$cashData['agent_id'] = (int)$user['agent_id'];
		// edit by wuya 按照请求时间计算报表日期
        //$report_date = date('Y-m-d');
        $report_date = date('Y-m-d',$_SERVER['REQUEST_TIME']);
        $this->load->model('cash/Report_model', 'report');
        $flag = $this->report->collect_cash_report($uid, $report_date, $cashData);/*汇总现金报表，计算平台额度*/
        if (!$flag) {
            $this->ccm->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '人工取款-'.$ot[$content['type']].' 失败']);
            $this->return_json(E_OP_FAIL, '更新现金报表失败！');
        }
        $this->load->model('log/Log_model', 'lo');
        /*if ($this->ccm->db->trans_status() === false) {
            $this->ccm->db->trans_rollback();
            $logData['content'] = '人工取款-'.$ot[$content['type']].' 失败';
            $this->lo->record($this->admin['id'], $logData);
            $this->return_json(E_OP_FAIL, '操作失败！');
        } else {
            $this->ccm->db->trans_commit();
            $this->omm->out_unlock($content['uid']);//出款解锁
            $this->ccm->cash_people_increby($this->admin['id'], abs($content['price']));
            $logData['content'] = '人工取款-'.$ot[$content['type']].',order_num:'.$order_num.',插入内容：('.implode(',', $content).')';
            $this->lo->record($this->admin['id'], $logData);
            $this->return_json(OK);
        }*/
        $this->ccm->db->trans_commit();
        $this->omm->out_unlock($content['uid']);//出款解锁
        $this->ccm->cash_people_increby($this->admin['id'], abs($content['price']));
        $logData['content'] = '人工取款-'.$ot[$content['type']].',order_num:'.$order_num.',插入内容：('.implode(',', $content).')';
        $this->lo->record($this->admin['id'], $logData);
        $this->return_json(OK);
    }
}
