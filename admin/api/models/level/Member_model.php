<?php
/**
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/3/27
 * Time: 10:39
 */

class Member_model extends MY_Model
{

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }
    public $username ;//会员的姓名
    /**
     * 统计会员总数和今日注册数于上在线会员数
    */
    public function count_user()
    {
        $day = date('Y-m-d');
        $time = strtotime($day);
        $sql  = "SELECT count(*) sum1,
                 count(CASE when  addtime > $time THEN balance END) sum2
                 FROM gc_user";
        $datax = $this->db->query($sql)->row_array();
        $data = [];
        $data['bank_name'] = $datax['sum2'];
        $data['username']  = $datax['sum1'];
        $onlne = $this->check_online();
        $data['online']= count($onlne);
        $data['user']  = $onlne;
        $data['sx_credit']=sprintf('%0.2f', $this->get_sx_set('credit'));
        $bets_num = $this->db->select('COUNT(DISTINCT uid) as total')
            ->from('report')
            ->where('valid_price > 0')
            ->where("report_date = '$day'")
            ->limit(1)
            ->get()->row_array();
        $data['bets_num'] = $bets_num['total'];
        return $data;
    }


    /**
     * 会员移动层级
    */
    public function move_level($data, $new_id)
    {
        $arr =[];
        foreach ($data as $k => $v) {
            foreach ($data as $kk => $vv) {
                if ($v['level_id'] == $vv['level_id']) {
                    if (!isset($arr[$v['level_id']])) {
                        $arr[$v['level_id']] =[];
                    }
                    array_push($arr[$v['level_id']], $v['id']);
                }
            }
        }
        foreach ($arr as $k => $v) {
            $arr[$k] = array_unique($v);
        }

        $total =[
            'price' =>0,
            'num'   =>0
        ];

        $user_num = 0;
        foreach ($arr as $k => $v) {
            $user_num += count($v);
            $where2 = [
                'wherein' =>['uid'=>$v]
            ];
            $str =  "sum(in_company_total+";//公司入款
            $str .= "in_online_total+";//线上入款
            $str .= "in_people_total";//人工入款
            $str .= ") price,sum(";//点卡充值
            $str .= "in_company_num+in_online_num+in_people_num) num";//点卡充值

            $arr  = $this->get_all($str, 'cash_report', [], $where2);
            $data = $arr[0];
            foreach ($data as $kk=>$vv) {
                if (empty($vv)) {
                    $data[$kk] = 0;
                }
            }

            $data['num']   < 0?$data['num']   =0 :true;
            $data['price'] < 0?$data['price'] =0 :true;
            $where  = ['id'=>$k];
            $this->db->set('use_times', 'use_times-'.$data['num'], false)
                     ->set('user_num', 'user_num-'.count($v), false)
                     ->set('use_total', 'use_total-'.$data['price'], false)
                     ->where($where)->update('level');

            $total['price'] += $data['price'];
            $total['num']   += $data['num'];
        }
        $where  = ['id'=>$new_id];
        $this->db->set('use_times', 'use_times+'.$total['num'], false)
                 ->set('use_total', 'use_total+'.$total['price'], false)
                 ->set('user_num', 'user_num+'.$user_num, false)
                 ->where($where)->update('level');
    }

    /**
     * 会员额度统计
    */
    public function count_cash($refresh=null)
    {
        $key = 'member:quoat_count';//额度统计
        $json = $this->redis_get($key);
        if (empty($json) || $refresh ==1) {
            $sql = "SELECT SUM(CASE when status =1 THEN balance END ) sum1,
                    SUM(CASE when  status=2 THEN balance END) sum2,
                    SUM(CASE when  status=3 THEN balance END) sum3 FROM gc_user
                   ";

            $arr = $this->db->query($sql)->row_array();
            $arrx['enable']          = $arr['sum1'];
            $arrx['disable']        = $arr['sum2'] + $arr['sum3'];
            $arrx['update_time'] = date('Y-m-d H:i:s');
            $this->redis_set($key, json_encode($arrx));
        } else {
            $arrx = json_decode($json, true);
        }

        return $arrx;
    }

    /**
     * 更新会员信息
     * @param $data array 会员的详细信息
     * @param  $id int 会员的id
     * @param $other array  会员其他的数据
    */
    public function user_update($data, $id, $other)
    {
        $this->db->trans_start();

        $user = $this->get_one('level_id,id,username', 'user', ['id'=>$id]);
        $userData = [];

        $this->username = $user['username'];
        if ($other['level_id'] != $user['level_id'] && !empty($other['level_id'])) {
            $this->move_level([$user], $other['level_id']);
            $userData['level_id'] = $other['level_id'];
        }
        if (!empty($other['pwd'])) {
            $userData['pwd'] = $other['pwd'];
            $this->load->model('Login_model');
            $this->Login_model->user_be_out($id);
        }
        if (isset($other['out_num'])) {
            $userData['out_num'] = (string)$other['out_num'];
        }
        if (isset($data['max_game_price'])) {
            $userData['max_game_price'] = $data['max_game_price'];
            unset($data['max_game_price']);
        }
        if (!empty($userData)) {
            //.更改了会员信息要把redis删掉
            $this->redis_select(6);
            $this->redis_hdel('user', $user['id']);
            $this->redis_select(5);
            $this->db->update('user', $userData, ['id'=>$id]);
        }
        ////////////
        $a    = $this->get_one('*', 'user_detail', ['uid'=>$id]);
        if (empty($a)) {
            if (!empty($data['bank_num']) || !empty($data['bank_pwd']) || !empty($data['bank_name']) ) {
                $ci  = get_instance();
                $str = "管理员{$ci->admin['username']} 添加会员信息".json_encode($data);
                wlog(APPPATH.'logs/chang_user_'.$this->sn.'_'.date('Ym').'.log', $str);
            }
            $this->db->insert('user_detail', array_merge($data, ['uid'=>$id]));
        } else {
            if (isset($data['bank_pwd'])) {
                $b = [
                    $data['bank_num'], $data['bank_pwd'], $data['bank_id'] , $data['address']
                ];
            }else{
                $b = [
                    $data['bank_num'], $data['bank_id'] , $data['address']
                ];
            }

            $c = [
                $a['bank_num'], $a['bank_pwd'], $a['bank_id'], $a['address']
            ];

            if (!empty($data['bank_name'])) {
                $b[] = $data['bank_name'];
                $c[] = $a['bank_name'];
            }
            if (count(array_diff($b,$c))>0) {
                $ci  = get_instance();
                $str = "管理员{$ci->admin['username']} 更改该会员信息".json_encode($a)."\n".json_encode($data);
                wlog(APPPATH.'logs/chang_user_'.$this->sn.'_'.date('Ym').'.log', $str);
            }
            $this->db->update('user_detail', $data, ['uid'=>$id]);
        }

        return $this->db->trans_complete();
    }


    /**
     * token 更改后判断会员是否在线
     * @param $uid int
     * @return  array
    */
    private function check_online_user($uid){
        $key      = "token_ID:".TOKEN_CODE_USER.":";
        $tokenkes = 'token:'.TOKEN_CODE_USER.":";
        if (!empty($uid)) {
           return $this->redis_EXISTS($key.$uid);
        }
        $arr = $this->redis_keys($key.'*');
        $temp = [];
        foreach ($arr as $value) {
            $temp[] = substr($value,strrpos($value,':')+1);
        }
        return $temp;
    }

    /**
     * @param $uid int 会员id
     * @param $type string
     * @return  array;
     * 判断会员是否在线
    */
    public function check_online($uid =null, $type = 'user')
    {
        if ($type =='user') {
            return $this->check_online_user($uid);
            $key      = "token_ID:".TOKEN_CODE_USER.":";
            $tokenkes = 'token:'.TOKEN_CODE_USER.":";
            $jian     = "expiration";
        } else {
            $key      = "token_ID:".TOKEN_CODE_ADMIN.":";
            $tokenkes = 'token:'.TOKEN_CODE_ADMIN.":";
            $jian     = "login_time";
        }

        $tokenArr = array();
        if ($uid) {
            $token =  $this->redis_GET($key.$uid);
            if ($uid ==7 && !empty($_GET['test'])) {
                if (empty($token)) {
                    print_r($key.$uid);
                    die;
                }
            }
            array_push($tokenArr, $token);
        } else {
            $keys  = $this->redis_keys($key."*");
            foreach ($keys as $value) {
                $temp = $this->redis_GET(substr($value, strlen($this->sn)+1));
                $tokenArr[]=$temp;
            }
        }


        $offLine = TOKEN_USER_OFF_LINE*60;
        $online = [];
        foreach ($tokenArr as $value) {
            $json = $this->redis_get($tokenkes.$value);
            $onlineData = json_decode($json, true);
            if (!isset($onlineData['be_outed_time']) && !empty($onlineData)&& $onlineData[$jian]+$offLine > $_SERVER['REQUEST_TIME']) {
                array_push($online, $onlineData['id']);
            }
        }
        return $online;
    }
}
