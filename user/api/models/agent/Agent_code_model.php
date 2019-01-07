<?php

/**
 * 邀请码model
 * Date: 2018/4/3
 */
class Agent_code_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    public function create_invite_code($uid, $rebate, $code, $type, $level)
    {
        try {
            $this->select_db('private');
            $data = [
                'uid'        => $uid,
                'rebate'     => is_array($rebate)?json_encode($rebate):$rebate,
                'invite_code'=> $code,
                'edit_rebate'=> is_array($rebate)?json_encode($rebate):$rebate,
                'junior_type'=> $type,
                'level'      => $level,
                'addtime'    => time()
            ];
            return $this->write('agent_code', $data);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update_regist_num($invite_code,$n=1)
    {
        try {
            $this->select_db('private');
            $bool = $this->db->set('register_num', 'register_num+'.$n, false)
                ->where(['invite_code'=>$invite_code])
                ->update('agent_code');
            return $bool;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function get_invite_code_list($uid)
    {
        try {
            $this->select_db('private');
            $join_on = 'l.uid=i.uid and i.is_delete = 0';
            if ($type = $this->P('type')) {
                $join_on .= ' and i.junior_type = '.$type;
            }
            $this->db->from('agent_line as l')
                ->join('agent_code as i',$join_on,'left')
                ->where('l.uid = '.$uid);
            $res = $this->db->select('l.rebate as self_rebate,i.invite_code,i.rebate,i.register_num,i.junior_type as type,i.addtime')->get();
            return $res->result_array();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function get_son_list($where)
    {
        try {
            $this->select_db('private');
            $res = $this->db->from('user as u')
                ->join('agent_line as l', 'l.uid=u.id', 'inner')
                ->join('agent_code as i', 'i.uid=u.id', 'left')
                //->where('u.agent_id = ' . $uid)
                    ->where($where)
                ->group_by('l.uid')
                ->select('l.uid,u.username,u.addtime,u.update_time,u.balance,l.level,l.type,l.rebate,SUM(i.register_num) as junior_num')
                ->get();
            //->get_compiled_select();
            return $res->result_array();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
