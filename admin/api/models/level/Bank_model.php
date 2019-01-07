<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Bank_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
    }

    /**
     * @param $data array 更新或者入库的数据
     * @param $id int    id
     * @param $tb string 要操作的表明
     * @return array     ['msg'=>提示信息,code=>状态值]
    */
    public function Bank_add($data, $tb='bank', $id=null)
    {
        $this->select_db('public');
        if (empty($id)) {
            if ($tb == 'bank') {
                $where = [
                    'bank_name' => $data['bank_name']
                ] ;
            }
            $a = $this->get_one('*', $tb, $where);
            if (empty($a)) {
            } else {
                return ['status'=>E_ARGS,'msg'=>'银行名称不能重复'];
            }
        }
        $where = [];
        if ($id) {
            $where = ['id'=>$id];
        }
        return $this->write($tb, $data, $where);
    }

    /**
     * 添加和更新银行卡信息
    */
    public function card_add($data, $where=[])
    {
        $this->write('bank_card', $data, $where);
    }

    /**
     * 支付平台获取
     * @param string 表明
     * @param  array $where  条件
     * @return array $arr  返回平台相关数据
    */
    public function bank_list($tb, $where=[])
    {
        $arr = [];
        if (is_array($tb)) {
            $arr['bank'] = $this->bank_select($tb[0], $where);
            $arr['bank_online'] =$this->bank_select($tb[1], $where);
        } else {
            $arr[$tb]=$this->bank_select($tb, $where);
        }
        return $arr;
    }

    /**
     * 查询数据库
    */
    public function bank_select($tb, $where=[])
    {
        $this->select_db('public');
        return  $this->get_list('*', $tb, $where);
    }

    public function bank_del($tb, $where)
    {
        return $this->db_public->where_in('id', $where)->delete($tb);
    }
}
