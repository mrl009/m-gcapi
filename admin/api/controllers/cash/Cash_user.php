<?php
/**
 *
 * 会员分析 有效会员 会员查询
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/4/4
 * Time: 15:09
 */
defined('BASEPATH') OR exit('No direct script access allowed');


class Cash_user extends MY_Controller
{

    function __construct() {
        parent::__construct();
        $this->load->model('cash/Cash_user_model');
    }

    public function index() {

        echo 'hello ';
    }

    /**
     * 会员优惠统计
     * 查询时间限制为2个月
     *
    */
    public function discount_count()
    {

        $agent_id = (int)$this->G('agent_id');
        $start      = $this->G('start') OR $start = date('Y-m-d',strtotime('-1 day'));;
        $username   = trim($this->G('username'));
        $end        = $this->G('end') OR    $end = date('Y-m-d');
        if ( (strtotime($end) - strtotime($start) > ADMIN_QUERY_TIME_SPAN )){
            $this->return_json(E_ARGS,'查询时间跨度不能超过2个月');
        }

      
        $start = date('Y-m-d',strtotime($start.' 00:00:00'));
        $end   = date('Y-m-d',strtotime($end.' 23:59:59'));

        $where = [
            'a.report_date >=' => $start,
            'a.report_date <=' => $end,
            'a.return_price >' => 0.0001
        ];
        if ($agent_id) {
            $where['a.agent_id'] = $agent_id;
        }

        if(!empty($username)) {
            $uid = $this->Cash_user_model->get_one('id', 'user',['username'=>$username]);
            $where['a.uid'] = empty($uid) ? '0' : $uid['id'];
        }
        $page  = (int)$this->G('page')  ;
        $rows  = $this->G('rows') ;
        $order = $this->G('order');
        $sort  = $this->G('sort')  ;
        if ($page <= 0) {
            $page = 1;
        }
        if(!in_array($order,['desc,asc'])){
            $order = 'desc';
        }

        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'order' => $order,
            'sort'  => $sort,
            'total' => 10000
        );

        $where2 = array('groupby' => array('a.uid'));
        $str = 'sum(a.valid_price) out_return_water,sum(a.return_price) xiaoji,a.uid as user_id, a.agent_id as agent_id';
        $arr = $this->Cash_user_model->get_list($str,'report',$where,$where2,$page);
        $arr['rows'] = $this->Cash_user_model->_id_to_name($arr['rows']);
        $floor['username']   = count($arr['rows']);
        $floor['xiaoji'] = '0';
        $floor['out_return_water'] = '0';
        foreach ($arr['rows'] as $k=>$v) {
            $arr['rows'][$k]['xiaoji']  = round($v['xiaoji'],3);
            $floor['xiaoji']           += round($v['xiaoji'],3);
            $floor['out_return_water'] += round($v['out_return_water'],3);
        }
        $floor = ['footer'=>$floor];

        
        /*** 去掉多余的小数点 ***/
        $arr = stript_float($arr);
        $floor = stript_float($floor);
        $this->return_json(OK,array_merge($arr,$floor));

    }

    /**
     * 会员查询
     * 查询区间限制2个月
    */
    public function user_select()
    {

        $start    = $this->G('start');
        $end      = $this->G('end');
        $username = trim($this->G('username'));
        $agent_id = (int)$this->G('agent_id');
        if ( (strtotime($end) - strtotime($start) > ADMIN_QUERY_TIME_SPAN )){
            $this->return_json(E_ARGS,'查询时间跨度不能超过2个月');
        }
        if (!empty($start)) {
            $start.=' 00:00:00';
            if (empty($end)) {
                $end = date('Y-m-d H:i:s');
            }else{
                $end .= '23:59:59';
            }
        }

        $page   = array(
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->G('order'),
            'sort'  => $this->G('sort'),
            'total' => -1,
        );

        $str = 'b.phone,b.qq,b.email,b.addip,b.bank_name,a.id,a.username,a.balance,a.addtime,a.agent_id';
        $where =[];
        if ($start){
            $where['a.addtime >'] = strtotime($start);
            $where['a.addtime <'] = strtotime($end);
        }
        $where2 = [
            'join'=>'user_detail',
            'on'=>'a.id=b.uid'
        ];
        if ($username) {
            $where  = ['a.username' => $username];
        }
        if ($agent_id) {
            $where['a.agent_id'] = $agent_id;
        }
        $arr = $this->Cash_user_model->get_list($str,'user',$where,$where2,$page);
        $arr['rows'] = $this->Cash_user_model->_agent_to_name($arr['rows']);
        foreach ($arr['rows'] as $k => &$v) {
            $v['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
            $v['addip']   = $v['addip'];
        }

        /**** 格式化小数点 ****/
        $arr['rows'] = stript_float($arr['rows']);
        
        $this->return_json(OK,$arr);
    }



    /**
     * user_count 会员统计 会员管理
     *
    */
    public function user_count(){
        $uid = $this->G('uid');
        if($uid<=0||empty($uid)){
            $this->return_json('id错误');
        }
        /*$where = [
            'user.id' => $uid
        ];
        $where2 = [
            'join'=>'cash_report',
            'on'  =>'user.id=b.uid',
        ];
        $str = "user.username,user.addtime,user.update_time logintime,user.loginip ,user.id,user.balance,user.login_times login_num,";//user
        $str .= "user.max_income_price,user.max_out_price,";
        $str .= "SUM(b.in_company_num+in_online_num+in_people_num) as in_t_num,";//总入款笔数
        $str .= "SUM(b.in_company_total+in_online_total+in_people_total) as in_t_totl,";//总存入金额
        $str .= "SUM(b.out_people_num+out_company_num) as out_t_num,";//总出款笔数
        $str .= "SUM(b.out_people_total+out_company_total) as out_t_totl ,";//总出款数
        $str .= "SUM(b.in_company_discount+b.in_online_discount+b.in_people_discount+b.in_card_total+b.in_register_discount+b.activity_total) as discount ";
        $arr = $this->Cash_user_model->get_one($str,'user',$where,$where2);*/
        $filed = 'username,addtime,update_time logintime,loginip,id,balance,login_times login_num,max_income_price,
            max_out_price,in_t_num,in_t_total in_t_totl,out_t_num,out_t_total out_t_totl,discount';
        $arr = $this->Cash_user_model->get_one($filed,'user',['id' => $uid]);
        $id  = $arr['id'];
        $detail = $this->Cash_user_model->cash_x([$uid]);
        isset($detail[$arr['id']]['bank_name'])?$bank_name = $detail[$arr['id']]['bank_name'] : $bank_name = '';
        $arr['bank_name'] = $bank_name;
        $arr['max_in']    = $arr['max_income_price'];
        $arr['max_out']   = $arr['max_out_price'];
        $arr['profit']    =  sprintf("%01.3f",$arr['out_t_totl'] - $arr['in_t_totl']);
        $arr['addtime']   = date('Y-m-d H:i:s',$arr['addtime']);

        $arr['login']     = date('Y-m-d H:i:s',$arr['logintime'])." / ".$arr['loginip'];
        $arr['login_num'] = $arr['login_num'];
        unset($arr['logintime']);
        unset($arr['loginip']);
        unset($arr['max_income_price']);
        unset($arr['max_out_price']);
        $temp = [];
        $temp['rows'] = $arr;
        $this->return_json(OK,$temp);

    }


    /**
     * 会员统计
     * 登录次数 ---! 点卡与退水不参与计算
     * 排序  type 1注册时间, 2盈利 ,3存款次数,4提款次数,5存款总额 6提款总额
    */
    public function member_count()
    {

        $level_id = $this->G('level_id');//层级id
        $username = trim($this->G('username'));//用户名
        $agent_id = $this->G('agent_id');
        $start    = $this->G('start');   // 开始时间
        $end      = $this->G('end');     //结束时间
        $order    = $this->G('order') OR $order='desc';   //排序
        $rows    = $this->G('rows')  OR $rows = 50; //排序
        $sort     = $this->G('sort') OR $sort = 'a.id';
        $page     = $this->G('page') OR $page = 1 ;
        //顺序
        switch ($sort) {
            case 'bank_name':
                $sort = 'bank_name';
                break;
            case 'username ':
                $sort = 'username';
                break;
            case 'addtime':
                $sort = 'addtime';
                break;
            case 'login':
                $sort = 'logintime';
                break;
            case 'login_num':
                $sort = 'login_times';
                break;
            case 'max_in':
                $sort = 'max_income_price';
                break;
            case 'max_out':
                $sort = 'max_out_price';
                break;

        }
        if (!empty($start)) {
            $start .=' 00:00:00';
            if (empty($end)) {
                $end = date("Y-m-d H:i:s");
            }else{
                $end .= ' 23:59:59';
            }
        }
        if ( (strtotime($end) - strtotime($start) > ADMIN_QUERY_TIME_SPAN )){
            $this->return_json(E_ARGS,'查询时间跨度不能超过2个月');
        }

        $where  = [];
        if (empty($end)) {
            $end = date('Y-m-d H:i:s');
        }
        if(!empty($username)){
            $where['a.username'] = $username;
        }
        if ($start){
            $where['a.addtime >'] = strtotime($start);
            $where['a.addtime <'] = strtotime($end);
        }

        if ($level_id) {
            $where['a.level_id'] = $level_id;
        }
        if ($agent_id) {
            $where['a.agent_id'] = $agent_id;
        }

        $str = "c.bank_name,a.username,a.addtime,a.update_time logintime,a.loginip ,a.id,a.login_times login_num,a.max_out_price,a.max_income_price,";//user
        $str .= "SUM(b.in_company_num+b.in_online_num+b.in_people_num) as in_t_num,";//总入款笔数
        $str .= "SUM(b.in_company_total+b.in_online_total+b.in_people_total) as in_t_totl,";//总存入金额
        $str .= "SUM(b.out_people_num+b.out_company_num) as out_t_num,";//总出款笔数
        $str .= "SUM(b.out_people_total+b.out_company_total) as out_t_totl";//总出款数

        $this->Cash_user_model->db->set_dbprefix('');
        $res = $this->Cash_user_model->db->select($str)->from('gc_user as  a')
            ->join('gc_cash_report as b','a.id=b.uid','left')
            ->join('gc_user_detail as c','a.id=c.uid','left')
            ->where($where)
            ->group_by('a.id')
            ->order_by($sort,$order);
        $totalSql = 'select count(*) as total_rows from('.$res->get_compiled_select('',false).') total_table';
        $ressql   =  $res->limit($rows,$rows*($page-1))->get_compiled_select('');
        $this->Cash_user_model->db->set_dbprefix('gc_');
        $totle =  $this->Cash_user_model->db->query($totalSql)->row_array();
        $arr['total'] = $totle['total_rows'];
        $arr['rows']  = $this->Cash_user_model->db->query($ressql)->result_array();;

        foreach ($arr['rows'] as $k=>&$v) {
            $v['max_in'] = $v['max_income_price'];
            $v['max_out']    = $v['max_out_price'];
            $v['profit']    =   $v['out_t_totl'] - $v['in_t_totl'];
            $v['addtime']   = date('Y-m-d H:i:s',$v['addtime']);
            $v['loginip']   = $v['loginip'];
            $v['login']     = date('Y-m-d H:i:s',$v['logintime'])." / ".$v['loginip'];
            unset($v['logintime']);
            unset($v['loginip']);
            unset($v['max_income_price']);
            unset($v['max_out_price']);
        }
        if (in_array($this->G('sort'),['in_t_totl','in_t_num','out_t_totl','out_t_num','profit'])) {
            $arr['rows'] = $this->arr_sort($arr['rows'],$this->G('sort'),$order);
        }

        /**** 格式化小数点 ****/
        $arr['rows'] = stript_float($arr['rows']);

        $this->return_json(OK,$arr);

    }


    /**
     * 今日有效人数 新增人数
    */
    public function date_effective()
    {
        $date = $this->G('date');
        $agent_id = $this->G('agent_id');
        $is_one_pay = $this->G('is_one_pay');
        if (empty($date)) {
            $this->return_json(E_ARGS,'请输入日期');
        }
        $date = date('Y-m-d',strtotime($date));
        $page   = [
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->G('order'),
            'sort'  => $this->G('sort'),
            'total' => -1,

        ];
        $where = [
            'b.report_date' => $date,
            //人工存款计算有效会员
            '(b.in_company_total+b.in_online_total+b.in_people_total) >' => "'0'"
            //'(b.in_company_total+b.in_online_total) >' => "'0'"
        ];
        if (!empty($agent_id)) {
            $where['b.agent_id'] = $agent_id;
        }
        if ($is_one_pay == 1) {
            $where['b.is_one_pay'] = 1;
        }
        $where2 = [
            'join' => 'cash_report',
            'on' => 'b.uid = a.id',
        ];
        $data = $this->Cash_user_model->get_list('a.addtime,a.username,a.id','user',$where,$where2,$page);
        foreach ($data['rows'] as $k => &$v) {
            $v['addtime'] = date("Y-m-d H:i:s", $v['addtime']);
        }
        $this->return_json(OK,$data);
    }
    /**
     * 今日新增人数
    */
    /*public function incr_effective()
    {
        $date = $this->G('date');
        $agent_id = $this->G('agent_id');
        if (empty($date)) {
            $this->return_json(E_ARGS,'请输入日期');
        }
        $date = date('Y-m-d',strtotime($date));
        $page   = [
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->G('order'),
            'sort'  => $this->G('sort'),
            'total' => -1,

        ];
        $where = [
            'b.report_date' => $date,
            'b.is_one_pay' => 1,
            '(b.in_company_total+b.in_online_total+b.in_people_total+b.in_card_total) >' => 0
        ];

        if (!empty($agent_id)) {
            $where['b.agent_id'] = $agent_id;
        }
        $where2 = [
            'join' => 'cash_report',
            'on' => 'b.uid = a.id',
        ];
        $data = $this->Cash_user_model->get_list('a.username,a.id','user',$where,$where2,$page);
        $this->return_json(OK,$data);
    }*/

    /**
     * 有效会员
    */
    public function effective_member()
    {
        $start = $this->G('start') OR $start = date('Y-m-d',strtotime('-1 month'));
        $end   = $this->G('end')   OR $end    = date('Y-m-d');

        $page  = $this->G('page') OR $page=1;
        $rows  = $this->G('rows') OR $rows=50;
        $order = $this->G('order') OR $order='desc';
        $sort  = $this->G('sort') OR $sort = 'id';
        $agent_id = $this->G('agent_id');
        if (empty($start)) {
            $this->return_json(E_ARGS,'请选择开始时间');
        }
        if ( (strtotime($end) - strtotime($start) > ADMIN_QUERY_TIME_SPAN )){
            $this->return_json(E_ARGS,'查询时间跨度不能超过2个月');
        }
        $start  = date('Y-m-d',strtotime($start));

        $where  = [
            'report_date <=' => $end,
            'report_date >=' => $start,
        ];
        if (!empty($agent_id)) {
            $where['agent_id'] = $agent_id;
        }
        $where2 = [
            'groupby' => ['report_date']
        ];

        $str     =  'SUM(in_company_total) in_company_total,SUM(in_company_num) in_company_num,id,';
        $str    .= 'SUM(in_online_total) in_online_total,SUM(in_online_num) in_online_num,';
        $str    .= 'SUM(in_people_total) in_people_total,SUM(in_people_num) in_people_num,';
        $str    .= 'SUM(in_card_total) in_card_total,SUM(in_card_num) in_card_num,';
        $str    .= 'SUM(out_people_total) out_people_total,SUM(out_people_num) out_people_num,';
        $str    .= 'SUM(out_company_total) out_company_total,SUM(out_company_num) out_company_num';
        $str    .= ',count(CASE when in_company_total+in_online_total+in_people_total > 1 THEN 1 end )total,report_date ,count(CASE when is_one_pay =1 THEN 1 end ) added ';

        $res = $this->Cash_user_model->db->select($str)->from('cash_report')
            //人工存款计算有效会员
            ->where($where)
            //->where('(in_company_total+in_online_total+in_people_total) >',"0",false)
            //->where($where)->where('(in_company_total+in_online_total) >',"0",false)
            ->group_by('report_date')
            ->order_by('report_date','desc');
        $totalSql = 'select count(*) as total_rows from('.$res->get_compiled_select('',false).') total_table';
        $ressql   =  $res->limit($rows,$rows*($page-1))->get_compiled_select('');
        $totle =  $this->Cash_user_model->db->query($totalSql)->row_array();
        $arr['total'] = $totle['total_rows'];
        $arr['rows']  = $this->Cash_user_model->db->query($ressql)->result_array();

        $rows    = $arr['rows'];//$this->arr_sort($arr['rows'],'report_date','asc');

        //统计会员数量
        $totle   = [];
        $new = 0;
        $floor = [
            'added' => 0 ,
            'in_company_num' => 0 ,
            'in_online_num' => 0 ,
            'in_people_num' => 0 ,
            'in_card_num' => 0 ,
            'in_company_total' => 0 ,
            'in_online_total' => 0 ,
            'in_people_total' => 0 ,
            'in_card_total' => 0 ,
            'out_people_total' => 0 ,
            'out_company_num' => 0 ,
            'out_people_num' => 0 ,
            'out_company_total' => 0 ,
            'total' => 0 ,
            'res' => 0 ,
        ];
        foreach ($rows as $k => &$v) {
            $v['in_company_total'] = round($v['in_company_total'],3);
            $v['in_online_total']  = round($v['in_online_total'],3);
            $v['in_people_total']  = round($v['in_people_total'],3);
            $v['in_company_total'] = round($v['in_company_total'],3);
            $v['out_people_total'] = round($v['out_people_total'],3);
            $v['out_company_total'] = round($v['out_company_total'],3);
           /* $uid = explode(',',$v['uid']);
            unset($v['uid']);
            $added   = count(array_diff($uid,$totle));
            $totle = array_merge($totle,$uid);
            $v['added'] = $added;*/
            $v['res'] = $v['in_company_total']+$v['in_online_total']+$v['in_people_total']
                            +$v['in_card_total']-$v['out_people_total']-$v['out_company_total'];
            $v['res'] = round($v['res'],3);
            $floor['in_company_num'] +=  round($v['in_company_num'],3);
            $floor['in_online_num'] +=  round($v['in_online_num'],3);
            $floor['in_people_num'] +=  round($v['in_people_num'],3);
            $floor['in_card_num'] +=  round($v['in_card_num'],3);
            $floor['out_people_num'] +=  round($v['out_people_num'],3);
            $floor['out_company_num'] += round($v['out_company_num'],3);
            $floor['added'] += round($v['added'],3);
            $floor['total'] += round($v['total'],3);
            $floor['res']   += round($v['res'],3);
            $floor['in_company_total'] += round($v['in_company_total'],3);
            $floor['in_online_total']  += round($v['in_online_total'],3);
            $floor['in_people_total'] += round($v['in_people_total'],3);
            $floor['in_card_total'] += round($v['in_card_total'],3);
            $floor['out_people_total'] += round($v['out_people_total'],3);
            $floor['out_company_total'] += round($v['out_company_total'],3);
        }
        $floor['id'] = '小计';
        $floor['total'] .= '/人次';
        $floor['added'] .= '/人数';
        $arr['rows'] = $this->arr_sort($rows,$sort,$order);
        $arr['footer'] = [$floor];

        /**** 格式化小数点 ****/
        $arr['rows'] = stript_float($arr['rows']);

        $this->return_json(OK,$arr);


    }


    /**
     * 对数据进行排序
     * @param  $arr array 要排序的数据
     * @param  $key string 排序的键
     * @param  $sort str   排序的规则
     * @return  $arr  array  排序完成后的数据
    */
    public function arr_sort($arr,$key,$sort)
    {
          $len=count($arr);
        for($i=0; $i<$len-1; $i++) {
            //先假设最小的值的位置
            $p = $i;
            for($j=$i+1; $j<$len; $j++) {
                //$arr[$p] 是当前已知的最小值
                if ($sort == 'asc') {
                    $bool = $arr[$p][$key] > $arr[$j][$key];
                }else{
                    $bool = $arr[$p][$key] < $arr[$j][$key];
                }
                if($bool) {
                    //比较，发现更小的,记录下最小值的位置；并且在下次比较时采用已知的最小值进行比较。
                    $p = $j;
                }
            }
            //已经确定了当前的最小值的位置，保存到$p中。如果发现最小值的位置与当前假设的位置$i不同，则位置互换即可。
            if($p != $i) {
                $tmp = $arr[$p];
                $arr[$p] = $arr[$i];
                $arr[$i] = $tmp;
            }
        }
        //返回最终结果
        return $arr;
    }

}
