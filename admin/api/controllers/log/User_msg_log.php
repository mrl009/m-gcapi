<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 用户消息日志
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/3/27
 * Time: 下午2:50
 */
class User_msg_log extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->load->model('log/User_msg_log_model');
    }

    /**
     * 获取消息类型
     */
    public function getMsgLogType()
    {
        $msgType = $this->User_msg_log_model->getMsgType();
        $this->return_json(OK, $msgType);
    }

    /**
     * 获取消息列表
     */
    public function getMsgLogList()
    {
        $this->core->open('log_user_msg');//打开表
        // 获取搜索条件
        $condition = [
            'uid' => $this->G('uid'),
            'from_time' => $this->G('from_time'),
            'to_time' => $this->G('to_time'),
            'account' => $this->G('account'),
            'msg_type' => $this->G('msg_type'),
            'terminal' => $this->G('terminal'),
        ];
        $searchInfo = $this->User_msg_log_model->getBasicAndSenior($condition);
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->core->get_list('*', 'log_user_msg', $searchInfo['basic'], $searchInfo['senior'], $page);
        $arr = $this->User_msg_log_model->formatData($arr);
        $rs = array('total' => $arr['total'], 'rows' => $arr['rows']);
        $this->return_json(OK, $rs);
    }

    /**
     * 删除信息
     */
    public function delete()
    {
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $this->core->delete('log_user_msg', explode(',', $id));

        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '删除了会员消息ID为:' . $id));
        $this->return_json(OK, '执行成功');
    }


    private function post_message($pingtai = '', $title, $content)
    {
		if(empty($pingtai) || $pingtai==2) {
			$d = $this->core->redis_get("sys:gc_set_appkey");
			if (empty($d)) {
				return false;
			} else {
				$d = json_decode($d, true);
			}
			$appkey_ad_arr[$d['app_key']] = $d['app_master_secret'];
		}
        if(empty($pingtai) || $pingtai==1){
			$o = $this->core->redis_get("sys:gc_ios_set_appkey");
			if(empty($o)){
				return false;
			}else{
				$o = json_decode($o,true);
			}
			$appkey_ios_arr[$o['ios_app_key']] = $o['ios_app_master_secret'];
		}

        $timestamp = $_SERVER['REQUEST_TIME'];
        $method = 'POST';
        $url = 'https://msgapi.umeng.com/api/send';
        $message = array(
            'appkey' => '',
            'timestamp' => $timestamp,
            'type' => 'broadcast',
            //'production_mode'=>true,
            'payload' => array(
                'display_type' => 'notification',
                'body' => array(
                    'ticker' => $title,
                    'title' => $title,
                    'text' => $content,
                    'play_vibrate' => true,
                    'play_lights' => true,
                    'play_sound' => true,
                    'after_open' => 'go_app',
                )),
        );
        $message_ios = array(
            'appkey' => '',
            'timestamp' => (string)$timestamp,
            'type' => 'broadcast',
            'production_mode'=>true,
            'payload' => array(
                'aps' => array(
                    'alert' => array(
                        'title' => '',
                        'subtitle' => '',
                        'body' => $content
                    )
                )
            ),
        );
        if ($pingtai == 2) {
            foreach ($appkey_ad_arr as $appkey => $app_master_secret) {//推送至安卓
                $message['appkey'] = $appkey;
                $json_message = json_encode($message, JSON_UNESCAPED_UNICODE);
                $sign_ad = md5($method . $url . $json_message . $app_master_secret);//生成签名
                $a = $this->send_post('https://msgapi.umeng.com/api/send?sign=' . $sign_ad, $json_message);//推送到安卓
                wlog(APPPATH . 'logs/push/push_message_' . $this->_sn . '.log', 'ad-' . $appkey . '-' . $a);
            }
        } elseif ($pingtai == 1) {
            foreach ($appkey_ios_arr as $appkey => $app_master_secret_ios) {
                $message_ios['appkey'] = $appkey;
                $json_message = json_encode($message_ios, JSON_UNESCAPED_UNICODE);
                $sign_ios = md5($method.$url.$json_message.$app_master_secret_ios);//生成签名
                $c = $this->send_post('https://msgapi.umeng.com/api/send?sign='.$sign_ios, $json_message);//推送到苹果
                wlog(APPPATH . 'logs/push/push_message_' . $this->_sn . '.log', 'ios-' . $appkey . '-' . $c);
            }
        } else {
            foreach ($appkey_ad_arr as $appkey => $app_master_secret) {//推送至安卓
                $message['appkey'] = $appkey;
                $json_message = json_encode($message, JSON_UNESCAPED_UNICODE);
                $sign_ad = md5($method . $url . $json_message . $app_master_secret);//生成签名
                $a = $this->send_post('https://msgapi.umeng.com/api/send?sign=' . $sign_ad, $json_message);//推送到安卓
                wlog(APPPATH . 'logs/push/push_message_' . $this->_sn . '.log', 'ad-' . $appkey . '-' . $a);
            }
            foreach ($appkey_ios_arr as $appkey => $app_master_secret_ios) {
                $message_ios['appkey'] = $appkey;
                $json_message = json_encode($message_ios, JSON_UNESCAPED_UNICODE);
                $sign_ios = md5($method.$url.$json_message.$app_master_secret_ios);//生成签名
                $c = $this->send_post('https://msgapi.umeng.com/api/send?sign='.$sign_ios, $json_message);//推送到苹果
                wlog(APPPATH . 'logs/push/push_message_' . $this->_sn . '.log', 'ios-' . $appkey . '-' . $c);
            }
        }
        return true;
    }



    //post推送
    private function send_post($url, $post_data)
    {
        wlog(APPPATH . 'logs/push/push_data_message_' . $this->_sn . '.log', json_encode($post_data));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT,26);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:23.0) Gecko/20100101 Firefox/23.0");
        $output = curl_exec($ch);
        $errorNo = curl_errno($ch);
        if ($errorNo) {
            $log = '[POST][error:cURL请求出错][cURL连接资源信息:' .json_encode(curl_getinfo($ch)).'][post参数:'. json_encode($post_data) .'][cURL错误信息:'.curl_error($ch).'][cURL错误码:'.$errorNo. "]" . PHP_EOL;
            wlog(APPPATH . 'logs/push/push_error_message_' . $this->_sn . '.log', $log);
        }
        curl_close($ch);
        return $output;
    }

    /**
     * 添加信息
     */
    public function add()
    {
        $admin_id = $this->P('admin_id');
        $content = $this->P('content');
        $title = $this->P('title');
        $terminal = $this->P('terminal');
        if (empty($admin_id) || empty($content) || empty($title)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $this->load->model('log/Log_model');
        //推送广播
        $is = $this->post_message($terminal, $title, $content);
        if (!$is) {
            // 记录操作日志
            $this->Log_model->record($this->admin['id'], array('content' => '发布推送消息' . $title . '失败'));
            $this->return_json(E_OP_FAIL, '操作失败!');
        }
        $data = array(
            'admin_id' => $admin_id,
            'content' => $content,
            'title' => $title,
            'terminal' => $terminal,
            'addtime' => time()
        );
        $where = array();
        $this->core->write('log_user_msg', $data, $where);

        // 记录操作日志
        $this->Log_model->record($this->admin['id'], array('content' => '新增了会员消息标题为:' . $title));
        $this->return_json(OK, '执行成功');
    }
}
