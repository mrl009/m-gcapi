<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @file Manager.php
 * @brief 站点设置
 *
 * @package controllers
 * @author bigChao <bigChao> 2017/03/23
 */
class Setting extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }
    /********************视讯配置start**********************/
    /*
     * */
    public function get_sx_credit(){
        $data=$this->core->get_sx_set('credit');
        if($data){
            $this->return_json(OK,$data);
        }
    }
    public function set_sx_credit(){
        $credit=$this->G('credit');
        $admin_name=$this->G('admin_name');
        $data=$this->core->set_sx_set('credit',$credit+0);
        $this->load->model('sx/Credit_model','Credit');
        if($data){
            $rs=$this->Credit->add_credit_record($credit,$admin_name);
            $this->return_json(OK,$data);
        }
    }
    /********************视讯配置end**********************/

    /**************************站点配置****************************/
    /**
     * 获取站点配置
     *
     * @access public
     * @return [code,msg,data]json
     */
    public function get_set()
    {
        $where2 = ['orderby'=>['sort'=>'asc']];
        $arr = $this->core->get_gcset();
        $d = $this->core->redis_get("sys:gc_set_appkey");
		$o = $this->core->redis_get("sys:gc_ios_set_appkey");
        $arr['fenxiang_string']= $this->core->redis_get("sys:fenxiang_string");
		$arr['ios_app_master_secret'] = $arr['ios_app_key'] = $arr['app_master_secret'] = $arr['app_key'] =  '';
        if(!empty($d)){
            $d = json_decode($d,true);
            $arr['app_key'] = $d['app_key'];
            $arr['app_master_secret'] = $d['app_master_secret'];
        }
		if(!empty($o)){
			$o = json_decode($o,true);
			$arr['ios_app_key'] = $o['ios_app_key'];
			$arr['ios_app_master_secret'] = $o['ios_app_master_secret'];
		}
        $arr['ios_app_setting'] = $this->core->get_one('*', 'version', array('app_type' => 1));
        $arr['android_app_setting'] = $this->core->get_one('*', 'version', array('app_type' => 2));
        $ttt=json_decode($arr['win_rate'],true);
        $this->core->redisP_select(REDIS_PUBLIC);
        $all=$this->core->redisP_hgetall('games');
        foreach($ttt as $k=>$v){
                $t = json_decode($all[$k],true);
                $arr['glist'][]=array('id'=>$k,'name'=>$t['name'],'rate'=>$v);
        }
        $this->return_json(OK, $arr);
    }

    /**
     * 保存站点配置
     *
     * @access public
     * @return [code,msg]json
     */
    public function save_set()
    {
        if (empty($this->P('web_name'))) {
            $this->return_json(E_ARGS, '参数错误');
        }

        $is_open_alipay = $this->P('is_open_alipay');
        $is_open_wechat = $this->P('is_open_wechat');

        /*** 修改redis：sys:gc_set_appkey ***/
        $d['app_key'] =$this->P('app_key');
        $d['app_master_secret'] = $this->P('app_master_secret');
		$o['ios_app_key'] =$this->P('ios_app_key');
		$o['ios_app_master_secret'] = $this->P('ios_app_master_secret');
        $fenxiang_string = $this->P('fenxiang_string');
        $this->core->redis_set("sys:gc_set_appkey",json_encode($d,JSON_UNESCAPED_UNICODE));
		$this->core->redis_set("sys:gc_ios_set_appkey",json_encode($o,JSON_UNESCAPED_UNICODE));
        $this->core->redis_set("sys:fenxiang_string",$fenxiang_string);
        /*** 修改gc_set ***/
        $data = array(
            //'win_rate' => $this->P('win_rate')?$this->P('win_rate'):'',
            'web_name' => $this->P('web_name')?$this->P('web_name'):'',
            'keyword' => $this->P('keyword')?$this->P('keyword'):1,
            'logo' => $this->P('logo')?$this->P('logo'):'#',
            'logo_wap' => $this->P('logo_wap')?$this->P('logo_wap'):'#',
            'wap_head_logo' => $this->P('wap_head_logo')?$this->P('wap_head_logo'):'#',
            'ios_qrcode' => $this->P('ios_qrcode')?$this->P('ios_qrcode'):'#',
            'android_qrcode' => $this->P('android_qrcode')?$this->P('android_qrcode'):'#',
            'h5_qrcode' => $this->P('h5_qrcode')?$this->P('h5_qrcode'):'#',
            'description' => $this->P('description'),
            'copyright' => $this->P('copyright'),
            'qq' => $this->P('qq'),
            'email' => $this->P('email'),
            'tel' => $this->P('tel'),
            'domain' => $this->P('domain')?$this->P('domain'):'#',
            'online_service' => $this->P('online_service')?$this->P('online_service'):'#',
            'wap_name' => $this->P('wap_name')?$this->P('wap_name'):'',
            'ios_name' => $this->P('ios_name')?$this->P('ios_name'):'',
            'wap_domain' => $this->P('wap_domain')?$this->P('wap_domain'):'#',
            'card_status' => $this->P('card_status')?$this->P('card_status'):10,
            'register_is_open' => $this->P('register_is_open')=='0'?$this->P('register_is_open'):1,
            'is_unique_name' => $this->P('is_unique_name')=='0'?$this->P('is_unique_name'):1,
            'is_phone' => $this->P('is_phone')=='0'?$this->P('is_phone'):1,
            'is_bomb_box' => $this->P('is_bomb_box')=='0'?$this->P('is_bomb_box'):1,
            'strength_pwd' => $this->P('strength_pwd')=='0'?$this->P('strength_pwd'):1,
            'is_agent' => $this->P('is_agent')!='0'?$this->P('is_agent'):0,
            'is_unique_bank' => $this->P('is_unique_bank')=='0'?$this->P('is_unique_bank'):1,
            'register_discout' => $this->P('register_discout')?$this->P('register_discout'):0,
            'register_discount_from_way' => $this->P('register_discount_from_way')?$this->P('register_discount_from_way'):2,
            //'lottery_auth' => $this->P('lottery_auth')?$this->P('lottery_auth'):'1,2',
            'lottery_auth' => '2',
            'sys_activity' => $this->P('sys_activity')?$this->P('sys_activity'):'-1',
            'reward_day' => $this->P('reward_day')?$this->P('reward_day'):'100,10000,200000',
            'register_num_ip' => $this->P('register_num_ip')?$this->P('register_num_ip'):5,
            'app_download' => $this->P('app_download')?$this->P('app_download'):'/',
            'app_color' => $this->P('app_color')?$this->P('app_color'):'#dc3b40',
            'bank_num_check' => $this->P('bank_num_check')?$this->P('bank_num_check'):0,
            'add_ip_check' => $this->P('add_ip_check')?$this->P('add_ip_check'):0,
            'quick_recharge_url' => $this->P('quick_recharge_url')?$this->P('quick_recharge_url'):'',
            'rate_type'=>$this->P('rate_type')?$this->P('rate_type'):1,
            'incompany_timeout'=>$this->P('incompany_timeout')?$this->P('incompany_timeout'):180,
            'income_time'=>$this->P('income_time')?$this->P('income_time'):0,
            'win_dml'=>$this->P('win_dml')?$this->P('win_dml'):0,
            'incompany_count'=>$this->P('incompany_count')?$this->P('incompany_count'):1,
            'is_open_alipay' => (!empty($is_open_alipay) || $is_open_alipay !=0) ? 1 : 0,
            'is_open_wechat' => (!empty($is_open_wechat) || $is_open_wechat !=0) ? 1 : 0,
            //'cp_default' => $this->P('cp_default')=='0'?$this->P('cp_default'):1,
            'cp_default' => 1,
            'register_open_verificationcode' => $this->P('register_open_verificationcode')=='0'?$this->P('register_open_verificationcode'):1,
            'register_open_username' => $this->P('register_open_username')=='0'?$this->P('register_open_username'):1,
            'android_link_check'    => $this->P('android_link_check'),
            'ios_link_check'    => $this->P('ios_link_check'),
            'default_out_num' => $this->P('default_out_num') ? intval($this->P('default_out_num')) : 0
        );
        $key_int = ['card_status', 'register_is_open', 'is_unique_name', 'close', 'credit', 'ip_cishu', 'user_card_cishu', 'register_num_ip','bank_num_check', 'add_ip_check', 'incompany_timeout', 'incompany_count', 'is_phone', 'register_discout', 'is_open_alipay', 'is_open_wechat', 'register_open_verificationcode', 'cp_default', 'register_open_username','win_dml','default_out_num'];
        foreach ($key_int as $key) {
            if (array_key_exists($key, $data)) {
                if (!is_numeric($data[$key])) {
                    $this->return_json(E_ARGS, $key.'参数错误');
                }
            }
        }
        /*$win_rand = json_decode($data['win_rate'],true);
        if($win_rand['win_rand']<1 || $win_rand['win_rand']>10){
            $this->return_json(E_ARGS, $key.'杀率只能在1-10之间整数');
        }*/
        $this->core->set_gcset($data);
        /*** 修改gc_version ***/
        $dataApp = [
            'is_must_update' => $this->P('ios_update_flag'),
            'version' => $this->P('ios_app_version'),
            'url' => $this->P('ios_app_download_url'),
        ];
        $arr = $this->core->write('version', $dataApp, array('app_type' => 1));
        $dataApp = [
            'is_must_update' => $this->P('android_update_flag'),
            'version' => $this->P('android_app_version'),
            'url' => $this->P('android_app_download_url'),
        ];
        $arr = $this->core->write('version', $dataApp, array('app_type' => 2));

        /*** 记录操作日志 ***/
        $this->core->redis_del("sys:gc_set");
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '更新了站点配置' . $data['web_name']));

        $this->return_json(OK, '执行成功');
    }

    /**
     * 保存站点配置
     * 专门针对google_key，google_status
     *
     * @access public
     * @return [code,msg]json
     */
    public function save_set2()
    {
        /*** 修改gc_set ***/
        $data = array(
            'google_key' => $this->P('google_key')?$this->P('google_key'):'',
            'google_status' => $this->P('google_status')?$this->P('google_status'):1,
        );
        $this->core->set_gcset($data);
        $this->core->redis_del("sys:gc_set");
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], 
            ['content'=>'更新了站点配置' .json_encode($data)]);
        $this->return_json(OK, '执行成功');
    }

    // 保存站点设置ip
    public function save_set_ip()
    {
        $ip = $this->P('access_ip');
        $this->core->set_gcset(array('access_ip' => $ip));
        $this->return_json(OK, '执行成功');
    }

    // 获取cp
    public function get_cp()
    {
        $s = [];
        $ctg = $this->G('ctg');
        $set = $this->core->get_gcset();
        $cp = explode(',', $set['cp']);
        if ($cp) {
            foreach ($cp as $v) {
                if ($ctg == 'sc' && in_array($v, explode(',', SC))) {
                    array_push($s, $v);
                } else if ($ctg == 'gc' && in_array($v, explode(',', GC))) {
                    array_push($s, $v);
                } else if (in_array($v, explode(',', SX))) {
                    array_push($s, $v);
                }
            }
        }
        $this->return_json(OK, implode(',', array_unique($s)));
    }

    // 获取cp
    public function get_cp_index()
    {
        $s = [];
        $ctg = $this->G('ctg');
        $set = $this->core->get_gcset();
        $cp_index = explode(',', $set['cp_index']);
        if ($cp_index) {
            foreach ($cp_index as $v) {
                if ($ctg == 'sc' && in_array($v, explode(',', SC))) {
                    array_push($s, $v);
                } else if ($ctg == 'gc' && in_array($v, explode(',', GC))) {
                    array_push($s, $v);
                } else if (in_array($v, explode(',', SX))) {
                    array_push($s, $v);
                }
            }
        }
        $this->return_json(OK, implode(',', array_unique($s)));
    }

    //保存cp
    public function save_set_cp()
    {
        $old_cp_cp_index = $this->core->get_gcset(['cp','cp_index']);
        $cp = $this->P('cp');
        $cp = implode(',',array_unique(explode(',',$cp)));
        $set_data['cp'] = $cp;
        if (isset($_POST['cp_index'])) {
            $cp_index = $this->P('cp_index');
            $cp_index = implode(',',array_unique(explode(',',$cp_index)));
            $set_data['cp_index'] = $cp_index;
        }
        $this->core->set_gcset($set_data);
        $new_cp_cp_index = $this->core->get_gcset(['cp','cp_index']);
        // 记录操作日志
        @wlog(APPPATH.'logs/record_cp_'.$this->core->sn.'_'.date('Ym').'.log','admin_id:'.$this->admin['id'] .'|admin_name:'. $this->admin['username']  . PHP_EOL .var_export(['new'=>$new_cp_cp_index,'old'=>$old_cp_cp_index],true));
        $this->return_json(OK, '执行成功');
    }

    //保存cp_index排序
    public function save_set_cp_index()
    {
        $old_cp_cp_index = $this->core->get_gcset(['cp','cp_index']);
        $cp_index = $this->P('cp_index');
        $cp_index = implode(',',array_unique(explode(',',$cp_index)));
        $this->core->set_gcset(array('cp_index' => $cp_index));
        $new_cp_cp_index = $this->core->get_gcset(['cp','cp_index']);
        // 记录操作日志
        @wlog(APPPATH.'logs/record_cp_'.$this->core->sn.'_'.date('Ym').'.log','admin_id:'.$this->admin['id'] .'|admin_name:'. $this->admin['username'] . PHP_EOL .var_export(['new'=>$new_cp_cp_index,'old'=>$old_cp_cp_index],true));
        $this->return_json(OK, '执行成功');
    }

    // 保存站点设置维护
    public function save_set_close()
    {
        $status = $this->P('status');
        $whContent = $this->P('wh_content')?$this->P('wh_content'):'';
        $this->core->set_gcset(array('close' => $status, 'close_info' => $whContent));
        $this->return_json(OK, '执行成功');
    }

    public function save_site_fee()
    {
        $site_fee = $this->P('site_fee');
        $this->core->set_gcset(array('site_fee' => $site_fee));
        $this->return_json(OK, '执行成功');
    }

    public function save_site_rate()
    {
        $site_rate = $this->P('site_rate');
        $this->core->set_gcset(array('site_rate' => $site_rate));
        $this->return_json(OK, '执行成功');
    }

    // 互动大厅设置
    public function save_set_hddt()
    {
        $is_open_hddt = $this->P('is_open_hddt');
        $this->core->set_gcset(array('is_open_hddt' => $is_open_hddt));
        $this->return_json(OK, '执行成功');
    }

    // IOS互動大廳
    public function save_set_ios_hddt()
    {
        $is_open_ios_hddt = $this->P('is_open_ios_hddt');
        $this->core->set_gcset(array('is_open_ios_hddt' => $is_open_ios_hddt));
        $this->return_json(OK, '执行成功');
    }
    /**************************END站点配置****************************/

    /**************************域名配置****************************/
    public function get_site_domain_list()
    {
        $basic = array(
            'domain' => $this->G('domain'),
            'is_binding' => $this->G('is_binding')
        ); //精确条件
        $senior = array(
            'wherein'=>array('a.type'=>[2,3,4,6,7])
        ); //高级搜索
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->core->get_list('*', 'set_domain', $basic, $senior, $page);
        $this->return_json(OK, $arr);
    }

    /**获取域名明细**/
    public function get_domain_info()
    {
        $id = $this->input->get('id');
        if (empty($id)) {
            $this->return_json(E_DATA_EMPTY);
        }
        $arr = $this->core->get_one('*', 'set_domain', array('id' => $id));
        $this->return_json(OK, $arr);
    }

    //保存修改
    public function save_domain()
    {
        if (empty($this->P('domain'))) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $id = $this->P('id');
        $data = array(
            'type' => $this->P('type'),
            'is_binding' => $this->P('is_binding')?$this->P('is_binding'):1,
            'domain' => $this->P('domain')
        );

        $where = array();
        if (!empty($id)) {
            $where['id'] = $id;
        } else {
            /** 新增则cname=站点配置的cname **/
            $arr = $this->core->get_gcset(['cname']);
            $data['cname'] = $arr['cname'];
        }
        $arr = $this->core->write('set_domain', $data, $where);
        // 记录操作日志
        $pre = !empty($id) ? '修改' : '新增';
        $type = $data['type'] == 1 ? '站点域名' : '支付域名';
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => $pre . $type . $data['domain']));
        $this->return_json(OK, '执行成功');
    }

    //超级后台保存修改域名
    public function super_save_domain()
    {
        $id = $this->P('id');
        $type = $this->P('type');
        $is_main = $this->P('is_main');
        $is_binding = $this->P('is_binding');
        $domain = $this->P('domain');
        $cname = $this->P('cname');
        if (empty($type) || empty($is_main) || empty($is_binding) || empty($domain) || empty($cname)) {
            $this->return_json(E_ARGS, '参数错误1');
        }
        $data = [
            'type' => $type,
            'is_main' => $is_main,
            'is_binding' => $is_binding,
            'domain' => $domain,
            'cname' => $cname
        ];
        if (empty($id)) {
            $data['addtime'] = time();
            $this->core->write('set_domain', $data);
        } else {
            $this->core->write('set_domain', $data, array('id' => $id));
        }
        $this->return_json(OK, '执行成功');
    }

    /**删除域名**/
    public function delete_domain()
    {
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $arr = $this->core->delete('set_domain', explode(',', $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "删除了域名配置{$id}"));
        $this->return_json(OK, '执行成功');
    }
    /**************************END站点配置****************************/

    /**************************获取网站(支付)信息****************************/
    public function get_site_info()
    {
        $arr = $this->core->get_list('*', 'set_article', array('type' => $this->G('type')));
        $this->return_json(OK, $arr);
    }

    public function get_site_one()
    {
        $arr = $this->core->get_one('*', 'set_article', array('id' => $_GET['id']));
        $this->return_json(OK, $arr);
    }

    public function saveSiteInfo()
    {
        $id = $this->input->post('id');
        $title = $this->input->post('title');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $data['content'] = $this->input->post('content');
        $arr = $this->core->write('set_article', $data, array('id' => $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '更新了' . $title));
        $this->return_json(OK, '执行成功');
    }
    /**************************END网站(支付)信息****************************/

    /**************************活动管理****************************/
    /**获取列表**/
    public function get_activity_list()
    {
        //$this->checkCtrlAuth(2,'SiteSetting','READ');
        $basic = array(
            'username' => $this->G('username'),
            'name' => $this->G('name')
        ); //精确条件
        $senior = array(); //高级搜索
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->core->get_list('*', 'set_activity', $basic, $senior, $page);
        $rs = array('total' => $arr['total'], 'rows' => $arr['rows']);
        $this->return_json(OK, $rs);
    }

    public function get_activity_info()
    {
        $arr = $this->core->get_one('*', 'set_activity', array('id' => $this->G('id')));
        $this->return_json(OK, $arr);
    }

    public function save_activity()
    {
        $id = $this->P('id');
        $data['title'] = $this->P('title');
        $data['extra_title'] = $this->P('extra_title') ? $this->P('extra_title') : '';
        $data['img_base64'] = $this->P('img_base64');
        if($id!=1001&&$id!=1002){
            $data['show_way'] = $this->P('show_way');
            $data['expiration_time'] = $this->P('expiration_time') ? strtotime($this->P('expiration_time')) : 0;
            $data['start_time'] = $this->P('start_time') ? strtotime($this->P('start_time')) : 0;
            $data['sort'] = $this->P('sort');
            $data['content'] = $this->input->post('content');
        }
        $where = array();
        if (!empty($id)) {
            $where = array('id' => $id);
        } else {
            $data['addtime'] = time();
        }
        $arr = $this->core->write('set_activity', $data, $where);
        // 记录操作日志
        $this->load->model('log/Log_model');
        $pre = !empty($id) ? '修改' : '新增';
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}了活动'{$data['title']}'"));
        $this->return_json(OK, '执行成功');
    }

    /**删除活动**/
    public function delete_activity()
    {
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $arr = $this->core->delete('set_activity', explode(',', $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "删除了活动{$id}"));
        $this->return_json(OK, '执行成功');
    }

    /**停用或启用**/
    public function update_status()
    {
        $id = $this->P('admin_id');
        $status = $this->P('status');
        $title = $this->P('title');
        if (empty($id) || empty($status)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $status = $status == 1 ? 2 : 1;
        $arr = $this->core->write('set_activity', array('status' => $status), array('id' => $id));
        // 记录操作日志
        $pre = $status == 1 ? '启用' : '停用';
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}了活动'{$title}'"));
        $this->return_json(OK, '执行成功');
    }
    /**************************END活动管理****************************/
    /*****************************活动黑名单**************************/
    /**获取列表**/
    public function get_activity_blacklist()
    {
        //$this->checkCtrlAuth(2,'SiteSetting','READ');
        $basic = array(
            'username' => $this->G('username'),
            'name' => $this->G('name')
        ); //精确条件
        $senior = array(); //高级搜索
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->core->get_list('*', 'activity_blacklist', $basic, $senior, $page);
        $rs = array('total' => $arr['total'], 'rows' => $arr['rows']);
        $this->return_json(OK, $rs);
    }
    /*保存活动黑名单*/
    public function save_activity_blacklist()
    {
        $id = $this->P('id');
        $user = $this->P('user');
        $data['activity_id'] = $this->P('show_way');
        $data['comment'] = $this->input->post('comment');
        $data['activity_title'] = $this->P('activity_title');
        $where = array();
        if (!empty($id)) {
            $where = array('id' => $id);
        } else {
            $userInfo = $this->M->get_one('username,id,vip_id', 'user', array('username' => $user));
            if(!$userInfo){
                $this->return_json(E_ARGS, '请核实会员名称');
            }
            $data['add_time'] = time();
            $data['vip_level'] = $userInfo['vip_id'];
            $data['user_name'] = $userInfo['username'];
            $data['id']      = $userInfo['id'];
        }
        $arr = $this->core->write('activity_blacklist', $data, $where);
        //var_dump($this->core);exit();
        //var_dump()
        // 记录操作日志
      /*  $this->load->model('log/Log_model');
        $pre = !empty($id) ? '修改' : '新增';
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}了活动'{$data['title']}'"));*/
        $this->return_json(OK, '执行成功');
    }
    //删除活动黑名单
    public function delete_activity_blacklist(){
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $arr = $this->core->delete('activity_blacklist', explode(',', $id));
        $this->return_json(OK, '执行成功');
    }
    //获取单条活动黑名单
    public function get_activity_blacklist_info(){
        $arr = $this->core->get_one('*', 'activity_blacklist', array('id' => $this->G('id')));
        $this->return_json(OK, $arr);
    }
    /*****************************END活动黑名单**************************/
    /**************************广告管理****************************/
    // 获取广告
    public function get_advertise_list()
    {
        $arr = $this->core->get_list('*', 'set_img', array(1 => 1));
        $this->return_json(OK, $arr);
    }

    //保存修改
    public function save_pics()
    {
        $type = $this->P('type');
        if (empty($type)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $data['img_json'] = $this->P('pics');
        $this->core->write('set_img', $data, array('id' => $type));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $advertiseType = $this->core->get_one('title', 'set_img', array('id' => $type));
        $this->Log_model->record($this->admin['id'], array('content' => '更新了' . $advertiseType['title']));
        $this->return_json(OK, '执行成功');
    }


    /**********************游戏管理***********************/
    /**
     * @模块   设置管理／游戏管理
     * @版本   Version 1.0.0
     * @日期   2017-04-25
     * @作者   shensiming
     */
    /* 游戏状态修改接口 */
    public function set_games_status()
    {
        $gid = (int)$this->P('gid');
        $status = (int)$this->P('status');
        $wh_content = $this->P('wh_content');
        /* 维护状态:0-正常,1-维护,2-永久下架 */
        $status_arr = array(0, 1, 2);
        if (!in_array($status, $status_arr) || $gid < 0) {
            $this->return_json(E_ARGS, '参数错误');
        }
        /* 如果status=0必须加上引号 */
        $status = ($status == 0) ? "'0'" : $status;
        /* 修改status字段 */
        $this->core->select_db('public');
        /**
         * 如果是1:维护状态还必须判断是否有维护内容
         * 如果是其他状态则清空维护内容
         */
        if ($status == 1) {
            if (empty($wh_content)) {
                $this->return_json(E_ARGS, '维护内容不能为空');
            }
            if (strlen($wh_content) > 1024) {
                $this->return_json(E_ARGS, '维护内容不能超过1024');
            }
            $wh_content = htmlspecialchars($wh_content);
            $this->core->db->set('wh_content', $wh_content);
        } else {
            $this->core->db->set('wh_content', '');
        }
        $this->core->db->where('id', $gid);
        $this->core->db->set('status', $status);
        $status = $this->core->db->update('games');
        if ($status) {
            $this->_games_update_add_log(array('gid' => $gid, 'status' => '成功', 'field' => 'status=' . $status));
            $this->return_json(OK);
        }
        $this->_games_update_add_log(array('gid' => $gid, 'status' => '失败', 'field' => 'status=' . $status));
        $this->return_json(E_ARGS, '修改失败');
    }

    /* 游戏热门修改接口 */
    public function set_games_hot()
    {
        $gid = (int)$this->P('gid');
        $hot = (int)$this->P('hot');
        /* 维护状态:0-正常,1-热门 */
        $hot_arr = array(0, 1);
        if (!in_array($hot, $hot_arr) || $gid < 0) {
            $this->return_json(E_ARGS, '参数错误');
        }
        /* 如果hot=0必须加上引号 */
        $hot = ($hot == 0) ? "'0'" : $hot;
        /* 修改hot字段 */
        $this->core->select_db('public');
        $status = $this->core->db->where('id', $gid)->set('hot', $hot)->update('games');
        if ($status) {
            $this->_games_update_add_log(array('gid' => $gid, 'status' => '成功', 'field' => 'hot=' . $hot));
            $this->return_json(OK);
        }
        $this->_games_update_add_log(array('gid' => $gid, 'status' => '失败', 'field' => 'hot=' . $hot));
        $this->return_json(E_ARGS, '修改失败');
    }


    /**
     * 添加操作日志
     * log = array(
     *    'gid'        游戏id
     *    'status'    修改状态：成功 or 失败
     *    'field'        修改字段：status=1 or hot=1
     * )
     */
    private function _games_update_add_log($log = array())
    {
        $game = $this->core->get_one('name', 'games',
            array('id' => $log['gid']));
        $content = "修改游戏：{$game['name']},状态：{$log['status']},{$log['field']}";
        $this->core->select_db('private');
        $this->load->model('log/Log_model');
        $data['content'] = $content;
        $this->Log_model->record($this->admin['id'], $data);
    }
    /**********************END游戏管理********************/


    /**********************游戏管理2.0***********************/
    /**
     * @模块   设置管理／游戏管理
     * @版本   Version 2.0.0
     * @日期   2017-05-10
     */
    public function set_games($games_arr = '')
    {
        $content = $where = array();
        if (empty($games_arr)) {
            $this->G('id') ? $where['id'] = (int)$this->G('id') : $this->return_json(E_ARGS, '修改失败');
            if ($this->G('name')) $content['name'] = $this->G('name');
            if ($this->G('max_money_play')) $content['max_money_play'] = $this->G('max_money_play');
            if ($this->G('max_money_stake')) $content['max_money_stake'] = $this->G('max_money_stake');
            if ($this->G('img')) $content['img'] = $this->G('img');
            if ($this->G('hot')) $content['hot'] = $this->G('hot');
            if (isset($content['hot']) && $content['hot'] == 2) $content['hot'] = "'0'";
            if ($this->G('sort')) $content['sort'] = $this->G('sort');
            if ($this->G('show')) $content['show'] = $this->G('show');
            if ($this->G('wh_content')) $content['wh_content'] = $this->G('wh_content');
            if ($this->G('tmp')) $content['tmp'] = $this->G('tmp');
            if ($this->G('status')) $content['status'] = $this->G('status');
        } else {
            $where['id'] = $games_arr['id'];
            unset($games_arr['id']);
            $content = $games_arr;
        }
        $this->core->select_db('public');
        $is = $this->core->write('games', $content, $where);
        $string = '';
        foreach ($content as $k => $v) {
            $string .= $k . '=' . $v . ',';
        }
        if ($is) {
            $redis = $this->core->del_redis();
            $this->_games_update_add_log(array('gid' => $where['id'], 'status' => '成功', 'field' => $string));
            $this->return_json(OK);
        } else {
            $this->_games_update_add_log(array('gid' => $where['id'], 'status' => '失败', 'field' => $string));
            $this->return_json(E_ARGS, '修改失败');
        }

    }

    /**
     * 六合彩手动开盘
     */
    public function lhc_kp()
    {
        $bs = $this->G('bs');
        $this->core->select_db('public');
        $where['gid'] = 3;
        if ($bs == 1) {
            $result = $this->core->get_one('open_time,current_kithe', 'open_time', $where);
            empty($result) ? $this->return_json(OK, $result) : $this->return_json(E_DATA_EMPTY);
        } else {
            $arr['open_time'] = $this->P('open_time');
            $arr['current_kithe'] = $this->P('current_kithe');
            if (empty($arr['open_time']) || empty($arr['current_kithe'])) {
                $this->return_json(E_ARGS, '参数错误!');
            }
            $is = $this->core->write('open_time', $arr, $where);
            $this->load->model('log/Log_model', 'lo');
            if ($is) {
                $logData['content'] = '修改六合彩的当前期数为' . $arr['current_kithe'] . '期，结束时间' . $arr['open_time'] . '次 成功';
                $this->lo->record($this->admin['id'], $logData);
                $this->return_json(OK, array('status' => 'OK', 'msg' => '执行成功'));
            } else {
                $logData['content'] = '修改六合彩的当前期数为' . $arr['current_kithe'] . '期，结束时间' . $arr['open_time'] . '次 成功';
                $this->lo->record($this->admin['id'], $logData);
                $this->return_json(E_OP_FAIL, '操作失败！');
            }
        }
    }

    /**********************END游戏管理2.0***********************/

    /**************************DSN配置修改增加****************************/
    /**超级后台DSN修改*/
    public function save_set_dsn()
    {
        $old_dsn_key = $this->P('old_dsn_key');
        $dsn_value = $this->P('dsn_value');
        $dsn_key = $this->P('dsn_key') ? explode(',', $this->P('dsn_key')) : '';
        if (empty($dsn_key) || empty($dsn_value)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $this->core->redisP_select(REDIS_PUBLIC);
        if ($old_dsn_key) {
            $this->core->redisP_hdel('dsn', $old_dsn_key);
        }
        foreach ($dsn_key as $v) {
            $this->core->redisP_hset('dsn', $v, $dsn_value);
        }
        $this->return_json(OK, '执行成功');
    }
    //批量删除修改
    public function multiple_save_dsn()
    {
        $keys = $this->P('keys');
        $value = $this->P('value');
        if (!empty($keys)) {
            $keys = explode(',', $keys);
            foreach ($keys as $key) {
                $this->core->redisP_select(REDIS_PUBLIC);
                $this->core->redisP_hdel('dsn', $key);
                $this->core->redisP_hset('dsn', $key, $value);
            }
        }
        $this->return_json(OK, '执行成功');
    }
    /**************************DSN配置修改增加****************************/

    public function get_apay_channel_list()
    {
        $this->load->model('Agentpay_model','ap');
        $res = $this->ap->get_apay_channels();
        $this->return_json(OK, $res);
    }

    // 获取在线人数
    public function get_online_user()
    {
        $key = "token_ID:" . TOKEN_CODE_USER . ":";
        $rs = $this->core->redis_keys($key.'*');
        $online = is_array($rs) ? count($rs) : 0;
        $this->return_json(OK, $online);
    }
}
