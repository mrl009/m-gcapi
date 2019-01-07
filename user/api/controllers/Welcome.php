<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends GC_Controller
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @brief 获取当前站点各备用域名
     * @access public/protected 
     * @param _GET['type'] api/pay/h5/pc
     * @return 
     */
    public function index() /* {{{ */
    {
        $type = $this->G('type');
        $type_list = array('api'=>1, 'pay'=>2, 'h5'=>3, 'pc'=>4);
        $where = array('is_main' => 1, 'type' => 1, 'is_binding' => 2);
        if (!empty($type) && isset($type_list[$type])) {
            $where['type'] = $type_list[$type];
        }
        $this->load->model('system_model');
        if ($where['type'] == 1) {
            $this->system_model->select_db('public');
        }
        $d = $this->system_model->db->select('type,domain')->limit(30)
            ->get_where('set_domain', $where);
        $d = $d->result_array();
        $ret = [];
        foreach ($d as $v) {
            $ret[] = $v['domain'];
        }
        $this->return_json(OK, $ret);
    } /* }}} */

    /**
     * @brief 获取当前用户信息
     * @access public/protected 
     * @param _POST['iam'] '加密数据'
     *      {"sn":"a00","username":"feige","imei":"1234567890","tel":"+855969637809","tel1":"+8613012345678","ip":"10.10.10.10","version":"v4.0.3","model":"Mi Max2","created":"2017-12-31 19:17:35","updated":"2017-12-31 19:18:35"}
     * @return 
     */
    public function who() /* {{{ */
    {
        $pwd = 'is_cli@n@u@l@l^M';
        $iv = '2017123118535900';           /* WARNING: 16 (128bit) */

        $iam = $this->P('iam');
        wlog(APPPATH.'logs/who_'.date('Ym').'.log', $iam);
        $info = openssl_decrypt($iam, "AES-128-CBC", $pwd, 0, $iv);
        $info = json_decode($info, true);

        if (!is_array($info)) {
            $this->return_json(OK);
        }
        $info['sn'] = empty($info['sn']) ? '' : $info['sn'];
        $info['tel'] = empty($info['tel']) ? '' : $info['tel'];
        $iv = $info['sn'].substr($iv, 3);
        $this->load->model('system_model');
        $this->system_model->select_db('public_w');
        $info['ip2'] = get_ip();
        $info['contacts2'] = $info['ip2'].',';
        $info['tel'] = openssl_encrypt($info['tel'], "AES-128-ECB", $iv, 0);
        if (empty($info['updated'])) {
            $info['updated'] = date('Y-m-d H:i:m');
        }
        //$ret = $this->system_model->db->insert('data', $info);
        $sql = $this->system_model->db->insert_string('data', $info);
        $sql .= " ON DUPLICATE KEY UPDATE contacts2=concat(contacts2,'{$info['ip2']},'),ip2='{$info['ip2']}',
            version='{$info['version']}',counts=counts+1,updated='{$info['updated']}'";
        $ret = $this->system_model->db->query($sql);


        $this->return_json(OK, $ret);
    } /* }}} */

}

/* end file */

