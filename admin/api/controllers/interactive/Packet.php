<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Packet extends MY_Controller
{
    public function __construct(){
        parent::__construct();
        $this->load->model('interactive/Packet_model','PM');
    }
    public function get_packet_list()
    {
        $post_data = $this->input->post(null,true);
        $start = $post_data['start'];
        $end = $post_data['end'];
        $conditions = $this->get_conditions($post_data);
        $where = [];
        $username = trim($post_data['username']);
        if (!empty($username)) $where['a.username'] = $username;

        if (!empty($start) && !empty($end)){
            $where['c.addtime >='] = strtotime($start);
            $where['c.addtime <'] = strtotime($end);
        }elseif (!empty($start)){
            $where['c.addtime >='] = strtotime($start);
            $where['c.addtime <'] = strtotime($start)+3600*24*60;
        }elseif (!empty($end)){
            $where['c.addtime >='] = strtotime($end)-3600*24*60;
            $where['c.addtime <'] = strtotime($end);
        }else{
            $where['c.addtime >='] = strtotime(date('Y-m-d'));
            $where['c.addtime <'] = strtotime(date('Y-m-d').'+1 day');
        }
        $params = $conditions['params'];

        $field = 'a.username,b.nickname,c.id,c.num,c.money,c.addtime,(c.money-c.balance) as grab_money,c.is_refund,c.balance,c.refund_time';

        $res = $this->PM->get_packet_list($field,$where,$params);
        $this->return_json(OK, $res);
    }

    public function get_packet_detail(){
        $post_data = $this->input->post(null,true);
        $id = $post_data['id'];
        $conditions = $this->get_conditions($post_data);
        $params = $conditions['params'];
        $res = $this->PM->get_packet_detail($id,$params);
        $data['rows'] = $res;
        $data['total'] = count($res);
        $this->return_json(OK,$data);
    }

    public function get_conditions($data){
        $page = !empty($data['page']) ? $data['page'] :1;
        $rows = !empty($data['rows']) ? $data['rows'] :50;
        $sort = !empty($data['sort']) ? trim($data['sort']) : 'a.id';
        $order = !empty($data['order']) ? trim($data['order']) : 'desc';
        $offset = ($page - 1) * $rows;
        switch ($sort) {
            case 'username ':
                $sort = 'username';
                break;
            case 'addtime':
                $sort = 'addtime';
                break;
            case 'packet_in':
                $sort = 'packet_in';
                break;
            case 'packet_out':
                $sort = 'packet_out';
                break;
            case 'packet_refund':
                $sort = 'packet_refund';
                break;
            case 'packet_profit':
                $sort = 'packet_profit';
                break;
        }
        //设置分页排序等参数
        $params = array(
            'offset' => $offset,
            'rows' => $rows,
            'sort' => $sort,
            'order' => $order
        );

        $conditions = [
            'params' => $params,
                                //TO DO
        ];

        return $conditions;
    }

    public function get_statistics_list(){
        $post_data = $this->input->post(null,true);
        $conditions = $this->get_conditions($post_data);
        $params = $conditions['params'];
        $start = $post_data['start'];
        $end = $post_data['end'];
        $where = [];
        $username = trim($post_data['username']);
        if (!empty($username)) $where['a.username'] = $username;

        if (!empty($start) && !empty($end)){
            $where['c.report_date >='] = $start;
            $where['c.report_date <'] = $end;
        }elseif (!empty($start)){
            $where['c.report_date >='] = $start;
            $where['c.report_date <'] = date('Y-m-d',strtotime($start)+3600*24*60);
        }elseif (!empty($end)){
            $where['c.report_date >='] = date('Y-m-d',strtotime($end)-3600*24*60);
            $where['c.report_date <'] = $end;
        }else{
            $where['c.report_date ='] = date('Y-m-d',time());
        }
        $result = $this->PM->get_statistics_list($where,$params);
        //$result['rows'] = stript_float($result['rows'],4);
        $this->return_json(OK,$result);
    }
}