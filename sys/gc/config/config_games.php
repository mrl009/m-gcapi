<?php
/**
 * @file config_games.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package gcapi
 * @author Langr <hua@langr.org> 2017/03/16 19:30
 * 
 * $Id$
 */

define('CONFIG_GAMES', true);
date_default_timezone_set("Asia/Shanghai");

const UPLOAD_URL = 'https://www.qzgao.com/uploads.php';   //圖片上傳服務器URL这个不能改
const REMEN_URL = 'https://www.qzgao.com/uploads/rm.png';       //熱門圖標URL
/* (产品表)球号 code 代码界限 */
//const G_MAX_CODE    = 800;
const G_MAX_RATE    = 888;      /* [最高]赔率 */
//const G_MIN_RATE    = 880;      /* 最低赔率 */
//const G_MIN_REBATE  = 881;      /* 低赔率返利 */
const G_PRO_MENU    = 999;      /* 产品菜单 */
const G_NO_PID      = 0;        /* 无父级id */

/* 现金流水类型 */
const BALANCE_PAY   = 7;        /* 充值+ */
const BALANCE_ORDER = 1;        /* 下注- */
const BALANCE_WIN   = 2;        /* 中奖+ */
const BALANCE_RETURN = 3;       /* 返水+ */
const BALANCE_HE    = 4;        /* 和局+ */
const BALANCE_CANCEL = 4;       /* 注单取消+ */
const BALANCE_PLUS  = 12;       /* 通用手动加操作+ */
const BALANCE_SUB   = 13;       /* 通用手动减操作- */

/* 玩家 status 状态 */
const USER_OK       = 1;        /* 正常账号 */
const USER_STOP     = 2;        /* 账号停用 */
const USER_LOCK     = 3;        /* 锁定账号 */
const USER_TEST     = 4;        /* 试玩账号 */

/* 游戏 status 状态 */
const STATUS_OK     = 0;
const STATUS_OFF    = 1;
const STATUS_DEL    = 2;
const STATUS_LOCK   = 1;
const IS_HOT        = 1;
/* 开奖 status 状态 */
const STATUS_OPENING = 1;       /* 未开奖 */
const STATUS_OPEN   = 2;        /* 已开奖 */
const STATUS_END    = 3;        /* 已结算 */
const STATUS_NOTRESULT = 4;     /* 没有结果 */
const STATUS_ENDING = 5;        /* 结算中 */

/* 注单开奖结果状态 */
const STATUS_WIN    = 1;        /* 中奖 */
const STATUS_HE     = 2;        /* 和局 */
const STATUS_CANCEL = 3;        /* 订单取消 */
const STATUS_CHASE  = 4;        /* 追号订单 */
/* 选项 */
const STATUS_All    = 0;        /* 全部订单 */
const STATUS_NOTOPEN= 4;        /* 未开奖 */
const STATUS_LOSE   = 5;        /* 未中奖 */
const STATUS_CANCELING = 6;     /* 已取消 */

/* 接口返回状态码 */
const OK            = 200;      // 请求成功！
const OK_OLINE      = 201;      // 支付宝转账成功标识！
const OK_RED        = 202;      // 红包已抢完
const OK_OLINE_MAX  = 205;      // 支付方式限额上限！
const E_OK          = 0;        // 请求成功！
const E_TOKEN       = 401;      // 校验错误, 未受权调用!
const E_POWER       = 402;      // 校验错误, 越权调用!
const E_DENY        = 403;      // 拒绝访问!
const E_API_NO_EXIST = 404;     // 接口不存在!
const E_METHOD      = 405;      // 请求方法不支持!
const E_DATA_INVALID = 420;     // 无效数据!
const E_DATA_EMPTY  = 421;      // 无数据!
const E_ARGS        = 422;      // 参数错误!
const E_OP_FAIL     = 423;      // 操作失败!
const E_YZM_CHECK   = 425;      // 需要验证码
const E_NOOP        = 429;      // 无操作!
const E_DATA_REPEAT = 304;      // 数据重复提交!
const E_SYS         = 503;      // 系统错误, 请联系管理员!
const E_SYS_1       = 504;      // 系统错误1!
const E_UNKNOW      = 999;      // 未知错误!
const E_YEBZ      = 888;      // 余额不足

const TOKEN_TIME_OUT = 600;     // TOKEN超时
const TOKEN_BE_OUTED = 601;     // token被踢出，也就是用户被踢出
const BLACK_IP = 602;           // IP被列入黑名单
const ADMIN_ACCESS_IP = 603;    // IP无法访问管理员后台
const LOGOUT = 604;             // 退出

/* 选择redis数据库 */
const REDIS_PUBLIC = 8;         // redis 公库
const REDIS_DB = 5;             // 默认私库
const REDIS_LONG = 15;          // 持久库/下注库
const EXPIRE_1 = 3600;          // 过期时间 1 小时
const EXPIRE_24 = 86400;        // 过期时间 1 天
const EXPIRE_48 = 172800;       // 过期时间 2 天

/* 用户信息长度限制 */
const USER_USERNAME_MIN_LENGTH = 4;     // 用户最小长度
const USER_USERNAME_MAX_LENGTH = 14;    // 用户最大长度
const USER_PWD_MIN_LENGTH = 6;          // 用户密码最小长度
const USER_PWD_MAX_LENGTH = 18;         // 用户密码最大长度
const USER_PWD_ERROR_AND_LOCK = 20;      // 用户输入错多少次，锁定用户
const EXTENSION = 'intr';               // 代理推广url传递参数keys

/* token的后台，前台区分 */
const TOKEN_PRIVATE_KEY_TIME = 3600;    // 获取token的键值的生存时间
const TOKEN_PRIVATE_KEY_CHECK_MIN_TIME = 3; // 获取token的键值的生存时间
const TOKEN_CODE_AUTH = 'AuthGC';       // 后台
const TOKEN_CODE_ADMIN = 'Admin';       // 后台
const TOKEN_CODE_USER = 'User';         // 会员
const TOKEN_CODE_AGENT = 'Agent';       // 代理
const TOKEN_ADMIN_LIVE_TIME = 24;       // 管理员超时时间，单位：小时
const TOKEN_USER_LIVE_TIME  =  1;       // 会员toekn超时时间，单位：小时
const TOKEN_USER_OFF_LINE   = 15;       // 会员自动离线时间，单位：分钟
const USER_BANK_PWD_ERROR   = 6;        // 取款密码错误次数
/* 密钥 */
const TOKEN_PRIVATE_ADMIN_KEY = 123456; // 管理员token密钥
const TOKEN_PRIVATE_USER_KEY = 234567;  // 会员token密钥

/* IP */
const BLACK_IP_LIVE = 86400;            // 黑名单IP生存时间
const CODE_IP_TIMES = 3  ;              // 连续输错多少次密码加验证码
const BLACK_IP_TIMES = 20;              // 连续输错多少次密码IP列为黑色单

/* 验证码 */
const VERIDY_CODE_LENGTH = 4;           // 验证码的长度
const VERIDY_CODE_RANGE = '0123456789'; // 验证码的长度
//const VERIDY_CODE_RANGE = '0123456789';// 验证码的范围
const VERIDY_CODE_LIVE_TIME = 600;      // 验证码生存时间

/* 确认状态 */
const OUT_NO = 1;       // 未确认
const OUT_DO = 2;       // 确认出款
const OUT_CANCEL = 3;   // 拒绝出款
const OUT_PREPARE = 4;  // 预备出款
const OUT_REFUSE = 5;   // 取消出款

/* 代付状态 */
const APAY_UNSURE = 1;   //代付中
const APAY_OK = 2;       //代付成功
const APAY_FAIL = 3;     //代付失败
const APAY_LOCK = 4;     //自动代付锁定

const MQ_USER = 'user'; // 会员抢登标记
const MQ_COMPANY_RECHARGE = 'admin_in'; // 公司入款标记
const MQ_COMPANY_IN     = 'user_in';    // 会员提交公司入款标记

const MQ_ONLINE_IN      = 'user_online';    // 会员提交线上入款标记
const MQ_ONLINE_RECHARGE  = 'admin_online'; // 线上入款标记

const MQ_USER_OUT  = 'user_out';    // 出款标记
const MQ_PAY_YB  = 'admin_out';     // 预备出款
const MQ_PAY_OK  = 'admin_out';     // 确认出款
const MQ_PAY_QX  = 'admin_out';     // 取消出款
const MQ_PAY_JJ  = 'admin_out';     // 拒绝出款
/*const MQ_COMPANY_RECHARGE = 'company';  // 公司入款标记
const MQ_COMPANY_IN     = 'company_in'; // 会员提交公司入款标记

const MQ_ONLINE_IN      = 'online_in';  // 会员提交线上入款标记
const MQ_ONLINE_RECHARGE  = 'online';   // 线上入款标记

const MQ_USER_OUT  = 'user_out';// 出款标记
const MQ_PAY_USER  = 'pay';     // 出款标记标记
const MQ_PAY_YB  = 'yb_pay';    // 预备出款
const MQ_PAY_OK  = 'ok_pay';    // 确认出款
const MQ_PAY_QX  = 'qx_pay';    // 取消出款
const MQ_PAY_JJ  = 'jj_pay';    // 拒绝出款*/

/* 会员中心／充值记录 选项集 */
const INCOME_OPT_ALL = 0;       // 全部
const INCOME_OPT_COMPANY = 1;   // 转账汇款
const INCOME_OPT_ONLINE = 2;    // 在线充值
const INCOME_OPT_CARD = 3;      // 彩豆充值
const INCOME_OPT_PEOPLE = 4;      // 人工存入

/* 来源 */
const FROM_IOS = 1;     // IOS
const FROM_ANDROID = 2; // 安卓
const FROM_PC = 3;      // PC
const FROM_WAP = 4;     // 手机浏览器
const FROM_UNKNOW = 5;  // 未知
const FROM_H5APP = 6;   // h5-app(ios h5 打包app)

const AUTO_INSERT_NUM = false;      // 是否开启自动插入开奖号码的功能

const APP_IS_CHENK       = 0;       // app 是否开启审核 0:关闭 1 :开启
const CASH_REQUEST_TIME  = 2;       // 入款提交时间间隔  s
const IN_COMPANY_COUNT  = 3;        // 公司入款次数
const CASH_AUTO_EXPIRATION = 180;   // 出入款自动过期时间   单位: 分钟
const IP_EXPIRE = 2592000;          // ip过期时间

const SHOW_BET_WIN_ROWS = 49;		// 显示中奖数据行数
const ADMIN_QUERY_TIME_SPAN = 5359280;	// 后端查询数据时间跨度限制62*86440
const ADMIN_ORDER_QUERY = 31*86440;	// 后端订单查询数据时间跨度限制
const OUT_BOUNODS_TIME = 86400;	    // 出款手续费扣除手续费时间 24小时
//const OUT_BOUNODS_TIME = 180;	    // 出款手续费扣除手续费时间 24小时测试环境

/* 后端API-红包中心 */
const A_RED_UPD_TIME_LIMIT = 5;	    // 红包修改时间限制，分钟
const A_RED_ADD_MAX_TIME_LIMIT = 1440;	// 红包添加时间限制最大，分钟
const A_RED_ADD_MIN_TIME_LIMIT = 30;	// 红包添加时间限制最小，分钟
/* 手机充值中心logo地址 */
const WX_IMG_PNG    = "https://www.qzgao.com/gc_bank_online/微信@3x.png";
const WX_GR_PNG     = "https://www.qzgao.com/gc_bank_online/微信扫码支付@3x.png";
const WX_WAP_PNG    = "https://www.qzgao.com/gc_bank_online/微信app@3x.png";
const ZFB_IMG_PNG   = "https://www.qzgao.com/gc_bank_online/支付宝扫码@3x.png";
const ZFB_GR_PNG    = "https://www.qzgao.com/gc_bank_online/支付宝@3x.png";
const ZFB_WAP_PNG   = "https://www.qzgao.com/gc_bank_online/支付宝app@3x.png";
const JD_IMG_PNG    = "https://www.qzgao.com/gc_bank_online/京东钱包@3x.png";
const JD_WAP_PNG    = "https://www.qzgao.com/gc_bank_online/京东钱包@3x.png";
const JD_GR_PNG     = "https://www.qzgao.com/gc_bank_online/京东钱包@3x.png";
const QQ_IMG_PNG    = "https://www.qzgao.com/gc_bank_online/qq钱包@3x.png";
const QQ_WAP_PNG    = "https://www.qzgao.com/gc_bank_online/qq钱包@3x.png";
const QQ_GR_PNG     = "https://www.qzgao.com/gc_bank_online/qq钱包@3x.png";
const BD_IMG_PNG    = "https://www.qzgao.com/gc_bank_online/百度钱包@3x.png";
const BD_WAP_PNG    = "https://www.qzgao.com/gc_bank_online/百度钱包@3x.png";
const BD_GR_PNG     = "https://www.qzgao.com/gc_bank_online/百度钱包@3x.png";
const CFT_IMG_PNG   = "https://www.qzgao.com/uploads//8ddaf15924ff847e7502c5d0ce2ce96e.png";
const CFT_WAP_PNG   = "https://www.qzgao.com/uploads//8ddaf15924ff847e7502c5d0ce2ce96e.png";
const CFT_GR_PNG    = "https://www.qzgao.com/uploads//8ddaf15924ff847e7502c5d0ce2ce96e.png";
const YL_IMG_PNG    = "https://www.qzgao.com/uploads/54daa37eb476cc82fcbc003ce3ad3d6a.png";     // 银联支付
const YL_WAP_PNG    = "https://www.qzgao.com/uploads/54daa37eb476cc82fcbc003ce3ad3d6a.png";     // 银联支付
const YL_GR_PNG     = "https://www.qzgao.com/uploads/54daa37eb476cc82fcbc003ce3ad3d6a.png";     // 银联支付
const KJ_IMG_PNG    = "https://www.qzgao.com/uploads//d8bc33c423aca3399c3939ee06c75364.png";    // 快捷支付
const SY_IMG_PNG    = "https://www.qzgao.com/uploads//25a6fb2cba3aab03acd9e8b7ffe594d9.png";    // 收银台
const SN_IMG_PNG    = "https://www.qzgao.com/uploads/1/587a2379f9224f34ed1c1736284526d7.png";   // 苏宁
const SN_WAP_PNG    = "https://www.qzgao.com/uploads/1/05aa2adfb0641cdfa1c001845fe3f20b.png";   // 苏宁WAP
//
const WIN_IMG_PNG  = "https://www.qzgao.com/uploads/1/876456ccfe08f205887608f55e8a42e6.png";    // 中奖排行榜默认头像
const USER_IMG_DOMAIN   = "https://www.qzgao.com";       // 会员头像域名
const USER_IMG    = "https://www.qzgao.com/uploads/46cef81b4d9320f21734c5169c969e73.png";       // 会员默认头像
/* 优惠活动等级晋级图片地址 */
const GRADE_IMG = "https://www.qzgao.com/uploads//7ed812a3ab3702f98a381ca0b9f0a65a.jpg?20171114";

/* 私彩国彩gid配置 */
const GC = '10,29,27,26,11,30,6,9,7,8,12,13,15,16,17,33,34,35,36,37,18,19,20,21,22,2,1,3,4';           // 国彩初始化排序
const SC = '60,61,77,3,4,56,76,24,25,59,73,74,57,58,62,63,65,66,67,82,83,84,85,86,87,88,68,69,70,71,72,51,52';  // 私彩初始化排序
const SX = '1001,1002,1003,1004,1005,1006';
const ZKC = '4,10,11,60,61,24,27,77,29,30,82,88';     // 自开彩
/* 无线代理返水分类，对应游戏id置空，则对应游戏不返水 */
const AGENT_GAMES = [1=>'fc3d',2=>'pl3',3=>'lhc',4=>'lhc',6=>'ssc',7=>'ssc',8=>'ssc',9=>'ssc',10=>'ssc',11=>'ssc',12=>'k3',13=>'k3',15=>'k3',16=>'k3',17=>'k3',33=>'k3',34=>'k3',35=>'k3',36=>'k3',37=>'k3',18=>'11x5',19=>'11x5',20=>'11x5',21=>'11x5',22=>'11x5',24=>'',25=>'',26=>'pk10',27=>'pk10',29=>'pk10',30=>'pk10',31=>'pk10',51=>'fc3d',52=>'pl3',56=>'ssc',57=>'ssc',58=>'ssc',59=>'ssc',60=>'ssc',61=>'ssc',62=>'k3',63=>'k3',65=>'k3',66=>'k3',67=>'k3',68=>'11x5',69=>'11x5',70=>'11x5',71=>'11x5',72=>'11x5',73=>'',74=>'',76=>'pk10',77=>'pk10',78=>'pk10',81=>'pk10',82=>'k3',83=>'k3',84=>'k3',85=>'k3',86=>'k3',87=>'k3',88=>'k3'];

const TMP_TO_GID = [
    'k3'     =>    [12,13,15,16,17,33,34,35,36,37],
    's_k3'   =>    [62,63,65,66,67,82,83,84,85,86,87,88],
    's_ssc'  =>    [56,57,58,59,60,61],
    'ssc'    =>    [6,7,8,9,10,11],
    's_yb'   =>    [51,52],
    'yb'     =>    [1,2],
    's_11x5' =>    [68,69,70,71,72],
    '11x5'   =>    [18,19,20,21,22],
    's_kl10' =>    [73,74],
    's_pk10' =>    [76,77,81],
    'pk10'   =>    [26,27,29,30,31],
    'lhc'    =>    [3,4],
    'pcdd'   =>    [24,25],
];

const CTG_TO_TMP = [
    'gc' => ['k3','ssc','yb','11x5','pk10','lhc'],
    'sc' => ['s_k3','s_ssc','s_yb','s_11x5','s_pk10','lhc','pcdd','s_kl10'],
    'sx' => ['ag','dg','lebo','pt']
];

const TMP_TO_ZWNAME = [
    'k3'=>'快3','ssc'=>'时时彩','yb'=>'低频彩','11x5'=>'11选5','pk10'=>'pk拾','lhc'=>'六合彩', 's_k3'=>'快3','s_ssc'=>'时时彩','s_yb'=>'低频彩','s_11x5'=>'11选5','s_pk10'=>'pk拾','s_kl10'=>'快乐拾','pcdd'=>'pc蛋蛋','sx'=>'视讯'
];

/* 视讯电子图片 */
const AG_IMG_PNG  = "https://www.qzgao.com/dz/ag/";     // ag电子图片路径
const MG_IMG_PNG  = "https://www.qzgao.com/dz/mg/";     // mg电子图片路径
const PT_IMG_PNG  = "https://www.qzgao.com/dz/pt/";     // pt电子图片路径
const BBIN_IMG_PNG  = "https://www.qzgao.com/dz/bbin/"; // BBIN电子图片路径

/* 视讯配置start;由baseapi迁移而来除了INTERFACE_URL其余名称不变 */
/**
 * ag配置项
 * @var string
 */
const AG_DEFAULT_CAGENT = 'M40_AGIN';
const AG_DEFAULT_MD5_KEY = '5kbNeSwf3jud';
const AG_DEFAULT_DES_KEY = 'E7LA8lXh';
const AG_DEFAULT_URL = 'http://gi.apikk.org:81';
const AG_GCI_URL = 'http://gci.apikk.org:81';

/**
 * dg配置项
 * @var string
 */
const DG_API_KEY = '1a04024cc66b42e8af80847062ecc681';
/**
 * pt配置项
 */
const PT_API_URL = 'http://ws-keryx2.imapi.net';
const MERCHANTNAME = '818gamingprod';
const MERCHANTCODE = 'MCSRaQ1uYdx07kTdPNHrRwyEYlKu8Fky';
const LEBO_KEY = '8029e4ea973f7931567959e2350ba1648fe74a4b';
/* INTERFACE_URL即原视讯baseapi当中的protected static $interface_url */
const INTERFACE_URL = [
    'dg'    => 'http://api.dg99api.com/',
    'lebo'  => 'https://lgtestapi.lgapi.co',
    'mg3'   => 'https://entservices204.totalegame.net?wsdl',
    'mg5'   => 'https://tegapi204.totalegame.net'
];

/* 开元棋牌配置 */
const KYQP_API_URL = "https://kyapi.ky206.com:189/channelHandle";         // 接口地址
const KYQP_ORDER_URL = "https://kyrecord.ky206.com:190/getRecordHandle";  // 拉订单地址
const KYQP_AGENT = 30073;
const KYQP_DESKEY = "0e6bfdd8d54045a8";
const KYQP_MD5KEY = "3d53c8b1f5194e0f";
/* 博友无限视讯站点标志 */
const WX_DSN = ['a18'];
/* 视讯配置end */

// 独立支付域名
const PYA_URL = 'https://www.yczfjj.com';

