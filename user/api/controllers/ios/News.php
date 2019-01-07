<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 咨询管理
 *
 * @file        user/api/controllers/ios/News
 * @package     user/api/controllers/ios
 * @author      ssm
 * @version     v1.7 2017/07/14
 * @created 	2017/07/12
 */
class News extends GC_Controller
{

    /**
     * 查询列表
     *
     * @access public
     * @param $page 	页数（默认1
     * @param $pid 		pid（默认41
     * @param $appkey 	appkey（默认1252
     * @return json
     */
    public function query($page=0, $pid=41, $appkey=1252)
    {
        // $url = 'http://lscy4.caeac.com.cn/api/news.php';
        // $post = ['appkey'=>$appkey,'pid'=>$pid,'page'=>$page];
        // $data = $this->_curl($url, $post);
        // $data = json_decode($data, true);
        // $this->return_json(OK, ['rows'=>$data]);
        $data = '{
"code":200,
"data":{
"rows":[
    {"title":"中国的租车行业前景怎么样呢?","date":"","newsID":"0"},
    {"title":"租车的时候，发生事故责任谁来承担?","date":"","newsID":"1"},
    {"title":"租车需要考虑哪些因素?","date":"","newsID":"2"},
    {"title":"租车可以给你的生活带来哪些便利?","date":"","newsID":"3"},
    {"title":"租车时这些检查很有必要","date":"","newsID":"4"},
    {"title":"租车时必须要了解的事情","date":"","newsID":"5"}, 
    {"title":"享受租车带来的无限魅力","date":"","newsID":"6"},
    {"title":"在租车时如何选择车型呢","date":"","newsID":"7"},
    {"title":"如何在花费最少的情况下租到最适合自己的车呢","date":"","newsID":"8"},
    {"title":"租车平台在日渐火爆","date":"","newsID":"9"},
    {"title":"租车多元化成为不少用车一族的首选","date":"","newsID":"10"},
    {"title":"租车前应该做的准备","date":"","newsID":"11"},
        {"title":"租车将成为绿色出行的首选","date":"","newsID":"12"},
    {"title":"租车成为当下的热点所在","date":"","newsID":"13"},
    {"title":"租出日益火爆既方便又便捷","date":"","newsID":"14"},
    {"title":"出行租车我们要注意的","date":"","newsID":"15"},
    {"title":"租好车——五一出行畅快玩","date":"","newsID":"16"},
    {"title":"租车热已经不断在提高","date":"","newsID":"17"},
    {"title":"租车或将成当代人的首选","date":"","newsID":"18"},
    {"title":"租车时应看清楚你的保险","date":"","newsID":"19"}
]
}
}';
        echo $data;
    }

    /**
     * 查询详情
     *
     * @access public
     * @param $id 	id（默认1
     * @param $appkey appkey（默认1252
     * @return json
     */
    public function info($id=1, $appkey=1252)
    {
        // $url = 'http://lscy4.caeac.com.cn/api/news.php';
        // $post = ['appkey'=>$appkey,'id'=>$id];
        // $data = $this->_curl($url, $post);
        // $data = json_decode($data, true);
        // $this->return_json(OK, $data);
        $path = __DIR__.'/news.json';
        $data = file_get_contents($path);
        $data = json_decode($data,true);

        foreach ($data['data']['rows'] as $key => $value) {
            if($value['ID'] == $id) {
                $data = $value;
                break;
            }
        }
        $this->return_json(OK,$data);
    }

    /**
     * 获取数据
     *
     * @access public
     * @param String $url 抓取地址
     * @param Array $post 提交数据
     * @return json
     */
    private function _curl($url, $post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data=curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
