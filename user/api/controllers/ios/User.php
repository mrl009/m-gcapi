<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 用户查询
 *
 * @file        user/api/controllers/ios/user
 * @package     user/api/controllers/ios
 * @author      ssm
 * @version     v1.0 2017/07/12
 * @created 	2017/07/12
 */
class User extends GC_Controller
{
    const IOS_USER_INFO = 'ios:user:info:';
    const IOS_USER_INFO_EXPIRE = 86440*14;
    const IOS_USER_TOKEN = 'ios:user:token:';
    const IOS_USER_TOKEN_EXPIRE = 86440*14;
    const IOS_USER_ORDER = 'ios:user:order:';
    const IOS_USER_ORDER_INCR = 'ios:user:order:incr:';
    const IOS_USER_ORDER_EXPIRE = 86440*14;
    const IOS_USER_ASK = 'ios:user:ask:';
    const IOS_USER_ASK_INCR = 'ios:user:ask:incr:';
    const IOS_USER_ASK_EXPIRE = 86440*14;
    protected $user = ['username'=>'','password'=>'',
                        'truename'=>'','cardid'=>''];

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
        $this->M->redisP_select(REDIS_PUBLIC);
    }

    /**
     * 用户登录
     *
     */
    public function login()
    {
        $this->_check();
        $key = self::IOS_USER_INFO.$this->user['username'];
        $user = $this->M->redis_GET($key);
        if (empty($user)) {
            $this->return_json(E_ARGS, '用户名或密码错误');
        }
        $user = json_decode($user, true);
        if ($user['password'] != $this->user['password']) {
            $this->return_json(E_ARGS, '用户名或密码错误');
        }
        // ------------
        $token = md5(uniqid().time());
        $key = self::IOS_USER_TOKEN.$token;
        $this->M->redis_SET($key, $this->user['username']);
        $this->M->redis_EXPIRE($key, self::IOS_USER_TOKEN_EXPIRE);
        $info = ['token'=>$token,'truename'=>$user['truename'],
                'cardid'=>$user['cardid'],'username'=>$user['username']];
        // ------------
        $this->return_json(OK, $info);
    }

    /**
     * 用户注册
     *
     */
    public function regis()
    {
        $this->_check();
        $key = self::IOS_USER_INFO.$this->user['username'];
        $flag = $this->M->redis_GET($key);
        if (!empty($flag)) {
            $this->return_json(E_ARGS, '用户已注册');
        }
        $this->M->redis_SET($key, json_encode($this->user));
        $this->M->redis_EXPIRE($key, self::IOS_USER_INFO_EXPIRE);
        // ------------
        $token = md5(uniqid().time());
        $key = self::IOS_USER_TOKEN.$token;
        $this->M->redis_SET($key, $this->user['username']);
        $this->M->redis_EXPIRE($key, self::IOS_USER_TOKEN_EXPIRE);
        $info = ['token'=>$token,'username'=>$this->user['username'],
                'cardid'=>'','truename'=>''];
        // ------------
        $this->return_json(OK, $info);
    }


    /**
     * 用户退出
     *
     */
    public function logout()
    {
        $token = $this->P('token');
        $key = self::IOS_USER_TOKEN.$token;
        $this->M->redis_DEL($key);
        $this->return_json(OK, '退出成功');
    }

    /**
     * 检查用户数据
     *
     */
    private function _check()
    {
        $this->user['username'] = $this->P('username');
        $this->user['password'] = $this->P('password');
        $rule = [
            'username'  => 'require|number|min:11|max:11',
            'password'  => 'require|min:6|max:15',
        ];
        $msg = [
            'username.require' => '手机号码只能11位并且是数字',
            'username.number' =>  '手机号码只能11位并且是数字',
            'username.max'     => '手机号码只能11位并且是数字',
            'username.min'     => '手机号码只能11位并且是数字',
            'pwd.require' => '密码只能6-15位',
            'pwd.max'   => '密码只能6-15位',
            'pwd.min'  => '密码只能6-15位',
        ];
        $this->validate->rule($rule, $msg);
        if (!$this->validate->check($this->user)) {
            $this->return_json(E_ARGS, $this->validate->getError());
        }
    }
}
