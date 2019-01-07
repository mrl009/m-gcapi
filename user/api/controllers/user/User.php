<?php
/**
 * 会员的操作
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/3/25
 * Time: 18:44
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/User_model');
    }

    public function index(){
    }

    public function get_touxiang()
    {
        $data = [];
        for($i=0;$i<49;$i++){
            $data[$i] = USER_IMG_DOMAIN .'/portrait/avatar'.($i+1).'.png';
        }
        $this->return_json(OK,$data);
    }

    public function agent_show()
    {
        $id = $this->user['id'];
        $userData = $this->User_model->get_one('id AS extension_num,username,balance,type,off_sum','user',['id'=>$id]);
        if ($userData['type'] != 2) {
            $this->return_json(E_ARGS,'你还不是代理');
        }
        $where = [
            'addtime >=' =>strtotime(date('Y-m-d 00:00:00')),
            'agent_id ' =>$id
        ];
        $date_num = $this->User_model->get_one('count(*) as date_num','user',$where);
        $date_num?$date_num=$date_num['date_num']:$date_num="0";
        /**** 新站点获取 ****/
        // $domain = $this->User_model->get_one('domain AS extension_url','set',[]);
        $domain2 = $this->User_model->get_gcset(['domain']);
        $domain['extension_url'] = $domain2['domain'];
        /**** end ****/
        if (substr($domain['extension_url'],0,4) != 'http') {
            $userData['extension_url'] = 'http://'.$domain['extension_url'] . "?" .EXTENSION . '=' . $id;
        }else{
            $userData['extension_url'] = $domain['extension_url'] . "?" .EXTENSION . '=' . $id;
        }

        $userData['date_num'] = $date_num;
        $this->return_json(OK,$userData);

    }
    /**
     * 用户信息展示
     */
    public function user_show()
    {
        $uid = $this->user['id'];
        if ($uid <= 0) {
            $this->return_json(E_ARGS,'id 错误');
        }
        $where = ['a.id'=>$uid];
        $where2 = [
            'join'=>'user_detail',
            'on'=>'a.id=b.uid',
        ];
        $str='a.username,a.level_id,a.update_time logintime,a.loginip,a.addtime,a.is_level_lock,b.*';

        $arr = $this->User_model->get_list($str,'user',$where,$where2);
        $arr = $arr[0];
        $arr['addtime']   = date('Y-m-d H:i:s',$arr['addtime']);
        $arr['logintime'] = date('Y-m-d H:i:s',$arr['logintime']);
        $arr['loginip']   = $arr['loginip'];
        $arr['addip']     = $arr['addip'];

        unset($arr['uid']);
        $this->return_json(OK,$arr);
    }


    /**
     * 会员中心-会员名与额度
    */
    public function user_balance()
    {
        $this->load->helper('common_helper');
        $userinfo  = $this->user;

        $id = $userinfo['id'];

        if (!$id) {
           $this->return_json(E_ARGS,'id错误');
        }
        $where  = [
            'a.id' => $id
        ];
        $where2['join'] = [
            ['table'=>'user_detail as b', 'on'=>'a.id=b.uid'],
            ['table'=>'grade_mechanism as c',     'on'=>'c.id=a.vip_id'],
        ];
        $str    = "a.username,a.balance,a.type,b.bank_num,b.bank_name,b.img,b.bank_pwd,c.id as dengji";
        $arr = $this->User_model->get_list($str,'user',$where,$where2);
        if (empty($arr)) {
            $this->return_json(E_ARGS,'错误!');
        }
        $arr = $arr[0];
        if(empty($arr['bank_pwd'])){
            $arr['bank_pwd'] = 0;
        }else{
            $arr['bank_pwd'] = 1;
        }
        $memberCard = $this->User_model->member_card();
        if (!empty($memberCard)) {
            $arr['binding'] = 1;
        }else{
            $arr['binding'] = 0;
        }
        if (empty($arr['img'])) {
            $arr['img'] = 0;
        }
        unset($arr['bank_num']);
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        $arr['balance'] = round($arr['balance'],3);
        $this->return_json(OK,$arr);
    }

    /**
     * 获取银行卡列表
    */

    public function bank_list(){
        // 获取银行卡和微信和支付宝
        $this->User_model->select_db('private');
        $user = $this->User_model->get_one('bank_num, wechat, alipay', 'user_detail', ['uid'=>$this->user['id']]);

        // 拼接SQL语句
        $sql = "SELECT `id`, `bank_name`, `img`, `is_qcode` FROM `gc_bank` WHERE `status` = 1 ";
        $sql1 = "";
        $set = $this->User_model->get_gcset(['is_open_alipay', 'is_open_wechat']);
        if (empty($user['bank_num'])) {
            if ($sql1 != "") {
                $sql1 .= " OR ";
            }
            $sql1 .= " `is_qcode` = '\'0\''";
        }
        if (empty($user['wechat']) && isset($set['is_open_wechat']) && $set['is_open_wechat'] != 0) {
            if ($sql1 != "") {
                $sql1 .= " OR ";
            }
            $sql1 .= "`id` = 52";
        }
        if (empty($user['alipay']) && isset($set['is_open_alipay']) && $set['is_open_alipay'] != 0) {
            if ($sql1 != "") {
                $sql1 .= " OR ";
            }
            $sql1 .= "`id` = 51";
        }
        if ($sql1 != "") {
            $sql .= ' AND ('.$sql1.')';
        } else {
            $this->return_json(E_ARGS, '你以全部绑定');
        }
        
        $this->User_model->select_db('public');
        $arr = $this->User_model->db->query($sql)->result_array();
        foreach ($arr as &$v) {
            if ($v['id'] == 51) {
                $v['img'] = ZFB_IMG_PNG;
            }
            if ($v['id'] == 52) {
                $v['img'] = WX_IMG_PNG;
            }
        }
        $this->return_json(OK,['rows'=>$arr]);
    }

    /**
     * user 真实姓名
    */
    public function bank_name()
    {
        $data = $this->User_model->get_one('bank_name,phone,bank_pwd','user_detail' , ['uid' => $this->user['id']]);
        $datax['name']  = $data['bank_name'];
        $datax['is_pwd']  = $data['bank_pwd'] ? true : false;
        empty($data['phone']) ? $datax['phone'] = '': $datax['phone'] = $data['phone'];

        $gcSet = $this->User_model->get_gcset();
        $datax['is_phone'] = empty($data['phone']) ? (int)$gcSet['is_phone'] : 0;
        $this->return_json(OK,$datax);
    }


    /**
     * 绑定银行卡
    */
    public function binding_bank()
    {
        $data['bank_id']   = $this->P('bank_id');  //银行id
        $data['bank_num']  = $this->P('bank_num'); //银行卡号
        $data['bank_pwd']  = $this->P('bank_pwd'); //取款密码
        $data['address']   = $this->P('address');  //开户行

        $gcSet = $this->User_model->get_gcset();
        $this->check_card($data,$gcSet['bank_num_check']);
        $uid  = $this->user['id'];
        $keys = "temp:user_bing_bank:$uid";
        $bool = $this->User_model->fbs_lock($keys);
        if (!$bool) {
            $this->return_json(E_ARGS,'系统繁忙');
        }
        $userData = $this->User_model->get_one('bank_pwd,bank_num,bank_name', 'user_detail', [ 'uid' => $uid ]);
        $data['bank_pwd'] =  bank_pwd_md5( $data['bank_pwd'] );
        if (!empty($userData['bank_pwd'])) {
            if( $userData['bank_pwd'] != $data['bank_pwd'] ){
                $this->User_model->fbs_unlock($keys);
                $this->return_json(E_ARGS,'资金密码和设置的资金密码不匹配');
            }
        }else{
            $this->return_json(E_ARGS,'请先绑定密码');
        }
        if (!empty($userData['bank_num'])) {
            $this->User_model->fbs_unlock($keys);
            $this->return_json(E_ARGS,'你已经绑定过了');
        }

        if($gcSet['is_unique_bank'] == 1){
            $a = $this->User_model->get_one('id','user_detail',['bank_num'=>$data['bank_num']]);
            if(!empty($a)){
                $this->User_model->fbs_unlock($keys);
                $this->return_json(E_ARGS,'银行卡号重复请联系客服');
            }
        }
        $bool = $this->User_model->db->update('user_detail',$data,['uid'=>$uid]);
        if ($bool) {
            $this->User_model->zhuceyouohui($uid,$this->user['username'],true,$this->user['agent_id']);
            $this->User_model->fbs_unlock($keys);
            $this->return_json(OK,'更新成功');
        }else{
            $this->User_model->fbs_unlock($keys);
            $this->return_json(E_OK,'更新失败请重试');
        }

    }

    /**
     * 添加资金密码
    */
    public function bank_pwd_add()
    {
        $data['bank_pwd']  = trim(trim($this->P('bank_pwd')),'.');
        $data['bank_name'] = $this->P('bank_name'); //持卡人

        $rule = [
            'bank_name' => 'require|chs_alpha', //持卡人
            'bank_pwd'  => 'require',//取款密码
        ];
        $msg  = [
            'bank_name' => '持卡人只能为汉字和字母和·,不能·开头和结尾', //持卡人
            'bank_pwd'  => '请输入取款密码',//取款密码
        ];
        $a = $this->User_model->get_one('id,uid,bank_name,bank_num,phone','user_detail',['uid'=>$this->user['id']]);
        $gcSet = $this->User_model->get_gcset();
        if (!empty($gcSet['is_phone']) && empty($a['phone'])) {
            $data['phone'] = $this->P('phone');
            $rule['phone'] = 'require|phone';
            $msg['phone']  = '请输入正确的11位手机号';
        }
        $this->validate->rule($rule,$msg);//验证数据
        $result   = $this->validate->check($data);
        if(!$result){
            $this->return_json(E_ARGS,$this->validate->getError());//返回错误信息
        }

        $uid = $this->user['id'];
        /* if(!is_numeric($data['bank_pwd'])||strlen($data['bank_pwd']) !=6){
            $this->return_json(E_ARGS,'资金密码只能为6位数字');
        }*/
        //8.12 资金密码更改
        $data['bank_pwd'] = bank_pwd_md5($data['bank_pwd']);
        $gcSet = $this->User_model->get_gcset();


        if (!empty($a['bank_name']) && $data['bank_name'] != $a['bank_name']) {
            $this->return_json(E_ARGS,'真实姓名不可能更改');
        }


        if($gcSet['is_unique_name'] == 1 && empty($a['bank_name'])){
            $a = $this->User_model->get_one('uid,bank_name,bank_num','user_detail',['bank_name'=>$data['bank_name']]);
            if(!empty($a)&&$a['uid'] != $uid){
                $this->return_json(E_ARGS,'姓名不能重复请联系客服');
            }
        }
        $phoneData = $this->User_model->get_one('','user_detail',['phone' => $this->P('phone')]);
        if (!empty($data['phone'])) {
            if (!empty($phoneData)) {
                $this->return_json(E_ARGS,'手机号已经使用过了');
            }

        }
        $userData  = $this->User_model->get_one('','user_detail',['uid' => $uid]);
        if (!empty($userData['bank_pwd']) && !empty($userData['bank_name'])) {
            $this->return_json(E_ARGS,'你已经绑定过了');
        }
        $bool = $this->User_model->db->update('user_detail',$data,['uid'=>$uid]);
        if ($bool) {
            $arrData = ['bank_name'=>$data['bank_name']];
            if (empty($a['bank_num'])) {
                $arrData['bank_num'] = '0';
            }else{
                $arrData['bank_num'] = '1';
            }
            $this->return_json(OK,$arrData);
        } else {
            $this->return_json(E_ARGS);
        }

    }
    /**
     * 更改资金密码
    */
    public function bank_pwd_chang()
    {
        //8.12 密码相关更改
        $data['bank_pwd'] = bank_pwd_md5( $this->P('bank_pwd') );
        $data['new_pwd']  = bank_pwd_md5( $this->P('new_pwd') );
        if($data['bank_pwd'] == $data['new_pwd']){
            $this->return_json(E_ARGS,'新旧密码不能相同');
        }
        $uid = $this->user['id'];

        /*if(strlen($data['bank_pwd']) !=6){
            $this->return_json(E_ARGS,'资金密码只能为6位数字');
        }*/

        /*if(preg_match('/\d{6}/u',$data['new_pwd']) == 0){
            $this->return_json(E_ARGS,'资金密码只能为6位数字');
        }*/
        $userData = $this->User_model->get_one('bank_pwd','user_detail',['uid'=>$uid]);
        if ($userData['bank_pwd'] != $data['bank_pwd']) {
            $this->return_json(E_ARGS,'资金密码错误');
        }
        $bool = $this->User_model->db->update('user_detail',array('bank_pwd'=>$data['new_pwd']),['uid'=>$uid]);
        if ($bool) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_ARGS);
        }

    }

    /**
     * 更改用户登录密码
    */
        public function chang_login_pwd()
    {
        $data['pwd']     = $this->P('pwd');
        $data['new_pwd'] = $this->P('new_pwd');
        if ($data['pwd'] == $data['new_pwd']) {
            $this->return_json(E_ARGS,'新旧密码不能相同');
        }

        //8.12 密码相关更改
        $rule = [
            'pwd'      => 'require',
            'new_pwd'  => 'require',
        ];

        $msg  =[
            'pwd'     => '请输原始入密码' ,
            'new_pwd' => '请输入新密码' ,
        ];

        $this->validate->rule($rule,$msg);//验证数据
        $result   = $this->validate->check($data);
        if(!$result){
            $this->return_json(E_ARGS,$this->validate->getError());//返回错误信息
        }


        $this->load->helper('common_helper');
        $pwd     = user_md5($data['pwd']);
        $new_pwd = user_md5($data['new_pwd']);
        $data = $this->User_model->get_one('','user',['id'=>$this->user['id'],'pwd'=>$pwd ]);

        if ($data) {

            $token = get_auth_headers(TOKEN_CODE_AUTH);
            $tokenKey = 'token:'.TOKEN_CODE_USER.':';
            $this->load->model('Login_model','Login');
            $this->Login->redis_del($tokenKey.$token);
            $token = $this->Login->get_token($data);

            $this->User_model->db->update('user',['pwd'=>$new_pwd], ['id'=>$this->user['id']]);
            $this->return_json(OK,['token'=>$token]);
        }else{
            $this->return_json(E_ARGS,'密码错误');
        }

    }
    /**
     * 我的银行卡   用户绑定 的银行卡
    */
    public function user_card()
    {
        $uid   = $this->user['id'];

        $arr   = $this->User_model->get_one('bank_num,bank_id,bank_name,bank_pwd','user_detail',['uid'=>$uid]);

        if (empty($arr['bank_num'])||empty($arr['bank_name'])) {
            $this->return_json(OK,[])  ;
        }
        unset($arr['bank_name']);
        unset($arr['bank_pwd']);
        $bankx = $this->User_model->bank_list();
        $bank = [];
        foreach($bankx as $k => $v){
            $bank[$v['id']]['bank_name'] = $v['bank_name'];
            $bank[$v['id']]['img']       = $v['img'];
        }

        if(empty($arr['bank_id'])){
            $this->return_json(OK,[]);
        }
        empty($arr['bank_id'])?$bank_name= '':$bank_name = $bank[$arr['bank_id']]['bank_name'];
        !empty($arr['bank_id'])?$img = $bank[$arr['bank_id']]['img']:$img= '';
        !empty($arr['bank_id'])?$background = $arr['bank_id']:$background= '1';
        $arr['bank_name'] = $bank_name;
        $arr['img']       = $img    ;
        $arr['background']= $this->background($background) ;
        isset($arr['bank_num'])?$bank_num = $arr['bank_num']:$bank_num= '';
        $str = '';
        for ($i=0;$i<strlen($bank_num)-8;$i++) {
            $str .= '*';
        }
        $arr['bank_num'] = substr_replace($bank_num,$str,4,-4) ;
        unset($arr['id']);

        $this->return_json(OK,$arr);
    }

    /**
     * 验证银行卡相关的参数
    */
    private function check_card($data,$bool=false)
    {
        //8.12 密码相关更改
        $rule = [
            'bank_id'   => 'require|intGt0' ,//银行id
            'bank_pwd'  => 'require',//取款密码
            'address'   => 'require|chsAlpha',//开户行
        ];

        $msg  = [
            'bank_id'   => '银行id错误' ,//银行id
            'bank_pwd'  => '请输入资金密码',//取款密码
            'address'   => '开户行只能为汉字和字母',//开户行
        ];
        if ($bool) {
            $rule['bank_num'] = "require|luhn|min:16|max:21";
            $msg['bank_num'] = "银行卡号错误";
        }else{
            $rule['bank_num'] = "require|int|min:16|max:21";
            $msg['bank_num'] = "银行卡号错误";
        }

        /*if(strlen($data['bank_pwd']) != 6){
            $this->return_json(E_ARGS,"取款密码长度为6位数字");
        }*/
        $this->validate->rule($rule,$msg);//验证数据
        $result   = $this->validate->check($data);
        if(!$result){
            $this->return_json(E_ARGS,$this->validate->getError());//返回错误信息
        }else{
            return true;
        }
    }


    /**
     * 验证银行卡相关的参数
     */
    private function check_detail($data)
    {
        $rule = [
            'qq'       => 'int',
            'birthday' => 'date',
            'email'    => 'email',
            'idcard'   => 'int',
            'phone'    => 'int',
        ];
        $msg  = [
            'qq'       => 'qq号错误',
            'birthday' => '生日格式错误',
            'email'    => '邮箱格式错误',
            'idcard'   => '身份证号码只能为数字',
            'phone'    => '手机号码 ',
        ];
        $this->validate->rule($rule,$msg);//验证数据
        $result   = $this->validate->check($data);
        if(!$result){
            $this->return_json(E_ARGS,$this->validate->getError());//返回错误信息
        }else{
            return true;
        }
    }


    /**
     * 用户详细的信息展示
    */
    public function user_detail()
    {
        $uid = $this->user['id'];
        $where = [
            'uid' => $uid,
        ];
        $str   = 'birthday,phone,qq,email,idcard';
        $arr   = $this->User_model->get_one($str,'user_detail',$where);

        $str = '';
        for ($i=0;$i<strlen($arr['idcard'])-10;$i++) {
            $str .= '*';
        }
        $arr['idcard'] = substr_replace($arr['idcard'],$str,5,-5) ;
        $arr['birthday'] = date('Y-m-d',$arr['birthday']);
        $this->return_json(OK,$arr);
    }

    /**
     * 更改用户信息
    */
    public function updata_detail()
    {
        $data = [
            'qq'       => $this->P('qq'),
            'birthday' => $this->P('birthday'),
            'email'    => $this->P('email'),
            'phone'    => $this->P('phone'),
        ];


        $this->check_detail($data);
        if(empty($data)){
            $this->return_json(E_ARGS);
        }
        foreach ($data as $k => $v) {
            if (empty($v)) {
                unset($data[$k]);
            }
        }
        if (!empty($data['birthday'])) {
            $data['birthday'] = strtotime($data['birthday']);
        }

        $uid = $this->user['id'];
        $boo = $this->User_model->db->update('user_detail',$data,['uid' => $uid]);

        if ($boo) {
            $this->return_json(OK, '更改成功');
        }else{
            $this->return_json(E_OK,'更改失败请重试');
        }
    }

    //用户上传头像
    public function user_head(){
        if($this->P('url')){
            $url = $this->P('url');
            $updata = [
                'img' => $url
            ];
        }else{
            $jsonStr  = $this->up_logo_do(false);
            $jsonData = json_decode($jsonStr,true);
            $updata = [
                'img' => $jsonData['result']
            ];
        }

        $bool = $this->User_model->db->update('user_detail',$updata,[ 'uid' => $this->user['id'] ]);
        if ($bool) {
            // 更新到 redis 中
            $this->user['img'] = $updata['img'];
            $this->User_model->redis_set('token:'. TOKEN_CODE_USER .':'. $this->_token,
                json_encode($this->user));

            $this->return_json(OK);
        } else {
            $this->return_json(E_ARGS,'请重试');
        }
    }
    /**
     * 返回对应银行背景色
    */
    private function background($bank_id){
        switch($bank_id){
            case 1:
                return '#CC2828';
            case 2:
                return '#CC2828';
            case 3:
                return '#0F3D93';
            case 4:
                return '#D41A23';
            case 5:
                return '#3B91BE';
            case 7:
                return '#CC2828';
            case 8:
                return '#248B42';
            case 9:
                return '#65AC69';
            case 10:
                return '#F2191E';
            case 11:
                return '#043671';
            case 12:
                return '#CC2828';
            case 13:
                return '#CC2828';
            case 14:
                return '#E56E26';
            case 15:
                return '#0F6BB4';
            case 16:
                return '#EC9A3B';
            case 17:
                return '#2E224D';
            case 18:
                return '#D4091D';
            case 19:
                return '#DEB62E';
            case 20:
                return '#98D2EA';
            case 21:
                return '#283F8D';
            case 22:
                return '#E30B20';
            case 23:
                return '#D1091C';
            case 24:
                return '#1072B4';
            case 25:
                return '#07448C';
            case 26:
                return '#DB0A1E';
            case 27:
                return '#F7BD2C';
            case 28:
                return '#E8202C';
            case 29:
                return '#BB2427';
            case 30:
                return '#E30B20';
            case 31:
                return '#F1C137';
            case 32:
                return '#14458F';
            case 33:
                return '#1B8F40';
            case 34:
                return '#051C89';
            case 35:
                return '#1B8F40';
            case 36:
                return '#9D2625';
            case 37:
                return '#9F3928';
            case 38:
                return '#159848';
            case 39:
                return '#1B8F40';
            case 40:
                return '#0F6AB4';
            case 41:
                return '#EA0B19';
            case 42:
                return '#C81F25';
            case 43:
                return '#AB2428';
            case 50:
                return '#074384';
            default :
                return '#E4E4E4';
        }
    }

    public function user_ip_chang(){
        echo long2ip(3232237670);
    }

    /**
     * 定时刷新接口
     */
    public function refresh()
    {
        $this->load->model('Login_model');
        $userData = $this->Login_model->get_one_user($this->user['username']);
        $result['type'] = $userData['type'];
        $result['status'] = $userData['status'];
        $token = $this->Login_model->get_token($userData,true);
        $result['token'] = $token;
        $result['refresh_token'] = TOKEN_USER_LIVE_TIME*3600;
        $this->return_json(OK, $result);
    }
}

