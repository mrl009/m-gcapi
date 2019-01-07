<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Login_model extends GC_Model {
	/**
     * 获取token
     * @auther frank
	 * @param  $data array
	 * @param  $refresh bool
     * @return $string
     **/
	public function get_token($data,$refresh = false)
	{
		$mr = explode('.', microtime(true));
		$t = strval(substr($mr[0],3).$mr[1]);
		$token = TOKEN_PRIVATE_USER_KEY.$t.uniqid();
		$en_token = token_encrypt($token,TOKEN_PRIVATE_USER_KEY);//token密码
		$rkTokenKey = 'token:'.TOKEN_CODE_USER.':'.$en_token;
		$rkIDToToken = 'token_ID:'.TOKEN_CODE_USER.':'.$data['id'];
		$existToken = $this->redis_get($rkIDToToken);

		//8.12 刷新toekn
		if (!empty($existToken) && $refresh) {
			$this->redis_setex('token:'.TOKEN_CODE_USER.':'.$existToken,8,json_encode($data));
		}
		$existime   = TOKEN_USER_LIVE_TIME*3600*2+180;
		if(!empty($existToken) && $this->redis_TTL('token:'.TOKEN_CODE_USER.':'.$existToken) >10 ){
			//已经有人在线，记录被踢出的时间
			$existJsonData = $this->redis_get($existToken);
			$existData = json_decode($existJsonData,true);
			$existData['be_outed_time'] = $_SERVER['REQUEST_TIME'];
			$this->redis_setex('token:'.TOKEN_CODE_USER.':'.$existToken,$existime,json_encode($existData));
		}
		$data['login_time'] = $_SERVER['REQUEST_TIME'];//记录在线时间
		$this->redis_setex($rkTokenKey,$existime,json_encode($data));
		$this->redis_setex($rkIDToToken,$existime,$en_token);
		$this->cache_agent_line($data['id']);//缓存代理线信息
		return $en_token;
	}

	public function refresh_token($data){

	}
	/**
     * 获取一个用户的信息
     * @auther frank
     * @return array
     **/
	public function get_one_user($username,$field='*')
	{
		$where['a.username'] = $username;
        $condition = [];
        if ($field === '*') {
            $field = explode(',',$field);
            array_walk($field,function (&$v) { $v = 'a.' . $v; });
            $field = implode(',',$field);
            $field .= ',b.nickname,b.img,b.img AS headimg';
            $condition['join'] = 'user_detail';
            $condition['on'] = ' a.id = b.uid';
        }
		return $this->get_one($field,'user as a',$where,$condition);
	}

	/**
     * 记录管理员登陆信息
     * @auther frank
     * @return bool
     **/
	public function login_record($data)
	{
		return $this->write('log_user_login',$data);
	}

	/**
     * 记录管理员登陆信息
     * @auther frank
     * @return bool
     **/
	public function user_update($data,$where)
	{
		return $this->db->set('login_times', 'login_times+1',false)->update('user',$data,$where);
	}

	/**
     * 写入验证码，或者获取验证码
     * @auther frank
     * @return bool
     **/
	public function get_or_set_code($private_token_key='',$value=0)
	{
		$rkKey = 'user:code:'.$private_token_key;
		if(empty($value)){
			return $this->redisP_get($rkKey);
		}
		else{
			$this->redisP_set($rkKey,$value);
			$this->redisP_expire($rkKey,VERIDY_CODE_LIVE_TIME);
		}
	}
	public function del_code($private_token_key){
		$rkKey = 'user:code:'.$private_token_key;
		$this->redisP_del($rkKey);
	}
	/**
     * 踢出
     * @auther frank
     * @return bool
     **/
	public function login_be_out($uid)
	{
		$rkIDToToken = 'token_ID:'.TOKEN_CODE_USER.':'.$uid;
		$token = $this->redis_get($rkIDToToken);
        if(empty($token)){
        	$this->redis_del($rkIDToToken);
        	return true;
        }
		$rkTokenKey = 'token:'.TOKEN_CODE_USER.':'.$token;
		$this->redis_del($rkIDToToken);
		$this->redis_del($rkTokenKey);
	}

    /**
     * 缓存代理线信息到redis
     */
	public function cache_agent_line($uid)
    {
        $info = $this->get_one('line','agent_line',['uid'=>$uid]);
        if (!empty($info)){
            $this->redis_setex(TOKEN_CODE_AGENT .':line:'. $uid,3600*24,$info['line']);
        }
    }
}
