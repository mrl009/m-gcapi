<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Agent_model', 'core');
    }

    public $game_names = ['k3'=>'快3','ssc'=>'时时彩','11x5'=>'11选5','fc3d'=>'福彩3D','pl3'=>'排列3','pk10'=>'PK10','lhc'=>'六合彩','pcdd'=>'PC蛋蛋'];

    /**
     * 代理审核-代理审核列表
     * @auther zdc
     * @return array
     **/
    public function get_agent_list()
    {

        $start = strtotime($this->G('time_start'));
        $end = strtotime($this->G('time_end'));

        if ($start && $end) {
            $time_start = mktime(0, 0, 0, date('m', $start), date('d', $start), date('Y', $start));
            $time_end = mktime(23, 59, 59, date('m', $end), date('d', $end), date('Y', $end));
        } else {
            $time_start = null;
            $time_end = null;
        }


//        $this->return_json(OK, $start);
        $basic = array(
            'a.name' => $this->G('name'),
            'a.status' => $this->G('status'),
            'a.addtime >=' => $time_start,
            'a.addtime <=' => $time_end,
        );

        $senior['join'] = [
            ['table' => 'user_detail as b', 'on' => 'b.uid=a.user_id']
        ];

        // 分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 50;
        $page = array(
            'page' => $page,
            'rows' => $rows,
            'sort' => $this->G('sort'),
            'order' => $this->G('order')
        );

        $arr = $this->core->get_agent_list($basic, $senior, $page);

        $site = $this->core->get_gcset();
        if ($arr != Array()) {
            $domain = $site['domain'];
        } else {
            $domain = null;
        }

        if ($arr != Array()) {
            foreach ($arr['rows'] as $key => $value) {
                $arr['rows'][$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                if ($value['status'] == 4) {
                    $arr['rows'][$key]['domain'] = $domain . '?intr=' . $value['userid'];
                }
            }
            $rs = array('total' => $arr['total'],
                'rows' => $arr['rows']);
        } else {
            $rs = array('total' => 0,
                'rows' => null,);
        }
        $this->return_json(OK, $rs);

    }

    /**
     * 代理审核-代理审核详情
     * @auther zdc
     * @return string
     **/
    public function get_agent_detail()
    {
        $id = (int)$this->G('id');
        $where = [
            'id' => $id,
        ];

        if (empty($id)) {
            $this->return_json(E_ARGS, '参数不能为空');
        }

        $arr = $this->core->get_agent_detail($where);
        if ($arr != Array()) {
            $this->return_json(OK, $arr);
        } else {
            $this->return_json(E_ARGS, '获取代理详情失败');
        }
    }


    /**
     * 代理审核-跟新状态
     * @auther zdc
     * @return string
     **/
    public function agent_detail_update()
    {
        $id = (int)$this->P('id');
        $user_id = (int)$this->P('user_id');
        $status = $this->P('status');
        $memo = $this->P('memo');
        $username = $this->P('username');
        //检查参数是否为空
        if (empty($id) || empty($user_id) || empty($status)) {
            $this->return_json(E_ARGS, '参数不能为空');
        }

        //检验参数是否合法
        $this->check_param([
            'id' => $id,
            'user_id' => $user_id,
            'status' => $status,
            'memo' => $memo,
        ]);

        $arr = $this->core->agent_detail_update($id, $status, $memo);

        if ($arr['status'] == 'OK' && $status == 4) {
            $result = $this->core->update_user_type($user_id);
            $agent_line_res = $this->core->create_top_agent_line($user_id);
            if ($result['status'] == 'OK' && $agent_line_res['code'] == 200) {
                $where = [
                    'id' => $id,
                ];
                $arr = $this->core->get_agent_detail($where);
                $arr = $this->core->update_user_detail($user_id, $arr['phone'], $arr['email'], $arr['qq']);
            } else {
                $this->return_json(E_ARGS, 'update agent failed');
            }
        }
        if ($arr['status'] == 'OK') {
            // 记录操作日志
            $act = '';
            if ($status == '1') {
                $act = '提交审核';
            } elseif ($status == '2') {
                $act = '补充资料';
            } elseif ($status == '3') {
                $act = '已拒绝';
            } elseif ($status == '4') {
                $act = '审核通过';
            }
            $this->load->model('log/Log_model');
            $this->Log_model->record($this->admin['id'], array('content' => $username. '的代理审核：'. $act));
            echo json_encode($arr);
        } else {
            $this->return_json(E_ARGS, 'update agent failed');
        }
    }

    /**
     * 校验数据
     * @param $data
     * @return bool
     */
    public function check_param($data)
    {
        $rule = [
            'id' => 'require|number',
            'user_id' => 'require|number',
            'status' => 'require|number',
//            'memo' => 'chsAlpha|alphaNum',
            'memo' => ''
        ];
        $msg = [
            'id' => 'id不合法',
            'user_id' => '用户id不合法',
            'status' => '状态不合法',
            'memo' => '备注内容不合法',
        ];

        $this->validate->rule($rule, $msg);
        $result = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());
        } else {
            return true;
        }
    }

    /**
     * 审核管理-代理会员列表
     * @auther zdc
     * @return string
     **/
    public function get_agent_user()
    {

        $id = (int)$this->G('id');

        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }

        $basic = [
            'agent_id' => $id,
        ];

        $senior = [

        ];

        // 分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 50;
        $page = array(
            'page' => $page,
            'rows' => $rows,
            'sort' => $this->G('sort'),
            'order' => $this->G('order')
        );

        $arr = $this->core->get_agent_sub_user_list($basic, $senior, $page);

        if ($arr != Array()) {
            foreach ($arr['rows'] as $key => $value) {
                $arr['rows'][$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
            }
            $rs = array('total' => $arr['total'],
                'rows' => $arr['rows']);
        } else {
            $rs = array('total' => 0,
                'rows' => null,);
        }
        $this->return_json(OK, $rs);
    }
    /******************公共方法*******************/

    public function report()
    {
        $username = $this->P('username');
        $agent = $this->P('agent');
        $agent_type = $this->P('agent_type');
        $start = $this->P('start');
        if ($start == date('Y-m-d')) {
            $this->report_today();
        }
        $page   = [
            'page'  => (int)$this->P('page') > 0 ? (int)$this->P('page') : 1,
            'rows'  => (int)$this->P('rows') > 0 ? (int)$this->P('rows') : 50,
            'order' => $this->P('order'),
            'sort'  => $this->P('sort'),
        ];
        $where = [];
        if ($start) {
            $where['a.report_date ='] = $start;
        }
        if ($username) {
            $where['b.username'] = $username;
        }
        if ($agent) {
            if ($agent_type !== 'id') {
                $agent = $this->core->db->select('id')
                    ->limit(1)
                    ->get_where('user',['username'=>$agent])
                    ->row_array();
                if (empty($agent)) {
                    $this->return_json(OK,['total'=>0,'rows'=>[]]);
                }
                $where['b.agent_id'] = $agent['id'];
            } else {
                $where['b.agent_id'] = $agent;
            }
        }
        $senior['join'] = [['table'=>'user as b','on'=>'b.id=a.agent_id']];
        $select = 'b.id,a.register_num,a.bet_num,a.first_charge_num,a.bet_money,a.prize_money,a.gift_money,a.team_rebates,a.charge_money,a.withdraw_money,a.team_profit,a.agent_rebates,a.junior_num,a.report_date,b.username';
        $data = $this->core->get_list($select,'agent_report_day',$where,$senior,$page);
        $this->return_json(OK,$data);
    }

    public function report_today()
    {
        $today = date('Y-m-d');
        $username = $this->P('username');
        $agent = $this->P('agent');
        $agent_type = $this->P('agent_type');
        if ($username || $agent) {
            $this->core->db->from('user as a')->select('a.id as uid,a.username');
            if ($username) {
                $this->core->db->where("a.username='$username'");
            }
            if ($agent) {
                if ($agent_type !== 'id') {
                    $this->core->db->join('user as b',"a.agent_id=b.id and b.username='$agent'",'inner');
                } else {
                    $this->core->db->where('a.agent_id='.$agent);
                }
            }
            $users = $this->core->db->get()->result_array();
            if (empty($users)) {
                $this->return_json(OK,['total'=>0,'rows'=>[]]);
            }
            $uids = array_column($users,'uid');
            $this->core->redis_pipeline();
            foreach ($uids as $uid) {
                $this->core->redis_hgetall(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $uid);
            }
            $data = $this->core->redis_exec();
            $data = array_make_key($data,'uid');
        } else {
            $keys = $this->core->redis_keys(TOKEN_CODE_AGENT . ':report:' . $today . ':*');
            $uids = [];
            $this->core->redis_pipeline();
            foreach ($keys as $item) {
                $uid = substr($item,strrpos($item, ":")+1);
                $uids[] = $uid;
                $this->core->redis_hgetall(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $uid);
            }
            $data = $this->core->redis_exec();
            $data = array_make_key($data,'uid');
            if (empty($uids)) {
                $this->return_json(OK,['total'=>0,'rows'=>[]]);
            }
            $users = $this->core->db->from('user')
                ->select('id as uid,username')
                ->where_in('id',$uids)
                ->get()
                ->result_array();
        }
        $users = array_make_key($users,'uid');
        $ret_data = [];
        foreach ($data as $uid => $item) {
            if (empty($item)) {
                continue;
            }
            $ret_data[] = [
                'id' => $uid,
                'username' => $users[$uid]['username'],
                'register_num' => $item['register_num'],
                'bet_num' => $item['bet_num'],
                'first_charge_num' => $item['first_charge_num'],
                'bet_money' => $item['bet_money_sum'],
                'prize_money' => $item['prize_money_sum'],
                'gift_money' => $item['gift_money_sum'],
                'withdraw_money' => $item['withdraw_money_sum'],
                'rebate_money' => $item['rebate_money_sum'],
                'charge_money' => $item['charge_money_sum'],
                'agent_rebates' => $item['self_rebate_money'],
                'team_rebates' => $item['rebate_money_sum'],
                'team_profit' => round(floatval($item['prize_money_sum']+$item['gift_money_sum']+$item['rebate_money_sum']-$item['bet_money_sum']),3),
                'report_date' => $today
            ];
        }
        $page   = [
            'page'  => (int)$this->P('page') > 0 ? (int)$this->P('page') : 1,
            'rows'  => (int)$this->P('rows') > 0 ? (int)$this->P('rows') : 50,
            'order' => $this->P('order'),
            'sort'  => $this->P('sort'),
        ];
        $page['order'] = $page['order'] == 'desc';
        $page['offset'] = ($page['page'] -1) * $page['rows'];
        $total = count($ret_data);
        sortArrByField($ret_data,$page['sort'],$page['order']);
        $rows = array_slice($ret_data,$page['offset'],$page['rows']);
        $this->return_json(OK,['total'=>$total,'rows'=>$rows]);
    }

    public function report_new()
    {
        $username = $this->P('username');
        $agent = $this->P('agent');
        $agent_type = $this->P('agent_type');
        $end = $this->P('end') ? $this->P('end') : date('Y-m-d',strtotime('-1 day'));
        $start = $this->P('start') ? $this->P('start') : date('Y-m-d',strtotime($end . ' -6 day'));
        $order = $this->P('order');
        $sort =  $this->P('sort');
        if($sort =='level'){
            $order == 'asc'?$order='desc':$order='asc';
        }
        $page   = [
            'page'  => (int)$this->P('page') > 0 ? (int)$this->P('page') : 1,
            'rows'  => (int)$this->P('rows') > 0 ? (int)$this->P('rows') : 50,
            'order' => $order ? $order :'desc' ,
            'sort'  => $sort? $sort : 'bet_money',
        ];
        $where = [];
        if ($start) {
            $where['a.report_date >='] = $start;
        }
        if ($end) {
            $where['a.report_date <='] = $end;
        }
        if ($username) {
            $where['b.username'] = $username;
        }
        if ($agent) {
            if ($agent_type !== 'id') {
                $agent = $this->core->db->select('id')
                    ->limit(1)
                    ->get_where('user',['username'=>$agent])
                    ->row_array();
                if (empty($agent)) {
                    $this->return_json(OK,['total'=>0,'rows'=>[]]);
                }
                $where['b.agent_id'] = $agent['id'];
            } else {
                $where['b.agent_id'] = $agent;
            }
        }

        $data = $this->core->get_report_list($where,$page);
        $this->return_json(OK,$data);
    }

    public function add_top_agent()
    {
        $data = [
            'username' => strtolower($this->P('username')),//用户名
            'pwd'      => trim($this->P('pwd')),//密码
            'from_way' => 5,//从哪里注册而来，1：ios，2：android，3：PC
        ];
        $is_demo = $this->P('is_demo');
        $addip = get_ip();
        $datax = [
            'level_id' => 1,
            'balance'  => '0',
            'addtime'  => time(),
            'update_time' => time(),
            'status'   => $is_demo ? 4 : 1,
            'loginip'  => $addip,//最后登录ip
            'is_level_lock'  => 0,
            'login_times'  => 1    ,
            'is_card'  => 1,
            'type' => 2
        ];
        if ($this->check_user($data)) {
            $data['pwd']   = user_md5($data['pwd']);
            if ($is_demo) {
                $bool = $this->core->get_one('id', 'user', ['status'=>4]);
                if ($bool) {
                    $this->return_json(E_OP_FAIL, '已存在顶级试玩账号,添加失败');
                }
            }
            $bool = $this->core->get_one('id', 'user', ['username'=>$data['username']]);
            if ($bool) {
                $this->return_json(E_ARGS, '用户名已注册请更换');
            }
            $data = array_merge($data, $datax);
            $this->core->db->trans_start();
            $this->core->write('user', $data);
            $uid  = $this->core->db->insert_id();
            $data = [
                'uid'   => $uid,
                'addip' => $addip,
                'bank_name' => '',
            ];
            $bool = $this->core->write('user_detail', $data);
            $res = $this->core->create_top_agent_line($uid);
            if ($bool && $res['code']==200) {
                $this->core->db->trans_complete();
                $this->return_json(OK);
            } else {
                $this->core->db->trans_rollback();
                $this->return_json(E_OP_FAIL,'数据写入出错');
            }
        } else {
            $this->return_json(E_ARGS,'参数出错');
        }

    }

    private function check_user($data, $scene=null)
    {
        $rule = [
            'username' => "require|myuser|length:".USER_USERNAME_MIN_LENGTH.",".USER_USERNAME_MAX_LENGTH."",//用户名
            'pwd'      => 'require',
        ];

        $msg  =[
            'username' => '请输入'.USER_USERNAME_MIN_LENGTH."-".USER_USERNAME_MAX_LENGTH.'位英文或数字且符合0-9,a-z,A-Z字符',//用户名
            'pwd'      => '请输入密码',
        ];

        $this->validate->rule($rule, $msg);//验证数据
        $this->validate->scene('username', ['username']);
        $this->validate->scene('bank_name', ['username', 'pwd']);
        $result   = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }

    }

    /*
     * 获取代理返点信息
     */
    public function get_info($uid)
    {
        if (empty($uid)) {
            $this->return_json(E_ARGS,'缺少参数');
        }
        $info = $this->core->db->select('a.uid,a.rebate,a.level,a.type,b.agent_id,b.username')
            ->from('agent_line as a')
            ->join('user as b','a.uid=b.id','inner')
            ->where('a.uid='.$uid)
            ->limit(1)
            ->get()
            ->row_array();
        if (empty($info)) {
            $this->return_json(E_ARGS,'未查到该用户返点信息');
        }
        if ($info['level'] == 1) {
            $this->return_json(E_OP_FAIL,'顶级代理不可修改');
        }
        $info['rebate'] = json_decode($info['rebate'],true);
        $max_min = $this->get_rebate_max_min($uid,$info['agent_id']);
        $ret = array_merge($info,$max_min);
        $this->return_json(OK,$ret);
    }

    /*
     * 计算用户的上级返点到下级返点最大值的区间
     */
    public function get_rebate_max_min($uid,$agent_id = 0)
    {
        // 计算最大值
        if (empty($agent_id)) {
            $max = $this->core->get_gcset(['default_rebate']);
        } else {
            $max = $this->core->db->select('rebate as default_rebate')->from('agent_line')->where('uid='.$agent_id)->limit(1)->get()->row_array();
        }
        $max = json_decode($max['default_rebate'],true);
        // 计算最小值
        $min_arr = $this->core->db->select('edit_rebate as rebate,is_delete,register_num')->from('agent_code')->where('uid='.$uid)->get()->result_array();
        if (empty($min_arr)) {
            $min = $max;
            array_walk($min,function (&$v){$v = 0.1;});
        } else {
            $min_arr = array_filter($min_arr,function ($item){
                if ($item['is_delete'] == 1 && $item['register_num'] == 0) {
                    return false;
                } else {
                    return true;
                }
            });
            $min_arr = array_values($min_arr);
            $keys = array_keys($max);
            array_walk($min_arr,function (&$v){$v = json_decode($v['rebate'],true);});
            $min = [];
            foreach ($keys as $key) {
                $rebates = array_column($min_arr,$key);
                $min[$key] = max($rebates);
            }
        }
        return ['max'=>$max,'min'=>$min];
    }

    /*
     * 修改更新用户返点
     * step1: 加锁
     * step2: 检测用户新返点值是否合法 和 是否有修改
     * step3: 更新agent_code表用户所使用邀请码的 edit_rebate 信息
     * step4: 更新agent_line表该用户的rebate和line信息,及所有后代的 line 字段信息
     * cautious: 使用事务
     */
    public function update_rebate()
    {
        $lock = "temp:update_rebate";//加锁
        $bool = $this->core->fbs_lock($lock,20);
        if (!$bool) {
            $this->return_json(E_OP_FAIL, ' 已有人在修改,无法操作');
        }
        $uid = $this->P('uid');
        $username = $this->P('username');
        if (empty($uid) || empty($username)) {
            $this->return_json(E_ARGS,'缺少参数');
        }
        $user = $this->core->db->select('a.agent_id,b.rebate,b.level')
            ->from('user as a')
            ->join('agent_line as b','a.id=b.uid','inner')
            ->where(['a.id'=>$uid,'a.username'=>$username])
            ->limit(1)
            ->get()->row_array();

        if (empty($user)) {
            $this->return_json(E_ARGS,'用户不存在');
        }
        if ($user['level'] == 1) {
            $this->return_json(E_OP_FAIL,'顶级代理不可修改');
        }
        $agent_id = $user['agent_id'];
        $old_rebate = json_decode($user['rebate'],true);

        $max_min = $this->get_rebate_max_min($uid,$agent_id);
        $rebate = [];
        $update = false;
        foreach ($old_rebate as $k => $v) {
            $point = $this->P($k);
            if (empty($point)) {
                $this->core->fbs_unlock($lock);
                $this->return_json(E_ARGS,'缺少参数'.$k);
            }
            $point = round(floatval($point),1);
            if ($update === false && $v != $point) {
                $update = true;
            }
            if ($point > $max_min['max'][$k] || $point < $max_min['min'][$k]) {
                $this->core->fbs_unlock($lock);
                $this->return_json(E_ARGS,$this->game_names[$k] . '返点设置有误');
            } else {
                $rebate[$k] = $point;
            }
        }
        if ($update) {
            $this->_update_user_rebate($uid,$username,$agent_id,$rebate,$lock);
        } else {
            $this->core->fbs_unlock($lock);
            $this->return_json(OK,'没有修改返点');
        }

    }

    /*
     * 修改用户返点
     */
    protected function _update_user_rebate($uid,$username,$agent_id,$rebate,$lock)
    {
        $dbn = $this->core->sn;
        $this->load->model('log/Log_model', 'lo');
        $teams = $this->core->get_list('descendant as uid','agent_tree',['ancestor'=>$uid]);
        $teams = array_column($teams,'uid');
        array_push($teams,$uid);
        $rebates = $this->core->get_list('id,uid,line','agent_line',[],['wherein'=>['uid'=>$teams],'orderby'=>['uid'=>'asc']]);
        $uids = array_column($rebates,'uid');
        wlog(APPPATH."logs/{$dbn}_update_agent_line_".date('Ym').'.log', "修改 {$username} uid:{$uid} 代理返点为" . json_encode($rebate) . "开始: 共有" . count($rebates) ."条记录需要修改,uid:(" . implode(',',$uids) .")" );
        foreach ($rebates as $k => &$item) {
            $item['line'] = json_decode($item['line'],true);
            if (!isset($item['line'][$uid])) {
                wlog(APPPATH."logs/{$dbn}_update_agent_line_".date('Ym').'.log', "uid:{$item['uid']}的line" . json_encode($item) . "未找到 uid:{$uid} 的返点信息,修改失败");
                $logData['content'] = "修改 {$username} uid:{$uid}代理返点为" . json_encode($rebate) . "失败:uid : {$item['uid']}的line" . json_encode($item) . "未找到uid:{$uid}的返点信息";
                $this->lo->record($this->admin['id'], $logData);
                $this->core->fbs_unlock($lock);
                $this->return_json(E_OP_FAIL,'更新数据库失败');
            } else {
                $old = $item['line'];
                $item['line'][$uid] = $rebate;
                $new = $item['line'];
                $item['line'] = json_encode($item['line']);
                wlog(APPPATH."logs/{$dbn}_update_agent_line_".date('Ym').'.log', "第". ($k+1) ."条,uid:{$item['uid']} [old_line] " . json_encode($old) . " [new_line] ".json_encode($new));
            }
            if ($uid == $item['uid']) {
                $item['rebate'] = json_encode($rebate);
            }
            unset($item['uid']);
        }
        $this->core->db->trans_begin();
        if ($agent_id) {
            //有上级代理则修改agent_code表的edit_rebate
            $res = $this->_update_edit_rebate($uid,$rebate);
            if ($res === false) {
                $this->core->db->trans_rollback();
                wlog(APPPATH."logs/{$dbn}_update_agent_line_".date('Ym').'.log', "修改 {$username} uid:{$uid} 代理返点为" . json_encode($rebate) . "失败" );
                $logData['content'] = "修改 {$username} uid:{$uid}代理返点为" . json_encode($rebate) . "失败:更新邀请码信息失败";
                $this->lo->record($this->admin['id'], $logData);
                $this->core->fbs_unlock($lock);
                $this->return_json(E_OP_FAIL,'更新邀请码信息失败');
            }
        }
        $flag = $this->core->db->update_batch('agent_line',$rebates,'id',50);
        if ($flag === false) {
            $this->core->db->trans_rollback();
            wlog(APPPATH."logs/{$dbn}_update_agent_line_".date('Ym').'.log', "修改 {$username} uid:{$uid} 代理返点为" . json_encode($rebate) . "失败" );
            $logData['content'] = "修改 {$username} uid:{$uid}代理返点为" . json_encode($rebate) . "失败:更新数据库失败";
            $this->lo->record($this->admin['id'], $logData);
            $this->core->fbs_unlock($lock);
            $this->return_json(E_OP_FAIL,'更新数据库失败');
        } else {
            $this->core->db->trans_commit();
            wlog(APPPATH."logs/{$dbn}_update_agent_line_".date('Ym').'.log', "修改 {$username} uid:{$uid} 代理返点为" . json_encode($rebate) . "成功: 共有" . count($rebates) ."条记录被修改,uid:(" . implode(',',$uids) .")" );
            $logData['content'] = "修改 {$username} uid:{$uid}代理返点为" . json_encode($rebate) . "成功";
            $this->lo->record($this->admin['id'], $logData);
            $this->_update_redis_rebate($uids);
            // 修改了用户返点，强制踢出用户，让用户重新登陆刷新返点
            $this->load->model('Login_model');
            $this->Login_model->user_be_out($uid);
            $this->lo->record($this->admin['id'], array('content' => "会员{$username}修改返点后,被踢线"));

            $this->core->fbs_unlock($lock);
            $this->return_json(OK,'更新成功');
        }
    }

    /*
     * 修改返点后,受影响的代理线全部更新redis缓存,新下注时按照新代理线计算反水
     */
    protected function _update_redis_rebate($uids)
    {
        $rebates = $this->core->get_list('uid,line','agent_line',[],['wherein'=>['uid'=>$uids]]);
        foreach ($rebates as $v) {
            if (!empty($v['line'])){
                $this->core->redis_del(TOKEN_CODE_AGENT .':line:'. $v['uid']);
                $this->core->redis_setex(TOKEN_CODE_AGENT .':line:'. $v['uid'],3600*24,$v['line']);
            }
        }

    }

    /*
     * 修改agent_code表edit_rebate字段
     * step1:找出$uid用户使用的邀请码invite_code
     * step2:计算出invite_code中的edit_rebate和用户新设置的rebate的每个游戏的最大值
     * step3:修改edit_rebate到最大值
     */
    protected function _update_edit_rebate($uid,$rebate)
    {
        $info = $this->core->db->select('a.invite_code,(CASE WHEN b.edit_rebate="" THEN b.rebate ELSE b.edit_rebate END) AS edit_rebate,b.id')
            ->from('agent_line as a')
            ->join('agent_code as b','a.invite_code=b.invite_code','inner')
            ->where('a.uid',$uid)
            ->limit(1)
            ->get()
            ->row_array();
        if (empty($info)) {
            return true;
        }
        $edit_rebate = json_decode($info['edit_rebate'],true);
        if (empty($edit_rebate)) {
            return false;
        }
        $update = false;
        foreach ($edit_rebate as $k => &$v) {
            if ($rebate[$k] > $v) {
                $v = $rebate[$k];
                $update = true || $update;
            }
        }
        if ($update) {
            $table = 'agent_code';
            $data = [
                'edit_rebate'   => json_encode($edit_rebate),
                'edittime'      => time()
                ];
            $where = ['id'=>$info['id']];
            $flag = $this->core->db->update($table,$data,$where,1);
            return $flag;
        } else {
            return true;
        }
    }

    /*
     * 代理報表中新注冊的會員列表
     */
    public function new_register()
    {
        $agent_id = $this->P('agent_id');
        $start = $this->P('start');
        $end = $this->P('end');
        $page   = [
            'page'  => (int)$this->P('page') > 0 ? (int)$this->P('page') : 1,
            'rows'  => (int)$this->P('rows') > 0 ? (int)$this->P('rows') : 50,
            'order' => $this->P('order'),
            'sort'  => $this->P('sort')
        ];
        $offset = ($page['page']-1) * $page['rows'];
        $where = [
            'a.addtime >=' => strtotime($start),
            'a.addtime <' => strtotime($end . ' +1 day'),
        ];
        $ids = $this->core->db->from('user as a')
            ->join('agent_tree as b','a.id=b.descendant','inner')
            ->select('b.descendant as uid')
            ->where('b.ancestor',$agent_id)
            ->where($where)
            ->get()->result_array();
        $ids = array_column($ids,'uid');
        array_push($ids,$agent_id);
        $field_rows = 'a.id,a.username,a.addtime';
        $field_total = 'COUNT(DISTINCT a.id) as total';
        $total = $this->core->db->select($field_total)
            ->from('user as a')
            ->where_in('a.id',$ids,false)
            ->where($where)
            ->limit(1)
            ->get()->row_array();
        $total = $total['total'];
        $rows = $this->core->db->select($field_rows)
            ->from('user as a')
            ->where_in('a.id',$ids,false)
            ->where($where)
            ->order_by($page['sort'],$page['order'])
            ->limit($page['rows'],$offset)
            ->get()->result_array();
        foreach ($rows as $k => &$v) {
            $v['addtime'] = date("Y-m-d H:i:s", $v['addtime']);
        }
        $data['total'] = $total;
        $data['rows'] = $rows;
        $this->return_json(OK,$data);
    }

    /*
     * 代理報表中 首充用戶
     */
    public function first_charge()
    {
        $agent_id = $this->P('agent_id');
        $start = $this->P('start');
        $end = $this->P('end');
        $page   = [
            'page'  => (int)$this->P('page') > 0 ? (int)$this->P('page') : 1,
            'rows'  => (int)$this->P('rows') > 0 ? (int)$this->P('rows') : 50,
            'order' => $this->P('order'),
            'sort'  => $this->P('sort')
        ];
        $offset = ($page['page']-1) * $page['rows'];
        $where = [
            'a.first_time >=' => strtotime($start),
            'a.first_time <' => strtotime($end . ' +1 day'),
        ];
        $ids = $this->core->db->from('user as a')
            ->join('agent_tree as b','a.id=b.descendant','inner')
            ->select('b.descendant as uid')
            ->where('b.ancestor',$agent_id)
            ->where($where)
            ->get()->result_array();
        $ids = array_column($ids,'uid');
        array_push($ids,$agent_id);

        $field_rows = 'a.id,a.username,a.addtime,a.first_time';
        $field_total = 'COUNT(DISTINCT a.id) as total';
        $total = $this->core->db->select($field_total)
            ->from('user as a')
            ->where_in('a.id',$ids,false)
            ->where($where)
            ->limit(1)
            ->get()->row_array();
        $total = $total['total'];
        $rows = $this->core->db->select($field_rows)
            ->from('user as a')
            ->where_in('a.id',$ids,false)
            ->where($where)
            ->order_by($page['sort'],$page['order'])
            ->limit($page['rows'],$offset)
            ->get()->result_array();
        foreach ($rows as $k => &$v) {
            $v['addtime'] = date("Y-m-d H:i:s", $v['addtime']);
            $v['first_time'] = date("Y-m-d H:i:s", $v['first_time']);
        }
        $data['total'] = $total;
        $data['rows'] = $rows;
        $this->return_json(OK,$data);
    }

    /*
     * 代理報表中 首充用戶
     */
    public function bet_user()
    {
        $agent_id = $this->P('agent_id');
        $start = $this->P('start');
        $end = $this->P('end');
        $page   = [
            'page'  => (int)$this->P('page') > 0 ? (int)$this->P('page') : 1,
            'rows'  => (int)$this->P('rows') > 0 ? (int)$this->P('rows') : 50,
            'order' => $this->P('order'),
            'sort'  => $this->P('sort')
        ];
        $offset = ($page['page']-1) * $page['rows'];
        $where = [
            'a.report_date >=' => $start,
            'a.report_date <=' => $end,
            'a.valid_price >'  => 0
        ];
        $ids = $this->core->db->from('report as a')
            ->join('agent_tree as b','a.uid=b.descendant','inner')
            ->select('b.descendant as uid')
            ->where('b.ancestor',$agent_id)
            ->where($where)
            ->get()->result_array();
        $ids = array_column($ids,'uid');
        array_push($ids,$agent_id);

        $field_rows = 'b.id,b.username,SUM(a.bets_num) as bets_num,SUM(a.valid_price) as bets_money';
        $field_total = 'COUNT(DISTINCT uid) as total';
        $total = $this->core->db->select($field_total)
            ->from('report as a')
            ->where_in('a.uid',$ids,false)
            ->where($where)
            ->limit(1)
            ->get()->row_array();
        $total = $total['total'];
        $rows = $this->core->db->select($field_rows)
            ->from('report as a')
            ->join('user as b','a.uid=b.id','inner')
            ->where_in('a.uid',$ids,false)
            ->where($where)
            ->group_by('a.uid')
            ->order_by('b.'.$page['sort'],$page['order'])
            ->limit($page['rows'],$offset)
            ->get()->result_array();
        $data['total'] = $total;
        $data['rows'] = $rows;
        $this->return_json(OK,$data);
    }
}
