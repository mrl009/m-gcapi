<?php
/**
 * @file Level_model.php
 * @brief  层级相关的model
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package controllers
 * @author Langr <hua@langr.org> 2017/03/13 16:59
 *
 *
 */
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Level_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    private $level_id = '';//层级id

    public function index()
    {
    }

    /**
     * 更新层级的所有信息
    */
    public function count_user()
    {
        $level = $this->get_all('id', 'level');
        $level = array_column($level, 'id');
        foreach ($level as $value) {
            $user_num = $this->get_one('COUNT(id) as num', 'user', ['level_id'=>$value]);
            $this->db->set('user_num', $user_num['num'], false)
                     ->where(['id'=>$value])->update('level');
        }
        $sql  = "select level_id ,GROUP_CONCAT(id) as id from gc_user GROUP BY level_id";
        $data = $this->db->query($sql)->result_array();
        print_r($data);
        foreach ($data  as $value) {
            $where2 = [
                'wherein' =>['uid'=>explode(',', $value['id'])]
            ];
            $str =  "sum(in_company_total+";//公司入款
            $str .= "in_online_total+";//线上入款
            $str .= "in_people_total";//人工入款
            $str .= ") price,sum(";//点卡充值
            $str .= "in_company_num+in_online_num+in_people_num) num";//点卡充值

            $arr  = $this->get_all($str, 'cash_report', [], $where2);
            $arr  = $arr[0];
            isset($arr['num'])?:$arr['num']=0;
            isset($arr['price'])?:$arr['price']=0;
            $this->db->set('use_times', $arr['num'], false)
                ->set('use_total', $arr['price'], false)->where("id={$value['level_id']}")->update('level');
        }
    }
    /**
     * @param array $data  要入库的数据
     * @param  int   $level_id  层级的id
     * @return array  ['txt'=>提示信息,'code'=>状态码]
     *
    */
    public function level_add($data, $level_id=null)
    {
        $this->level_id = $level_id;
        $bank_id   = $data['bank_id'];
        $online_id = $data['online_id'];

        unset($data['bank_id']);
        unset($data['online_id']);
        unset($data['level_id']);
        unset($data['move_id']);
        //判断时间

        $where = ['level_name'=>$data['level_name']];

        $is_name = $this->get_list('id', 'level', $where, []);
        //判断层级名称
        if ($is_name) {
            $idx  = array_column($is_name, 'id');
            foreach ($idx as $ke => $item) {
                if ($item == $level_id) {
                    unset($idx[$ke]);
                }
            }
            if (!empty($idx)) {
                return ['txt'=>'层级名称不能重复','status'=>E_ARGS];
            }
        }

        if (empty($level_id)) {  //新增操作

            $this->db->trans_start();
            //判断默认层级
            $default = $this->get_one('id', 'level', ['is_default'=>1]);
            if ($default) {
                $data['is_default'] = 0;
            } else {
                $data['is_default'] = 1;
            }

            $this->db->insert('level', $data);
            $insert_id = $this->db->insert_id();
            $this->level_id = $insert_id;
            $arr_bank = [];
            foreach ($bank_id as $k => $value) {
                $arr_bank[$k]['card_id']  = $value;
                $arr_bank[$k]['level_id'] = $insert_id;
            }

            if (!empty($online_id)) {
                $arr_online =$this->online_pay_sql($online_id);
            }

            if (!empty($arr_bank)) {
                $this->db->query(hInsert($arr_bank, 'gc_level_bank'));
            }
            if (!empty($arr_online)) {
                $this->db->query(hInsert($arr_online, 'gc_level_bank_online'));
            }
            $code = $this->db->trans_complete();
            //todo 层级名缓存
            $this->level_cache($insert_id, $data['level_name'], false);
        } else {//更新操作
            //要插入的交集
            $card_set    = [];
            $online_set  = [];
            //查出数据库原来的数据
            if (!empty($online_id)) {
                $sql ="select GROUP_CONCAT(pay_code) pay_code,online_id,GROUP_CONCAT(id) id from gc_level_bank_online  where level_id=$this->level_id GROUP BY online_id";
                $tempx = $this->db_private->query($sql)->result_array();
                $i = 0;
                $temp = [];
                foreach ($tempx as $k => $v) {
                    $temp1 = explode(',', $v['pay_code']);
                    $temp2 = explode(',', $v['id']);
                    foreach ($temp1 as $kk => $vv) {
                        $temp[$v['online_id']][$kk][]=$vv;
                        $temp[$v['online_id']][$kk]['id']=$temp2[$kk];
                    }
                }

                $tb_online = $this->online_pay_sql($temp);
                $online_id = $this->online_pay_sql($online_id);
                $big_o     = $this->online_diff($online_id, $tb_online);
                $online_del = $big_o['del'];
                $online_temp = $big_o['add'];
                foreach ($online_temp as $k => $v) {
                    $online_set[$k]['online_id'] = $v[0];
                    $online_set[$k]['level_id']  = $level_id;
                    $online_set[$k]['pay_code']  = $v[1];
                }
            }
            //事务开启

            $this->db->trans_start();

            $tb_bank   = $this->get_all('card_id', 'level_bank', ["level_id"=>$level_id]);
            $tb_bank   = array_column($tb_bank, 'card_id');
            //要删除的差集
            $card_del   = array_diff($tb_bank, $bank_id);

            //要插入的差集
            $card_temp   = array_diff($bank_id, $tb_bank);

            foreach ($card_temp as $k => $v) {
                $card_set[$k]['card_id']   = $v;
                $card_set[$k]['level_id']  = $level_id;
            }
            $where      = ['level_id'=>$level_id];
            //执行未选中的数据
            $this->genxin($card_del, 'level_bank', $where, 'card_id');


            if (!empty($online_id)) {
                if ($online_del) {
                    $this->db->where_in('id', $online_del)->delete('level_bank_online');
                }
            } else {
                $this->db->where($where)->delete('level_bank_online');
            }
            //添加新数据

            $this->genxin($card_set, 'gc_level_bank');
            $this->genxin($online_set, 'gc_level_bank_online');
            //更新层级的信息

            $this->db->update('level', $data, "id = $level_id");
            //todo 层级名缓存
            $this->level_cache($level_id, $data['level_name'], false);

            //移动层级
            //if ($move_id != $level_id && !empty($move_id)) {
            //  $where = [
            //        'is_level_lock' =>0,
            //        'level_id'      =>$level_id
            //    ];
            //    $user = $this->get_all('id','user',$where,[]);
            //    $uid  = array_column($user,'id');
            //    if(!empty($uid)){
            //        $this->chang_level($uid,[$level_id,$move_id]);
            //   }

            //    $this->db->update('user',['level_id'=>$move_id],['level_id' =>$level_id]);
            //}
            $code = $this->db->trans_complete();
        }
        if ($code) {
            return ['txt'=>'操作成功','status'=>OK];
        } else {
            return ['txt'=>'操作失败','status'=>E_OK];
        }
    }


    /**
     * 更新层级时所要展示的数据
     * @param  int $level_id 层级id
     * @return  array  层级的数据
     *
    */
    public function level_addx($level_id)
    {
        $where  = [
            'status' =>1
        ];
        $where2 = [
        ];

        //取银行卡的数据
        $card_bank = $this->get_all('id,card_num,card_username,qrcode,bank_id', 'bank_card', $where, $where2);
        $bank = $this->get_bank('bank',[ 'status' => 1 ]);
        foreach ($card_bank as $key => $value) {
            if (isset($bank[$value['bank_id']])) {
                $card_bank[$key]['bank_name'] = $bank[$value['bank_id']];
            }

        }

        //入款银行的信息
        $bank  = $this->get_bank('bank_online');
        $where = ['level_id' => $level_id];
        $level_card   = $this->get_all('card_id', 'level_bank', $where);
        $level_card   = array_column($level_card, 'card_id');

        //线上入款
         $online  = $this->get_level_o($level_id);
        //本层级的数据
        $data = $this->db->where('id', $level_id)->select_one('level');

        //支付设定
        $all_pay = $this->get_all('id,pay_name', 'pay_set');

        foreach ($all_pay as $k => $v) {
            if ($v['id'] == $data['pay_id']) {
                $all_pay[$k]['is_check'] =1;
            } else {
                $all_pay[$k]['is_check'] =0;
            }
        }

        $arr = [];
        //数据重组
        foreach ($card_bank as $key => $value) {
            $arr['bank'][$key] = $value;
            if (in_array($value['id'], $level_card)) {
                $arr['bank'][$key]['is_checked'] = 1;
            } else {
                $arr['bank'][$key]['is_checked'] = 0;
            }

            if (!empty($value['qrcode'])) {
                $arr['bank'][$key]['card_username'] .= "/".$value['bank_name'];
            }
        }
        if (!empty($arr['bank'])) {
            foreach ($arr['bank'] as $kk => $vv) {
                unset($arr['bank'][$kk]['bank_id']);
                unset($arr['bank'][$kk]['card_address']);
                unset($arr['bank'][$kk]['max_amount']);
                unset($arr['bank'][$kk]['remark']);
                unset($arr['bank'][$kk]['status']);
            }
        }

        $arr['online']     = $online;
        $arr['pay_move']   = $all_pay;
//        查询改层级的基本数据
        return  array_merge($arr, $data);
    }



    /**
     * 更新中间表的信息
     * @param  array  $data 更新的数据
     * @param  string $tb   数据库的表名
     * @param  array  $where 更新条件
     * @param  string  $key  条件的key值
     *@return  bool    bool
     *
    */
    public function genxin($data, $tb, $where=null, $key=null)
    {
        if ($data) {
            if ($where) {
                $this->db->where_in($key, $data)->where($where)->delete($tb);
            } else {
                $this->db->query(hInsert($data, $tb));
            }
        }
    }

    /**
     * 获取到银行卡 和支付平台
     * @param $tb string 表名
     * @return $arr array
    */
    private function get_bank($tb,$where=[])
    {
        if ($tb == 'bank') {
            $key = 'bank_name';
        } else {
            $key = 'online_bank_name';
        }
        $this->select_db('public');
        $arr = $this->get_all('*', $tb,$where);
        $this->select_db();

        foreach ($arr as $item) {
            $arrx[$item['id']] = $item[$key];
        }
        return $arrx;
    }
    /**
     * 层级数据展示  层级数据统计
     * @param $page array 分页数组
     * @return array
    */
    public function count_level($page)
    {
        $str ='level_name,user_num,max_times, total_num,max_total ,total_deposit,id,remark,use_times,use_total';
        $arr = $this->Level_model->get_list($str, 'level', [], [], $page);
        $sql = 'select count(a.card_id) num,a.level_id from gc_level_bank a LEFT JOIN gc_bank_card  b on a.card_id=b.id where b.status=1 GROUP BY level_id';

        $temp = $this->db->query($sql)->result_array();
        foreach ($temp as $k => $v) {
            $bank[$v['level_id']] = $v['num'];
        }
        $sql = 'select count(a.online_id) num,a.level_id from gc_level_bank_online a LEFT JOIN gc_bank_online_pay  b on a.online_id=b.id where b.status=1  GROUP BY level_id';
        $temp = $this->db->query($sql)->result_array();
        foreach ($temp as $k => $v) {
            $online[$v['level_id']] = $v['num'];
        }
        foreach ($arr['rows'] as $k => $v) {
            $num = isset($bank[$v['id']])?$num =$bank[$v['id']]:$num=0;
            $arr['rows'][$k]['bank_num']   = $num;
            $num = isset($online[$v['id']])?$num =$online[$v['id']]:$num=0;
            $arr['rows'][$k]['online_num'] = $num;
        }

        return $arr;
    }

    /**
     * @param  $data array 线上支付的参数信息
     * @return    array
    */
    public function online_pay_sql($data)
    {
        $i=0;
        $arr = [];
        foreach ($data as $k=>$v) {
            foreach ($v as $kk=>$vv) {
                if (isset($arr[$i]['id'])) {
                    $arr[$i]['id'] =$vv['id'];
                    $arr[$i]['pay_code']  = $vv[0];
                } else {
                    $arr[$i]['pay_code']  = $vv;
                }
                $arr[$i]['level_id']  = $this->level_id;
                $arr[$i]['online_id'] = $k;
                $i++;
            }
        }
        return $arr;
    }
    /**
     * @param $data1 array 表单数据
     * @param  $data2 array 数据库数据
     * @return  $arr array  差集数据
    */
    private function online_diff($data1, $data2)
    {
        $temp2 = [];
        $temp1 = [];
        $id = [];
        $arr   = [];
        foreach ($data1 as $k => $v) {
            $temp1[]=$v['online_id'].'_'.$v['pay_code'];
        }
        foreach ($data2 as $k => $v) {
            $temp2[]=$v['online_id'].'_'.$v['pay_code'][0];
        }
        $add_t = array_diff($temp1, $temp2);
        $del_t = array_diff($temp2, $temp1);
        $add = [];
        $del = [];

        foreach ($add_t as $k => $v) {
            $add[] = explode('_', $v);
        }
        foreach ($del_t as $k => $v) {
            $del[] = explode('_', $v);
        }
        foreach ($data2 as $k=>$v) {
            foreach ($del as $kk => $vv) {
                if ($v['online_id'] == $vv[0] && $v['pay_code'][0] == $vv[1]) {
                    $id[]=$v['pay_code']['id'];
                }
            }
        }
        $arr = [
            'add'=>$add,
            'del'=>$id
        ];

        return $arr;
    }
    /**
     * 获取层级下面线上支付的信息
     * @param $level_id int 层级id
     * @return  array
     *
    */
    public function get_level_o($level_id)
    {
        /*$this->select_db('public');
        $online = $this->db->select('id,pay_code,online_bank_name name')->where(['status'=>1])->get('bank_online')->result_array();
        $this->select_db();*/

        $baseData = $this->base_bank_online('bank_online');
        $where2 = [
            //'wherein' => ['bank_o_id'=>array_column($online,'id')],
        ];
        $xx = $this->get_all("bank_o_id,id", "bank_online_pay", ["status"=>1],$where2);

        /*$a  = array();
        foreach ($xx as $k=>$value) {
            $a[$value['bank_o_id']] = $value['id'];
        }
        foreach ($online as $k=>$v) {
            if (isset($a[$v['id']])) {
                $online[$k]['id'] = $a[$v['id']];
            } else {
                unset($online[$k]);
            };
        }*/

        $online =[];
        foreach ($xx as $k => $value) {
            if (isset($baseData[$value['bank_o_id']])) {
                $temp=[
                    'id'=>$value['id'],
                    'pay_code'=>$baseData[$value['bank_o_id']]['pay_code'],
                    'name'=>$baseData[$value['bank_o_id']]['online_bank_name'],
                ];
                array_push($online,$temp);
            }
        }
        $id     = array_column($xx, 'id');
        $str    = 'GROUP_CONCAT(a.pay_code) pay_code ,online_id id';
        $tba    = 'level_bank_online';
        $tbb    = 'bank_online_pay';
        $where  = [
            'b.status'   => 1,
            'a.level_id' =>$level_id,
        ];
        $where2 = [
            'groupby'  => ['a.online_id'],
            'where in' => $id,
            'join'     => $tbb,
            'on'       => 'a.online_id=b.id',
        ];

        $arr    = $this->get_all($str, $tba, $where, $where2);
        foreach ($online as $k => $v) {
            $name[$v['id']] = $v['name'];
        }

        $online = online_z($online);
        $arr    = online_z($arr);
        $temp1  = array_diff($online, $arr);
        $temp2  = array_intersect($online, $arr);
        $arr    = [];
        $online = [];
        foreach ($temp1 as $k=>$v) {
            $temp = explode('-', $v);
            $arr[$k]['id']    = $temp[0];
            $arr[$k]['code']  = $temp[1];
            $arr[$k]['check'] = 0;
        }
        foreach ($temp2 as $k=>$v) {
            $temp = explode('-', $v);
            $online[$k]['id']    = $temp[0];
            $online[$k]['code']  = $temp[1];
            $online[$k]['check'] = 1;
        }
        $arr = array_merge($arr, $online);
        //组合支付名称
        foreach ($arr as $kk => $vv) {
            $arr[$kk]['name'] = $name[$vv['id']];
        }

        return $arr;
    }

    /**
     * 移动层级更改数据
     * @param $uid array    数组
     * @param  level_id array   [0原1新]
    */
    public function chang_level($uid, $level)
    {
       /* $level_id = $level[0];
        $new_id   = $level[1];

        $where2 = [
            'wherein' =>['uid'=>$uid]
        ];
        $str =  "sum(in_company_total+";//公司入款
        $str .= "in_online_total+";//线上入款
        $str .= "in_people_total";//人工入款
        $str .= ") price,sum(";//点卡充值
        $str .= "in_company_num+in_online_num+in_people_num) num";//点卡充值

        $arr  = $this->get_all($str, 'cash_report', [], $where2);
        $data = $arr[0];
        $data['num']   < 0?$data['num']   =0 :true;
        $data['price'] < 0?$data['price'] =0 :true;
        $where  = ['id'=>$level_id];

        $this->db->set('use_times', 'use_times-'.$data['num'], false)
                 ->set('user_num', 'user_num-'.count($uid), false)
                 ->set('use_total', 'use_total-'.$data['price'], false)->where($where)->update('level');
        $where  = ['id'=>$new_id];

        $this->db->set('use_times', 'use_times+'.$data['num'], false)
               ->set('user_num', 'user_num+'.count($uid), false)
            ->set('use_total', 'use_total+'.$data['price'], false)->where($where)->update('level');*/
    }

    /**
     *
     * 获取到支持银行 和 线上支付平台的的基本信息
     * @param $name  string 要获取的东西 bank || bank_online
     * @param id  int 获取单条
     * @return $arr  array
     */
    public function base_bank_online($name = 'bank', $id=null)
    {
        $this->select_db('public');
        if ($id) {
            $wher = [
                'status' => 1,
                'id'     => $id,
            ];
            $arr  = $this->get_one('*', $name, $wher);
            return $arr;
        }
        $arr = $this->get_all('*', $name, ['status'=>1]);
        $this->select_db('private');
        $temp = [];
        foreach ($arr as $k => $item) {
            $temp[$item['id']] = $item;
        }
        $this->select_db('private');
        return $temp;
    }

    /**
     * 根据层级信息判断是否需要移动对应条件的会员
     * @param $id int 层级的ID
     * @return  array;
     */

    public function is_chang_level($id,$new_id)
    {

        /*if ($this->redis_setnx('level:user_chushi2',time())) {
            $this->redis_expire('level:user_chushi2',3600*24*30);
            $this->chushihua();
        }*/
        $data = $this->get_one('total_deposit,total_num','level',[ 'id' => $new_id]);
        $where2 = [
            'join' => 'cash_report' ,
            'on'   => 'a.id=b.uid',
            'groupby' => [ 'a.id'],
        ];
        $tj1 = 'total >=';
        $tj2 = 'num >=';
        $where = [
            'a.level_id' => $id,
        ];
        $data['total_deposit']>0?$having[$tj1] = $data['total_deposit']:false;
        $data['total_num']>0?$having[$tj2] = $data['total_num']:false;


        $this->db->where('a.is_level_lock=0');
        $selectStr = 'a.id,a.username,a.agent_id,
                     sum(b.in_company_total+b.in_online_total+b.in_people_total) total,
                     sum(b.in_company_num+b.in_online_num+b.in_people_num) num';
        if (!empty($having)) {
            $this->db->having($having);
        }
        $user = $this->get_all($selectStr,'user',$where,$where2);
        if (empty($user)) {
            return "没有达到条件的会员";
        }
        $uid  = array_column($user, 'id');
        $total = 0;
        $num   = 0;
        if (!empty($uid)) {
            foreach ($user as $value) {
                $num   += $value['num'];
                $total += $value['total'];
                //会员缓存
                $x =[ 'username' => $value['username'],'level_id' => $new_id, 'agent_id' => $value['agent_id']];
                $this->user_cache($value['id'], $x, false);
            }
        }
        $this->db->where_in('id',$uid)->update('user', ['level_id'=>$new_id]);
        return $this->chushihua();
        /*$this->db->trans_begin();

        $bool = $this->db->set('use_times', 'use_times-'.$num, false)
            ->set('user_num', 'user_num-'.(int)count($uid), false)
            ->set('use_total', 'use_total-'.$total, false)->where('id='.$id)->update('level');

        if (!$bool) {
            $this->db->trans_rollback();
            return '更新失败请重试';
        }
        $bool = $this->db->set('use_times', 'use_times+'.$num, false)
            ->set('user_num', 'user_num+'.(int)count($uid), false)
            ->set('use_total', 'use_total+'.$total, false)->where('id='.$new_id)->update('level');
        if (!$bool) {
            $this->db->trans_rollback();
            return '更新失败请重试';
        }
        $bool = $this->db->where_in('id',$uid)->update('user', ['level_id'=>$new_id]);
        if (!$bool) {
            $this->db->trans_rollback();
            return '更新失败请重试';
        }
        return $this->db->trans_commit();*/

    }

    /**
     * @param $id string 多个ID逗号分隔
     *
    */
    public function chushihua($id = null)
    {
        $where = '';
        if (!empty($id)) {
            $where = "a.id in ({$id}) ";
        }
        $sql = "update gc_level
                left join
                    (select sum(c.in_company_total+c.in_online_total+c.in_people_total) total, sum(c.in_company_num+c.in_online_num+c.in_people_num) num,a.id
                    from gc_level a
                    left JOIN gc_user b on a.id=b.level_id
                    left join gc_cash_report c ON c.uid=b.id
                    $where
                    GROUP BY a.id) as temp
                on temp.id = gc_level.id
                set gc_level.use_times = temp.num,gc_level.use_total=temp.total    ";
        $this->db->query($sql);
        $where = '';
        if (!empty($id)) {
            $where = "level_id in ({$id}) ";
        }
        $sql = 'update gc_level
                left join (select level_id ,count(*) usernum from gc_user '.$where.' GROUP BY level_id) as temp
                ON temp.level_id = gc_level.id
                set gc_level.user_num = temp.usernum';
        return $this->db->query($sql);
    }
}
