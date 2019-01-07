<?php
defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__.'/User.php';

/**
 * 深处管理
 *
 * @file        user/api/controllers/ios/News
 * @package     user/api/controllers/ios
 * @author      ssm
 * @version     v1.0 2017/07/12
 * @created 	2017/07/12
 */
class Depths extends User
{

    /**
     * 用户名也是用户的唯一标示
     */
    protected $username = '';


    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $token = $this->P('token');
        $token_key = self::IOS_USER_TOKEN.$token;
        $username = $this->M->redis_GET($token_key);
        if (empty($username)) {
            $this->return_json(E_ARGS, '用户没有登录');
        }
        $this->username = $username;
    }

    /**
     * 用户更新
     *
     * @access public
     * @param post cardid 身份证
     * @param post truename 真实姓名
     * @return json
     */
    public function update()
    {
        $user_key = self::IOS_USER_INFO.$this->username;
        $user = $this->M->redis_GET($user_key);
        $user = json_decode($user, true);
        $user['cardid'] = $this->P('cardid');
        $user['truename'] = $this->P('truename');
        $this->M->redis_SET($user_key, json_encode($user));
        $this->M->redis_EXPIRE($user_key, self::IOS_USER_INFO_EXPIRE);
        $this->return_json(OK, '用户更改信息成功');
    }

    /**
     * 获取订单
     *
     * @access public
     * @param int $page 	页数
     * @return json
     */
    public function orders($page=0)
    {
        $rows = 15;
        $page -= 1;
        $page = $page*$rows;
        if ($page) {
            $page+=1;
        }
        $rows += $page;
        $order_key = self::IOS_USER_ORDER.$this->username;
        $order_arr = $this->M->redis_LRANGE($order_key, $page, $rows);
        if (empty($order_arr)) {
            $order_arr = ['rows'=>[]];
        } else {
            $order_arr = array_map(function ($order) {
                return json_decode($order, true);
            }, $order_arr);
            $order_arr = ['rows'=>$order_arr];
        }
        $this->return_json(OK, $order_arr);
    }


    /**
     * 添加订单号
     *
     * @access public
     * @param post
     * @return json
     */
    public function addOrder()
    {
        $data = [
            'id' => $this->M->redis_INCR(self::IOS_USER_ORDER_INCR),
            'from' => $this->P('from'),
            'destined' => $this->P('destined'),
            'starttime' => $this->P('starttime'),
            'endtime' => $this->P('endtime'),
            'pricesum' => $this->P('pricesum'),
            'train' => $this->P('train'),
            'data' => $this->P('data'),
            'seat' => $this->P('seat'),
            'ordernum' => order_num(1,1)
        ];
        foreach ($data as $key => $value) {
            if (empty($value)) {
                $this->return_json(E_ARGS, "{$key}必须有参数");
            }
        }
        $tempdata = $data;
        $data = json_encode($data);
        $order_key = self::IOS_USER_ORDER.$this->username;
        $flag = $this->M->redis_RPUSH($order_key, $data);
        $this->M->redis_EXPIRE($order_key, self::IOS_USER_ORDER_EXPIRE);
        if ($flag) {
            $this->return_json(OK, ['ordernum'=>$tempdata['ordernum']]);
        } else {
            $this->return_json(E_ARGS, '添加订单失败');
        }
    }

    /**
     * 添加提问
     *
     * @param post&ask  post参数：ask
     * @return Array ['code'=>'','msg'=>'']
     */
    public function addAsk()
    {
        $data = [
            'id' => $this->M->redis_INCR(self::IOS_USER_ASK_INCR),
            'ask' => $this->P('ask'),
        ];
        foreach ($data as $key => $value) {
            if (empty($value)) {
                $this->return_json(E_ARGS, "{$key}必须有参数");
            }
        }
        $data = json_encode($data);
        $ask_key = self::IOS_USER_ASK.$this->username;
        $flag = $this->M->redis_RPUSH($ask_key, $data);
        $this->M->redis_EXPIRE($ask_key, self::IOS_USER_ASK_EXPIRE);
        if ($flag) {
            $this->return_json(OK, '添加提问成功');
        } else {
            $this->return_json(E_ARGS, '添加提问失败');
        }
    }
}
