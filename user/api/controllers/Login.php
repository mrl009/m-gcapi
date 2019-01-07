<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
    }


    /**
     * 获取token的键值
     * @auther frank
     * @return $string  $token值
     **/
    public function get_token_private_key()
    {
        $result['token_private_key'] = md5(uniqid());
        $this->M->redisP_set('token_private_key:'.TOKEN_CODE_USER.':'.$result['token_private_key'], $_SERVER['REQUEST_TIME']);
        $this->M->redisP_expire('token_private_key:'.TOKEN_CODE_USER.':'.$result['token_private_key'], TOKEN_PRIVATE_KEY_TIME);
        $this->return_json(OK, $result);
    }

    /**
     * 获取试玩账号 和 默认试玩邀请码
     * @return username e.g. Guest01
     * @return invite_code
     */
    public function get_demo_account()
    {
        $demo_user = 'guest';
        $num = $this->M->db->query("SELECT COUNT(id) AS num FROM gc_user WHERE status=4")->row_array();
        if ($num['num'] < 1) {
            $this->return_json(E_DATA_INVALID,'缺少顶级试玩代理');
        }
        $sql = "SELECT a.invite_code FROM gc_agent_code a INNER JOIN gc_user b ON a.uid = b.id WHERE b.status=4 AND a.is_delete=0 ORDER BY b.id ASC ,a.id ASC LIMIT 1";
        $code = $this->M->db->query($sql)->row_array();
        if (empty($code)) {
            $this->return_json(E_DATA_INVALID,'缺少试玩邀请码');
        }
        $num = $num['num'] + 1;
        $username = $demo_user . ($num > 9 ? $num : '0'.$num);
        $this->return_json(OK,['invite_code'=>$code['invite_code'],'username'=>$username]);
    }

    /**
     * 获取token
     * @auther frank
     * @return $string  $token值
     **/
    public function token()
    {
        $rule = array(
            'username'  => 'require|myuser',
            'pwd'       => 'require',
        );
        $msg = array(
            'username.require' => '用户名不能为空',
            'username.myuser' =>  '用户名字母和数字,特殊字符除外',
            'pwd.require' => '密码不能为空'
        );
        $username = strtolower($this->P('username'));
        $pwd = $this->P('pwd');
        $data = array(
            'username'  => $username,
            'pwd'      => $pwd,
        );
        $ip = get_ip();
        $rkBlackIp = 'black_ip:'.$ip;// 限制IP登陆
        $this->load->model('Login_model');
        $this->M->redis_select(7);
        $times = $this->M->redis_get($rkBlackIp);// 获取该错误次数
        $this->M->redis_expire($rkBlackIp, IP_EXPIRE);
        $this->M->redis_select(5);

        //8.12 登录token更改
        $auto_login = false;
        if (!empty($this->P('auto_login'))) {
            $str = "username={$username}&pwd=$pwd";
            if ($this->P('auto_login') == md5($str)) {
                $auto_login=true;
            }
        }
        if (!$auto_login) {
            if ($times >= CODE_IP_TIMES || !empty($this->P('code'))) {
                $token_private_key = $this->P('token_private_key');
                $data['token_private_key'] = $token_private_key;
                if (!$this->M->redisP_get('token_private_key:'.TOKEN_CODE_USER.':'.$token_private_key)) {
                    $this->return_json(E_YZM_CHECK, '刷新重试');
                }
                if ($_SERVER['REQUEST_TIME'] - $this->M->redis_get($token_private_key)<TOKEN_PRIVATE_KEY_CHECK_MIN_TIME) {
                    $this->return_json(E_ARGS, '获取token的键值验证太快');
                }
                //增加验证码
                $rule['token_private_key']='require';
                $rule['code'] = 'require';
                $msg['code.require'] = '验证码不能为空';
                $msg['token_private_key.require'] = 'token键值不能为空，不能为空';
                $code = $this->Login_model->get_or_set_code($token_private_key);
                if (empty($code) || strtolower($this->P('code'))!=$code) {
                    $this->return_json(E_YZM_CHECK, '验证码出错');
                }
                //8.28 IP 黑名单加回
                if ($times>=BLACK_IP_TIMES) {
                    $this->return_json(BLACK_IP, 'IP列入黑名单');
                }
                $data['code'] = $code;
                $this->Login_model->del_code($token_private_key);
            }
        }

        $this->validate->rule($rule, $msg);
        if (!$this->validate->check($data)) {
            $this->return_json(E_ARGS, $this->validate->getError());
        }

        $this->load->helper('common_helper');
        $userData = $this->Login_model->get_one_user($username);
        if (empty($userData)) {
            $this->M->redis_select(7);
            $this->M->redis_INCR($rkBlackIp, 1);// 记录用户错误数
            $this->return_json(E_ARGS, '用户不存在');
        } elseif ($userData['status']==2) {
            $this->M->redis_select(7);
            $this->M->redis_INCR($rkBlackIp, 1);// 记录用户错误数
            $this->return_json(E_ARGS, '用户被停用');
        }
        $loginData['url'] = $_SERVER['HTTP_HOST'];
        $rs = explode(';', $_SERVER['HTTP_AUTHGC']);
        $loginData['gcurl'] = isset($rs[0]) ? $rs[0] : '';
        $loginData['uid'] = $userData['id'];
        $loginData['from_way'] = $this->from_way;
        $loginData['login_time'] = $_SERVER['REQUEST_TIME'];
        $loginData['ip'] = $ip;
        $rkAdminErrorPwd  = 'user:error:password:'.$userData['id'];
        $updateData['update_time'] = $_SERVER['REQUEST_TIME'];
        $updateData['loginip'] = $ip;
        $updateWhere['id'] = $userData['id'];
        if ($userData['pwd']!=user_md5($pwd)) {

            $loginData['content'] = '自动登录失败';
            $loginData['is_success'] = 2;
            //$this->Login_model->login_record($loginData);
            if ($auto_login) {
                $this->return_json(E_ARGS, "自动登录失败");
            }
            $loginData['content'] = '密码错误';
            $loginData['is_success'] = 2;
            $this->Login_model->login_record($loginData);
            $this->M->redis_select(7);
            $this->M->redis_INCR($rkBlackIp, 1);// 记录用户错误数
            $this->M->redis_select(5);

            $loginErrorTimes = $this->M->redis_INCR($rkAdminErrorPwd, 1);// 记录用户错误数
            if ($loginErrorTimes >= USER_PWD_ERROR_AND_LOCK) {
                $this->M->redis_del($rkAdminErrorPwd);
                $updateData['status'] = 2;
                $this->Login_model->user_update($updateData, $updateWhere);
                $this->return_json(E_ARGS, "账户停用");

            }
            $num  = USER_PWD_ERROR_AND_LOCK;
            $num  = $num - $loginErrorTimes;
            $this->return_json(E_ARGS, "密码错误剩余错误次数$num");
        }
        $userData['login_time'] = $_SERVER['REQUEST_TIME'];
        $token = $this->Login_model->get_token($userData);
        $result['token'] = $token;
        $this->M->redis_select(7);
        $this->M->redis_del($rkBlackIp);// 登陆成功，清除IP错误记录
        $this->M->redis_select(5);
        $this->M->redis_del($rkAdminErrorPwd);// 登陆成功，清除用户错误次数
        $loginData['content'] = '登陆成功';
        if ($auto_login) {
            $loginData['content'] = '自动登录成功';
        }
        $loginData['is_success'] = 1;
        $this->Login_model->login_record($loginData);
        $this->Login_model->user_update($updateData, $updateWhere);
        $result['username'] = $userData['username'];
        $result['type'] = $userData['type'];
        $result['status'] = $userData['status'];

        $this->return_json(OK, $result);
    }


    /**
     * 登陆是否需要验证码
     * @auther frankxx`
     * @return $int
     **/
    public function code()
    {
        $rule = array(
            'token_private_key'  => 'require',
        );
        $msg = array(
            'token_private_key.require' => '请刷新重试',
        );
        $token_private_key = $this->G('token_private_key');
        $data = array(
            'token_private_key'  => $token_private_key,
        );
        $this->validate->rule($rule, $msg);
        if (!$this->validate->check($data)) {
            $this->return_json(E_ARGS, $this->validate->getError());
        }
        if (!$this->M->redisP_get('token_private_key:'.TOKEN_CODE_USER.':'.$token_private_key)) {
            $this->return_json(E_ARGS, '请刷新重试');
        }
        $this->load->library('code');
        $randcode=$this->code->getCode();
        $randcode = strtolower($randcode);
        $this->load->model('Login_model');
        $this->Login_model->get_or_set_code($token_private_key, $randcode);
        $this->code->outPut();
    }

    /**
     * 退出
     * @auther frank
     * @return $int
     **/
    public function logout()
    {
        $token = $this->_token;
        if (empty($token) || !isset($token{10})) {
            $this->return_json(E_ARGS, '参数出错token');
        }
        $rkTokenKey = 'token:'.TOKEN_CODE_USER.':'.$token;
        $jsondata = $this->M->redis_get($rkTokenKey);
        if (empty($jsondata)) {
            $this->return_json(LOGOUT, '已经退出');
        }
        $data = json_decode($jsondata, true);
        $rkIDToToken = 'token_ID:'.TOKEN_CODE_USER.':'.$data['id'];
        $this->M->redis_del($rkTokenKey);
        $this->M->redis_del($rkIDToToken);
        $this->return_json(LOGOUT, '退出成功');
    }


    public function is_user_add()
    {
        $data = $this->M->get_gcset();
        if ($data['register_is_open'] == 1) {
            $this->return_json(OK, ['open'=> 1]);
        } else {
            $this->return_json(OK, ['open'=> 0]);
        }
    }

    /**
     *  添加会员
     *  注册送钱没添加
     *
     */
    public function user_add()
    {
        $this->load->model('user/User_model');
        $this->load->model('Login_model');
        $user_add_keys = 'user:add_ip:';//用户注册的ip
        $data = [
            'username' => strtolower($this->P('username')),//用户名
            'pwd'      => $this->P('pwd'),//密码
            'from_way' => $this->from_way,//从哪里注册而来，1：ios，2：android，3：PC
        ];
        $gcSet = $this->M->get_gcset();
        //验证码
        $token_private_key = $this->P('token_private_key');
        $yzmx      = $this->Login_model->get_or_set_code($token_private_key);
        $yzm       =  $this->P('yzm');//验证码
        if($gcSet['register_open_verificationcode']==1){
            if (empty($yzmx)) {
                $this->Login_model->del_code($token_private_key);
                $this->return_json(E_ARGS, '验证码错误');
            }
            if (strtolower($yzm) !== strtolower($yzmx) || empty($yzm)) {
                $this->Login_model->del_code($token_private_key);
                $this->return_json(E_ARGS, '验证码错误');
            }
        }

        //真實姓名
        if($gcSet['register_open_username']==1){
            $data['bank_name'] = $this->P('bank_name');
            if (empty($data['bank_name'])) {
                $this->return_json(E_ARGS, '真實姓名錯誤');
            }
        }

        // $this->Login_model->del_code($token_private_key);
        $addip = get_ip();
        wlog(APPPATH.'logs/user_add_.'.$this->Login_model->sn.'._'.date('Ym').'.log', "$addip 会员名{$data['username']} 提交参数:".json_encode($_REQUEST));
        if (filter_var($addip, FILTER_VALIDATE_IP) == false) {
            $this->return_json(E_ARGS, '获取ip失败请更换网络环境1');
        }
        /**判断站点是否开启注册**/
        if ($gcSet['register_is_open'] != 1) {
            $this->return_json(E_ARGS, '本站未开启注册');
        }

        $userIp = $this->P('ip');

        /* edit by wuya iOS、Android、未知来源需要POST IP */
        if (empty($userIp) && in_array($this->from_way,[1,2,5])) {
            $this->return_json(E_ARGS, '缺少IP地址参数');
        }

        if (!empty($gcSet['add_ip_check'])) {
            if ($userIp != $addip) {
                //$this->return_json(E_ARGS,$userIp.'地址获取错误请更换网络环境3'.$addip);
            }
        }

        /**判断ip限制**/
        if (!empty($gcSet['register_num_ip'])) {
            $this->M->redis_select(7);
            $ipNum =  $this->M->redis_get($user_add_keys.$addip);
            $this->M->redis_select(5);
            if ($ipNum >= $gcSet['register_num_ip']) {
                $this->return_json(E_ARGS, 'ip限制');
            }
        }
        //新增 用户默认出款次数上限设置
        $out_num = isset($gcSet['default_out_num']) ? $gcSet['default_out_num'] : 0;
        $datax = [
            'level_id' => 1,   //层级id
            'balance'  => '0',   //额度
            'addtime'  => time(),//注册时间
            'update_time' => time(),//最后登录时间
            'status'   => 1,//1：正常，2：停用，
            'loginip'  => $addip,//最后登录ip
            'is_level_lock'  => 0,//是否锁定该层级，0：未锁定，1：已锁定
            'login_times'  => 1    ,//是否锁定该层级，0：未锁定，1：已锁定
            'is_card'  => 1,//点卡充值
            'type'  => 1,//
            'out_num' => $out_num
        ];
        //todo 代理添加
        $agent_id = 0;
        $agentId = (int)$this->P('agent_id');
        if (!empty($agentId) || !empty($gcSet['is_agent'])) {
            if (empty($agentId) && $gcSet['is_agent'] == 2) {
                $this->return_json(E_ARGS,'请输入邀请码');
            }
            $agentData = $this->M->get_one('id', 'user', ['id'=>$agentId,'type'=>2]);
            if (empty($agentData) && $gcSet['is_agent'] == 2) {
                $this->return_json(E_ARGS,'请填入正确的邀请码');
            }
            if ($agentData) {
                $datax['agent_id'] = $agentId;
                $agent_id = $agentId;
            }
        }

        $this->load->helper('common_helper');
        $scene = $gcSet['register_open_username'] == 1 ? 'bank_name' : '';
        if ($this->check_user($data, $scene)) {
            $data['pwd']   = user_md5($data['pwd']);
            $where =[];
            $bool = $this->User_model->get_one('id', 'user', ['username'=>$data['username']]);
            if ($bool) {
                $this->return_json(E_ARGS, '用户名已注册请更换');
            }
            $data = array_merge($data, $datax);

            $this->M->redis_select(7);
            $this->M->redis_INCRBY($user_add_keys.$addip, 1);
            $this->M->redis_expire($user_add_keys.$addip, IP_EXPIRE);
            $this->M->redis_select(5);
            $uid = $this->User_model->user_add($data, $addip);
            if ($uid) {
                $loginData['url'] = $_SERVER['HTTP_HOST'];
                $loginData['uid'] = $uid;
                $loginData['from_way'] = $this->from_way;
                $loginData['login_time'] = $_SERVER['REQUEST_TIME'];
                $loginData['ip'] = $addip;
                $loginData['content'] = '登陆成功';
                $loginData['is_success'] = 1;
                $this->M->write('log_user_login', $loginData);

                /**判断注册送优惠**/
                $this->User_model->zhuceyouohui($uid, $data['username'], false,$agent_id);
                $d =['id'=>$uid,'username' =>  $data['username'] ,'agent_id'=>$agent_id , 'level_id' => 1,'vip_id' => 1];
                $userData = $this->Login_model->get_token($d);
                //todo 会员缓存添加
                $this->Login_model->user_cache($uid, $d, false);
                $this->return_json(OK, ['token'=>$userData]);
            } else {
                $this->return_json(E_ARGS);
            }
        }
    }

    /**
     * 用户注册
     * @time 2018/04/04
     */
    public function user_register()
    {
        $this->load->model('user/User_model');
        $this->load->model('Login_model');
        $user_add_keys = 'user:add_ip:';//用户注册的ip
        $demouser_add_keys = 'user:demo:add_ip:';//试玩用户注册的ip
        $data = [
            'username' => strtolower($this->P('username')),//用户名
            'pwd'      => $this->P('pwd'),//密码
            'from_way' => $this->from_way,//从哪里注册而来，1：ios，2：android，3：PC
        ];
        //验证码
        $token_private_key = $this->P('token_private_key');
        $yzmx      = $this->Login_model->get_or_set_code($token_private_key);
        $yzm       =  $this->P('yzm');//验证码
        $invite_code = $this->P('invite_code');//邀请码
        if (empty($invite_code)) {
            $this->return_json(E_ARGS,'请输入邀请码');
        }
        $code_data = $this->M->db->select('a.uid,a.invite_code,a.junior_type,a.rebate,a.level,b.status')
            ->from('agent_code as a')
            ->join('user as b','a.uid=b.id','inner')
            ->where(['a.invite_code'=>$invite_code,'a.is_delete'=>0])
            ->limit(1)
            ->get()->row_array();
        if (empty($code_data)) {
            $this->return_json(E_ARGS,'请填入正确的邀请码');
        }

        $gcSet = $this->M->get_gcset();
        if($gcSet['register_open_verificationcode']==1 && $code_data['status'] != 4){
            if (empty($yzmx)) {
                $this->Login_model->del_code($token_private_key);
                $this->return_json(E_ARGS, '验证码过期');
            }
            if (strtolower($yzm) !== strtolower($yzmx) || empty($yzm)) {
                $this->Login_model->del_code($token_private_key);
                $this->return_json(E_ARGS, '验证码错误');
            }
        }

        //真實姓名
        if($gcSet['register_open_username']==1 && $code_data['status'] != 4){
            $data['bank_name'] = $this->P('bank_name');
            if (empty($data['bank_name'])) {
                $this->return_json(E_ARGS, '真實姓名錯誤');
            }
            //真實姓名唯一
            if ($gcSet['is_unique_name'] == 1) {
                $is_unique_name = $this->M->get_one('id', 'user_detail', ['bank_name' => $data['bank_name']]);
                if (!empty($is_unique_name)) {
                    $this->return_json(E_ARGS, '真實姓名重复');
                }
            }
        }

        $addip = get_ip();
        wlog(APPPATH.'logs/user_add_.'.$this->Login_model->sn.'._'.date('Ym').'.log', "$addip 会员名{$data['username']} 提交参数:".json_encode($_REQUEST));
        if (filter_var($addip, FILTER_VALIDATE_IP) == false) {
            $this->return_json(E_ARGS, '获取ip失败请更换网络环境1');
        }
        /**判断站点是否开启注册**/
        if ($gcSet['register_is_open'] != 1) {
            $this->return_json(E_ARGS, '本站未开启注册');
        }
        $userIp = $this->P('ip');

        /* edit by wuya iOS、Android、未知来源需要POST IP */
        if (empty($userIp) && in_array($this->from_way,[1,2,5])) {
            $this->return_json(E_ARGS, '缺少IP地址参数');
        }

        if (!empty($gcSet['add_ip_check'])) {
            if ($userIp != $addip) {
                //$this->return_json(E_ARGS,$userIp.'地址获取错误请更换网络环境3'.$addip);
            }
        }

        /**判断ip限制**/
        if (!empty($gcSet['register_num_ip'])) {
            $this->M->redis_select(7);
            $ipNum =  $this->M->redis_get($user_add_keys.$addip);
            $this->M->redis_select(5);
            if ($ipNum >= $gcSet['register_num_ip']) {
                $this->return_json(E_ARGS, 'ip限制');
            }
        }
        //新增 用户默认出款次数上限设置
        $out_num = isset($gcSet['default_out_num']) ? $gcSet['default_out_num'] : 0;
        $datax = [
            'level_id' => 1,   //层级id
            'balance'  => '0',   //额度
            'addtime'  => time(),//注册时间
            'update_time' => time(),//最后登录时间
            'status'   => 1,//1：正常，2：停用，3：锁定，4：试玩
            'loginip'  => $addip,//最后登录ip
            'is_level_lock'  => 0,//是否锁定该层级，0：未锁定，1：已锁定
            'login_times'  => 1    ,//是否锁定该层级，0：未锁定，1：已锁定
            'is_card'  => 1,//点卡充值
            'out_num' => $out_num
        ];
        $datax['agent_id'] = $agent_id = $code_data['uid'];
        $datax['type'] = $code_data['junior_type'];
        if ($code_data['status'] == 4) {
            $datax['status'] = 4;
            $datax['balance'] = 2000;
            /**判断试玩用户ip注册限制**/
            $this->M->redis_select(7);
            $ipNum =  $this->M->redis_get($demouser_add_keys.$addip);
            $this->M->redis_select(5);
            if ($ipNum >= 3) {
                $this->return_json(E_ARGS, '试玩用户IP注册数量达到限制');
            }
        }

        $this->load->helper('common_helper');
        $scene = $gcSet['register_open_username'] == 1 ? 'bank_name' : '';
        $scene = $datax['status'] == 4 ? '' : $scene;
        if ($this->check_user($data, $scene)) {
            $data['pwd']   = user_md5($data['pwd']);
            $bool = $this->User_model->get_one('id', 'user', ['username'=>$data['username']]);
            if ($bool) {
                $this->return_json(E_ARGS, '用户名已注册请更换');
            }
            $data = array_merge($data, $datax);
            $this->M->redis_select(7);
            $this->M->redis_INCRBY($user_add_keys.$addip, 1);
            $this->M->redis_INCRBY($demouser_add_keys.$addip, 1);
            $this->M->redis_expire($user_add_keys.$addip, IP_EXPIRE);
            $this->M->redis_select(5);
            $uid = $this->User_model->user_add($data, $addip);
            if ($uid) {
                $this->load->model('agent/Agent_code_model','invite_code');
                $this->load->model('agent/Agent_line_model','agent_line');
                //更新邀请码注册人数
                $this->invite_code->update_regist_num($invite_code);
                //写入代理线数据
                $this->agent_line->record($uid,$code_data);
                //更新代理金字塔表
                $this->agent_line->update_agent_tree($uid,$code_data['uid']);
                $loginData['url'] = $_SERVER['HTTP_HOST'];
                $loginData['uid'] = $uid;
                $loginData['from_way'] = $this->from_way;
                $loginData['login_time'] = $_SERVER['REQUEST_TIME'];
                $loginData['ip'] = $addip;
                $loginData['content'] = '登陆成功';
                $loginData['is_success'] = 1;
                $this->M->write('log_user_login', $loginData);

                /**判断注册送优惠**/ //todo ??? 跟邀请码有什么关系 ???
                if ($datax['status'] != 4) {
                    $this->User_model->zhuceyouohui($uid, $data['username'], false,$agent_id);
                }
                $d =['id'=>$uid,'username' =>  $data['username'] ,'agent_id'=>$agent_id , 'level_id' => 1,'vip_id' => 1,'type'=>$datax['type'] ? $datax['type'] : 1,'status'=>$datax['status']];
                $userData = $this->Login_model->get_token($d);
                //todo 会员缓存添加
                $this->Login_model->user_cache($uid, $d, false);
                $this->return_json(OK, ['token'=>$userData,'username'=>$d['username'],'type'=>$d['type'],'status'=>$d['status']]);
            } else {
                $this->return_json(E_ARGS);
            }
        }
    }

    /**
     * 用户资料验证
     * @param array $data 需要验证的参数
     * @param string $scene 验证场景
     * @return bool
     *
     */
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
        if ($scene == 'bank_name') {
            $rule['bank_name'] = 'require|chs_alpha';
            $msg['bank_name'] = '姓名只能为汉字和字母和·,不能·开头和结尾';
        }

        $this->validate->rule($rule, $msg);//验证数据
        $this->validate->scene('username', ['username']);
        $this->validate->scene('bank_name', ['username', 'bank_name', 'pwd']);
        if ($scene) {
            $result   = $this->validate->scene($scene)->check($data);
        } else {
            $result   = $this->validate->check($data);
        }

        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }
}
