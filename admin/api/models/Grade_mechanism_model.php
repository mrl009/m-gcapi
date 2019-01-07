<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Grade_mechanism模块
 *
 * @author      ssm
 * @package     models
 * @version     v1.0 2017/10/16
 */
class Grade_mechanism_model extends MY_Model
{

	/**
	 * 获取gc_grade_mechanism表全部数据
	 *
	 * @access public
	 * @param Array $where 查询条件
	 * @param Array $where 查询条件2
	 * @param Array $page 分页条件
	 * @return Array
	 */
	public function get_alls($where, $where2, $page)
	{
		$vipData = $this->get_list('*', 'gc_grade_mechanism', $where, $where2, $page);

		return array_map(function($value) {
			$value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
			return $value;
		}, $vipData['rows']);
	}

	/**
	 * 统计VIP的用户数量，并写进数据库
	 *
	 * @access public
	 * @param Integer $vipId vipId
	 * @return Array
	 */
	public function user_count($vipId)
	{
		$vipData = $this->get_one('*', 'grade_mechanism', ['id'=>$vipId]);
		if (empty($vipData)) {
			return [E_ARGS, '没有找到该VipID'];
		}

		$user_count = $this->get_list('count(*) as count', 'user', ['vip_id'=>$vipId]);
		if ($user_count[0]['count'] == $vipData['user_sum']) {
			return [OK, ['user_sum'=>$vipData['user_sum']]];
		} else {
			$data['user_sum'] = $user_count[0]['count'];
			$flag = $this->write('grade_mechanism', $data, array('id'=>$vipId));
			$this->load->model('log/Log_model');
			if ($flag) {
		        $data['content'] = "{$vipData['title']}更新人数成功：{$data['user_sum']}";
		        $this->Log_model->record($this->admin['id'], $data);
			} else {
				$data['content'] = "{$vipData['title']}更新人数失败";
		        $this->Log_model->record($this->admin['id'], $data);
			}
			return [OK, ['user_sum'=>$data['user_sum']]];
		}
	}

	/**
	 * 更新gc_grade_mechanism表数据
	 *
	 * @access public
	 * @param Array $id 更新的ID
	 * @param Array $data 更新的数据
	 * @return Array
	 */
	public function update_data($id, $data)
	{
		$flag = $this->core->write('gc_grade_mechanism', $data, ['id'=>$id]);
		if ($flag) {
    		$content = "修改VIP资料成功：id={$id}---".json_encode($data);
	        $this->add_log($content);
    	} else {
    		$content = "修改VIP资料失败：id={$id}---".json_encode($data);
	        $this->add_log($content);
    	}
    	return $flag;
	}

	/**
	 * 新增gc_grade_mechanism表数据
	 *
	 * @access public
	 * @param Array $data 新增的数据
	 * @return Array
	 */
	public function add_data($data)
	{
		$data['add_time'] = time();
		$flag = $this->core->write('gc_grade_mechanism', $data);
		if ($flag) {
			$id = $this->core->db->insert_id();
    		$content = "新增VIP资料成功：id={$id}---".json_encode($data);
	        $this->add_log($content);
    	} else {
    		$content = "新增VIP资料失败：".json_encode($data);
	        $this->add_log($content);
    	}
    	return $flag;
	}

	/**
     * 对操作进行记录
     */
    public function add_log($content)
    {
        $this->select_db('private');
        $this->load->model('log/Log_model');
        $data['content'] = $content;
        $this->Log_model->record($this->admin['id'], $data);
    }

    /**
     * 线上入款、公司入款、人工入款确认会员等级晋级相关操作
     * 会员加积分、改等级、写晋级奖励记录
     * @param $uid int 用户ID
     * @param $integral int 要增加的积分
     * @return array ['integral' => 0, 'vip_id' => 0] 积分&等级
     */
    public function grade_doing($uid, $integral)
    {
        $res = ['integral' => 0, 'vip_id' => 0];
        if (empty($uid) || empty($integral)) {
            return $res;
        }
        // 取整
        $integral = intval($integral);
        // 获取用户当前等级积分信息
        $userInfo = $this->get_one('id,integral,vip_id', 'user', array('id' => $uid, 'status' => 1));
        if (empty($userInfo)) {
            return $res;
        }
        // 获取等级机制
        $gradeMechanism = $this->get_list('*', 'grade_mechanism', array('status' => 1), array('orderby' => array('integral' => 'desc')));
        if (empty($gradeMechanism)) {
            return $res;
        }
        $gradeInfo = [];
        foreach ($gradeMechanism as $v) {
            if ($integral + $userInfo['integral'] >= $v['integral']) {
               $gradeInfo = $v;
               break;
            }
        }
        if (empty($gradeInfo)) {
            // 没达到最低等级直接去加积分
            return ['integral' => $integral, 'vip_id' => $userInfo['vip_id']];
        }
        if ($userInfo['vip_id'] == $gradeInfo['id']) {
            // 没有晋级直接加积分
            return ['integral' => $integral, 'vip_id' => $userInfo['vip_id']];
        } else if ($userInfo['vip_id'] + 1 == $gradeInfo['id']) {
            // 晋级没有跳级
            $promotionData = [
                'uid' => $uid,
                'before_id' => $userInfo['vip_id'],
                'grade_id' => $gradeInfo['id'],
                'jj_money' => $gradeInfo['jj_money'],
                'integral' => $userInfo['integral'] + $integral,
                'is_tj' => 1,
                'is_receive' => 1,
                'add_time' => time(),
            ];
            $flag = $this->write('promotion_detail', $promotionData);
            return $flag == false ? $res : array('integral' => $integral, 'vip_id' => $gradeInfo['id']);
        } else if ($userInfo['vip_id'] + 1 < $gradeInfo['id']) {
            $beforeGrade = [];
            foreach ($gradeMechanism as $v) {
                if ($userInfo['vip_id'] == $v['id']) {
                    $beforeGrade = $v;
                    break;
                }
            }
            if (empty($beforeGrade)) {
                return $res;
            }
            // 晋级跳级
            $promotionData = [
                'uid' => $uid,
                'before_id' => $userInfo['vip_id'],
                'grade_id' => $gradeInfo['id'],
                'jj_money' => (int)$gradeInfo['tj_money'] - (int)$beforeGrade['tj_money'],
                'integral' => $userInfo['integral'] + $integral,
                'is_tj' => 2,
                'is_receive' => 1,
                'add_time' => time(),
            ];
            $flag = $this->write('promotion_detail', $promotionData);
            return $flag == false ? $res : array('integral' => $integral, 'vip_id' => $gradeInfo['id']);
        }
        return $res;
    }
}