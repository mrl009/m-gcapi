<?php
/**
 * @模块   现金系统／出款管理
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Out_manage extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/Out_manage_model', 'core');
    }
    


    /******************公共方法*******************/
    /**
     * 获取出款管理数据
     */
    public function get_list()
    {
        //精确条件
        $time_type = $this->G('time_type') ? (int)$this->G('time_type') : 1;
        $from_time = $time_type == 1 ? 'a.addtime >=' : 'a.updated >=';
        $to_time = $time_type == 1 ? 'a.addtime <=' : 'a.updated <=';
        $basic = array(
            'a.agent_id'   => (int)$this->G('agent_id'),
            'a.id'   => (int)$this->G('id'),
            'a.from_way' => (int)$this->G('froms'),
            'a.actual_price >=' => (int)$this->G('price_start'),
            'a.actual_price <=' => (int)$this->G('price_end'),
            $from_time => strtotime($this->G('time_start').' 00:00:00'),
            $to_time => strtotime($this->G('time_end').' 23:59:59'),
            'a.status'     => (int)$this->G('status'),
            'a.uid'     => (int)$this->G('uid'),
            );

        /*** 查询时间跨度不能超过两个月 ***/
        $diff_time = $basic[$to_time] - $basic[$from_time];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic[$to_time] = $basic[$from_time] + ADMIN_QUERY_TIME_SPAN;
        }
        $is_first = $this->G('is_first');
        if (is_numeric($is_first)) {
            $basic['a.is_first'] = ($is_first == 0 ? "'0'" : $is_first);
        }

        $username = $this->G('f_username');
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
        }
        //.按照操作者来搜索
        $admin = $this->G('f_admin');
        if (!empty($admin)) {
            $uid = $this->core->get_one('id', 'admin', ['username'=>$admin]);
            $basic['a.admin_id'] = empty($uid) ? '-1' : $uid['id'];
            //.不做单独条件
            if (empty($this->G('time_start')) && empty($this->G('time_end'))) {
                unset($basic[$from_time]);
                unset($basic[$to_time]);
            }
        }
        // 高级搜索
        // $senior = array(
        // 	'join' => 'user',
        // 	'on' => 'a.uid=b.id');
        // 高级搜索
        // 余额联表查询现金流水(9.11)
        $senior['join'] = [
            ['table'=>'user_detail as e','on'=>'e.uid=a.uid'],
            ['table' => 'admin as c','on' => 'c.id=a.admin_id'],
            ['table' => 'cash_list as d','on' => 'd.order_num=a.order_num']];
        $basic['d.type ='] = 14;
        $level_id = (int)$this->G('levels');
        if ($level_id > 0) {
            $senior['join'][] = ['table' => 'user as b','on' => 'b.id=a.uid'];
            $basic['b.level_id ='] = $level_id;
        }
        // 分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
                (int)$this->G('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'sort'  => $this->G('sort'),
            'order' => $this->G('order')
        );
        // 排序
        $sort_field = array('is_first',
                        'is_pass', 'addtime',
                        'status', 'admin_id',
                        'url','price', 'hand_fee',
                        'admin_fee', 'actual_price','balance');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }
        if ($page['sort'] == 'balance') {
            $senior['page_limit'] = array($rows, $page);
            $senior['orderby'] = array('b.balance'=>$page['order']);
            $page = array();
        }

        $impounded = $this->G('impounded');
        if ($impounded == 1) {
            $basic['a.status'] = null;
            $senior['wheresql'][] = '(a.status = 3 or (a.status = 2 and a.price != a.actual_price))';
        }

        $arr = $this->core->get_outmanage($basic, $senior, $page);
        $this->return_json(OK, $arr);
    }



    //.定义一个方法获取自动出款数据
    public function get_auto_list()
    {
        //精确条件
        $basic = array(
            'a.o_id'   => (int)$this->G('outId'),
            'a.order_num'=> $this->G('f_ordernum'),
            'a.addtime >=' => strtotime($this->G('time_start').' 00:00:00'),
            'a.addtime <=' => strtotime($this->G('time_end').' 23:59:59'),
            'a.status'     => (int)$this->G('status')
            );

        /*** 查询时间跨度不能超过两个月 ***/
        $diff_time = $basic['a.addtime <=']-$basic['a.addtime >='];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.addtime <='] = $basic['a.addtime >=']+ADMIN_QUERY_TIME_SPAN;
        }

         /*** 特殊查询则取消时间限制：订单号 ***/
        if (!empty($basic['a.order_num'])) {
            unset($basic['a.addtime >=']);
            unset($basic['a.addtime <=']);
        }
        // 高级搜索
        $senior['join'] = [
            ['table'=>'user_detail as e','on'=>'e.uid=a.uid'],
            ['table' => 'admin as c','on' => 'c.id=a.admin_id']];
        // 分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
                (int)$this->G('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'sort'  => $this->G('sort'),
            'order' => $this->G('order')
        );
        // 排序
        $sort_field = array('addtime',
                        'status', 'admin_id','price');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }

        $arr = $this->core->get_auto_outmanage($basic, $senior, $page);
        $this->return_json(OK, $arr);
    }

    /**
     * 获取预备自动出款的数据
     */
    public function get_pre_auto_out_list()
    {
        if (strtolower($this->admin['username']) !== 'syszdchukuan') {
            $this->return_json(OK,['rows'=>[],'total'=>0]);
        }
        $where = [];
        //精确条件
        $price_start = $this->P('price_start')?(int)$this->P('price_start'):1;
        $price_end = $this->P('price_end')?(int)$this->P('price_end'):500;
        if ($price_start > $price_end) {
            $this->return_json(OK,['rows'=>[],'total'=>0]);
        }
        $addtime = $this->P('addtime')?(int)$this->P('addtime'):10;
        $addtime = $_SERVER['REQUEST_TIME']-60*$addtime;
        $where = [
            'actual_price >=' => $price_start,
            'actual_price <=' => $price_end,
            'addtime >=' => $addtime,
            'status'     => 1,
            'o_status'   => 0,
            'admin_id'   => 0,
            'o_id'   => 0
        ];
        if ($this->P('fee_stop')) {
            $where['hand_fee'] = 0;
            $where['admin_fee'] = 0;
        }
        if ($this->P('people_remark')) {
            $where['people_remark'] = $this->P('people_remark');
        }
        $update = [
            'status' => 4,//改成预备出款
            'o_status' => 4,// 自动出款锁定
            'admin_id' => (int)$this->admin['id'],
            'updated' => $_SERVER['REQUEST_TIME'],
            'people_remark' => '自动出款锁定'
        ];
        $this->core->db->where($where)->set($update)->update('cash_out_manage');
        $where2['a.status'] = 4;
        $where2['a.o_status > '] = 0;
        $where2['a.admin_id'] = (int)$this->admin['id'];
        $total = $this->core->db->where($where2)
            ->count_all_results('cash_out_manage as a', FALSE);
        $this->core->db->join('user as b','a.uid=b.id','inner')
            ->join('admin as c','a.admin_id=c.id','inner')
            ->join('user_detail as d','a.uid=d.uid','inner');
        $select =  'b.balance as balance,
                    b.username as user_name,
                    c.username as admin_name,
                    d.bank_name as bank_name,
                    d.bank_num as bank_num,
                    a.uid as uid,
                    a.id as id,
                    a.order_num,
                    a.price as price,
                    a.hand_fee as hand_fee,
                    a.admin_fee as admin_fee,
                    a.people_remark as people_remark,
                    a.remark as remark,
                    a.actual_price as actual_price,
                    a.addtime as addtime,
                    a.updated as updated,
                    a.status as status,
                    a.admin_id as admin_id,
                    a.o_id as o_id,
                    a.o_status as o_status,
                    a.is_first as is_first';
        $page = (int)$this->P('page');
        $limit = (int)$this->P('rows');
        $sort = (string)$this->P('sort');
        $order = (string)$this->P('order');
        $offset = ($page-1)*$limit;
        $rows = $this->core->db->limit($limit,$offset)
            ->order_by($sort,$order)
            ->select($select)
            ->get()
            ->result_array();
        $this->load->model('Agentpay_model','ap');
        $apay_channels = $this->ap->get_apay_channels();
        $apay_channels = array_make_key($apay_channels,'o_id');
        array_walk($rows,function (&$v,$k) use ($apay_channels){
            $v['index'] = $k;
            $v['price'] = floatval($v['price']);
            $v['hand_fee'] = floatval($v['hand_fee']);
            $v['admin_fee'] = floatval($v['admin_fee']);
            $v['balance'] = floatval($v['balance']);
            $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            if ($v['updated'] != 0) {
                $v['apay_at'] = $v['updated'];
                $v['updated'] = date('Y-m-d H:i:s', $v['updated']);
            } else {
                $v['apay_at'] = 0;
                $v['updated'] = '-';
            }
            if ($v['o_id'] > 0 && isset($apay_channels[$v['o_id']])) {
                $v['queryapi'] = $apay_channels[$v['o_id']]['doquery_api'];
            } else {
                $v['queryapi'] = '';
            }
        });
        $this->return_json(OK,['rows'=>$rows,'total'=>$total]);
    }

    public function unlock_apay_order()
    {
        $order_num = $this->P('order_num');
        $update = [
            'status' => 1,//改成预备出款
            'o_status' => 3,// 自动出款状态改为失败
            'admin_id' => 0,
            'updated' => $_SERVER['REQUEST_TIME'],
            'people_remark' => '解除自动出款锁定'
        ];
        $flag = $this->core->db->where('order_num',$order_num)
            ->where('admin_id',(int)$this->admin['id'])
            ->where_in('o_status',[1,3,4])
            ->where('status',4)
            ->set($update)
            ->update('cash_out_manage');
        if ($flag) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OP_FAIL,'解除锁定失败');
        }
    }



    /**
     * 获取出口订单支付类型
     *
     * @access public
     * @param Integer $id
     */
    public function outtype($id)
    {
        // 1
        $id = (int)$id;
        if ($id<1) {
            $this->return_json(E_ARGS, '订单ID不正确');
        }
        $outData = $this->core->get_one('out_type, uid', 'cash_out_manage', ['id'=>$id]);
        $select = 'wechat, wechat_qrcode, alipay, alipay_qrcode, bank_num, bank_id, address';
        $userData = $this->core->get_one($select, 'user_detail', ['uid'=>$outData['uid']]);
        // 2
        $data = ['pay_type'=>'', 'pay_user'=>'', 'pay_address'=>'', 'pay_image'=>''];
        switch ($outData['out_type']) {
            case 1:
                $this->core->select_db('public');
                $bankName = $this->core->get_one('bank_name', 'bank', ['id'=>$userData['bank_id']]);
                $data['pay_type'] = $bankName['bank_name'];
                $data['pay_user'] = $userData['bank_num'];
                $data['pay_address'] = $userData['address'];
                break;
            case 2:
                $data['pay_type'] = '支付宝';
                $data['pay_user'] = $userData['alipay'];
                $data['pay_image'] = $userData['alipay_qrcode'];
                break;
            case 3:
                $data['pay_type'] = '微信';
                $data['pay_user'] = $userData['wechat'];
                $data['pay_image'] = $userData['wechat_qrcode'];
                break;
            default:
                $this->core->select_db('public');
                $bankName = $this->core->get_one('bank_name', 'bank', ['id'=>$userData['bank_id']]);
                $data['pay_type'] = $bankName['bank_name'];
                $data['pay_user'] = $userData['bank_num'];
                $data['pay_address'] = $userData['address'];
                break;
        }

        return $this->return_json(OK, $data);
    }

    /**
     * 获取层级列表
     */
    public function get_level_list()
    {
        $resu['rows'] = $this->core->_table_list(
            'id, level_name as name', 'level', 'private');
        array_unshift($resu['rows'], array('id'=>0,'name'=>'全部'));
        $this->return_json(OK, $resu);
    }

    /**
     * 根据订单号查询出该用户选择出款的方式
    */

    public function out_order_detail()
    {
        $id = (int)$this->G('id');
        $where = [
            'cash_out_manage.id' => $id,
        ];
        $where2 = [
            'join' => 'user_detail',
            'on'   => 'cash_out_manage.uid=b.uid'
        ];
        $finde = 'cash_out_manage.actual_price,cash_out_manage.out_type,b.bank_id,b.bank_num,b.address,b.bank_name,b.wechat,b.wechat_qrcode
                  ,b.alipay,b.alipay_qrcode';
        $data = $this->core->get_one($finde,'cash_out_manage',$where,$where2);

        $res  = [];
        if (!empty($data)) {
            $res['id'] = $this->G('id');
            $res['out_type'] = $data['out_type'];
            $res['actual_price'] = $data['actual_price'];
            $res['bank_name'] = $data['bank_name'];
            if ($data['out_type'] == 2) {
                $res['bank_name'] = '支付宝';
                $res['bank_num'] = $data['alipay'];
                $res['address'] = $data['alipay_qrcode'];

            }elseif ($data['out_type'] == 3) {
                $res['bank_name'] = '微信';
                $res['bank_num'] = $data['wechat'];
                $res['address'] = $data['wechat_qrcode'];
            }else{
                $this->core->select_db('public');
                $bank = $this->core->get_one('bank_name','bank',[ 'id' => $data['bank_id'] ]);
                $res['bank_num'] = $data['bank_num'];
                $res['bank_name'] = $bank['bank_name'];
                $res['address'] = $data['address'];
            }

        }
        $this->return_json(OK,$res);
    }

    /**
     * 出款订单数数据更改
    */

    public function chang_order()
    {
        $id = $this->P('id');
        $hand_fee     = $this->P('hand_fee');//出款手续费
        $admin_fee    = $this->P('admin_fee');//行政费用
        $data = array(
            'hand_fee'     => $hand_fee,
            'admin_fee'    => $admin_fee,
        );

        if ($id <= 0) {
            $this->return_json(E_ARGS, '"已处理的订单"无法修改手续费');
        }
        $this->check_chang_data($data);
        $orderData = $this->core->get_detail($id);
        if (empty($orderData)) {
            $this->return_json(E_ARGS, '订单已出款');
        }
        if ($orderData['admin_id'] != $this->admin['id'] && !empty($orderData['admin_id'])&& $orderData['updated']+900 >= time()) {
            $this->return_json(E_ARGS, '你不能操作其他管理员的订单');
        }

        $bool = $this->core->set_chang_lock($id);
        if (!$bool) {
            $this->return_json(E_ARGS, '订单操作中');
        }
        $userName    = $orderData['username'];
        $orderNum    = $orderData['order_num'];

        $tempmoney   = $data['hand_fee']+$data['admin_fee'];

        $data['actual_price'] = $orderData['price'] - $tempmoney;
        if ($data['actual_price'] <=0) {
            $this->core->del_chang_lock($id);
            $this->return_json(E_ARGS, '实际出款额度不能小于0');
        }
        $where       = array();
        $where['id'] = $id;
        foreach ($data as $k => $value) {
            $this->core->db->set($k, $value, false);
        }
        $bool = $this->core->db->where_in('status', [OUT_NO,OUT_PREPARE])->update('cash_out_manage', [], $where);

        /***********日志*******/
        ($bool)?$x="成功":$x="失败";
        $this->load->model('log/Log_model');
        $str = "会员出款手续:{$orderData['hand_fee']} 改为 {$data['hand_fee']} ;出款行政费用 {$orderData['admin_fee']} 改为 {$data['admin_fee']}";
        $logData['content'] = "更改了会员{$userName}出款订单号:{$orderNum}的出款信息"."状态:$x $str";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);
        /***********日志*******/
        if ($bool) {
            $this->core->del_chang_lock($id);
            $this->return_json(OK);
        } else {
            $this->core->del_chang_lock($id);
            $this->return_json(E_OK, "写入失败");
        }
    }
    /**
     * 预备出款
     * @return bool
    */
    public function out_handle()
    {
        $id = (int)$this->P('id');
        $status = (int)$this->P('status');
        if ($id<=0) {
            $this->return_json(E_ARGS, '参数出错');
        }

        $admin = $this->admin;
        $this->load->model('cash/Cash_common_model', 'comm');
        $where['id'] = $id;
        $outData = $this->comm->get_one('price,order_num,admin_id,addtime,updated', 'cash_out_manage', $where);
        $bool    = $this->comm->check_in_or_out($admin['id'], $admin['max_credit_out_in'], $outData['price']);
        if ($bool !== true) {
            $jsonData['code'] = E_ARGS;
            $jsonData['msg'] = "操作失败,你的操作额度不够";
            echo json_encode($jsonData);
            die;
        }
        //判断订单是否被其他管理员点击
        if ($outData['admin_id'] != $this->admin['id'] && !empty($outData['admin_id'])&& $outData['updated']+900 >= time()) {
            $this->return_json(E_ARGS, '你不能操作其他管理员的订单');
        }
        $bool = $this->core->set_chang_lock($id, false);
        if (!$bool) {
            $this->return_json(E_ARGS, '订单操作中');
        }
        switch ($status) {
            case OUT_DO:
                $b = $this->core->out_do($id, $this->admin);
                $a = "确认";
                $pushStatus =MQ_PAY_OK ;
                break;
            case OUT_CANCEL:
                $remark = $this->P('remark');
                // if (empty($remark)) {
                //     $this->return_json(E_ARGS, '备注不能为空');
                // }
                $b = $this->core->out_cancel($id, $remark, $this->admin);
                $a = "拒绝";
                $pushStatus =MQ_PAY_JJ ;

                break;
            case OUT_PREPARE:
                $rkOutPrepareLock = 'admin:out_prepare_lock:'.$id;
                if (!$this->M->fbs_lock($rkOutPrepareLock, 3600)) {
                    $this->return_json(E_OK, '该出款已经在处理！');
                }
                $b = $this->core->out_prepare($id);
                $this->M->fbs_unlock($rkOutPrepareLock);
                $pushStatus = MQ_PAY_YB ;
                $a = '预备出款';
                break;
            case OUT_REFUSE:
                $remark = $this->P('remark');
                // if (empty($remark)) {
                //     $this->return_json(E_ARGS, '备注不能为空');
                // }
                $rkOutPrepareLock = 'admin:out_prepare_lock:'.$id;
                if (!$this->M->fbs_lock($rkOutPrepareLock, 3600)) {
                    $this->return_json(E_OK, '该出款已经在处理！');
                }
                $b = $this->core->out_refuse($id, $remark, $this->admin);
                $this->M->fbs_unlock($rkOutPrepareLock);
                $a = "取消";
                $pushStatus = MQ_PAY_QX ;
                break;
            default:
                $this->return_json(E_ARGS, '参数出错');
                break;
        }
        if ($b) {
            if (!empty($a)) {
                $this->load->model('log/Log_model');
                $logData['content'] = "管理员{$this->admin['username']}$a 会员出款,订单号:{$outData['order_num']}  状态成功";//内容自己对应好
                //$this->push($pushStatus, "管理员{$this->admin['username']}$a 会员出款");
                $this->Log_model->record($this->admin['id'], $logData);
            }
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK, '当前状态不需要该操作!');
        }
    }

     /**
     * 人工备注编辑
     * @return bool
    */
    public function remark_handle()
    {
        $id = (int)$this->P('id');
        $peopel_remark = $this->P('remark');
        if ($id<=0) {
            $this->return_json(E_ARGS, '参数出错');
        }
        $admin = $this->admin;
        $this->load->model('cash/Cash_common_model', 'comm');
        $where['id'] = $id;
        $outData = $this->comm->get_one('price,order_num,admin_id,addtime,updated', 'cash_out_manage', $where);
        //判断订单是否被其他管理员点击
        if ($outData['admin_id'] != $this->admin['id'] && !empty($outData['admin_id'])&& $outData['updated']+900 >= time()) {
            $this->return_json(E_ARGS, '你不能操作其他管理员的订单');
        }
        $bool = $this->core->set_chang_lock($id, false);
        if (!$bool) {
            $this->return_json(E_ARGS, '订单操作中');
        }
        $data['updated'] = $_SERVER['REQUEST_TIME'];
        $data['people_remark'] = $peopel_remark;
        $b = $this->core->write('cash_out_manage', $data, $where);
        if ($b) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK, '编辑失败');
        }
    }

    /**
     * 验证是否是同一个管理员
     * @return bool
    */
    public function match_handle()
    {
        $id = (int)$this->P('id');
        if ($id<=0) {
            $this->return_json(E_ARGS, '参数出错');
        }
        $admin = $this->admin;
        $this->load->model('cash/Cash_common_model', 'comm');
        $where['id'] = $id;
        $outData = $this->comm->get_one('price,order_num,admin_id,addtime,updated', 'cash_out_manage', $where);
        //判断订单是否被其他管理员点击
        if ($outData['admin_id'] != $this->admin['id'] && !empty($outData['admin_id'])) {
            $this->return_json(E_ARGS, '你不能操作其他管理员的订单');
        }
        $this->return_json(OK);
    }
    /**
     * 检查表单提交的参数
    */
    private function check_chang_data($data)
    {
        $rule = [
            'hand_fee'     => 'int|egt:0',
            'admin_fee'    => 'int|egt:0',
        ];
        $msg  = [
            'hand_fee.number'     => '出款手续费必须为整数',
            'admin_fee.number'    => '行政费必须为整数',
            'hand_fee.egt'        => '出款手续费不能小于0',
            'admin_fee.egt'       => '行政费不能小于0',
        ];

        $this->validate->rule($rule, $msg);
        $result   = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }
    /********************************************/
}
