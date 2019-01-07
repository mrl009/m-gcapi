<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class System extends GC_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('MY_Model','M');
	}

	/**
     * 获取系统基本信息
     * @auther frank
     * @return json
     **/
	public function index()
	{
		$ip = get_ip();
		$this->M->redis_select(7);
		$rkBlackIp = 'black_ip:'.$ip;// 限制IP登陆
		$times = $this->M->redis_get($rkBlackIp);// 获取该错误次数
		$this->M->redis_select(5);
		$is_code = 0;
		if($times >= CODE_IP_TIMES){
			$is_code = 1;
		}
		$this->load->model('System_model','sys');
		$app_type = $this->G('app_type');
		switch ($app_type) {
			case 'ios':
				$app_type = FROM_IOS;
				break;
			case 'android':
				$app_type = FROM_ANDROID;
				break;
			case 'wap':
				$app_type = FROM_WAP;
				break;
			case 'pc':
				$app_type = FROM_PC;
				break;
			default:
				$this->return_json(E_ARGS, '未知应用');
				break;
		}
		$versionData = array();
		if($app_type==FROM_IOS || $app_type==FROM_ANDROID){
			$versionData = $this->sys->get_version($app_type);
			if(empty($versionData)){
				$this->return_json(E_ARGS, '请联系管理员，版本信息出错');
			}
		}

		$result['is_code'] = $is_code;
		$result['version'] = $versionData;
		$result['is_jc'] = 0;
		if(APP_IS_CHENK === 0){
            $result['is_check'] = 0;
        }

        $result['system_time'] = time();
        $result['refresh_token'] = TOKEN_USER_LIVE_TIME*3600;
		$gcset = $this->M->get_gcset();
		empty($gcset['strength_pwd'])?$result['strength_pwd']=0:$result['strength_pwd']=1;
		empty($gcset['is_agent'])?$result['is_agent']=0:$result['is_agent']=1;
		empty($gcset['cp_default'])?$result['cp_default']=0:$result['cp_default']=1;
		empty($gcset['register_is_open'])?$result['register_is_open']=0:$result['register_is_open']=1;
		$result['is_agent'] = empty($gcset['is_agent']) ? 0 : $gcset['is_agent'];
		empty($gcset['register_open_verificationcode'])?$result['register_open_verificationcode']=0:$result['register_open_verificationcode']=1;
		$result['register_open_verificationcode'] = empty($gcset['register_open_verificationcode']) ? 0 : $gcset['register_open_verificationcode'];
		$result['register_open_username'] = empty($gcset['register_open_username']) ? 0 : $gcset['register_open_username'];
		$result['lottery_auth'] = empty($gcset['lottery_auth']) ? '1,2' : $gcset['lottery_auth'];
		$result['register_open_username'] = empty($gcset['register_open_username']) ? 0 : $gcset['register_open_username'];
        empty($gcset['app_color'])?$result['app_color']='#':$result['app_color']=$gcset['app_color'];
        $result['piwik']= $gcset['piwik'];
        $result['piwik_domain']= $gcset['piwik_domain'];
        $result['sys_games']= empty($gcset['sys_games']) ? '博友彩票' : $gcset['sys_games'];
        $result['sys_activity'] = $gcset['sys_activity'] == -1 ? '' : $gcset['sys_activity'];
        $result['reward_day'] = $gcset['reward_day'] ? explode(',', $gcset['reward_day']) : '';
		$this->return_json(OK, $result);
	}

	/**
     * 出错日志
     * @auther frank
     * @return json
     **/
	public function bug()
	{

		$imei = $this->P('imei');
		$phone_type = $this->P('phone_type');
		$app_type = $this->P('app_type');
		$app_version = $this->P('app_version');
		$network_type = $this->P('network_type');
		$location_fun = $this->P('location_fun');
		$system_version = $this->P('system_version');
		if($app_type=='ios'){
			$app_type = 1;
		}elseif($app_type=='android'){
			$app_type = 2;
		}
		else{
			$this->return_json(E_ARGS, '未知应用');
		}
		if(!is_numeric($network_type)){
			$this->return_json(E_ARGS, '参数错误');
		}
		$this->load->model('System_model','sys');
		$data['imei'] = $imei;
		$data['phone_type'] = $phone_type;
		$data['app_type'] = $app_type;
		$data['app_version'] = $app_version;
		$data['network_type'] = $network_type;
		$data['system_version'] = $system_version;
		$data['location_fun'] = $location_fun;
		$data['addtime'] = $_SERVER['REQUEST_TIME'];
		$b = $this->sys->add_bug($data);
		if($b){
			$this->return_json(OK);
		}else{
			$this->return_json(E_OK,'数据异常');
		}
	}
}
