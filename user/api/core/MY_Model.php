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


	/**
     * 检测token
     * @auther frank
     * @return array
     **/
	public function check_token($en_token)
	{
		$tokenKey = 'token:'.TOKEN_CODE_USER.':'.$en_token;
		$jsonData = $this->redis_get($tokenKey);
		if(empty($jsonData)){
			return false;
		}
		$data = json_decode($jsonData,true);
		if(isset($data['be_outed_time'])){
			//已经被踢出
			$this->redis_del($tokenKey);
			return TOKEN_BE_OUTED;
		}

		return $data;


		if (empty($data['expiration'])) {
			$this->redis_del($tokenKey);
			return TOKEN_TIME_OUT;
		}
		if($_SERVER['REQUEST_TIME']-$data['expiration']>TOKEN_USER_LIVE_TIME*3600*24){
			$this->redis_del($tokenKey);
			$this->load->model('Login_model');
			$token = $this->Login_model->get_token($data);
			return [TOKEN_TIME_OUT=>$token];
		}
		$data['expiration'] = $_SERVER['REQUEST_TIME'];
		$existime   = TOKEN_USER_LIVE_TIME*3600+180;
		$this->redis_setex($tokenKey,$existime,json_encode($data));
	}




	/**
	 *
	 * 获取到支持银行 和 线上支付平台的的基本信息
	 * @param $name  string 要获取的东西 bank || bank_online
	 * @param $id  int 获取单条 || array where 条件
	 * @return $arr  array
	 */
	public function base_bank_online($name = 'bank',$id=null)
	{
		$this->select_db('public');
		if(!empty($id) && !is_array($id)){
			$wher = [
				'status' => 1,
				'id'     => $id,
			];
			$arr  = $this->get_one('*',$name,$wher);
			return $arr;
		}
		$where = ['status'=>1];
		if (is_array($id)) {
			$where =  $id;
		}
        /*
        * lqh 2018/09/18
        * 新增查詢排序條件 
         */ 
        $condition = array(
            'orderby' => ['id' => 'desc']
        );
		$arr = $this->get_all('*',$name,$where,$condition);
		$this->select_db('private');
		$temp = [];
		foreach ($arr as $k => $item) {
			$temp[$item['id']] = $item;
		}
		$this->select_db('private');//为什么要select两次private？
		return $temp;
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
    public function get_mg_row_id($where){

    }
    public function get_sx_set($key){
        $this->redis_select(5);
        $keys = 'sx:'.$key;
        $data = $this->redis_get($keys);
        return  $data;
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
