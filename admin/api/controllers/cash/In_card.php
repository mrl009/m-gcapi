<?php
/**
 * @模块   优惠卡入款
 * @版本   Version 1.0.0
 * @日期   2017-03-31
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class In_card extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/In_card_model', 'card');
        $this->load->model('MY_Model', 'core');
    }

    public function index()
    {
        $type_arr = array(1=>'username',2=>'pwd',3=>'ip');
        $type = (int)$this->G('type') ? (int)$this->G('type') : 2; //查询类型：1用户名 2优惠卡密码 3充值过的IP
        $chaxun = $this->G('chaxun');
        if ($type==3 && !empty($chaxun)) {
            if (!preg_match('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', $chaxun)) {
                $this->return_json(E_ARGS, '请输入正确的IP！');
            }
            $basic['b.'.$type_arr[$type]] = bindec(decbin($chaxun));
        } elseif (!empty($chaxun)) {
            $basic[$type_arr[$type]] = $chaxun;
        }
        $basic['is_used'] = $this->G('use')?$this->G('use'):1;//2未使用 1已使用
        //默认查询已使用的点卡
        if (empty($basic['is_used'])) {
            $basic['is_used'] = 1;
        }
        //$basic['substring(a.order_num,1,4)'] = $this->G('ymt');//编号前4位 date('ym',time())
        $ymt = $this->G('ymt')?$this->G('ymt'):date('ym', time());//编号前4位
        $basic['use_time >='] = $this->G('time_start')?strtotime($this->G('time_start'). '00:00:00') : '';
        $basic['use_time <='] = $this->G('time_end')?strtotime($this->G('time_end'). '23:59:59') : '';
        $condition['join'] = 'black_list_ip';
        $condition['on'] = 'a.uid=b.uid';
        $sort_arr = array('id','pwd','price','is_used','uid','username','use_time');

        $page   = array(
                'page'  => $this->G('page')?(int)$this->G('page'):1,
                'rows'  => ($this->G('rows') and $this->G('rows')<1000)?(int)$this->G('rows'):50,
                'order' => 'desc',//排序方式
                'sort'  => 'use_time',//排序字段
                'total' => -1,
        );

        if (!empty($page['sort'])) {
            if (!in_array($page['sort'], $sort_arr)) {
                $this->return_json(E_ARGS, '参数错误:'.$page['sort']);
            }
        }
        $this->core->select_db('card');
        $arr = $this->core->get_list('a.*,b.ip,b.ip_status,b.ip_count', 'card_'.$ymt, $basic, $condition, $page);
        $this->core->select_db('private');
        foreach ($arr['rows'] as $kk => $vv) {
            if(!empty($vv['ip'])){
            	$arr['rows'][$kk]['ip'] = $vv['ip'];
            }
            if(!empty($vv['use_time'])) {
				$arr['rows'][$kk]['use_time'] = date('Y-m-d H:i:s', $vv['use_time']);
			}
            $arr['rows'][$kk]['is_card']='';
            if (!empty($vv['uid'])) {
                $is_card = $this->core->get_one('is_card', 'user', array('id'=>$vv['uid']));
                if (!empty($is_card)) {
                    $arr['rows'][$kk]['is_card'] = $is_card['is_card'];
                }
            }
        }
        /**** 新站点获取 ****/
        // $resu['cishu'] = $this->core->get_one('ip_cishu,user_card_cishu', 'set', array('id'=>1));
        $resu['cishu'] = $this->core->get_gcset(['ip_cishu','user_card_cishu']);
        /**** end ****/
        $top4_list =  $this->card->calculate_Top4No_list();
        $jieguo  = array('total'=>$arr['total'],'cishu'=>$resu['cishu'],'top4list'=>$top4_list,'rows'=>$arr['rows']);
        $this->return_json(OK, $jieguo);
    }


    /**
     * 获取全部头部信息
     * @return mixed
     */
    private function getallheaders()
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }


    //优惠卡入款 单项设置
    public function set_one()
    {
        $ip_status = $this->P('ip_status');
        $is_card = $this->P('is_card');
        $uid = $this->P('uid');
        if (empty($uid)) {
            $this->return_json(E_ARGS, 'uid参数错误!');
        }
        if ($ip_status!=1 && $ip_status!=2 && $is_card!=1 && $is_card!=2) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        if (!empty($ip_status)) {
            $this->M->select_db('card');
            $arr['ip_status'] = $ip_status;
            $where['uid'] = (int)$uid;
            $is = $this->M->write('black_list_ip', $arr, $where);
        } else {
            $arr['is_card'] = $is_card;
            $where['uid'] = (int)$uid;
            $con['orderby']['time'] = 'desc';
			$ip = $this->M->get_one('ip','black_list_ip',$where,$con);
			if(empty(ip['ip'])){
				$this->return_json(E_ARGS, '找不到该IP!');
			}
            $is = $this->M->write('user', $arr, $ip);
        }
        $this->load->model('log/Log_model', 'lo');
        if ($is) {
            $logData['content'] = '彩豆入款-设置:uid为'.$uid.'的'.array_keys($arr)[0].'='.array_values($arr)[0].'成功';
            $this->lo->record($this->admin['id'], $logData);
            $this->return_json(OK, array('status'=>'OK','msg'=>'执行成功'));
        } else {
            $logData['content'] = '彩豆入款-设置:uid为'.$uid.'的'.array_keys($arr)[0].'='.array_values($arr)[0].' 失败';
            $this->lo->record($this->admin['id'], $logData);
            $this->return_json(E_OP_FAIL, '操作失败！');
        }
    }



    //优惠卡入款 总体参数设置
    public function set_all()
    {
        //$this->admin['id'] = 1;
        $arr = array();
        $ip_cishu = $this->P('ip_cishu');
        $user_card_cishu = $this->P('user_card_cishu');
        $sid = $this->P('sid');//预留多站点功能
        if (empty($user_card_cishu) || empty($ip_cishu) || !is_numeric($user_card_cishu)
                || !is_numeric($ip_cishu) || $ip_cishu<0 || $user_card_cishu<0) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        //$arr['ip_cishu'] =  $ip_cishu;
        //$arr['user_card_cishu'] =  $user_card_cishu;
        $where['id'] = empty($sid)?$sid=1:$sid;
        /**** 新站点获取 ****/
        // $duibi = $this->core->get_one('ip_cishu,user_card_cishu', 'set', array('id'=>1));
        $duibi = $this->core->get_gcset(['ip_cishu','user_card_cishu']);
        /**** end ****/
        if ($ip_cishu != $duibi['ip_cishu']) {
            $arr['ip_cishu'] =  $ip_cishu;
        }
        if ($user_card_cishu != $duibi['user_card_cishu']) {
            $arr['user_card_cishu'] =  $user_card_cishu;
        }
        if (!empty($arr)) {
            /**** 新站点配置 ****/
            $is = $this->core->set_gcset($arr);
            // $is = $this->core->write('set', $arr, $where);
            /**** end ****/
        } else {
            $is = false;
        }
        $this->load->model('log/Log_model', 'lo');
        if ($is) {
            $logData['content'] = '彩豆入款-充值次数设置:每个用户为'.$user_card_cishu.'次，每个IP为'.$ip_cishu.'次 成功';
            $this->lo->record($this->admin['id'], $logData);
            $this->return_json(OK, array('status'=>'OK','msg'=>'执行成功'));
        } else {
            $logData['content'] = '彩豆入款-充值次数设置:每个用户为'.$user_card_cishu.'次，每个IP为'.$ip_cishu.'次 失败';
            $this->lo->record($this->admin['id'], $logData);
            $this->return_json(E_OP_FAIL, '操作失败！');
        }
    }
}
