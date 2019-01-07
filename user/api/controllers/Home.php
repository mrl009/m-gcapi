<?php
/**
 * @brief 前台主页
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/18
 * Time: 下午6:37
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends GC_Controller
{

    private $win_hash = 'win_uid_list';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Home_model');
    }

    /**
     * 主页-获取主页数据
     */
    public function getHomeData()
    {
        $show_location = $this->getShowLocation($this->from_way);
        $rs = $this->Home_model->getHomeData($show_location);
        $rs['cp_data'] = $this->Home_model->getHomeCP();
        $this->return_json(OK, $rs);
    }

    /**
     * 获取购彩页数据
     */
    public function all_cp()
    {
        $all_cp = $this->Home_model->getAllCP();
        $this->return_json(OK, $all_cp);
    }

    /**
     * 获取会员公告
     * @comment $type 公告类型0：最新公告，1：弹出（PC）2： 会员公告
     */
    public function getNotice()
    {
        $type = $this->G('type') ? intval($this->G('type')) : 0;
        $show_location = $this->getShowLocation($this->from_way);
        $rs = $this->Home_model->getUserNotice($type, $show_location);
        $this->return_json(OK, $rs);
    }

    /**
     * 主页-获取分享页数据
     */
    public function get_fenxiang()
    {
        $siteinfo = $this->Home_model->getSiteInfo();
        if($this->from_way == FROM_ANDROID){
            $arr['qrcode'] = $siteinfo['android_qrcode'];
        }elseif($this->from_way == FROM_IOS){
            $arr['qrcode'] = $siteinfo['ios_qrcode'];
        }elseif($this->from_way == FROM_WAP){
            $arr['qrcode'] = $siteinfo['h5_qrcode'];
        }else{
            $arr['qrcode'] = $siteinfo['h5_qrcode'];
        }
        $arr['fenxiang_string'] = $this->Home_model->redis_get("sys:fenxiang_string");
        if(empty($arr['fenxiang_string'])){
            $arr['fenxiang_string'] = '分享给新用户注册有大量积分相送！';
        }
        $this->return_json(OK, $arr);
    }

    /**
     * 主页-关于我们
     */
    public function aboutUs()
    {
        $rs = $this->Home_model->aboutUs();
        $this->return_json(OK, $rs);
    }

    /**********************中奖榜***********************/
    /**
     * @模块   app首页／中奖榜
     * @版本   Version 1.0.0
     * @日期   2017-04-25
     * @作者   lss
     */
    /* 获取中奖榜数据列表 */
    public function get_list_wins()
    {
        $this->load->model('user/Bet_record_model',
            'br_model');
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 10;
        if ($rows > 10) $rows = 10;
        $page = array(
            'page' => $page,
            'rows' => $rows,
        );
        $rs = $this->br_model->get_list_wins($page);
        foreach($rs as $k => $v){
            unset($rs[$k]['uid']);
        }
        shuffle($rs);
        $this->return_json(OK, $rs);
    }

    /**
     * @模块   发现／昨日奖金榜
     * @版本   Version 1.0.0
     * @日期   2017-11-3
     * @作者   lss
     */
    /* 获取昨日中奖榜数据列表 */
    public function get_win()
    {
        $this->load->model('user/Bet_record_model',
            'br_model');
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 10;
        if ($rows > 10) $rows = 10;
        $page = array(
            'page' => $page,
            'rows' => $rows,
        );
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $mmday = date('Y-m-d', strtotime('-1 day'));
        $rs = $this->br_model->get_win($page,$yesterday,$mmday);
        foreach($rs as $k => $v){
            unset($rs[$k]['uid']);
        }
        $this->return_json(OK, $rs);
    }


    /**
     * @模块   app首页／中奖榜
     * @版本   Version 1.0.0
     * @日期   2017-04-25
     * @作者   lss
     */
    /* 获取今日中奖榜数据列表 */
    public function today_win()
    {
        $this->load->model('user/Bet_record_model', 'br_model');
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 10;
        if ($rows > 10) $rows = 10;
        $page = array(
            'page' => $page,
            'rows' => $rows,
        );
        $rs = $this->br_model->get_list_wins($page);
        $rs = $this->conversion_uid($rs);
        shuffle($rs);
        $this->return_json(OK, $rs);
    }


    /**
     * @模块   发现／昨日奖金榜
     * @版本   Version 1.0.0
     * @日期   2017-11-14
     */
    /* 获取昨日中奖榜数据列表 */
    public function yesterday_win()
    {
        // 伪造数据
        $this->add_rand_data();
        $this->load->model('user/Bet_record_model', 'br_model');
        if ($this->Home_model->redisP_hget('dsn','w02')) {
            // 改成取 w02 易彩试玩站的数据
            $this->_db_private = null;
            $this->br_model->db_private = null;
            $this->br_model->db = null;
            $this->br_model->init('w02');
        }
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 10;
        if ($rows > 10) $rows = 10;
        $page = array(
            'page' => $page,
            'rows' => $rows,
        );
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $mmday = date('Y-m-d', strtotime('-1 day'));
        $rs = $this->br_model->get_win($page,$yesterday,$mmday);
        $rs = $this->conversion_uid($rs);
        $this->return_json(OK, $rs);
    }

    /**
     * 昨日数据造假
     */
    private function add_rand_data()
    {
        $rs = $this->Home_model->add_rand_data();
        $this->return_json(OK, $rs);
    }

    /**
     * @模块   uid转换
     * @版本   Version 1.0.0
     * @日期   2017-11-14
     */
    private function conversion_uid($rs,$jiechu=0)
    {
        if( empty($jiechu) ){
            foreach ($rs as $k => $v) {
                $shu = ((($v['uid']*$v['uid'])+18)*745-827)*1193;
                $rs[$k]['uid'] = (string)$shu;
            }
        } else {
            $rs = sqrt((($rs/1193 + 827)/745)-18);
            if (floor($rs)!=$rs) {
                $this->return_json(E_ARGS, '参数错误！');
            }
            $rs = intval($rs);
        }
        return $rs;
    }


    /**
     * 个人中奖信息
     *
     * @access public
     * @return Array
     */
    public function win_info()
    {
        $type = (int)$this->G('type');
        $id = (int)$this->G('uid');
        if ($type == 2) {
            $this->rand_win_info($id);
        }
        $this->load->model('user/User_info_model', 'model');
        if ($type==2 && $this->Home_model->redisP_hget('dsn','w02')) {
            // 昨日中奖信息 改成取 w02 易彩试玩站的数据
            $this->_db_private = null;
            $this->model->db_private = null;
            $this->model->db = null;
            $this->model->init('w02');
        }
        if(empty($id)){
            $this->return_json(E_ARGS, '参数错误！');
        }
        $id = $this->conversion_uid($id,1);


        /*if ($type === 1) {
            $data['front']= date('Y-m-d');
            $data['back']= date('Y-m-d');
            $data = $this->model->win_info($id, $data);
        } else {*/
            $data['front']= date('Y-m-d');
            $data['back']= date('Y-m-d', strtotime('-30 days'));
            $data = $this->model->win_info($id, $data);
        //}
        $this->return_json(OK, $data);
    }

    /**
     * 昨日数据详情造假
     * @param $uid
     */
    private function rand_win_info($uid)
    {
        $data = $this->Home_model->yesterday_win_redis();
        if (isset($data[$uid])) {
            $rs = $data[$uid]['win_info'];
        } else {
            $rs = $this->Home_model->rand_win_info();
        }
        $this->return_json(OK, $rs);
    }

    /**
     * 会员中心／详细设定
     * 会员中心／奖金详情
     * 获取筛选类型
     */
    public function get_game_opt()
    {
        $this->load->model('user/Detailed_set_model',
            'core');
        $opts = $this->core->get_opts();
        $opts['rows'] = array_filter($opts['rows'],function ($v){
            if ($v['id']>1000) {
                return false;
            }
            return true;
        });
        $opts['rows'] = array_values($opts['rows']);
        $this->return_json(OK, $opts);
    }
    /**********************END中奖榜********************/
    /**
     * 获取显示位置
     * @param $from_way
     * @return int
     */
    private function getShowLocation($from_way)
    {
        $show_location = 0;
        if ($from_way == 1) {
            $show_location = 2;
        } else if ($from_way == 2) {
            $show_location = 1;
        } else if ($from_way == 3) {
            $show_location = 4;
        } else if ($from_way == 4) {
            $show_location = 3;
        }
        return $show_location;
    }

    /*
     * 返回一天的开奖计划 距离当天0点时间戳的秒数
     */
    public function games_plan()
    {
        $this->load->model('Open_time_model', 'open_time');
        $games_plan = $this->open_time->get_set_games_plan();
        $this->return_json(OK,$games_plan);
    }

    public function kithe_plan()
    {
        $this->load->model('Open_time_model', 'open_time');
        $gid = $this->G('gid');
        $games_plan = $this->open_time->get_games_kithe_plan($gid);
        $this->return_json(OK,$games_plan);
    }

    /*
     * 返回当前距离0点的时间戳
     */
    public function now_stamp()
    {
        $now = time();
        $now = $now - strtotime(date('Y-m-d',$now));
        $now = (string)$now;
        $this->return_json(OK,['s'=>$now]);
    }

    /**
     * 通过域名获取邀请码
     */
    public function get_invite_code_by_domain()
    {
        $domain = $this->G('domain') ? $this->G('domain') : '';
        $rs = $this->Home_model->getInviteCodeByDomain($domain);
        $this->return_json(OK, $rs);
    }
}
