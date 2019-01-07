<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 红包模块
 *
 * @author      ssm
 * @package     models
 * @version     v1.0 2017/10/16
 */
class Grade_mechanism_model extends MY_Model
{
    /**
     * 对应表
     */
    private $table = 'grade_mechanism';

    /**
     * 获取gc_grade_mechanism表全部数据
     *
     * @access public
     * @return Array
     */
    public function all()
    {
        $data = $this->get_list('id, title, integral', $this->table, [], []);

        return array_map(function ($value) {
            $value['id'] = 'VIP' . $value['id'];
            return $value;
        }, $data);
    }

    /**
     * 获取用户当前等级信息、未领取的奖励金额
     * @param $uid
     * @return array
     */
    public function getUserGrade($uid)
    {
        $rs = ['vip_id' => 1, 'money' => 0, 'total_money' => 0, 'is_reward' => false];
        if (empty($uid)) {
            return $this->status(E_ARGS, '参数错误');
        }
        //用户等级
        $user = $this->get_one('vip_id', 'user', array('id' => $uid));
        if (empty($user)) {
            return $this->status(E_DATA_EMPTY, '没有该用户');
        }
        $rs['vip_id'] = isset($user['vip_id']) ? $user['vip_id'] : 1;
        //未领取金额
        $grade = $this->get_list('jj_money', 'promotion_detail', array('uid' => $uid, 'is_receive' => 1));
        if (!empty($grade)) {
            $money = 0;
            foreach ($grade as $v) {
                $money += isset($v['jj_money']) ? $v['jj_money'] : 0;
            }
            $rs['money'] = $money;
        }
        //最多领取金额
        $gradeInfo = $this->get_one('tj_money', $this->table, [], array('orderby' => array('id' => 'desc')));
        $rs['total_money'] = isset($gradeInfo['tj_money']) ? $gradeInfo['tj_money'] : 0;
        $rs['is_reward'] = $rs['money'] > 0 ? true : false;
        return $this->status(OK, $rs);
    }

    /**
     * 是否有晋级奖励
     * @param int $uid 用户ID
     * @return array
     */
    public function isReward($uid)
    {
        $rs = $this->get_one('id', 'promotion_detail', array('uid' => $uid, 'is_receive' => 1));
        return $rs ? ['is_reward' => true] : ['is_reward' => false];
    }

    /**
     * 更新领取记录
     * @param $uid
     * @return bool
     */
    public function updateReceive($uid)
    {
        if (empty($uid)) {
            return false;
        }
        $this->db->set('is_receive', 2);
        $this->db->set('update_time', time());
        return $this->db->where(array('uid' => $uid, 'is_receive' => 1))->update('promotion_detail');
    }

    /**
     * 线上自动入款会员等级晋级相关操作
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