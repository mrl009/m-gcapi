<?php

//session_start();
if (!defined('BASEPATH'))
{
    exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class game_order_model extends SX_Model
{
    public function inset_order( $data ,$platform = 'mg',$sn)
    {
        //var_dump($data);exit();
        $this->select_db('shixun');
        $tabel_name = 'mg_game_order' . date( 'm' );
        $list_id = [];
        $this->load->model( 'sx/user_model' );
        $this->load->model( 'sx/bet_report_model' );
        foreach ( $data as $k => $value )
        {
            $this->select_db('shixun');
            $count=$this->db->where( 'row_id', $value['RowId'] )->from( $tabel_name )->count_all_results();
            $user = $this->user_model->get_user_info(substr($value['AccountNumber'],strlen($sn)), 'mg' );
            $insert_value['sn']=$user['sn'];
            $insert_value['snuid']=$user['snuid'];
            $insert_value['username']=$value['AccountNumber'];
            $insert_value['row_id']=$value['RowId'];
            $insert_value['account_number']=$value['AccountNumber'];
            $insert_value['display_name']=$value['DisplayName'];
            $insert_value['display_game_category']=$value['DisplayGameCategory'];
            $insert_value['session_id']=$value['SessionId'];
            $insert_value['game_end_time']=$value['GameEndTime'];
            $insert_value['total_wager']=$value['TotalWager'];
            $insert_value['total_payout']=$value['TotalPayout'];
            $insert_value['ProgressiveWager']=$value['ProgressiveWage'];
            $insert_value['iso_code']=$value['ISOCode'];
            $insert_value['game_platform']=$value['GamePlatform'];
            $insert_value['module_id']=$value['ModuleId'];
            $insert_value['client_id']=$value['ClientId'];
            $insert_value['transaction_id']=$value['TransactionId'];
            $insert_value['pca']=$value['PCA'];
            $insert_value['is_free_game']=$value['IsFreeGame'];
            if( !$count )
            {
                $this->select_db('shixun_w');
                $rs=$this->write($tabel_name,$insert_value);
                $this->select_db('shixun');
                //var_dump($insert_value);exit();
                $this->bet_report_model->mg_day_report( $insert_value );
                //统计用户打码量
                $this->redis_select(REDIS_LONG);
                $this->redis_hincrbyfloat('user:dml', $insert_value['snuid'], $insert_value['ProgressiveWager']);
            }
        }
        return $list_id;
    }
    public function get_lastRowId($sn = 'gc0'){
        $this->select_db('shixun');
        $tabel_name = 'gc_mg_game_order' . date( 'm' );
        $sql='select row_id from '.$tabel_name.' where sn = '."'".$sn."'" .' order by row_id desc limit 1';
        $query=$this->db->query($sql);
        $row = $query->row_array();
        if(isset($row['row_id'])&&$row['row_id']){
            return $row['row_id'];
        }else{
            return 0;
        }
    }
}