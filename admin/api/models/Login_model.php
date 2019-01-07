<?php
//session_start();
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Login_model extends GC_Model
{
    /**
     * 获取token
     * @auther frank
     * @return $string
     **/
    public function get_token($data)
    {
        $mr = explode('.', microtime(true));
        $t = strval(substr($mr[0], 3).$mr[1]);
        $token = TOKEN_PRIVATE_ADMIN_KEY.$t.uniqid();
        $en_token = token_encrypt($token, TOKEN_PRIVATE_ADMIN_KEY);//token密码
        $rkTokenKey = 'token:'.TOKEN_CODE_ADMIN.':'.$en_token;
        $rkIDToToken = 'token_ID:'.TOKEN_CODE_ADMIN.':'.$data['id'];
        $existToken = $this->redis_get($rkIDToToken);
        $existime   = TOKEN_ADMIN_LIVE_TIME*3600;

        if (!empty($existToken)) {
            //已经有人在线，记录被踢出的时间
            $existJsonData = $this->redis_get($existToken);
            $existData = json_decode($existJsonData, true);
            $existData['be_outed_time'] = $_SERVER['REQUEST_TIME'];
            $this->redis_setex('token:'.TOKEN_CODE_ADMIN.':'.$existToken, $existime, json_encode($existData));
        }
        $data['login_time'] = $_SERVER['REQUEST_TIME'];//记录在线时间
        $this->redis_SETEX($rkTokenKey, $existime, json_encode($data));
        $this->redis_SETEX($rkIDToToken, $existime, $en_token);
        return $en_token;
    }

    /**
     * 获取一个管理员的信息
     * @auther frank
     * @return array
     **/
    public function get_one_admin($username, $field='*')
    {
        $where['username'] = $username;
        return $this->get_one($field, 'admin', $where);
    }

    /**
     * 记录管理员登陆信息
     * @auther frank
     * @return bool
     **/
    public function login_record($data)
    {
        return $this->write('log_admin_login', $data);
    }

    /**
     * 记录管理员登陆信息
     * @auther frank
     * @return bool
     **/
    public function admin_update($data, $where)
    {
        $this->db->set('login_times', 'login_times+1', false);
        return $this->write('admin', $data, $where);
    }

    /**
     * 写入验证码，或者获取验证码
     * @auther frank
     * @return bool
     **/
    public function get_or_set_code($private_token_key, $value=0)
    {
        $rkKey = 'admin:code:'.$private_token_key;
        if (empty($value)) {
            return $this->redisP_get($rkKey);
        } else {
            $this->redisP_set($rkKey, $value);
            $this->redisP_expire($rkKey, VERIDY_CODE_LIVE_TIME);
        }
    }

    public function del_code($private_token_key)
    {
        $rkKey = 'admin:code:'.$private_token_key;
        $this->redisP_del($rkKey);
    }
    /**
     * 踢出
     * @auther frank
     * @return bool
     **/
    public function login_be_out($admin_id)
    {
        $rkIDToToken = 'token_ID:'.TOKEN_CODE_ADMIN.':'.$admin_id;
        $token = $this->redis_get($rkIDToToken);
        if (empty($token)) {
            $this->redis_del($rkIDToToken);
            return true;
        }
        $rkTokenKey = 'token:'.TOKEN_CODE_ADMIN.':'.$token;
        $this->redis_del($rkIDToToken);
        $this->redis_del($rkTokenKey);
    }

    /**
     * 会员踢出
     * @auther frank
     * @return bool
     **/
    public function user_be_out($uid)
    {
        $rkIDToToken = 'token_ID:'.TOKEN_CODE_USER.':'.$uid;
        $token = $this->redis_get($rkIDToToken);
        if (empty($token)) {
            $this->redis_del($rkIDToToken);
            return true;
        }
        $existJsonData = $this->redis_get($token);
        $existData = json_decode($existJsonData, true);
        $existData['be_outed_time'] = $_SERVER['REQUEST_TIME'];
        $this->redis_set('token:'.TOKEN_CODE_USER.':'.$token, json_encode($existData));
        $this->redis_del($rkIDToToken);
    }


    /**
     * 更新会员token信息
     * @auther ma
     * @return bool
     **/
    public function update_token($uid)
    {
        $rkIDToToken = 'token_ID:'.TOKEN_CODE_USER.':'.$uid;
        $token = $this->redis_get($rkIDToToken);
        if (empty($token)) {
            $this->redis_del($rkIDToToken);
            return true;
        }
        $data = $this->get_one('*','user',['id'=>$uid]);
        $this->redis_set('token:'.TOKEN_CODE_USER.':'.$token,json_encode($data));
    }
}
