<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Pay_set_model extends MY_Model
{
    private $payset_key = 'pay:set:pay_Id:';//注意层级拼接支付设定的id

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
    }
    /**
     * 更新reids里面的缓存内容
    */
    public function update_keys($id, $data)
    {
        if (!empty($id)) {
            $redis_key = $this->payset_key.$id;
            if (empty($data)) {
                $this->redis_del($redis_key);
            } else {
                $data['data'] = json_decode($data['data'], true);
                $this->redis_set($redis_key, (string)json_encode($data, JSON_UNESCAPED_UNICODE));
            }
            $this->redis_select(REDIS_DB);
        }
    }




    public function show_data($id =null)
    {
        $str = 'id,pay_name';
        $where = [];
        if ($id) {
            $where['id'] = $id;
            $str = '*';
        }
        $page   = array(
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => 'asc',
            'sort'  => 'id',
            'total' => -1,
        );

        $arr = $this->get_list($str, 'pay_set', $where, [], $page);
        if ($id) {
            $arr['rows'][0]['pay_set_content'] = json_decode($arr['rows'][0]['pay_set_content'], true);
        }
        $arrx['rows'] = $arr['rows'];
        $arrx['total'] = $arr['total'];
        return ['status'=>OK,'data'=>$arrx];
    }




    /**
     * 删除支付设定
    */
    public function pay_del($pay_id=null)
    {
        //        where_in('id',$data)->delete('bank_online_pay')
//        $data = $this->db->where_in('id',$pay_id)->get('level')->result_array();
//
//        if ($data) {
//            $idArr = array_column($data,'id');
//            foreach($pay_id as $k => $v){
//                if (in_array($v,$idArr)) {
//                    unset($pay_id[$k]);
//                }
//            }
//        }
//        foreach($pay_id as $v){
//           $this->update_keys($v,[]);
//        }
//        $bool = $this->db->where_in('id',$pay_id)->delete('pay_set');
//        if ($bool){
//            if(!empty($idArr)){
//                return ['status'=>OK,'data'=>'操作成功使用中的未删除id'.implode(',',$idArr)];
//            }
//            return ['status'=>OK,'data'=>'操作成功'];
//        }
//        return ['status'=>E_OK,'data'=>'操作失败'];
    }



    /**
     * 获取一个会员的支付设定信息
     * @param int   $uid    会员ID
     * return array
     */
    public function get_pay_set($uid=0, $field='*')
    {
        $where['user.id'] = $uid;
        $this->db->where($where);
        $condition['join'] =
            array(
                array('table'=>'level as l','on'=>'user.level_id = l.id'),
                array('table'=>'pay_set as ps','on'=>'ps.id = l.pay_id'),
            );
        $data = $this->get_one($field, 'user', $where, $condition);
        if (empty($data)) {
            return false;
        }
        $payData = json_decode($data['pay_set_content'], true);
        return array_merge((array)$payData, (array)$data);
    }
}
