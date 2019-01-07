<?php
/**
 * 用户的银行卡 微信 支付宝管理
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/9/25
 * Time: 14:56
 */
class User_card extends MY_Controller{

    private  $phone;
    private  $is_unique_name;
    private  $is_unique_bank;
    private  $postData;
    private  $is_phone;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/User_model');
    }

    /**
     * 用户银行卡(支付宝/微信/银行卡)
    */
    public function user_card()
    {
         $this->return_json(OK,$this->User_model->member_card());
    }

    /**
     * 用户银行卡(支付宝/微信/银行卡)新版
     */
    public function new_user_card()
    {
        $this->return_json(OK,$this->User_model->new_member_card());
    }

    /**
     * 添加银行卡
    */
    public function card_add()
    {
        $this->postData = [
            'bank_id' => $this->P('bank_id'),
            'num' => $this->P('num'),
            'address' => $this->P('address'),
            'bank_pwd' => $this->P('bank_pwd'),
            'bank_name' => $this->P('bank_name'),
            'phone' => $this->P('phone'),
        ];
        $data = $this->User_model->get_one('phone','user_detail' , ['uid' => $this->user['id']]);
        $gcSet = $this->User_model->get_gcset();
        $this->is_phone = empty($data['phone']) ? (int)$gcSet['is_phone'] : 0;
        $this->is_unique_bank = $gcSet['is_unique_bank'];
        $this->is_unique_name = $gcSet['is_unique_name'];
        $this->check_card($this->postData,$gcSet);
        /**
         * 上传二维码
        */
        if (in_array($this->postData['bank_id'], [ 51,52]) &&
            $this->from_way == FROM_ANDROID) {
            $jsonStr  = $this->up_logo_do(false);
            $jsonData = json_decode($jsonStr,true);
            $this->postData['address'] = $jsonData['result'];
        } elseif (empty($this->postData['address'])) {
            $file = $this->P('file');
            if (empty($file) ||
                !in_array(strtolower(substr($file, -3)), ['jpg','png', 'jpeg', 'gif'])) {
                $this->return_json(E_ARGS,'二维码为空或者不是jpg和png格式');
            }
            $this->postData['address'] = $this->P('file');
        }

        $keys = "temp:user_bing_bank:".$this->user['id'];
        $bool = $this->User_model->fbs_lock($keys);
        if (!$bool) {
            $this->return_json(E_ARGS,'系统繁忙');
        }
        $user_card = $this->User_model->member_card();

        if (!empty($user_card)) {
            $bankArr = array_column($user_card,'bank_id');
            if (in_array($this->postData['bank_id'],$bankArr)) {
                $echoStr="你已经绑定过".User_model::bank_id_name($this->postData['bank_id']);
                $this->return_json(E_ARGS,$echoStr);
            }
        }

        $pwdError = "user:error:bank_add";
        //if (!empty($this->User_model->member['bank_pwd']) && !empty($this->User_model->member['bank_name']) ) {
        if (!empty($this->User_model->member['bank_pwd'])) {
            if ($this->User_model->member['bank_pwd'] != bank_pwd_md5($this->postData['bank_pwd'])) {
                $error = $this->User_model->redis_HINCRBY($pwdError,$this->user['id'],1);
                if ($error >= USER_BANK_PWD_ERROR) {
                    $this->load->model('Login_model');
                    $this->User_model->stop_user($this->user['id']);
                    $this->Login_model->login_be_out($this->user['id']);
                    $this->User_model->redis_del($pwdError);
                    $this->return_json(E_ARGS,'资金密码错误,账户已停用,请联系客服!');
                }else{
                    $this->return_json(E_ARGS,'资金密码错误');
                }
            }
            $this->User_model->redis_Hdel($pwdError,$this->user['id']);
        }

        $this->User_model->select_db('private');
        $this->card_unique();
        $this->phone_unique();
        $this->bank_name_unique();

        $data = $this->userUpData();
        $bool = $this->User_model->write('user_detail',$data,[ 'uid' => $this->user['id'] ]);
        if ($bool) {
            if ((51 > $this->postData['bank_id']) || 
              (65 < $this->postData['bank_id'])) {
                $type = 6;
            } elseif ($this->postData['bank_id'] == 51){
                $type = 7;
            } elseif ($this->postData['bank_id'] == 52){
                $type = 8;
            }
            $this->User_model->zhuceyouohui($this->user['id'],$this->user['username'],$type,$this->user['agent_id']);
            $this->User_model->fbs_unlock($keys);
            $this->return_json(OK,'更新成功');
        }else{
            $this->User_model->fbs_unlock($keys);
            $this->return_json(E_ARGS,'更新失败请重试');
        }
    }


    /**
     * 获取用户支持的银行卡列表
    */
    public function bank_list()
    {
        $where = [
            'status' => 1,
            'id <=' => 52,
        ];
        $temp = $this->User_model->base_bank_online('bank',$where);
        $this->return_json(OK,$temp);
    }

    /**
     * 格式化要更新的数据
     * $bank_id array 银行ID
    */

    private function userUpData()
    {
        $res = [];
        //$res['bank_name'] = $this->postData['bank_name'];
        $res['bank_pwd'] = bank_pwd_md5($this->postData['bank_pwd']);
        if (!empty($this->phone)) {
            $res['phone'] = $this->phone;
        }
        switch ($this->postData['bank_id']) {
            case $this->postData['bank_id']<=50:
                $res['bank_name'] = $this->postData['bank_name'];
                $res['bank_num'] = $this->postData['num'];
                $res['address'] = $this->postData['address'];
                $res['bank_id'] = $this->postData['bank_id'];
                break;
            case $this->postData['bank_id'] > 65:
                $res['bank_name'] = $this->postData['bank_name'];
                $res['bank_num'] = $this->postData['num'];
                $res['address'] = $this->postData['address'];
                $res['bank_id'] = $this->postData['bank_id'];
                break;
            case 51:
                $res['alipay'] = $this->postData['num'];
                $res['alipay_qrcode'] = $this->postData['address'];
                break;
            case 52:
                $res['wechat'] = $this->postData['num'];
                $res['wechat_qrcode'] = $this->postData['address'];
                break;
        }
        return $res;
    }

    /**
     * 检查手机号唯一
    */
    private function phone_unique()
    {
        $data = $this->postData;
        if ($this->is_phone ==1 ) {
            $where = [
                'phone' => $this->P('phone'),
                'uid !=' => $this->user['id']
            ];
            $phoneData = $this->User_model->get_one('uid','user_detail',$where);
            if (!empty($data['phone'])) {
                if (!empty($phoneData)) {
                    $this->return_json(E_ARGS,'手机号已经使用过了');
                }

            }
            $this->phone= $data['phone'];
        }

    }
    /**
     * 检查姓名
     */
    private function bank_name_unique()
    {
        /*if($this->is_unique_name== 1 && !empty($this->User_model->member['bank_name'])){
            $a = $this->User_model->get_one('uid,bank_name,bank_num','user_detail',['bank_name'=>$this->postData['bank_name']]);
            if(!empty($a)&&$a['uid'] != $this->user['id']){
                $this->return_json(E_ARGS,'姓名不能重复请联系客服');
            }
        }*/
        $flag = true;
        $uid = $this->user['id'];
        $bankName = $this->postData['bank_name'];
        if (!empty($uid)) {
            $user = $this->User_model->get_one('bank_name', 'user_detail', array('uid' => $uid));
            if (empty($user)) {
                $this->return_json(E_DATA_EMPTY, '没有该用户');
            }
            if ($user['bank_name'] == $bankName) {
                $flag = false;
            }
        }

        if ($flag) {
            $where = array(
                'uid !=' => $uid,
                'bank_name' => $bankName
            );
            if ($this->is_unique_name == 1) {
                $user = $this->User_model->get_one('uid', 'user_detail', $where);
                if (!empty($user)) {
                    $this->return_json(E_ARGS, '姓名不能重复请联系客服');
                }
            }
        }
    }

    /**
     * 判断银行卡唯一
     * data array 用户传入参数
     * is_bank_unique int 银行卡号是否唯一
     * @return bool;
    */
    private function card_unique()
    {
        $data  = $this->postData;
        $where = [];
        switch ($data['bank_id']) {
            case $data['bank_id']<=50 && $this->is_unique_bank==1:
                $where['bank_num'] =$data['num'];
                break;
            case 51:
                $where['alipay'] =$data['num'];
                break;
            case 52:
                $where['wechat'] =$data['num'];
                break;
        }

        if (!empty($where)) {
            $data = $this->User_model->get_one('uid','user_detail',$where);
            if (!empty($data) && $this->user['id'] != $data['uid']) {
                $echoStr = User_model::bank_id_name($this->postData['bank_id']);
                $echoStr .= "已经有人使用了,请联系管理员";
                $this->return_json(E_ARGS,$echoStr);
            }
        }
        return true;
    }


    /**
     * !用户不能修改银行卡
     * 更改用户银行卡
    */
    private function card_update()
    {

    }

    /**
     * post 参数验证
    */
    private function check_card($data,$set)
    {
        // 支付宝 微信通道是否开启验证
        if ($data['bank_id'] == 51 && $set['is_open_alipay'] == 0) {
            $this->return_json(E_ARGS, '支付宝通道已经关闭');
        }
        if ($data['bank_id'] == 52 && $set['is_open_wechat'] == 0) {
            $this->return_json(E_ARGS, '微信通道已经关闭');
        }

        //8.12 密码相关更改
        $rule = [
            'bank_id'   => 'require|intGt0' ,//银行id
            'bank_pwd'  => 'require|length:32',//取款密码
            //'bank_name' => 'require|chs_alpha',//开户行
        ];

        $msg  = [
            'bank_id'   => '银行id错误' ,//银行id
            'bank_pwd'  => '请输入资金密码',//取款密码
            //'bank_name'   => '姓名只能为汉字和字母和·,不能·开头和结尾',//开户行
        ];
        // 12.11 去除支付宝微信用户名输入
        if ($data['bank_id'] != 51 && $data['bank_id'] != 52) {
            $rule['bank_name'] = 'require|chs_alpha';
            $msg['bank_name'] = '姓名只能为汉字和字母和·,不能·开头和结尾';
        }
        //edit lqh 2018/05/03 
        if (51 == $data['bank_id'])
        {
            $rule['num'] = 'require|alpha@Num|min:6|max:20';
            $msg['num.require']  = '请填入支付宝账号';
            $msg['num.alpha@Num']  = '支付宝账号不能有特殊字符';
            $msg['num.min']  = '支付宝账号长度不能小于6';
            $msg['num.max']  = '支付宝账号长度不能大于20';
        } elseif (52 == $data['bank_id']) {
            $rule['num'] = 'require|alpha@Num|min:6|max:20';
            $msg['num.require']  = '请填入微信账号';
            $msg['num.alpha@Num']  = '微信账号不能有特殊字符';
            $msg['num.min']  = '微信账号长度不能小于6';
            $msg['num.max']  = '微信账号长度不能大于20';
        } elseif ((51 > $data['bank_id']) || (65 < $data['bank_id'])) {
            if ($set['bank_num_check'])
            {
                $rule['num'] = "require|luhn|min:16|max:21";
                $msg['num'] = "银行卡号错误";
            } else {
                $rule['num'] = "require|int|min:16|max:21";
                $msg['num'] = "银行卡号错误";
            }
            $rule['address'] = 'require';
            $msg['address']  = '开户行不能为空';
        } else {
            $this->return_json(E_ARGS,'非法参数bank_id');
        }
        if ($this->is_phone) {
            $rule['phone'] = 'require|phone';
            $msg['phone']  = '请输入正确的手机号';
        }
        $this->validate->rule($rule,$msg);//验证数据
        $result = $this->validate->check($data);
        if(!$result){
            $this->return_json(E_ARGS,$this->validate->getError());//返回错误信息
        }else{
            return true;
        }
    }

}
