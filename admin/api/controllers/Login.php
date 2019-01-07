<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Login extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
        $this->load->model('Login_model');
    }


    public function google_status()
    {
        $siteData = $this->M->get_gcset(['google_status']);
        $this->return_json(OK, $siteData);
    }

    /**
     * 获取token的键值
     * @auther frank
     * @return $string  $token值
     **/
    public function get_token_private_key()
    {
        $result['token_private_key'] = md5(uniqid());
        $this->M->redisP_set('token_private_key:'.TOKEN_CODE_ADMIN.':'.$result['token_private_key'], $_SERVER['REQUEST_TIME']);
        $this->M->redisP_expire('token_private_key:'.TOKEN_CODE_ADMIN.':'.$result['token_private_key'], TOKEN_PRIVATE_KEY_TIME);
        $this->return_json(OK, $result);
    }
    
    /**
     * 获取token
     * @auther frank
     * @return $string  $token值
     **/
    public function token()
    {
        $rule = array(
            'username'  => 'require',
            'pwd'   => 'require',
            'token_private_key'=>'require',
        );
        $msg = array(
            'username.require' => '用户名不能为空',
            'pwd.require' => '密码不能为空',
            'token_private_key.require' => 'token键值不能为空，不能为空'
        );
        $token_private_key = $this->G('token_private_key');
        $username = $this->G('username');
        $pwd = $this->G('pwd');
        $data = array(
            'username'  => $username,
            'pwd'   => $pwd,
            'token_private_key' =>$token_private_key,
        );
        if ($_SERVER['REQUEST_TIME'] - $this->M->redis_get($token_private_key)<TOKEN_PRIVATE_KEY_CHECK_MIN_TIME) {
            $this->return_json(E_ARGS, '获取token的键值验证太快');
        }
        $ip = long2ip(get_auth_headers('Authgci'));//get_ip();
        $rkBlackIp = 'admin_black_ip:'.$ip;// 限制IP登陆
        $siteData = $this->M->get_gcset(['access_ip']);
        if ($siteData['access_ip']!='*') {
            $accessIPData = explode(',', $siteData['access_ip']);
            if (empty($accessIPData)) {
                $loginData['content'] = '登陆失败，原因IP没有授权。客户端IP:'.$ip.',允许IP授权是空的';
                $loginData['is_success'] = 2;
                $loginData['login_time'] = time();
                $loginData['ip'] = $ip;
                $this->Login_model->login_record($loginData);
                $this->return_json(ADMIN_ACCESS_IP, 'IP必需授权,您的登陆信息将被我们详细记录!');
            }
            if (!in_array($ip, $accessIPData)) {
                //$loginData['content'] = '登陆失败，原因IP没有授权。客户端IP:'.$ip.',允许IP:'.implode(',', $accessIPData);
                $loginData['content'] = '登陆失败，原因IP没有授权。客户端IP:'.$ip.',登录用户:'.$username;
                $loginData['is_success'] = 2;
                $loginData['login_time'] = time();
                $loginData['ip'] = $ip;
                $this->Login_model->login_record($loginData);
                $this->return_json(ADMIN_ACCESS_IP, 'IP必需授权,您的登陆信息将被我们详细记录!');
            }
        }
        //$code = $this->Login_model->get_or_set_code($token_private_key);
        /*if(empty($code) || strtolower($this->G('code'))!=$code){
            $this->return_json(E_ARGS,'验证码出错');
        }*/
        $this->Login_model->del_code($token_private_key);

        $this->M->redis_select(7);
        $times = $this->M->redis_get($rkBlackIp);// 获取该错误次数
        $this->M->redis_expire($rkBlackIp,IP_EXPIRE);
        $this->M->redis_select(5);
        if ($times>=BLACK_IP_TIMES) {
            $this->return_json(BLACK_IP, 'IP列入黑名单,您的登陆信息将被我们详细记录!');
        }
        $this->validate->rule($rule, $msg);
        if (!$this->validate->check($data)) {
            $this->return_json(E_ARGS, $this->validate->getError());
        }
        $this->load->helper('common_helper');
        $adminData = $this->Login_model->get_one_admin($username);
        if (empty($adminData)) {
            $this->M->redis_select(7);
            $loginErrorTimes = $this->M->redis_INCR($rkBlackIp, 1);// 记录用户错误数
            $this->return_json(E_ARGS, '用户不存在,您的登陆信息将被我们详细记录!');
        }
        if ($adminData['status']==2) {
            $this->M->redis_select(7);
            $loginErrorTimes = $this->M->redis_INCR($rkBlackIp, 1);// 记录用户错误数
            $this->return_json(E_ARGS, '用户被停用,您的登陆信息将被我们详细记录!');
        }
        $loginData['url'] = $_SERVER['HTTP_HOST'];
        $loginData['gcurl'] = array_shift(explode(';', $_SERVER['HTTP_AUTHGC']));
        $loginData['admin_id'] = $adminData['id'];
        $loginData['from_way'] = FROM_PC;
        $loginData['login_time'] = $_SERVER['REQUEST_TIME'];
        $loginData['ip'] = $ip;
        $rkAdminErrorPwd  = 'admin:error:password:'.$adminData['id'];
        $updateData['update_time'] = $_SERVER['REQUEST_TIME'];
        $updateData['ip'] = $ip;
        $updateWhere['id'] = $adminData['id'];
        if ($adminData['pwd']!=admin_md5($pwd)) {
            $loginData['content'] = '密码错误,您的登陆信息将被我们详细记录!';
            $loginData['is_success'] = 2;
            $this->Login_model->login_record($loginData);
            $this->M->redis_select(7);
            $this->M->redis_INCR($rkBlackIp, 1);// 记录用户错误数
            $this->M->redis_select(5);
            $loginErrorTimes = $this->M->redis_INCR($rkAdminErrorPwd, 1);// 记录用户错误数
            if ($loginErrorTimes>=USER_PWD_ERROR_AND_LOCK) {
                $updateData['status'] = 2;
                $this->M->redis_del($rkAdminErrorPwd);
                $this->Login_model->admin_update($updateData, $updateWhere);
            }
            $num  = USER_PWD_ERROR_AND_LOCK;
            $num  = $num - $loginErrorTimes;
            $this->return_json(E_ARGS, "密码错误剩余次数 $num 次,您的登陆信息将被我们详细记录!");
        }
        $adminData['login_time'] = $_SERVER['REQUEST_TIME'];
        $adminData['expiration'] = $_SERVER['REQUEST_TIME'];
        $token = $this->Login_model->get_token($adminData);
        $result['token'] = $token;
        $result['name'] = $adminData['name'];
        $result['username'] = $adminData['username'];
        $result['id'] = $adminData['id'];
        $result['sid'] = $this->Login_model->sn;
        $this->M->redis_select(7);
        $this->M->redis_expire($rkBlackIp, 1);// 登陆成功，清除IP错误记录
        $this->M->redis_select(5);
        $this->M->redis_expire($rkAdminErrorPwd, 1);// 登陆成功，清除用户错误次数
        $loginData['content'] = '登陆成功';
        $loginData['is_success'] = 1;
        $this->Login_model->login_record($loginData);
        $this->Login_model->admin_update($updateData, $updateWhere);
        $this->return_json(OK, $result);
    }

    /**
     * 登陆是否需要验证码
     * @auther frank
     * @return $int
     **/
    public function code()
    {
        $rule = array(
            'token_private_key'  => 'require',
        );
        $msg = array(
            'token_private_key.require' => 'token_private_key不能为空',
        );
        $token_private_key = $this->G('token_private_key');
        $data = array(
            'token_private_key'  => $token_private_key,
        );
        $this->validate->rule($rule, $msg);
        if (!$this->validate->check($data)) {
            $this->return_json(E_ARGS, $this->validate->getError());
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
        $rkTokenKey = 'token:'.TOKEN_CODE_ADMIN.':'.$token;
        $jsondata = $this->M->redis_get($rkTokenKey);
        if (empty($jsondata)) {
            $this->return_json(LOGOUT, '已经退出');
        }
        $data = json_decode($jsondata, true);
        $rkIDToToken = 'token_ID:'.TOKEN_CODE_ADMIN.':'.$data['id'];
        $this->M->redis_del($rkTokenKey);
        $this->M->redis_del($rkIDToToken);
        $this->return_json(LOGOUT, '退出成功');
    }

}
