<?php
/**
 * @brief 前台主页
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/18
 * Time: 下午6:47
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Home_model extends MY_Model
{

    /**
     * 主页-获取主页数据
     * @comment $show_location 显示位置，0：全部，1：安卓，2：苹果 ,3：H5,4：PC
     * @return array
     */
    public function getHomeData($show_location)
    {
        // 站点名称，客服
        $siteInfo = $this->getSiteInfo();
        //.加一个ios_name
        $rs['ios_name'] = isset($siteInfo['ios_name']) ? $siteInfo['ios_name'] : '';
        $rs['web_name'] = isset($siteInfo['web_name']) ? $siteInfo['web_name'] : '';
        $rs['wap_name'] = isset($siteInfo['wap_name']) ? $siteInfo['wap_name'] : '';
        $rs['app_color'] = isset($siteInfo['app_color']) ? $siteInfo['app_color'] : '';
        $rs['register_open_verificationcode'] = $siteInfo['register_open_verificationcode'];
        $rs['register_open_username'] = $siteInfo['register_open_username'];
        $rs['quick_recharge_url'] = $siteInfo['quick_recharge_url'];
        $rs['lottery_auth'] = $siteInfo['lottery_auth'];
        $rs['is_agent'] = $siteInfo['is_agent'];
        $rs['cp_default'] = $siteInfo['cp_default'];
        $rs['share_url'] = isset($siteInfo['share_url']) ? $siteInfo['share_url'] : '';
        $rs['register_is_open'] = $siteInfo['register_is_open'];
        $rs['strength_pwd'] = $siteInfo['strength_pwd'];
        $rs['online_service'] = isset($siteInfo['online_service']) ? $siteInfo['online_service'] : '';
        $rs['ios_qrcode'] = isset($siteInfo['ios_qrcode']) ? $siteInfo['ios_qrcode'] : '';
        $rs['android_qrcode'] = isset($siteInfo['android_qrcode']) ? $siteInfo['android_qrcode'] : '';
        $rs['h5_qrcode'] = isset($siteInfo['h5_qrcode']) ? $siteInfo['h5_qrcode'] : '';
        $rs['is_open_wechat'] = isset($siteInfo['is_open_wechat']) ? $siteInfo['is_open_wechat'] : 0;
        $rs['is_open_alipay'] = isset($siteInfo['is_open_alipay']) ? $siteInfo['is_open_alipay'] : 0;
        $rs['qq'] = isset($siteInfo['qq']) ? explode(',', $siteInfo['qq']) : '';
        $rs['tel'] = isset($siteInfo['tel']) ? explode(',', $siteInfo['tel']) : '';
        $rs['logo'] = $siteInfo['logo'];
        $rs['logo_wap'] = $siteInfo['logo_wap'];
        $rs['wap_head_logo'] = $siteInfo['wap_head_logo'];
        $rs['keyword'] = $siteInfo['keyword'];
        $rs['description'] = $siteInfo['description'];
        $rs['copyright'] = $siteInfo['copyright'];
        $rs['wap_domain'] = $siteInfo['wap_domain'];
        $rs['domain'] = $siteInfo['domain'];
        $rs['piwik'] = $siteInfo['piwik'];
        $rs['sys_games'] = $siteInfo['sys_games'];
        $rs['sys_activity'] = $siteInfo['sys_activity'] == -1 ? '' : $siteInfo['sys_activity'];
        $rs['reward_day'] = $siteInfo['reward_day'] ? explode(',', $siteInfo['reward_day']) : '';
        $rs['is_open_hddt'] = isset($siteInfo['is_open_hddt']) ? $siteInfo['is_open_hddt'] : 0;
        $rs['is_open_ios_hddt'] = isset($siteInfo['is_open_ios_hddt']) ? $siteInfo['is_open_ios_hddt'] : 0;
        $rs['tmp_to_zwname'] = TMP_TO_ZWNAME;
        //获取下载地址
        $m = $this->get_list('app_type,url', 'version', array());
        $rs['ios_url'] = $m[0]['url'];
        $rs['android_url'] = $m[1]['url'];
        // 公告信息
        $notice = $this->getUserNotice(0, $show_location,1);
        $rs['new_notice'] = isset($notice) ? $notice : [];

        // 图片信息
        $img = $this->getImg();
        if (!empty($img) && is_array($img)) {
            $imgType = ['3' => 'wap_slides_img', '4' => 'wap_bottom_img', '5' => 'wap_bottom_unselected_img'];
            foreach ($img as $v) {
                if (!empty($v['img_json'])) {
                    $v['id'] == 1 && $rs['wap_banner_img'] = json_decode($v['img_json'], true);
                    $v['id'] == 1 && $rs['pc_banner_img'] = json_decode($v['img_json'], true);
                    if (in_array($v['id'], array(3, 5))) {
                        $temp = json_decode($v['img_json'], true);
                        foreach ($temp as $item) {
                            if ($item['status'] == 1) {
                                $rs[$imgType[$v['id']]][] = $item;
                            }
                        }
                    }
                }
            }
        }
        $cp = $this->get_gcset(['cp']);
        $cp = explode(',',$cp['cp']);
        $gc = $this->redisP_zrange('ctg:gc', 0, -1);//获取索引
        $sc = $this->redisP_zrange('ctg:sc', 0, -1);//获取索引
        $g = $s = 0;
        foreach($cp as $k => $v){
            if(in_array($v,$gc)){
                $g = 1;continue;
            }
            if(in_array($v,$sc)){
                $s = 2;
            }
        }
        $rs['qs'] = empty($g)?'':$g.',';
        empty($s)?$rs['qs'] = rtrim($rs['qs'], ','):$rs['qs']=$rs['qs'].$s;
        //.增加 每日嘉奖和晋级奖励数据
        $rs['jinji_jiajiang'] = $this->get_all('*', 'set_activity', array(),['wherein' =>['id'=>[1001,1002]]]);
        return $rs;
    }

    /**
     * 根据ID获取站点信息
     * @return array
     */
    public function getSiteInfo()
    {
        //.加一个ios_name
        $field = 'ios_name,web_name,online_service,wap_name,app_color,logo_wap,wap_head_logo,keyword,description,copyright,wap_domain,domain,qq,tel,ios_qrcode,android_qrcode,h5_qrcode,logo,register_open_verificationcode,is_agent,strength_pwd,register_is_open,cp_default,register_open_username,quick_recharge_url,lottery_auth,piwik,sys_games,sys_activity,reward_day,share_url,is_open_wechat,is_open_alipay,is_open_hddt,is_open_ios_hddt';
        if ($field !== '*') {
            $field = explode(',',$field);
        }
        return $this->get_gcset($field);
    }

    /**
     * 获取会员公告
     * @param int $type 公告类型0：最新公告，1：弹出（PC）2： 会员公告
     * @param int $show_location 显示位置，0：全部，1：安卓，2：苹果 ,3：H5,4：PC
     * @return mixed
     */
    public function getUserNotice($type, $show_location,$limit=5)
    {
        $this->db->select('content,addtime,title,show_location,status');
		if ($show_location!=0){
			$this->db->where_in('show_location', [0, $show_location]);
		}
		$this->db->order_by('addtime','desc');
		$res = $this->db->get_where('log_user_notice', array('status' => 1, 'notice_type' => $type), $limit)->result_array();
        foreach ($res as &$v) {
            $v['time'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        return $res;
    }

    /**
     * 主页-图片
     * @return array
     */
    private function getImg()
    {
        return $this->get_all('id,img_json', 'set_img');
    }

    /**
     * 关于我们
     * @return array
     */
    public function aboutUs()
    {
        return $this->get_one('pic,content', 'set_article', array('id' => 1));
    }

    /**
     *  获取首页games数据
     */
    public function getHomeCP()
    {
        $gcset = $this->get_gcset(['cp_index','lottery_auth']);
        $cp_index = array_unique(explode(',',$gcset['cp_index']));
        $lottery_auth = array_unique(explode(',',$gcset['lottery_auth']));
        if (! $this->redisP_exists('games')) {
            $this->load->model('Comm_model','comm');
            $this->comm->cache_all_games();
        }
        $games = $this->redisP_hmget('games',$cp_index);
        array_walk($games,function (&$item){
            $item = json_decode($item,true);
        });
        $tab = [1=>explode(',',GC),2=>explode(',',SC),4=>explode(',',SX)];
        $types = [1=>'gc',2=>'sc',4=>'sx'];
        $games = array_values($games);
        $data = [];
        foreach ($lottery_auth as $v) {
            foreach ($games as $k => $vv) {
                if (in_array($vv['id'],$tab[$v])) {
                    $vv['gid'] = $vv['id'];
                    $data[$types[$v]][] = $vv;
                    unset($games[$k]);
                }
            }
        }
        return $data;
    }

    /**
     *  获取购彩页games数据
     */
    public function getAllCP()
    {
        $gcset = $this->get_gcset(['cp','lottery_auth']);
        $cp = array_unique(explode(',',$gcset['cp']));
        $lottery_auth = array_unique(explode(',',$gcset['lottery_auth']));
        if (! $this->redisP_exists('games')) {
            $this->load->model('Comm_model','comm');
            $this->comm->cache_all_games();
        }
        $games = $this->redisP_hmget('games',$cp);
        array_walk($games,function (&$item){
            $item = json_decode($item,true);
        });
        $tab = [1=>explode(',',GC),2=>explode(',',SC),4=>explode(',',SX)];
        $types = [1=>'gc',2=>'sc',4=>'sx'];
        $games = array_values($games);
        $data = [];
        foreach ($lottery_auth as $v) {
            foreach ($games as $k => $vv) {
                if (in_array($vv['id'],$tab[$v])) {
                    $vv['gid'] = $vv['id'];
                    if (get_instance()->from_way == FROM_PC) {
                        // 来源为PC时,加上开奖信息
                    }
                    if ($vv['id'] > 1000) {
                        $data[$types[$v]][] = $vv;
                    } else {
                        if (!isset($data[$types[$v]][$vv['tmp']])) {
                            $data[$types[$v]][$vv['tmp']] = [];
                        }
                        $data[$types[$v]][$vv['tmp']][] = $vv;
                    }
                    if(!in_array($vv['id'],[3,4,24,25])){
                        unset($games[$k]);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 昨日数据造假
     */
    public function add_rand_data()
    {
        $rs = $this->yesterday_win_redis();
        if (empty($rs)) {
            $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
            $vip = [
                5 => '知府',
                6 => '總督',
                7 => '巡撫',
                8 => '丞相',
                9 => '帝王',
            ];
            $price = $this->rand_price();
            for ($i = 0; $i < 10; $i++) {
                $vip_id = mt_rand(5, 9);
                $img = $this->get_head_img();
                $username = $pattern{mt_rand(0, 35)} . '***' . $pattern{mt_rand(0, 35)};
                $rs[$i] = [
                    'img' => $img,
                    'lucky_price' => $price[$i],
                    'uid' => $i,
                    'username' => $username,
                    'win_info' => [
                        'VipID' => 'VIP' . $vip_id,
                        'VipName' => $vip[$vip_id],
                        'img' => $img,
                        'lucky_price' => $price[$i] + mt_rand(100, 10000),
                        'nickname' => $username,
                        'sex' => mt_rand(0, 1) ? '男' : '女',
                        'username' => $username,
                        'game_list' => [
                            [
                                'gid' => 56,
                                'name' => '重庆时时彩',
                                'game_img' => 'https://www.qzgao.com/cp5/cq_ssc.png',
                            ],
                            [
                                'gid' => 62,
                                'name' => '江苏快3',
                                'game_img' => 'https://www.qzgao.com/cp5/k3.png',
                            ],
                            [
                                'gid' => 76,
                                'name' => '北京PK拾',
                                'game_img' => 'https://www.qzgao.com/cp5/bjpk10-1.png',
                            ],
                            [
                                'gid' => 82,
                                'name' => '易彩快3',
                                'game_img' => 'https://www.qzgao.com/cp5/k3.png',
                            ],
                        ],
                    ]
                ];
            }
            $this->yesterday_win_redis($rs);
        }
        return $rs;
    }

    /**
     * 随机金额
     * group 100-300，200-500，300-700
     * @return array
     */
    private function rand_price()
    {
        $group = [
            [1000000000, 3000000000],
            [2000000000, 5000000000],
            [3000000000, 7000000000]
        ];
        $key = rand(0, 2);
        for ($i = 0; $i < 10; $i++) {
            $rs[] = rand($group[$key][0], $group[$key][1]) / 1000;
        }
        /*$rs = [
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
            rand($group[$key][0], $group[$key][1]) / 1000,
        ];*/
        rsort($rs);
        return $rs;
    }

    /**
     * 随机金额
     * group1 150-500 三名 70-250 四名 30-150 三名
     * group2 100-300 三名 50-120 四名 20-60 三名
     * group3 100-200 三名 40-100 四名 10-50 三名
     * @return array
     */
    private function rand_price_bak()
    {
        $group = [
            [[1500000000, 5000000000], [700000000, 2500000000], [300000000, 1500000000]],
            [[1000000000, 3000000000], [500000000, 1200000000], [200000000, 600000000]],
            [[1000000000, 2000000000], [400000000, 1000000000], [100000000, 500000000]]
        ];
        $key = rand(0, 2);
        $rs = [
            rand($group[$key][0][0], $group[$key][0][1]) / 1000,
            rand($group[$key][0][0], $group[$key][0][1]) / 1000,
            rand($group[$key][0][0], $group[$key][0][1]) / 1000,
            rand($group[$key][1][0], $group[$key][1][1]) / 1000,
            rand($group[$key][1][0], $group[$key][1][1]) / 1000,
            rand($group[$key][1][0], $group[$key][1][1]) / 1000,
            rand($group[$key][1][0], $group[$key][1][1]) / 1000,
            rand($group[$key][2][0], $group[$key][2][1]) / 1000,
            rand($group[$key][2][0], $group[$key][2][1]) / 1000,
            rand($group[$key][2][0], $group[$key][2][1]) / 1000,
        ];
        rsort($rs);
        return $rs;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function yesterday_win_redis($data = [])
    {
        $key = 'yesterday_win';
        if (empty($data)) {
            $rs = $this->redis_get($key);
            $rs = json_decode($rs, true);
        } else {
            $rs = $this->redis_set($key, json_encode($data));
            $this->redis_expire($key, strtotime(date('Y-m-d',strtotime('+1 day'))) - time());
        }
        return $rs;
    }

    /**
     * 随机一个中奖详情
     * @return array
     */
    public function rand_win_info() {
        return [
            'VipID' => 'VIP6',
            'VipName' => '總督',
            'img' => $this->get_head_img(),
            'lucky_price' => rand(100000000, 500000000) / 1000,
            'nickname' => '',
            'sex' => mt_rand(0, 1) ? '男' : '女',
            'username' => 'm***4',
            'game_list' => [
                [
                    'gid' => 56,
                    'name' => '重庆时时彩',
                    'game_img' => 'https://www.qzgao.com/cp5/cq_ssc.png',
                ],
            ],
        ];
    }

    /**
     * 伪造头像
     * https://www.qzgao.com/portrait/avatar1.png
     */
    private function get_head_img()
    {
        return 'https://www.qzgao.com/portrait/avatar' . rand(1, 40) . '.png';
    }

    /**
     * 通过域名获取邀请码
     * @param $domain
     * @return array
     */
    public function getInviteCodeByDomain($domain)
    {
        $rs = ['invite_code' => ''];
        if (empty($domain)) {
            return $rs;
        }
        $inviteCode = $this->get_one('invite_code', 'set_domain', ['domain' => $domain]);
        $rs['invite_code'] = isset($inviteCode['invite_code']) ? $inviteCode['invite_code'] : '';
        return $rs;
    }
}
