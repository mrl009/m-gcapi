<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Data_delete extends GC_Controller 
{
    //.公库删除时候,表对对应判断字段
    private $base_table_field = ['gc_open_num'=>'open_time'];
    //.私库删除时候,表及对应的判断字段
    private $private_table_field = [
      'gc_agent_rebate'=>'created',
      'gc_agent_report_day'=>'report_date',
      'gc_agent_report_month'=>'report_month',
      'gc_bet_index'=>'created',
      'gc_bet_settlement'=>'created',
      'gc_bet_wins'=>'created',
      'gc_cash_list'=>'addtime',
      'gc_cash_in_company'=>'addtime',
      'gc_cash_in_online'=>'addtime',
      'gc_cash_in_people'=>'addtime',
      'gc_cash_out_manage'=>'addtime',
      'gc_cash_out_people'=>'addtime',
      'gc_cash_report'=>'report_date',
      'gc_report'=>'report_date',
      'gc_auth_log'=>'addtime',
      'gc_log_admin_login'=>'login_time',
      'gc_log_admin_record'=>'record_time',
      'gc_log_user_login'=>'login_time',
      'gc_red_activity'=>'add_time',
      'gc_red_order'=>'add_time',
      'gc_reward_day_log'=>'add_time',//.下面是各个彩种的订单表
      'gc_bet_ah11x5'=>'bet_time',
      'gc_bet_ahk3'=>'bet_time',
      'gc_bet_aj3fc'=>'bet_time',
      'gc_bet_bj28'=>'bet_time',
      'gc_bet_bjk3'=>'bet_time',
      'gc_bet_bjpk10'=>'bet_time',
      'gc_bet_bjssc'=>'bet_time',
      'gc_bet_cqssc'=>'bet_time',
      'gc_bet_fc3d'=>'bet_time',
      'gc_bet_ffssc'=>'bet_time',
      'gc_bet_ftpk10'=>'bet_time',
      'gc_bet_gd11x5'=>'bet_time',
      'gc_bet_gsk3'=>'bet_time',
      'gc_bet_gxk3'=>'bet_time',
      'gc_bet_gzk3'=>'bet_time',
      'gc_bet_hbk3'=>'bet_time',
      'gc_bet_hebk3'=>'bet_time',
      'gc_bet_jlk3'=>'bet_time',
      'gc_bet_jsk3'=>'bet_time',
      'gc_bet_jslhc'=>'bet_time',
      'gc_bet_jspk10'=>'bet_time',
      'gc_bet_jx11x5'=>'bet_time',
      'gc_bet_lhc'=>'bet_time',
      'gc_bet_pl3'=>'bet_time',
      'gc_bet_s_ah11x5'=>'bet_time',
      'gc_bet_s_ahk3'=>'bet_time',
      'gc_bet_s_bjk3'=>'bet_time',
      'gc_bet_s_bjpk10'=>'bet_time',
      'gc_bet_s_bjssc'=>'bet_time',
      'gc_bet_s_cqkl10'=>'bet_time',
      'gc_bet_s_cqssc'=>'bet_time',
      'gc_bet_s_fc3d'=>'bet_time',
      'gc_bet_s_ffssc'=>'bet_time',
      'gc_bet_s_gd11x5'=>'bet_time',
      'gc_bet_s_gdkl10'=>'bet_time',
      'gc_bet_s_gsk3'=>'bet_time',
      'gc_bet_s_gxk3'=>'bet_time',
      'gc_bet_s_gzk3'=>'bet_time',
      'gc_bet_s_hbk3'=>'bet_time',
      'gc_bet_s_hebk3'=>'bet_time',
      'gc_bet_s_jlk3'=>'bet_time',
      'gc_bet_s_jsk3'=>'bet_time',
      'gc_bet_s_jx11x5'=>'bet_time',
      'gc_bet_s_pl3'=>'bet_time',
      'gc_bet_s_sd11x5'=>'bet_time',
      'gc_bet_s_sfpk10'=>'bet_time',
      'gc_bet_s_sfssc'=>'bet_time',
      'gc_bet_s_sh11x5'=>'bet_time',
      'gc_bet_s_shk3'=>'bet_time',
      'gc_bet_s_tjssc'=>'bet_time',
      'gc_bet_s_wfk3'=>'bet_time',
      'gc_bet_s_xjssc'=>'bet_time',
      'gc_bet_s_yck3'=>'bet_time',
      'gc_bet_sd11x5'=>'bet_time',
      'gc_bet_sfpk10'=>'bet_time',
      'gc_bet_sfssc'=>'bet_time',
      'gc_bet_sh11x5'=>'bet_time',
      'gc_bet_shk3'=>'bet_time',
      'gc_bet_tjssc'=>'bet_time',
      'gc_bet_xjp28'=>'bet_time',
      'gc_bet_xjssc'=>'bet_time',
    ];
    public function __construct() 
    {
        parent::__construct();
        $this->load->model('MY_Model', 'open');
        $this->load->model('Open_result_model', 'orm');
    } 

    /**
      *@desc 定义一个方法 定期删除公库某些表2个月前的数据 例如：['gc_open_num']
      ***/
    public function base_data_delete($table)
    {
      if (!is_cli()) {
         header('HTTP/1.1 405 fuck u!');
         $this->return_json(E_METHOD, 'method nonsupport!');
      }
      //.程序开始时间,日志记录
      $start = date("Y-m-d H:i:s");
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据启动时间：'.$start, true);
      //.获取这个表对应的判断字段
      $field = $this->base_table_field[$table];
      if(empty($field)){
        wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'表名不合法', true);
        echo '表名不合法';
        return false;
      }
      //.取2个月前数据的id,返回最后删除的最后一条数据id
      $rs = $this->orm->delete_base_data($table,$field);
      //.将删除的最后一条日志记录到日志中
      if($rs){
        wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据成功,最后id为：'.$rs['from_id'].',受影响行数：'.$rs['affected_rows'], true);
      }
      //.程序结束时间
      $end = date("Y-m-d H:i:s");
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据结束时间：'.$end, true);
      $jiange = strtotime($end)-strtotime($start);
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据总耗时：'.$jiange, true);
    }


    /**
      *@desc 定义一个方法 定期删除公库某些表2个月前的数据 例如：['gc_agent_rebate']
      *@param $dsn 站名
      *@param $table 表名
      ***/
    public function private_date_delete($dsn = 'w01',$table)
    {
      if (!is_cli()) {
          header('HTTP/1.1 405 fuck u!');
          $this->return_json(E_METHOD, 'method nonsupport!');
      }
      //.程序开始时间,日志记录
      $start = date("Y-m-d H:i:s");
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据启动时间：'.$start, true);
      /* 支持动态配置的私库时可用 */
      if (!empty($dsn)) {
          $this->orm->init($dsn);
          $dbn = $this->orm->sn;
      } else {
          wlog(APPPATH.'logs/dsn_web'.date('Y').'.log', 'dsn error:'.$dbn, true);
          return false;
      }
      //.删除数据
      $this->private_delete_base($dbn,$table);
      //.程序结束时间
      $end = date("Y-m-d H:i:s");
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据结束时间：'.$end, true);
      $jiange = strtotime($end)-strtotime($start);
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据总耗时：'.$jiange, true);

    }

    /**
      *@desc 定义一个方法 定期批量删除公库某些表2个月前的数据 例如：['gc_agent_rebate']
      *@param $dsn 站名
      ***/
    public  function private_together_delete($dsn)
    {
      if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
         $this->return_json(E_METHOD, 'method nonsupport!');
      }
      //.程序开始时间,日志记录
      $start = date("Y-m-d H:i:s");
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据启动时间：'.$start, true);
      /* 支持动态配置的私库时可用 */
      if (!empty($dsn)) {
          $this->orm->init($dsn);
          $dbn = $this->orm->sn;
      } else {
          wlog(APPPATH.'logs/dsn_web'.date('Y').'.log', 'dsn error:'.$dbn, true);
          return false;
      }
      foreach ($this->private_table_field as $k => $v) {
        $this->private_delete_base($dbn,$k);
      }
      //.程序结束时间
      $end = date("Y-m-d H:i:s");
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据结束时间：'.$end, true);
      $jiange = strtotime($end)-strtotime($start);
      wlog(APPPATH.'logs/'.$table.date('Ym').'.log', $table.'删除数据总耗时：'.$jiange, true);

    }


    /**
      *@desc 私库删除数据的基本方法
      */
    private function private_delete_base($dbn,$table)
    {
      //.获取这个表的删除数据的判断字段
      $field = $this->private_table_field[$table];
      if(empty($field)){
        wlog(APPPATH.'logs/'.$dbn.'_'.$table.date('Ym').'.log', $table.'表名不合法', true);
        echo '表名不合法';
        return false;
      }
      //.删除近2个月的数据
      $rs = $this->orm->delete_private_data($table,$field);
      if($rs){
        wlog(APPPATH.'logs/'.$dbn.'_'.$table.date('Ym').'.log', $table.'删除数据成功,最后id为：'.$rs['from_id'].',受影响行数：'.$rs['affected_rows'], true);
      }
    }

}