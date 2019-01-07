<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @file Manager.php
 * @brief 后台管理员  权限 管理
 *
 * @package controllers
 * @author bigChao <bigChao> 2017/03/23
 */
class Manager extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model('MY_Model','core');

	}
	/**获取子帐号列表**/
	public function get_child_list()
	{
		$this->core->open('admin');//打开表
		$basic  = array(
			'username' => $this->G('username'),
			'name'     => $this->G('name')
		); //精确条件
		$senior = array(); //高级搜索
		$page   = array(
			'page'  => $this->G('page'),
			'rows'  => $this->G('rows'),
			'order' => $this->G('order'),
			'sort'  => $this->G('sort'),
			'total' => -1,
		);
		$this->load->model('level/Member_model','member');
		$arr = $this->core->get_list('id,name,username,addtime,update_time,status,ip,login_times,site_id','admin',$basic,$senior,$page);
		foreach($arr['rows'] as $k=>$v){
			$online = $this->member->check_online($v['id'],'admin');
			$arr['rows'][$k]['online']= !empty($online)?1:2;
		}
		$rs  = array('total'=>$arr['total'],'rows'=>$arr['rows']);
		$this->core->level_cache(1);
		$this->return_json(OK,$rs);
	}

	/**获取子帐号明细**/
	public function get_info()
	{
		$id = $this->input->get('admin_id');
		if(empty($id)){
			$this->return_json(E_DATA_EMPTY);
		}
		$arr=$this->core->get_one('*','admin',array('id'=>$id));
		$this->return_json(OK,$arr);
	}
	/**获取当前登陆管理员信息**/
	public function get_active_admin(){
		$id = $this->input->get('admin_id');
		if(empty($id)){
			$this->return_json(E_DATA_EMPTY);
		}
		$arr=$this->core->get_one('id,privileges','admin',array('id'=>$id));
		$this->return_json(OK,$arr);
	}
	
	/**添加修改管理员**/
	public function update_user()
	{
		$id       = $this->P('admin_id');
		$name     = $this->P('name');
		$username = $this->P('username');
		$pwd      = $this->P('pwd');
		$two_pwd  = $this->P('two_pwd');
		$site_id  = $this->P('site_id') ? $this->P('site_id') : '';
		if(empty($username) || empty($pwd) || empty($two_pwd)){
			$this->return_json(E_ARGS,'Parameter is null');
		}
		if($pwd != $two_pwd){
			$this->return_json(E_ARGS,'Two passwords are inconsistent');
		}
        // 验证数据
        $this->check_manage(array('username' => $username, 'name' => $name, 'pwd' => $pwd));
        $data =array(
            'username' => $username,
            'name'     => $name,
            'pwd'      => admin_md5($pwd)
        );
        if (empty($id) && !empty($site_id)) {
            $rs = $this->core->get_one('id','admin',array('site_id'=>$site_id));
            if (!empty($rs)) {
                $this->return_json(E_ARGS,'站点标志重复');
            }
            $data['site_id'] = $site_id;
            $data['privileges'] = '*';
        }
        $rs = $this->core->get_one('id','admin',array('username'=>$username));
        $where = array();
        if(!empty($id)){
            $where['id']=$id;
             if($rs!=array() && $id!=$rs['id']){
            	$this->return_json(E_ARGS,'管理员已存在');
        	}
        }else{
            $data['addtime'] = time();
            if($rs!=array()){
            	$this->return_json(E_ARGS,'管理员已存在');
        	}

        }
        $arr=$this->core->write('admin',$data,$where);
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "修改了子账号{$username}密码"));
        $this->return_json(OK,'执行成功');
	}

    /**
     * 校验数据
     * @param $data
     * @return bool
     */
    public function check_manage($data){
        $rule = [
            'username'  => 'require|alphaNum|max:12|min:5',
            'name'      => 'chsAlpha|max:12|min:2',
            'pwd'       => 'require',
        ];
        $msg  = [
            'username'  => '帐号最少5个字符最多12个字符,只能数字和字母',
            'name'      => '名称只能是中文或字母a-z长度2-12位',
            'pwd'       => '请输入密码',

        ];

        $this->validate->rule($rule,$msg);
        $result   = $this->validate->check($data);
        if(!$result){
            $this->return_json(E_ARGS,$this->validate->getError());
        }else{
            return true;
        }
    }
	
	/**修改权限**/
	public function save_privilege()
	{
		$id = $this->P('admin_id');
		$a  = $this->P('privileges');
		$max_credit_out_in=$this->P('max_credit_out_in');
		$max_credit_in_people=$this->P('max_credit_in_people');
		$username = $this->P('username');
		if(empty($id)){
			$this->return_json('ERROR','Parameter is null');
		}
		$arr=$this->core->write('admin',array(
			'privileges' => $a,
			'max_credit_out_in'   =>$max_credit_out_in,
			'max_credit_in_people'=>$max_credit_in_people
		),array('id' => $id));

        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "修改了子账号{$username}的权限"));
        $this->return_json(OK,'执行成功');
	}

	/**停用或启用**/
	public function update_status()
	{
		$id      = $this->P('admin_id');
		$status  = $this->P('status');
		$username= $this->P('username');
		if(empty($id) || empty($status)){
			$this->return_json(E_ARGS,'Parameter is null');
		}
		$id = intval($id);
		$pre = $status == 1 ? '暂停' : '开启';
		$status = $status==1?2:1;
		$arr=$this->core->write('admin',array('status' => $status),array('id' => $id));
		if($status==2){
			$this->load->model('Login_model');
			$this->Login_model->login_be_out($id);
		}

		// 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => $pre. '子账号'. $username));
        $this->return_json(OK,'执行成功');
	}
	/**删除管理员**/
	public function delete_user()
	{
		$id = $this->P('admin_id');
		if(empty($id)){
			$this->return_json(E_ARGS,'Parameter is null');
		}
		$arr=$this->core->delete('admin',explode(',', $id));
		$this->return_json(OK,'执行成功');
	}
	/*******修改密码********/
	public function edit_pwd(){
		$id       = $this->P('admin_id');
		$pwd      = $this->P('pwd');
		$two_pwd  = $this->P('two_pwd');
		$old_pwd  = $this->P('old_pwd');
		if(empty($id) || empty($pwd) || empty($two_pwd)){
			$this->return_json(E_ARGS,'参数为空');
		}
		if($pwd != $two_pwd){
			$this->return_json(E_ARGS,'两次密码不一致');
		}
		$admin=$this->core->get_one('pwd','admin',array('id'=>$id));
		if($admin['pwd']!=admin_md5($old_pwd)){
			$this->return_json(E_ARGS,'旧密码输入错误');
		}
		$arr=$this->core->write('admin',array('pwd' => admin_md5($pwd)),array('id' => $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '修改了密码'));
		$this->return_json(OK,'执行成功');
	}

	public function push_msg(){
		$msg=$this->G('msg');
		$this->push(MQ_USER,$msg);
		$this->return_json();
	}

}
