<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Agent_model', 'core');
    }

    /**
     * 生成邀请码
     */
    public function create_invite_code()
    {
        $default_rebate = $this->core->get_gcset(['default_rebate']);
        $default_rebate = json_decode($default_rebate['default_rebate'],true);
        $default_games = array_keys($default_rebate);
        $agent_line = $this->core->redis_get(TOKEN_CODE_AGENT .':line:'. $this->user['id']);
        $agent_line = json_decode($agent_line,true);
        $agent_games = array_keys($agent_line[$this->user['id']]);
        $game = array_intersect($agent_games,$default_games);
        $junior_type = (int)$this->P('type');
        if ($junior_type !== 2) {
            $junior_type = 1;//2:代理  1:玩家
        }

        // 检验 格式化 返点值
        $level = 1;
        $rebate = $this->check_rebate_set_level($game,$level);
        $this->load->model('agent/Agent_code_model','invite_code');
        while ($this->uniqid_invite_code($code)){}
        // 写入邀请码表
        $flag = $this->invite_code->create_invite_code($this->user['id'],$rebate,$code,$junior_type,$level);
        if (true === $flag) {
            $this->return_json(OK,['invite_code'=>$code]);
        } else {
            $this->return_json(E_OP_FAIL);
        }
    }

    /*
     * 生成唯一的8位数字的邀请码
     */
    private function uniqid_invite_code(&$code)
    {
        $uniqid = rand(1,9) . rand(0,9) . substr(time(),4,6);
        $code = $uniqid;
        $res = $this->invite_code->get_one('invite_code','agent_code',['invite_code'=>$code]);
        return !empty($res);
    }

    /**
     * 获取邀请码detail
     */
    public function invite_code_list()
    {
        $this->load->model('agent/Agent_code_model','agent_code');
        $res = $this->agent_code->get_invite_code_list($this->user['id']);
        $data = [];
        foreach ($res as $v) {
            $data['self_rebate'] = json_decode($v['self_rebate']);
            $v['rebate'] = json_decode($v['rebate']);
            unset($v['self_rebate']);
            $data['invite_codes'][] = $v;
        }
        if (count($res) === 1 && empty($res[0]['invite_code'])){
            $data['invite_codes'] = [];
        }
        $this->return_json(OK,$data);
    }

    /**
     * 删除邀请码
     */
    public function delete_invite_code()
    {
        $invite_code = $this->P('invite_code');
        if (empty($invite_code)) {
            $this->return_json(E_ARGS,'参数错误');
        }
        $rs = $this->core->write('agent_code',['is_delete'=>1],['uid'=>$this->user['id'],'invite_code'=>$invite_code]);
        if ($rs) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OP_FAIL);
        }
    }

    /**
     * 下级代理
     */
    public function junior_member()
    {
        $username = trim($this->P('username'));
        $type = intval($this->P('type'));
        $where = [];
        //判读参数
        if (!empty($username)) {
            if ($username == $this->user['username']) {
                $this->return_json(E_ARGS,'不能查询自己哦！');
            }
            $query = $this->core->db->select('u.id')
                ->from('agent_tree as t')
                ->join('user as u','u.id=t.descendant','inner')
                ->where('t.ancestor',$this->user['id'])
                ->where('u.username',$username)
                ->limit(1)
                ->get();
            $id = $query->row_array();
            if ($query && !empty($id)) {
                $where['u.id'] = $id['id'];
            } else {
                $this->return_json(E_OP_FAIL,'您搜索的会员账号不存在！');
            }
        } else {
            $uid = empty($this->P('uid')) ? $this->user['id'] : $this->P('uid');
            $where['u.agent_id'] = $uid;
        }
        if (!empty($type)) {
            $where['u.type'] = $type;
        }

        $this->load->model('agent/Agent_code_model','invite_code');
        $res = $this->invite_code->get_son_list($where);
        if (is_array($res)) {

            array_walk($res,function(&$item){
                $item['rebate'] = json_decode($item['rebate']);
                $item['junior_num'] += 0 ;
            });
            $this->return_json(OK,$res);
        } else {
            $this->return_json(E_OP_FAIL);
        }
    }


    /**
     *   检测代理设置返点
     * @param 玩法类型
     * @param 邀请码注册用户的层级
     */
    private function check_rebate_set_level($game = [],&$level)
    {
        $this->load->model('agent/Agent_line_model','agent_line');
        $current_rebate = $this->agent_line->get_rebate($this->user['id']);
        if (empty($current_rebate)){
            $this->return_json(E_ARGS,'您不是代理用户，无法创建邀请码');
        } else {
            if ($current_rebate['level'] == 99 && 2 == $this->P('type')) {
                $this->return_json(E_OP_FAIL,'您是最大层级,只能生成玩家类型的邀请码!');
            }
            $level = $current_rebate['level'] + 1;
            $current_rebate = json_decode($current_rebate['rebate'],true);
        }
        if ($level > 100) {
            $this->return_json(E_OP_FAIL,'层级超过最大限制');
        }
        $rebate = [];
        foreach ($game as $type){
            $value = $this->P($type);
            if (is_null($value)) {
                $this->return_json(E_ARGS,'缺少参数'.$type.'=>'.$value);
            }
            $value = round(floatval($value),1);
            if ($value<0.1 || $value>$current_rebate[$type]) {
                $this->return_json(E_ARGS,'返点设置有误');
            }
            $rebate[$type] = $value;
        }
        return empty($rebate)?'':$rebate;
    }

    /**
     * 注册用户申请成为代理
     */
    public function create_agent_account()
    {
        $user_id = $this->user['id'];

        //检查user表，判断用户是否已存在
        $data = $this->core->check_agent_register($user_id);
        if ($data != array()) {
            $this->return_json(E_ARGS, '用户已存在');
        } else {

        }

        $phone = $this->P('phone');
        $email = $this->P('email');
        $qq = $this->P('qq');
        $user_memo = $this->P('user_memo');

        //检查参数是否为空
        if (empty($phone) || empty($email) || empty($qq)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }

        //校验数据合法性
        $this->check_param(array('phone' => $phone, 'email' => $email, 'qq' => $qq, 'user_memo' => $user_memo));


        //检查agent_review表，判断用户是否已存在
        $data = $this->core->check_agent_review_register($user_id);
        if ($data != null) {

            if ($data['status'] == 2) {
                $tempPhone = $phone;
                $tempEmail = $email;
                $tempQq = $qq;

                $temp = $this->core->check_agent_review_register($user_id);
                if ($phone == $temp['phone']) {
                    $tempPhone = null;
                }

                if ($email == $temp['email']) {
                    $tempEmail = null;
                }

                if ($qq == $temp['qq']) {
                    $tempQq = null;
                }

                $resu = $this->core->check_paramter($tempPhone, $tempEmail, $tempQq);
                if ($resu['code'] != 200) {
                    $this->return_json(E_ARGS, $resu['msg']);
                }

                $result = $this->core->update_agent_review_status($user_id, $phone, $email, $qq, $user_memo, 1);
                if ($result) {
                    $this->return_json(OK, '更新代理');
                } else {
                    $this->return_json(E_ARGS, '更新代理状态失败');
                }


            } else {
                $this->return_json(E_ARGS, '代理用户已存在');
            }
        } else {
            $resu = $this->core->check_paramter($phone, $email, $qq);
            if ($resu['code'] != 200) {
                $this->return_json(E_ARGS, $resu['msg']);
            }

            //创建代理审核账号
            $data = $this->core->create_agent_data($user_id, $phone, $email, $qq, $user_memo);
            if ($data['code'] == 200) {
                $this->return_json(OK, $data);
            } else {
                $this->return_json(E_ARGS, '新建代理错误');
            }
        }
    }

    //检查账户是否已存在
    public function check_agent_register()
    {

        $user_id = $this->user['id'];

        //检查user表
//        $data = $this->core->check_agent_register($user_id);
//        if ($data != array()) {
//
//        } else {
//            $this->return_json(E_ARGS, 'no user exist!');
//        }

        //检查agent_review表
        $data = $this->core->check_agent_review_register($user_id);
        if ($data != array()) {
            $data['addtime'] = date('Y-m-d H:i:s', $data['addtime']);
            $this->return_json(OK, $data);
        } else {
            $result = ['status' => 0];
            $this->return_json(OK, $result);
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
            'phone' => 'require|number|max:11|min:11',
            'email' => 'require|email',
            'qq' => 'require|alphaNum|max:20|min:5',
//            'user_memo' => '',
        ];
        $msg = [
            'phone' => '电话号码位11位,只能是数字',
            'email' => '邮箱格式错误',
            'qq' => 'qq或微信号最少5个字符最多20个字符,只能是数字或字母',
//            'user_memo' => '申请理由不合法',
        ];

        $this->validate->rule($rule, $msg);
        $result = $this->validate->check($data);
        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());
        } else {

            if (preg_match("/^1[34578]{1}\d{9}$/", $data['phone'])) {

            } else {
                $this->return_json(E_ARGS, '手机号码不合法');
            }

            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
            if (preg_match($pattern, $data['email'])) {

            }else{
                $this->return_json(E_ARGS, '邮箱地址不合法');
            }

            return true;
        }
    }

    /******************获取下级用户*******************/
    public function get_agent_user()
    {
        // 获取搜索条件
        $page = array(
            'page' => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
            'rows' => 15,
        );
        // 精确条件
        $basic['agent_id'] = $this->user['id'];
        $basic['username'] = $this->G('username') ? $this->G('username') : '';
        $rs = $this->core->get_list('username,balance,addtime', 'user', $basic, [], $page);
        if (!empty($rs['rows'])) {
            foreach ($rs['rows'] as &$v) {
                $v['addtime'] = date('Y-m-d', $v['addtime']);
            }
        }
        $this->return_json(OK, $rs);
    }
    /******************END下级用户*******************/

    /******************获取代理报表*******************/
    public function get_agent_report()
    {
        $time_start = $this->G('time_start');
        $time_end = $this->G('time_end');
        $basic['agent_id'] = $this->user['id'];
        // 获取搜索条件
        $fields = 'SUM(now_price) as total_now_price,SUM(rate_price) as total_rate_price';
        if ($time_start || $time_end) {
            $basic['report_date >='] = $time_start;
            $basic['report_date <='] = $time_end;
        } else {
            $basic['report_date'] = date('Y-m-d', strtotime('-1 day'));
        }
        $senior = [
            'wherein' => array('status' => [1, 2])
        ];
        $rs = $this->core->get_list($fields, 'agent_report', $basic, $senior, []);
        foreach ($rs as &$v){
            $v['total_now_price'] = $v['total_now_price']?$v['total_now_price']:0;
            $v['total_rate_price'] = $v['total_rate_price']?$v['total_rate_price']:0;
        }
        $this->return_json(OK, $rs);
    }
    /******************END代理报表*******************/

    /******************获取代理分成设定*******************/
    public function get_agent_set()
    {
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }
        $rs = $this->core->get_list('*', 'agent_level', array('status' => 1));
        $this->return_json(OK, $rs);
    }
    /******************END代理分成设定*******************/

    /******************获取代理佣金*******************/
    public function get_agent_count()
    {
        // 获取搜索条件
        $page = array(
            'page' => (int)$this->G('page') > 0 ? (int)$this->G('page') : 1,
            'rows' => 15,
        );
        // 精确条件
        $time_start = date('Y-m-01', time());
        $time_end = date('Y-m-d', strtotime("$time_start +1 month -1 day"));
        $basic['report_date >='] = $time_start;
        $basic['report_date <='] = $time_end;
        $basic['agent_id'] = $this->user['id'];
        $fields = 'report_date,now_price,rate_price,status';
        $senior = array('wherein' => array('status' => [1, 2]));
        // 结果集
        $rs = $this->core->get_list($fields, 'agent_report', $basic, $senior, $page);
        // 统计数据
        $fields = 'SUM(now_price) as total_now_price,SUM(rate_price) as total_rate_price';
        $count = $this->core->get_one($fields, 'agent_report', $basic, $senior);
        $rs['total_now_price'] = $count['total_now_price']?$count['total_now_price']:0;
        $rs['total_rate_price'] = $count['total_rate_price']?$count['total_rate_price']:0;

        $this->return_json(OK, $rs);
    }
    /******************END代理佣金*******************/

    /*
     * 获取当前要查询的代理
     */
    private function _query_agent(&$uid,&$junior,$junior_return = true)
    {
        $uid = $this->user['id'];
        $junior = $this->core->get_list('descendant as uid','agent_tree',['ancestor'=>$uid]);
        $junior = array_column($junior,'uid');
        if ($junior_name = $this->P('username')) {
            if (empty($junior)){
                $this->return_json(E_ARGS,'您查找的用户不存在');
            } else {
                $where = [
                    'username'=>$junior_name,
                    'wherein'=>['id'=>$junior]
                ];
                $user = $this->core->get_one('id','user',$where);
                if (empty($user)){
                    $this->return_json(E_ARGS,'您查找的用户不存在');
                }
                $uid = $user['id'];
                if ($junior_return) {
                    $junior = $this->core->get_list('descendant as uid','agent_tree',['ancestor'=>$uid]);
                    $junior = array_column($junior,'uid');
                }
            }

        }
    }

    /*
     * 格式化报表数据
     */
    private function _format_report(&$report)
    {
        $arr1 = ['bet_money','prize_money','gift_money','team_rebates','team_profit','team_balance','charge_money','withdraw_money','agent_rebates' ,'agent_salary','agent_fenhong'];
        $arr2 = ['bet_num', 'first_charge_num','register_num','junior_num'];
        foreach ($arr1 as $key) {
            if (!isset($report[$key])) {
                $report[$key] = 0.000;
            }
            if ($key != 'team_profit') {
                $report[$key] = abs($report[$key]);
            }
            $report[$key] = sprintf('%.2f',round(floatval( $report[$key]),2));
        }
        unset($key);
        foreach ($arr2 as $key) {
            if (!isset($report[$key])) {
                $report[$key] = 0;
            }
        }
    }

    /*
     * 代理报表 今日
     */
    public function today_report()
    {
        $this->core->select_db('privite');
        $this->_query_agent($uid,$junior);
        $this->_today_report($uid,$junior);
    }

    private function _today_report($uid,$junior,$return = false)
    {
        $today = date('Y-m-d');
        $init_data = [
            'bet_money_sum'=>0.000,
            'prize_money_sum'=>0.000,
            'gift_money_sum'=>0.000,
            'rebate_money_sum'=>0.000,
            'bet_num'=>0,
            'register_num'=>0,
            'first_charge_num'=>0,
            'charge_money_sum'=>0.000,
            'withdraw_money_sum'=>0.000,
            'self_rebate_money'=>0.000,
            //'agent_salary'=>0.000,
            //'agent_fenhong'=>0.000
        ];
        $user_data = $this->core->redis_hgetall(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $uid);
        $data = array_merge($init_data,$user_data);
        $junior_num = count($junior);
        array_push($junior, $uid);
        $team_uids = $junior;
        $team_balance = $this->core->db->where_in('id',$team_uids)->select('sum(balance) as team_balance')->from('user')->limit(1)->get()->row_array();
        $team_balance = $team_balance['team_balance'];
        $today_report = [
            'bet_money' => $data['bet_money_sum'],
            'prize_money' => $data['prize_money_sum'],
            'gift_money' => $data['gift_money_sum'],
            'team_rebates' => $data['rebate_money_sum'],
            'bet_num' => $data['bet_num'],
            'first_charge_num' => $data['first_charge_num'],
            'register_num' => $data['register_num'],
            'charge_money' => $data['charge_money_sum'],
            'withdraw_money' => $data['withdraw_money_sum'],
            'agent_rebates' => $data['self_rebate_money'],
            'junior_num' => $junior_num,
            'team_balance' => $team_balance,
            'team_profit' => round(floatval( $data['prize_money_sum']+$data['gift_money_sum']+$data['rebate_money_sum']-$data['bet_money_sum']),3),
            'agent_salary' => 0.000,
            'agent_fenhong' => 0.000
        ];
        if ($return) {
            $this->_format_report($today_report);
            return $today_report;
        }
        $this->_format_report($today_report);
        $this->return_json(OK, $today_report);
    }

    /*
     * 代理报表 昨日
     */
    public function yesterday_report()
    {
        $yesterday = date('Y-m-d',strtotime('-1 day'));
        $this->core->select_db('privite');
        $this->_query_agent($uid,$junior,true);
        $data = $this->core->redis_hgetall(TOKEN_CODE_AGENT . ':report:' . $yesterday . ':' . $uid);
        if (empty($data)) {
            $report = $this->core->get_one('bet_money,prize_money,gift_money,team_rebates,team_profit,bet_num,first_charge_num,register_num,junior_num,team_balance,charge_money,withdraw_money,agent_rebates,agent_salary,agent_fenhong' ,'agent_report_day',['report_date'=>$yesterday,'agent_id'=>$uid]);
        } else {
            $report = [
                'bet_money' => $data['bet_money_sum'],
                'prize_money' => $data['prize_money_sum'],
                'gift_money' => $data['gift_money_sum'],
                'team_rebates' => $data['rebate_money_sum'],
                'bet_num' => $data['bet_num'],
                'first_charge_num' => $data['first_charge_num'],
                'register_num' => $data['register_num'],
                'charge_money' => $data['charge_money_sum'],
                'withdraw_money' => $data['withdraw_money_sum'],
                'agent_rebates' => $data['self_rebate_money'],
                'junior_num' => 0,
                'team_balance' => 0,
                'team_profit' => round(floatval( $data['prize_money_sum']+$data['gift_money_sum']+$data['rebate_money_sum']-$data['bet_money_sum']),3),
                'agent_salary' => 0.000,
                'agent_fenhong' => 0.000
            ];
        }
        $junior_num = count($junior);
        array_push($junior, $uid);
        $team_uids = $junior;
        $team = $this->core->get_one('SUM(balance) AS balance', 'user',['wherein'=>['id' => $team_uids]]);
        $team_balance = $team['balance'];
        $yesterday_report = [
            'bet_money' => isset($report['bet_money'])?$report['bet_money']:0.000,
            'prize_money' => isset($report['prize_money'])?$report['prize_money']:0.000,
            'gift_money' => isset($report['gift_money'])?$report['gift_money']:0.000,
            'team_rebates' => isset($report['team_rebates'])?$report['team_rebates']:0.000,
            'team_profit' => isset($report['team_profit'])?$report['team_profit']:0.000,
            'bet_num' => isset($report['bet_num'])?$report['bet_num']:0,
            'first_charge_num' => isset($report['first_charge_num'])?$report['first_charge_num']:0,
            'register_num' => isset($report['register_num'])?$report['register_num']:0,
            'junior_num' => $junior_num,
            'team_balance' => $team_balance,
            'charge_money' => isset($report['charge_money'])?$report['charge_money']:0.000,
            'withdraw_money' => isset($report['withdraw_money'])?$report['withdraw_money']:0.000,
            'agent_rebates' => isset($report['agent_rebates'])?$report['agent_rebates']:0.000,
            'agent_salary' => isset($report['agent_salary'])?$report['agent_salary']:0.000,
            'agent_fenhong' => isset($report['agent_fenhong'])?$report['agent_fenhong']:0.000,
        ];
        //$yesterday_report['team_profit'] = round(floatval( $yesterday_report['prize_money']+$yesterday_report['gift_money']+$yesterday_report['team_rebates']-$yesterday_report['bet_money']),3);
        $this->_format_report($yesterday_report);
        $this->return_json(OK,$yesterday_report);
    }


    /*
     * 当月代理报表
     */
    public function cur_month_report()
    {
        //当月截至到昨天的，加上今天的
        $this->core->select_db('privite');
        $this->_query_agent($uid,$junior,true);
        $today_report = $this->_today_report($uid,$junior,true);
        if (date('d') == '01') {
            $this->return_json(OK,$today_report);
        }
        array_push($junior, $uid);
        $team_uids = $junior;
        $yesterday = date('Y-m-d',strtotime('-1 day'));
        //.判断条件加了日期
        $history_report = $this->core->get_one('bet_money,prize_money,gift_money,team_rebates,team_profit,bet_num,first_charge_num,register_num,charge_money,withdraw_money,agent_rebates,agent_salary,agent_fenhong' ,'agent_report_month',['agent_id'=>$uid,'report_month>='=>date('Y-m-01')],['orderby'=>['report_month'=>'desc']]);
        $arr = ['bet_money','prize_money','gift_money','team_rebates','team_profit','first_charge_num','register_num','charge_money','withdraw_money','agent_rebates','agent_salary','agent_fenhong'];
        foreach ($arr as $k) {
            if ( !isset($history_report[$k]) ) {
                $history_report[$k] = 0;
            }
            $report[$k] = $history_report[$k] + $today_report[$k];
        }
        $report['junior_num'] = $today_report['junior_num'];
        $report['team_balance'] = $today_report['team_balance'];
        $bet_num = $this->core->get_list('DISTINCT(uid)','report',['valid_price >'=>0,'report_date >= '=>date('Y-m-01')],['wherein'=>['uid'=>$team_uids]]);
        $report['bet_num'] = count($bet_num);
        $this->_format_report($report);
        $this->return_json(OK,$report);
    }
    /*
     * 上月代理报表
     */
    public function last_month_report()
    {

        $month = (int)date("n");
        if ($month === 1) {
            $month = 12;
        } else {
            $month = $month - 1;
        }
        $this->core->select_db('privite');
        $this->_query_agent($uid,$junior,true);
        $report = $this->core->get_one('bet_money,prize_money,gift_money,team_rebates,team_profit,bet_num,first_charge_num,register_num,junior_num,team_balance,charge_money,withdraw_money,agent_rebates,agent_salary,agent_fenhong,report_month' ,'agent_report_month',['MONTH(`report_month`)'=>$month,'agent_id'=>$uid],['orderby'=>['DAY (`report_month`)'=>'DESC']]);
        $junior_num = count($junior);
        array_push($junior, $uid);
        $team_uids = $junior;
        $team = $this->core->get_one('SUM(balance) AS balance', 'user', ['wherein'=>['id' => $team_uids]]);
        $team_balance = $team['balance'];
        $last_month_report = [
            'bet_money' => isset($report['bet_money'])?$report['bet_money']:0.000,
            'prize_money' => isset($report['prize_money'])?$report['prize_money']:0.000,
            'gift_money' => isset($report['gift_money'])?$report['gift_money']:0.000,
            'team_rebates' => isset($report['team_rebates'])?$report['team_rebates']:0.000,
            'team_profit' => isset($report['team_profit'])?$report['team_profit']:0.000,
            'bet_num' => isset($report['bet_num'])?$report['bet_num']:0,
            'first_charge_num' => isset($report['first_charge_num'])?$report['first_charge_num']:0,
            'register_num' => isset($report['register_num'])?$report['register_num']:0,
            'junior_num' => $junior_num,
            'team_balance' => $team_balance,
            'charge_money' => isset($report['charge_money'])?$report['charge_money']:0.000,
            'withdraw_money' => isset($report['withdraw_money'])?$report['withdraw_money']:0.000,
            'agent_rebates' => isset($report['agent_rebates'])?$report['agent_rebates']:0.000,
            'agent_salary' => 0.000,
            'agent_fenhong' => 0.000
        ];
        //$yesterday_report['team_profit'] = round(floatval( $yesterday_report['prize_money']+$yesterday_report['gift_money']+$yesterday_report['team_rebates']-$yesterday_report['bet_money']),3);
        $this->_format_report($last_month_report);
        $this->return_json(OK,$last_month_report);
    }

    /*
     * 下级报表 今日
     */
    public function junior_report_today()
    {
        $uid = empty($this->P('uid')) ? $this->user['id'] : (int)$this->P('uid');
        if (empty($uid)) {
            $this->return_json(E_ARGS,"无效的参数");
        }
        $son_member = $this->core->get_list('a.username,a.id,b.level,a.type','user',['agent_id'=>$uid],['join'=>'agent_line','on'=>'a.id=b.uid']);
        if (empty($son_member)) {
            $this->return_json(OK,[]);
        }
        $data = [];
        $today = date('Y-m-d');
        foreach ($son_member as $son) {
            $temp = [];
            $son_data = $this->core->redis_hmget(TOKEN_CODE_AGENT .':report:'.$today.':'. $son['id'],['bet_num','bet_money_sum','prize_money_sum','gift_money_sum','rebate_money_sum']);
            if ($son_data && $son_data['bet_num'] > 0){
                $temp['bet_num'] = $son_data['bet_num'];
                $temp['bet_money'] = $son_data['bet_money_sum'];
                $temp['prize_money'] = $son_data['prize_money_sum'];
                $temp['gift_money'] = $son_data['gift_money_sum'];
                $temp['team_rebates'] = $son_data['rebate_money_sum'];
                $temp['team_profit'] = round(floatval($temp['prize_money']+$temp['gift_money']+$temp['team_rebates']-$temp['bet_money']),3);
                $temp['username'] = $son['username'];
                $temp['level'] = $son['level'];
                $temp['type'] = $son['type'];
                $temp['uid'] = $son['id'];
                $data[] = $temp;
            }
        }
        $this->format_junior_report($data);
        $this->return_json(OK,$data);
    }

    /*
     * 下级报表 昨日
     */
    public function junior_report_yesterday()
    {
        $uid = empty($this->P('uid')) ? $this->user['id'] : (int)$this->P('uid');
        if (empty($uid)) {
            $this->return_json(E_ARGS,"无效的参数");
        }
        $yesterday = date('Y-m-d',strtotime('-1 day'));
        $condition = [
            'join'=>[
                ['table'=>'agent_report_day as b','on'=>'b.agent_id=a.id AND b.report_date='."'$yesterday'"],
                ['table'=>'agent_line as c','on'=>'c.uid=a.id']
            ]
        ];
        $son_member = $this->core->get_list('a.username,a.id as uid,c.level,a.type,b.bet_num,b.bet_money,b.prize_money,b.gift_money,b.team_rebates,b.team_profit','user',['a.agent_id'=>$uid],$condition);
        if (empty($son_member)) {
            $this->return_json(OK,[]);
        }
        $data = [];
        foreach ($son_member as $son) {
            if ($son['bet_num'] > 0) {
                $data[] = $son;
            }
        }
        $this->format_junior_report($data);
        $this->return_json(OK,$data);
    }

    /*
     * 下级报表 本月
     */
    public function junior_report_cur_month()
    {
        if (date('d') === '01') {
            $this->junior_report_today();
        }
        $uid = empty($this->P('uid')) ? $this->user['id'] : (int)$this->P('uid');
        if (empty($uid)) {
            $this->return_json(E_ARGS,"无效的参数");
        }
        $yesterday = date('Y-m-d',strtotime('-1 day'));
        $condition = [
            'join'=>[
                ['table'=>'agent_report_month as b','on'=>'b.agent_id=a.id and b.report_month='."'$yesterday'"],
                ['table'=>'agent_line as c','on'=>'c.uid=a.id'],
            ]
        ];
        $son_member = $this->core->get_list('a.username,a.id,c.level,a.type,b.bet_num,b.bet_money,b.prize_money,b.gift_money,b.team_rebates,b.team_profit','user',['a.agent_id'=>$uid],$condition);
        if (empty($son_member)) {
            $this->return_json(OK,[]);
        }

        // 统计投注人数
        $bet_uids = $this->core->db->select('DISTINCT(a.uid) as uid')
            ->from('report as a')
            ->join('agent_tree as b',"a.uid=b.descendant and b.ancestor=".$uid,'inner')
            ->where(['a.valid_price >'=>0,'a.report_date >= '=>date('Y-m-01')])
            ->get()
            ->result_array();
        $bet_uids = array_column($bet_uids,'uid');

        // 统计下级uid
        $guids = $this->core->db->select('a.ancestor as agent_id,a.descendant as uid')
            ->from('agent_tree as a')
            ->join('user as b',"a.ancestor=b.id",'inner')
            ->where('b.agent_id='.$uid)
            ->get()
            ->result_array();
        $agents_arr = [];
        foreach ($guids as $item) {
            if (isset($agents_arr[$item['agent_id']])) {
                $agents_arr[$item['agent_id']][] = $item['uid'];
            } else {
                $agents_arr[$item['agent_id']] = [$item['uid']];
            }
        }
        $data = [];
        $today = date('Y-m-d');
        foreach ($son_member as $son) {
            $team_ids = isset($agents_arr[$son['id']])?$agents_arr[$son['id']]:[];
            array_push($team_ids,$son['id']);
            $bets = array_intersect($bet_uids,$team_ids);
            $bet_num = count($bets);
            if ($bet_num === 0) {
                continue;
            }
            $temp = [
                'bet_num'=>$bet_num,
                'bet_money'=>$son['bet_money'],
                'prize_money'=>$son['prize_money'],
                'gift_money'=>$son['gift_money'],
                'team_rebates'=>$son['team_rebates'],
                'username'=>$son['username'],
                'level'=>$son['level'],
                'type'=>$son['type'],
                'uid'=>$son['id'],
            ];
            $son_data = $this->core->redis_hmget(TOKEN_CODE_AGENT .':report:'.$today.':'. $son['id'],['bet_money_sum','prize_money_sum','gift_money_sum','rebate_money_sum']);
            if ($son_data){
                $temp['bet_money'] += $son_data['bet_money_sum'];
                $temp['prize_money'] += $son_data['prize_money_sum'];
                $temp['gift_money'] += $son_data['gift_money_sum'];
                $temp['team_rebates'] += $son_data['rebate_money_sum'];
            }
            $temp['team_profit'] = round(floatval($temp['prize_money']+$temp['gift_money']+$temp['team_rebates']-$temp['bet_money']),3);
            $data[] = $temp;
        }
        $this->format_junior_report($data);
        $this->return_json(OK,$data);
    }

    /*
     * 下级报表 上月
     */
    public function junior_report_last_month()
    {
        $uid = empty($this->P('uid')) ? $this->user['id'] : (int)$this->P('uid');
        if (empty($uid)) {
            $this->return_json(E_ARGS,"无效的参数");
        }
        $day = date("Y-m-d",strtotime("-1 day",strtotime(date("Y-m-01"))));
        $condition = [
            'join'=>[
                ['table'=>'agent_report_month as b','on'=>'b.agent_id=a.id and b.report_month='."'$day'"],
                ['table'=>'agent_line as c','on'=>'c.uid=a.id'],
            ]
        ];
        $son_member = $this->core->get_list('a.username,a.id as uid,c.level,a.type,b.bet_num,b.bet_money,b.prize_money,b.gift_money,b.team_rebates,b.team_profit','user',['a.agent_id'=>$uid],$condition);
        if (empty($son_member)) {
            $this->return_json(OK,[]);
        }
        $data = [];
        foreach ($son_member as $son) {
            if ($son['bet_num'] > 0){
                $data[] = $son;
            }
        }
        $this->format_junior_report($data);
        $this->return_json(OK,$data);
    }

    /*
     * 下级报表格式化
     */
    private function format_junior_report(&$report)
    {
        $arr = ['bet_money','prize_money','gift_money','team_rebates','team_profit'];
        foreach ($report as &$item) {
            foreach ($arr as $key) {
                if (!isset($item[$key])) {
                    $item[$key] = 0.000;
                }
                $item[$key] = sprintf('%.2f',round(floatval($item[$key]),2));
            }
        }
        $sort = $this->P('sort');
        if ($sort && isset($report[0][$sort])) {
            $order = $this->P('order') ? $this->P('order') : 'desc';
            $order = $order==='desc';
            sortArrByField($report,$sort,$order);
        } else {
            sortArrByField($report,'bet_money',true);
        }
    }

    private function _prase_param(&$where,&$condition)
    {
        $where = $condition = [];
        $this->_query_agent($uid,$junior,false);
        if ($uid !== $this->user['id']) {
            $where['a.uid'] = $uid;
        } else {
            if (empty($junior)) {
                $this->return_json(OK,[]);
            }
            $condition['wherein'] = ['a.uid'=>$junior];
        }
        $between_day = (int)$this->P('between_day');
        $today = strtotime(date('Y-m-d'));
        if ($between_day === 0) {
            $where['a.addtime >='] = $today;
        } elseif ($between_day === 1) {
            $where['a.addtime >='] = $today-3600*24;
            $where['a.addtime <'] = $today;
        } elseif ($between_day === 7) {
            $where['a.addtime >='] = $today-3600*24*7;
        }
        $num = (int)$this->P('num');
        $index = (int)$this->P('index');
        $num = $num ? $num : 10;
        $index = $index ? $index : 0;
        $condition['page_limit'] = [$num,$index];
    }
    /*
     * 代理交易明细-》账户明细
     */
    public function transaction_detail()
    {
        $this->_prase_param($where,$condition);
        $type = (int)$this->P('type');
        if ( $type !== 0 ) {
            $where['a.type'] = $type;
        }
        $fields = 'a.uid,a.addtime,a.remark,a.amount,a.balance,b.username,c.name as type,c.cash_category as category';
        $table = 'cash_list';
        $condition['join'] = [
            ['table'=>'user as b','on'=>'a.uid=b.id'],
            ['table'=>'cash_type as c','on'=>'c.id=a.type']
            ];
        $condition['orderby'] = [
            'a.addtime'=>'desc'
        ];
        $res = $this->core->get_list($fields,$table,$where,$condition);
        foreach ($res as &$item) {
            $item['amount'] = sprintf('%.2f',round(floatval( $item['amount']),2));
            $item['balance'] = sprintf('%.2f',round(floatval( $item['balance']),2));
            //$item['addtime'] = date('Y-m-d H:i:s',$item['addtime']);
        }
        $this->return_json(OK,$res);
    }

    /*
     * 代理交易明细-》提现记录
     */
    public function withdraw_records()
    {
        $this->_prase_param($where,$condition);
        $list = $this->core->get_withdraw_list($where,$condition);
        if ($list !== false) {
            $this->return_json(OK,$list);
        } else {
            $this->return_json(E_OP_FAIL);
        }
    }

    /*
     * 代理交易明细-》充值记录
     */
    public function charge_records()
    {
        $this->_prase_param($where,$condition);
        $list = $this->core->get_charge_list($where,$condition);
        if ($list !== false) {
            $this->return_json(OK,$list);
        } else {
            $this->return_json(E_OP_FAIL);
        }
    }

}
