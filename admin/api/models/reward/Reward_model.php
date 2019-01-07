<?php

defined('BASEPATH') or exit('No direct script access allowed');


class Reward_model extends MY_Model
{
    public function get_auth_list($sn)
    {
        $rs = [];
        if (empty($sn)) {
            return $rs;
        }
        $this->select_db('public');
        $siteInfo = $this->get_one('*', 'site', ['site_id' => $sn]);
        if (empty($siteInfo)) {
            return $rs;
        }
        $condition = [
            'wherein' => array('parent_id' => [$siteInfo['id'], -1])
        ];
        $authList = $this->get_list('id,name,parent_id,site_id', 'site', ['site_id != ' => $sn], $condition);
        foreach ($authList as $k => $v) {
            $authList[$k]['is_child'] = $v['parent_id'] == $siteInfo['id'] ? 1 : 0;
        }
        return ['data' => $authList, 'rebate' => $siteInfo['rebate']];
    }

    public function save_auth($sn, $id, $rebate)
    {
        $this->select_db('public');
        $siteInfo = $this->get_one('*', 'site', ['site_id' => $sn]);
        if (empty($siteInfo)) {
            return false;
        }
        $this->select_db('public_w');
        $this->write('site', array('rebate' => $rebate), array('id' => $siteInfo['id']));
        $this->db->where(array('parent_id' => $siteInfo['id']))->update('site', array('parent_id' => -1));
        $this->db->where_in('id', explode(',', $id));
        $this->db->update('site', array('parent_id' => $siteInfo['id']));
        return true;
    }

    public function reward_report($adminId, $start, $end)
    {
        if ($adminId == 1) {
            $rs = $this->all_report($start, $end);
        } else {
            $rs = $this->one_report($adminId, $start, $end);
        }
        $total = $this->format_total($rs);
        return ['data' => $rs, 'total' => $total];
    }

    /*******************报表信息**********************/

    /**
     * @param $adminId
     * @param $start
     * @param $end
     * @return array
     */
    private function one_report($adminId, $start, $end)
    {
        $rs = [];
        $adminInfo = $this->get_one('site_id', 'admin', ['id' => $adminId]);
        if (empty($adminInfo)) {
            return $rs;
        }
        // 获取代理站信息
        $this->select_db('public');
        $siteInfo = $this->get_one('*', 'site', ['site_id' => $adminInfo['site_id']]);
        if (empty($siteInfo)) {
            return $rs;
        }
        $siteList = $this->get_list('site_id,name', 'site', ['parent_id' => $siteInfo['id']]);
        if (empty($siteList)) {
            return $rs;
        }
        foreach ($siteList as $v) {
            $this->init($v['site_id']);
            $tmp = $this->get_site_report($start, $end);
            $tmp = $this->format_rs($tmp, $v['name'], $siteInfo['rebate']);
            array_push($rs, $tmp[0]);
        }
        return $rs;
    }

    private function all_report($start, $end)
    {
        $rs = [];
        $this->select_db('public');
        $siteList = $this->get_list('site_id,name,parent_id', 'site', ['parent_id !=' => -1]);
        if (empty($siteList)) {
            return $rs;
        }
        $siteInfo = $this->get_list('id,site_id,rebate', 'site', [], ['wherein' => array('id' => array_column($siteList, 'parent_id'))]);
        $siteInfo = array_make_key($siteInfo, 'id');
        if (empty($siteInfo)) {
            return $rs;
        }
        foreach ($siteList as $v) {
            $rebate = isset($siteInfo[$v['parent_id']]) ? $siteInfo[$v['parent_id']]['rebate'] : 0;
            $this->init($v['site_id']);
            $tmp = $this->get_site_report($start, $end);
            $tmp = $this->format_rs($tmp, $v['name'], $rebate);
            array_push($rs, $tmp[0]);
        }
        return $rs;
    }

    private function get_site_report($start, $end)
    {
        $this->select_db('private');
        $select = 'if(sum(bets_num) is null, 0, sum(bets_num)) as bets_num,
                    if(sum(price) is null, 0, sum(price)) as total_price,
                    if(sum(valid_price) is null, 0, sum(valid_price)) as valid_price,
                    if(sum(return_price) is null, 0, sum(return_price)) as return_price,
                    if(sum(lucky_price) is null, 0, sum(lucky_price)) as lucky_price';
        $where = ['report_date >=' => $start, 'report_date <=' => $end];
        $rs = $this->get_list($select, 'report', $where);
        return $rs;
    }

    private function format_rs($data, $site_name, $rebate)
    {
        foreach ($data as &$v) {
            $v['site_name'] = $site_name;
            $v['cor_diff_price'] = sprintf("%.3f", floatval($v['valid_price'] - $v['lucky_price'] - $v['return_price']));
            $v['rebate'] = $rebate;
            $v['money'] = $v['cor_diff_price'] > 0 ? sprintf("%.3f", $v['cor_diff_price'] * $rebate / 100) : 0;
        }
        return $data;
    }

    private function format_total($data)
    {
        if (empty($data)) {
            return [];
        }
        return [
            'total_money' => sprintf("%.3f", array_sum(array_column($data, 'money'))),
            'total_price' => sprintf("%.3f", array_sum(array_column($data, 'total_price'))),
            'total_valid_price' => sprintf("%.3f", array_sum(array_column($data, 'valid_price'))),
            'total_return_price' => sprintf("%.3f", array_sum(array_column($data, 'return_price'))),
            'total_lucky_price' => sprintf("%.3f", array_sum(array_column($data, 'lucky_price'))),
        ];
    }
}