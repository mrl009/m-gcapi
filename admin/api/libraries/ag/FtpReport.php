<?php

class FtpReport
{
    /**
     * ftp配置项
     * @var string
     */
    //private $host = 'xml.agingames.com';
    private $host = 'xb.gdcapi.com';
    private $username = 'M40.Kings';
    private $password = '8DGRr6%IM8';
    private $port = 21;
    private $ftp;
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('ftp');
        $this->ftp = $this->ci->ftp;
        $config['hostname'] = $this->host;
        $config['username'] = $this->username;
        $config['password'] = $this->password;
        $config['port'] = $this->port;
        $config['debug'] = true;
        if (!$this->ftp->connect($config)) {
            return false;
        }
    }

    public function close()
    {
        $this->ftp->close();
    }

    //将XML转为array
    private function xml_to_array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * 获取订单
     */
    public function get_bet_order()
    {
        $game_type = ['AGIN', 'HUNTER', 'XIN', 'YOPLAY'];
        $xml_dir = APPPATH . 'cache/ag_xml';
        $this->ci->load->model('sx/ag/Game_order_model', 'game_order');
        $this->ci->load->model('sx/User_model', 'user_model');
        $grab_num = 30;

        foreach ($game_type as $val) {
            $today_dir = '/' . $val . '/' . date('Ymd', time() - 12 * 60 * 60);
            $today_dir_path = $xml_dir . $today_dir;
            if (!is_dir($today_dir_path)) mkdir($today_dir_path, 0777, true);
            $list = $this->ftp->list_files($today_dir);
            if (!empty($list)) {
                $list_count = count($list);
                if ($list_count > $grab_num) {
                    $start_count = $list_count - $grab_num;
                } else {
                    $start_count = 0;
                }
                for ($i = $start_count; $i < $list_count; $i += 1) {
                    if (!isset($list[$i])) continue;
                    $file_path = $today_dir_path . strrchr($list[$i], '/');
                    if (is_file($file_path)) unlink($file_path);
                    if ($this->ftp->download($list[$i], $file_path)) {
                        $this->read_file($file_path);
                    }
                }
            }
            #补漏
            $lost_and_found_dir = '/' . $val . '/lostAndfound';
            $lost_list = $this->ftp->list_files($lost_and_found_dir);
            $lost_and_found_path = $xml_dir . $lost_and_found_dir;
            if (!empty($lost_list)) {
                for ($i = 0; $i < count($lost_list); $i += 1) {
                    if (!isset($lost_list[$i])) continue;
                    $file_path = $lost_and_found_path . strrchr($list[$i], '/');
                    if (is_file($file_path)) unlink($file_path);
                    if ($this->ftp->download($lost_list[$i], $file_path)) {
                        $this->read_file($file_path);
                    }
                }
            }
        }

        $this->close();
        return true;
    }

    public function read_file($file_path)
    {
        $file = fopen($file_path, 'r');
        $user_names=$this->ci->user_model->get_list('g_username','ag_user');
        $all_users=array_column($user_names,'g_username');
        while (!feof($file)) {
            $file_line = fgets($file);
            if (!$file_line) continue;
            $data = current($this->xml_to_array($file_line));
            $rs=$this->ci->game_order->insert_order($data,$all_users);
        }
    }
}
