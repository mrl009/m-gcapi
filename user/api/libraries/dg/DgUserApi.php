<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class DgUserApi extends BaseApi
{
    /**
     * 会员注册
     * @param string $username 用户名
     * @param string $password 密码
     * @param int $win_limit 奖金限额( 默认不限制 )
     * @param string $data 目标限红组号(不填则为A组)
     * @param string $currency_name 货币名称( 默认CNY )
     * @return bool|mixed
     */
    public function signup($username, $password, $win_limit = 0, $data = 'A', $currency_name = 'CNY')
    {
        $data = [
            'data' => $data,
            'member' => ['username' => $username, 'password' => $password, 'currencyName' => $currency_name, 'winLimit' => $win_limit]
        ];
        return self::send($data);
    }

    /**
     * 会员登录
     * @param string $username 用户名
     * @param string $password 可以不传,如果密码不同,将自动修改DG数据库保存的密码
     * @param string $lang 语言(默认为cn)
     * @return bool|mixed
     */
    public function login($username, $password = '', $lang = 'cn')
    {
        $data = [
            'lang' => $lang,
            'member' => ['username' => $username, 'password' => $password]
        ];
        //无需校验token
        self::$verify_token = false;
        return self::send($data);
    }

    /**
     * 会员试玩登入
     * @param int $device (设备类型,默认为web)
     * @param string $lang 语言(默认为en)
     * @return bool|mixed
     */
    public function free($device = 1, $lang = 'cn')
    {
        $data = [
            'lang' => $lang,
            'device' => $device
        ];
        //无需校验token
        self::$verify_token = false;
        return self::send($data);
    }

    /**
     * 更新会员信息
     * @param string $username 用户名
     * @param string $password 密码
     * @param int $status
     * @param int $win_limit 奖金限额(默认不限制)
     * @return bool|mixed 会员状态：0:停用, 1:正常, 2:锁定(不能下注)（默认正常）
     */
    public function update($username, $password, $status = 1, $win_limit = 0)
    {
        $data = [
            'member' => ['username' => $username, 'password' => $password, 'winLimit' => $win_limit, 'status' => $status]
        ];
        return self::send($data);
    }

    /**
     * 获取会员余额
     * @param $username
     * @return bool|mixed
     */
    public function getBalance($username)
    {
        $data = [
            'member' => ['username' => $username],
            'method' => 'getBalance'
        ];
        return self::send($data);
    }

    /**
     * 更新用户余额
     * @param $username
     * @param $platform_name
     * @return bool|mixed
     */
    public function updateBalance($username, $platform_name)
    {
        $data = $this->getBalance($username);
        if (isset($data['codeId']) && $data['codeId'] == 0 && isset($data['member']['balance']) && $username == strtolower($data['member']['username'])) {
            $balance = $data['member']['balance'];
            self::$ci->load->model('sx/dg/user_model', 'user_model');
            if (self::$ci->user_model->update_balance($username, $balance, $platform_name)) {
                return $data;
            }
        }
        return false;
    }

    /**
     * 获取当前代理下在DG在线会员信息
     * 请求间隔 30 秒
     */
    public function onlineReport()
    {
        return self::send([]);
    }
}