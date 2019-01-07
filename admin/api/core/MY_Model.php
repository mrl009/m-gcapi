<?php
if (!defined('BASEPATH')) {
	exit('No direct access allowed.');
}
// 引入自己的核心model
require BASEPATH.'gc/common/GC_Model.php';

class MY_Model extends GC_Model 
{
	public function __construct()
	{
		parent::__construct();
	}

	//检查控制器权限
	public function checkCtrlAuth($id,$name,$auth){
		if(!$id||!$name||!$auth) exit('Parameter Error!');
		//$rs = $this->get_one('role_id,privileges',array('id'=>$id),'admin');
		$this->_checkCtrlAuth('public',$data['public_auth'],$rs['privileges'],$name,$auth);
	}
	private function _checkCtrlAuth($key,$arr,$pr,$name,$auth){
		$pr = json_decode($pr,true);
		foreach(json_decode($arr,true) as $k=>$v){
			foreach($v['submenu'] as $s){
				if($s['en_name']==$name){
					$current = array();
					foreach($pr[$key] as $i=>$t){ 
						if(in_array($name,array_keys($t))){
							foreach($pr[$key][$i][$s['en_name']] as $o){
								if(in_array($o, explode(',',$s['auth']))) $current[]=$o;
							}
						}
					}
					if(!in_array($auth,$current)) exit('<div class="alert alert-danger"><b>您没有权限进行此操作！</b></div>'); 
				}
			}
		}
	}

	/**
     * 检测token
     * @auther frank
     * @return array
     **/
	public function check_token($en_token)
	{
		$tokenKey = 'token:'.TOKEN_CODE_ADMIN.':'.$en_token;
		$jsonData = $this->redis_get($tokenKey);
		if(empty($jsonData)){
			return false;
		}
		$data = json_decode($jsonData,true);
		if(isset($data['be_outed_time'])){
			$this->redis_del($tokenKey);//已经被踢出
			return TOKEN_BE_OUTED;
		}
		if (empty($data['expiration'])) {
			$this->redis_del($tokenKey);
			return TOKEN_TIME_OUT;
		}
		if($_SERVER['REQUEST_TIME']-$data['expiration'] > TOKEN_ADMIN_LIVE_TIME*3600){
			$this->redis_del($tokenKey);
			return TOKEN_TIME_OUT;
		}
		$data['expiration'] = $_SERVER['REQUEST_TIME'];
		$existime   = TOKEN_USER_LIVE_TIME*3600;
		$this->redis_setex($tokenKey,$existime,json_encode($data));
		return $data;
	}

    /*
     * 对四号公库的sx_total_limit进行更新
     * @param $price int 要更新的幅度可以为正负
     */
    public function update_sx_limit($price){
        $this->redis_select(4);
        $keys = 'sys:gc_set';
        $jsondata = $this->redis_get($keys);
        if (empty($jsondata)) {
            $this->select_db("private");
            $data = $this->get_list('key,value', 'set');
            $set_arr = [];
            foreach ($data as $key => $value) {
                $set_arr[$value['key']] = trim($value['value']);
            }
            $data = $set_arr;
        } else {
            $data = json_decode($jsondata, true);
        }
        $data['sx_total_limit']=$data['sx_total_limit']+$price;
        $rs=$this->redis_set($keys, json_encode($data));
    }
	//方便控制器调用redis
	public function select_redis()
	{
		return $this->redis;
	}
    //专门读取5号库sx配置
    public function get_sx_set($key){
        $this->redis_select(5);
        $keys = 'sx:'.$key;
        //var_dump($keys);exit();
        $data = $this->redis_get($keys);
        return  $data;
    }
    //专门修改5号库sx配置
    public function set_sx_set($key,$value){
        $this->redis_select(5);
        $keys = 'sx:'.$key;
        $rs=$this->redis_set($keys, json_encode($value));
        return $rs;
    }
	//方便控制器调用redis
	public function del_redis()
	{
		$this->redisP_del('hot');
		$this->redisP_del('games');
		$this->redisP_del('all_games');
		$this->redisP_del('main_games');
	}
    public function update_sx_set($key,$value,$expiration=EXPIRE_48){
        $this->redis_select(5);
        $keys = 'sx:'.$key;
        $rs=$this->redis_set($keys, json_encode($value));
        if($expiration!==0){
            $rs=$this->redis_expire($keys, $expiration );
        }
        return $rs;
    }
}
