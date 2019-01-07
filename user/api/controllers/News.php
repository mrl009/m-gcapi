<?php
/**
 * @模块   新闻
 * @版本   Version 1.0.0
 * @日期   2017-06-19
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class News extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }


    /*
     * 获取今日头条的新闻，存入redis以及返回给前端
     */
    public function index()
    {
        $hash = 'news:'.date('Ymd');
        $news_json = $this->core->redisP_hgetall($hash);
        $nj = '';
        $news = array();
        if (empty($news_json)) {
            $url = 'http://www.toutiao.com/search_content/?offset=0&format=json&keyword=彩票&autoload=true&count=50&cur_tab=1';
            $news_all = file_get_contents($url);
            if (!empty($news_all)) {
                $news_ten = json_decode($news_all, true);
                $news = $news_ten['data'];
            } else {
                $this->return_json(E_DATA_EMPTY, '接口数据为空!');
            }
            foreach ($news as $key => $value) {
                $nj = json_encode($value, JSON_UNESCAPED_UNICODE);
                $is  = $this->zhua_detail($value);//抓取新闻对应的内容数据并插入redis
                 if ($is) {//新闻内容插入成功再插入对应的列表
                     $this->core->redisP_hset($hash, $key, $nj);
                 }
            }
            $this->index();
        } else {
            sort($news_json);
            $news = array_map(function ($v) {
                return json_decode($v, true);
            }, $news_json);
        }
        $this->return_json(OK, $news);
    }


    /*
     * 根据item_id获取新闻内容
     */
    public function get_detail()
    {
        $item_id = $this->G('item_id')?$this->G('item_id'):0;
        if (empty($item_id)) {
            $this->return_json(E_ARGS);
        }
        $data = $this->core->redisP_hget('news_detail:'.date('Ymd'), $item_id);
        if (!empty($data)) {
            $data = json_decode($data, true);
        }
        //echo $data['data']['content'];exit;
        $this->return_json(OK, $data);
    }

    /*
     * 递归抓取新闻对应的内容数据并插入redis
     */
    public function zhua_detail($value = '')
    {
        if (!empty($value['item_id'])) {
            $detail_url = 'http://m.toutiao.com/i'.$value['item_id'].'/info';
            @$detail = file_get_contents($detail_url);
            if (!empty($detail)) {
                if (isset($detail['data'])) {
                    if (empty($detail['data'])) {
                        return false;
                    }
                }
                $this->core->redisP_hset('news_detail:' . date('Ymd'), $value['item_id'], $detail);
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function get_diesj2()
    {
        $content = '{"code":200,
"data":{
"page":1,
	"list":
[
{"title":"洲際國家盃最高身價陣容，沒有山齊士？",
"content":"有世界盃預演賽之稱的洲際國家盃正在俄羅斯進行得如火如荼，率先殺入決賽的智利，有主將阿歷斯山齊士擺脫輕傷復出，更在國際賽場數及入球數字上有所突破，榮升智利國家隊史上第一。\n\n在球會中，尚未與阿仙奴續約的山齊士去季在英超達到入球及助攻「雙雙」，成為直接及間接製造入球最多的球員，是今夏最搶手的射手之一，傳聞曼城屬意以他取代阿古路，爭奪戰已經展開。",
"img":"https://s17.postimg.org/9fy7feocf/tm1b.jpg",
"url":"http://football.fanpiece.com/m/World-Soccer/c1287623.html"
},
{"title":"干地在英超的第一份成績表",
"content":"初到貴境既干地，就成功帶領早一季一沉不起既車路士重奪聯賽冠軍，可以話令球迷們喜出望外之餘同時亦都係超額完成。然而每一位名帥本身都係有優點亦有弊病，當然干地都唔會例外，一齊回顧一下意大利人喺英超既第一份成績表，睇下你又會比佢幾多分？",
"img":"http://e0.365dm.com/16/08/16-9/20/antonio-conte-conte-chelsea_3760008.jpg?20160813063619",
"url":"http://football.fanpiece.com/m/bluexlion/c1287575.html"
},
{"title":"曼城青訓球員巡禮——Daniel Grimshaw",
"content":"格連梳爾 (Daniel Grimshaw) 是英格蘭人，於1998年在曼徹斯特出生。他在五歲那年開始在曼城青年軍受訓，到現時是曼城 Elite Development Squad (精英發展隊) 的主力球員，司職門將。\n\n格連梳爾的兒時偶像是前曼城門將大衛占士 (David James) 及彼德舒米高 (Peter Schmeichel)，因此他也在球隊中擔任門將，並渴望自己將來也像他們一樣出色。",
"img":"http://imageshack.com/a/img922/322/24sCNr.jpg",
"url":"http://football.fanpiece.com/m/outsideofmancity/c1287559.html"
},
{"title":"[淺論隨筆]最合適之人已在陣中？",
"content":"今次唔係講雲加(Wenger)，因為雲加無以下問題，甚至過左火。不過確實，好多時，可能旁觀者清，或者足球圈內所須考慮既因素，甚至關係太多，往往發現一個現象就係，好多時球隊需要既人，有時真係係陣中已經有。\n\n其實事緣筆者同朋友吹水，個位朋友係曼聯迷，佢話曼聯據講會買馬迪(Matic)，話都唔知好無，我話其實防守性幾好，岩摩連奴(Mourinho)用，不過有時穩定d就更好，個位朋友就話：「咁當初做乜要賣舒拉達連(Schneiderlin)？佢咪防中，佢功架都ok架。」筆者無言以對，當然我本身對舒拉唔算好熟悉，而對當時離隊情況都唔太了解，可能接觸過後摩佬覺得唔夾，都猶未可知。但確實，反正當初收購佢既價錢都唔低，亦屬防守型球員，當初何不留用，反而而家又要買馬迪，論實用性，筆者覺得舒拉在馬迪之上，可能更有效解放普巴(Pogba)既進攻能力。",
"img":"http://i3.mirror.co.uk/incoming/article8281590.ece/ALTERNATES/s615/Morgan-Schneiderlin-main.jpg",
"url":"http://football.fanpiece.com/m/chelsea-sk/c1287542.html"
}]
}}';
        echo $content;
    }


    public function get_m()
    {
        $content = '{"code":200,"ntype":"1","desc":"init","data":""}';
        echo $content;
    }


    public function get_k()
    {
        $content = '<Resp code="0" desc="获取数据成功">
<normal showNum="100">
<lottery evid="SSQ" gid="01" pools="奖池:7亿5291万" day="1" lotteryName="双色球" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/ssq@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/ssq@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=01&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=01&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=01&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=01&pagetab=0&pageextend=hezhi" showSale="1" addAward="0" style="1"/>
<lottery evid="dlt" gid="50" pools="奖池:38亿5439万" lotteryName="大乐透" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/dlt@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/dlt@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=50&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=50&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=50&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=50&pagetab=0&pageextend=hezhi" showSale="1" addAward="0" style="1"/>
<lottery evid="jczq" gid="70" remainMatch="14场比赛在售" lotteryName="竞彩足球" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/jjz@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/jjz@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=70&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=70&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=70&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=70&pagetab=0&pageextend=hezhi" showSale="1" addAward="0" style="1"/>
<lottery evid="jclq" gid="71" lotteryName="竞彩篮球" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/jjlq@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/jjlq@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=71&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=71&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=71&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=71&pagetab=0&pageextend=hezhi" showSale="1" addAward="0" style="1"/>
<lottery evid="3d" gid="03" trycode="" lotteryName="福彩3D" desc="500万元派奖中" imgUrl="http://mobile.9188.com/uploads/allimg/170109/3d@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/3d@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=03&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=03&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=03&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=03&pagetab=0&pageextend=hezhi" showSale="1" style="1" addAward="1"/>
<lottery evid="p3" gid="53" lotteryName="排列三" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/p3@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/p3@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=53&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=53&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=53&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=53&pagetab=0&pageextend=hezhi" showSale="1" style="1" addAward="0"/>
<lottery evid="zqdc" gid="85" lotteryName="北京单场" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/dcjcz@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/dcjcz@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=85&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=85&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=85&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=85&pagetab=0&pageextend=hezhi" showSale="1" style="1" addAward="0"/>
<lottery evid="sfc" gid="80" lotteryName="胜负彩" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/sfc@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/sfc@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=80&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=80&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=80&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=80&pagetab=0&pageextend=hezhi" showSale="1" style="1" addAward="0"/>
<lottery evid="rx9" gid="81" lotteryName="任选九" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/rx9@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/rx9@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=81&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=81&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=81&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=81&pagetab=0&pageextend=hezhi" showSale="1" style="1" addAward="0"/>
<lottery evid="sfdg" gid="84" lotteryName="胜负过关" desc="" imgUrl="http://mobile.9188.com/uploads/allimg/170109/ssgg@3x.png" newimgUrl="http://mobile.9188.com/uploads/allimg/170109/ssgg@3x.png" adlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=84&pagetab=0&pageextend=hezhi" newadlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=84&pagetab=0&pageextend=hezhi" iOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=84&pagetab=0&pageextend=hezhi" newiOSlink="http://mobile.9188.com/app?pagetype=Lottery&pageid=84&pagetab=0&pageextend=hezhi" showSale="1" style="1" addAward="0"/>
</normal>
</Resp>';
        echo $content;
    }

    public function get_diesj()
    {
        $content = '{"code":200,
"data":{
"page":1,
	"list":
[{"title":"《迪路菲奧，「爆返去」巴塞隆拿？》",
"content":"有傳上季無法摘下西甲冠軍的巴塞隆拿，正考慮於今夏，進行一系列的收購補充實力，而巴黎聖日耳門中場華拉堤、利物浦球星古天奴以及廣州恆大核心保連奴，盛傳已獲得「地上最強」的覬覦。\n\n不過，在一眾的貴價球星以外，有一位價格相對低廉的實力戰將，現時亦與巴塞隆拿愈走愈近，此人就是愛華頓翼鋒迪路菲奧。",
"img":"https://www.thesun.co.uk/wp-content/uploads/2017/03/sport-preview-gerard-deulofeu-to-barcelona.jpg",
"url":"http://football.fanpiece.com/m/footballhawkeye/c1287424.html"
},
{"title":"《或於今夏離開意甲的七大球星》",
"content":"幾乎在每一個夏天，意甲賽場均是其他主流聯賽的主要挖角對象，像馬高斯阿朗素、羅拔圖蘇利安奴等實力戰將，去夏離開意大利球壇發展後，均持續上佳的發揮。\n\n今個夏天，或再有一批的猛將，從意甲被挖走，或可能包括以下7位。",
"img":"http://images.performgroup.com/di/library/GOAL/7d/bf/stevan-jovetic-sevilla-2017_hkpd3pc3acg118p54b8kedzf2.png?t=465072700&w=620&h=430",
"url":"http://football.fanpiece.com/m/footballhawkeye/c1287426.html"
},
{"title":"[鎚評英超] 組軍速度計 (上) - 中型班當然是中等車速",
"content":"現時離7月1日還有一星期，大家可能只知道當日是主權移交20週年的日子。然而對球壇而言，就算所有簽約已經落實，已簽約的球員都未能正式成為己方球員。因為國際足協的轉會窗，是由7月1日開始。\n\n相信大家都知道 Big 6 的轉會新聞，所以鎚仔小編想帶大家看看各支被漠視的英超中下游球隊，組軍情況如何。今日先講那些開行油，但組軍基本上只完成了一半的球隊。",
"img":"http://www.theevertonforum.co.uk/wp-content/uploads/2017/02/barkley-lukaku.jpg",
"url":"http://football.fanpiece.com/m/hammershome/c1287379.html"
},
{"title":"繼國家隊後，朗尼也將失去曼聯隊長臂章，普巴最有機會接任",
"content":"曼聯前鋒朗尼繼失去英格蘭國家隊隊長之位後，在球會也將要除下隊長臂章。\n\n朗尼由2014年至今一直擔任曼聯隊長之職，自從「七小福」相繼退役後，朗尼已成為現時在曼聯資歷最深的球員，而自泰利離開車路士後，他更成為了現役英超球員中效力同一球會年期最長的一人。",
"img":"http://a.espncdn.com/combiner/i/?img=/photo/2017/0418/r201011_1296x729_16-9.jpg&w=738&site=espnfc",
"url":"http://football.fanpiece.com/m/extra-time/c1287422.html"
},
{"title":"從旺角到魯營？",
"content":"效力廣州恆大的巴西中場保連奴，4月才隨隊作客旺角大球場出戰亞冠盃分組賽，更在香港的東方龍獅身上取得兩球。最近竟傳出巴塞隆拿向他提出斟介，倘若交易成功，筆者該慶幸曾現場目睹他的演出。但也不甘要問，巴塞為何要看上一名效力中超的球員？難道市面上沒有更好的選擇？",
"img":"http://img.bleacherreport.net/img/images/photos/003/670/391/hi-res-71509afb20fc965715a51f7c49258ed2_crop_north.jpg?h=533&w=800&q=70&crop_x=center&crop_y=top",
"url":"http://football.fanpiece.com/m/danfung/c1287406.html"
},
{"title":"【中甲初探筆記】第二章：杭州綠城的中華台北中場陳柏良",
"content":"上回提到我在四月的復活節假檔期到內地睇波，但一場火災令我無法觀看上海申花的賽事，唯有即時轉到杭州看杭州綠城的中甲比賽。\n\n整支杭州綠城中，恕我對中國球隊的了解不深，我對這支球隊的認識只有三個：第一是他們剛由中超降至中甲；第二是香港球員吳偉超效力過；第三是現時隊內有位曾在香港聯賽打滾的11號中華台北中場陳柏良，唯一一位我認識的球員。",
"img":"http://i67.tinypic.com/2iax6du.jpg",
"url":"http://football.fanpiece.com/m/twoandthree/c1287331.html"
},
{"title":"詳細分析：法明奴轉披9號球衣，背後意義是？",
"content":"「埃及美斯」沙拿的到來，不僅讓利物浦花費大約3400萬鎊左右的轉會費，更讓巴西國腳法明奴讓出11號球衣，看來紅軍對於新加盟的前者，相當器重。\n\n讓出11號球衣後，法明奴被安排穿上懸空一季的9號球衣，反映著高普的球隊，未有偏心沙拿，依然對於巴西人保持高度關注。除此之外，轉換球衣號碼，似乎更意味著紅軍將於來季，更加倚重法明奴摧城拔寨。",
"img":"http://1tvs492zptzq380hni2k8x8p.wpengine.netdna-cdn.com/wp-content/uploads/2017/02/Roberto-Firmino.jpg",
"url":"http://football.fanpiece.com/m/liverpoolforeverred/c1287395.html"
},
{"title":"摩連奴可補「險簽」遺憾，拜仁大減價「益」曼聯",
"content":"德甲班霸拜仁慕尼黑願意把曼聯主帥摩連奴心儀已久的收購對象割價出讓，由3,050萬鎊減價至2,300萬鎊，七五折夏日大傾銷，售完即止，欲購從速。\n\n德國足球雜誌《踢球者》在本周報道， 拜仁主帥安察洛堤對隊中的葡萄牙中場連拿度山捷士 (Renato Sanches) 不是太滿意，準備把他賣給曼聯，而且不惜蝕本清貨，由去年夏季買入價(未計附加條款8,000萬歐元) 的3,500萬歐元(約合3,050萬鎊)，降價至2,600萬歐元(約合2,300萬鎊)。",
"img":"https://img.bleacherreport.net/img/article/media_slots/photos/002/692/887/bef6cf2de54696db1a5f9b4d388bc76c_crop_exact.jpg?h=533&w=800&q=70&crop_x=center&crop_y=top",
"url":"http://football.fanpiece.com/m/extra-time/c1287394.html"
},
{"title":"【支出與收入】利物浦轉會市場三大收入來源",
"content":"利物浦呢排洗左咁多錢去買人，諗下點樣搵返啲收入。咁據BBC Sports嘅Phil McNulty所講，沙高，馬高域同埋摩蘭奴呢三位將會係利物浦係夏季轉會市場嘅主要收入來源。\n\n高普係時候諗諗下季個陣點排啦，諗完就要試陣練兵嘛！利物浦仲要8月仲要踢歐聯附加賽架！",
"img":"https://s7.postimg.org/5gxi22kez/2324.jpg",
"url":"http://football.fanpiece.com/m/yntoxica/c1287389.html"
},
{"title":"也許 年青球員對車路士黎講只是一盤生意",
"content":"夏季轉會窗即將展開，由於轉換贊助窗關係，相信新收購最快要英國時間7月1號才可對外公怖。而近日不少英超冠軍新貴成員紛紛成為其它球會目標，當中更不乏年青球員，車路士究竟如何為哩啲球員定位？今日將會同大家探討一下。\n\n相信大家都唔會唔知道對上一個出自車路士青訓體系而成功上到一隊既球員，就只有隊長泰利，而佢亦都喺岩岩哩一季離開球隊尋求正選機會。喺現今球壇下，各球會要培訓自家青訓球員成功晉身一隊上陣，並唔係一件容易既事，但係睇落只係在乎你有冇心去搞。可以睇下就算星光熠熠既皇馬都有個自家青訓莫拉達，馬體會亦有同屬西班牙籍既沙奧尼古斯、高基，就算曼聯比雲高爾錯有錯著都有個打得起既拉舒福特。",
"img":"http://www.downvids.net/video/bestimages/img-chalobah-aina-loftus-cheek-and-solanke-for-friday-night-live-175.jpg",
"url":"http://football.fanpiece.com/m/bluexlion/c1287380.html"
},
{"title":"《巴迪斯達：貪字得個貧的人辦》",
"content":"近年，數之不盡的前巴西國腳曾回流國內發展，比如法比安奴、柏圖和卡卡，當中亦不乏一些被遺忘的名字，曾效力皇家馬德里和阿仙奴等豪門的巴迪斯達（Julio Baptista），就是其中一人。\n\n西維爾除了是西甲的新星兵工廠外，在2000年代初期也以球探網絡見稱，正如今日的波圖與賓菲加一樣，擅長在南美發掘具潛質的年青球員。",
"img":"http://talksport.com/sites/default/files/field/image/201407/72956218.jpg",
"url":"http://football.fanpiece.com/m/forgotthestar/c1287341.html"
},
{"title":"《29+1，結局各不同。》",
"content":"《29+1，結局各不同。》\n三十而立，作為一名男人，往往在三十歲的年齡，正值事業高峰期，或多或少都會定斷到人生以後的道路。對於不平凡的球員路，三十歲可是一個尷尬的分水嶺，體能走下坡，保持狀態需要更多的鍛鍊，球壇上縱然有大器晚成的例子，但三十歲後狀態漸下滑的例子更加之多。今集無名英雄細數多位1987年出世的球星，他們今年已踏入三十歲的人生大關，他們的結局向有不同，各有各精彩。",
"img":"https://media-public.fcbarcelona.com/20157/0/document_thumbnail/20197/8/89/184/45635848/1.0-2/45635848.jpg?t=1493036744000",
"url":"http://football.fanpiece.com/m/UnHero/c1287307.html"
},
{"title":"《當拿隆馬以外，五位值得期待的意大利「95後」小將》",
"content":"意大利是一個足球事業發達的國家，全國有近500萬的足球人口，幾乎在每12個意大利人中，只有1位從事與足球相關的工作，所以該國所生產的球員，亦有一定的質量保證。\n\n近年來，意大利國家隊遭受的重點批評，離不開戰力老化，但在比洛迪、貝拿迪斯治和當拿隆馬等後起之秀，紛紛獲得國家隊主帥雲杜拉關注後，成功讓藍衣軍團的平均年齡大降。",
"img":"http://talksport.com/sites/default/files/styles/just_scale/public/field/image/201704/pellegrini.jpg?itok=jHJcwiXM",
"url":"http://football.fanpiece.com/m/footballhawkeye/c1287347.html"
},
{"title":"大清洗？十位準備告別晏菲路的「紅軍」",
"content":"除了收購新援之外，不少的利物浦球迷均對愛隊的陣容縮減，表示相當關心，而作為紅軍的喉舌，英國《利物浦迴聲報》便預料，或有10位的球員，將於今夏告別晏菲路球場，其中包括10年老臣盧卡斯。",
"img":"https://www.theanfieldwrap.com/uploads/2017/01/P170118-046-Plymouth_Liverpool.jpg",
"url":"http://football.fanpiece.com/m/liverpoolforeverred/c1287336.html"
},
{"title":"【轉會傳聞】基昂講麥巴比（Kylian Mbappe）是安歷卡與亨利混合體",
"content":"阿仙奴傳奇基昂，日前談到今個夏天阿仙奴在轉會市場目標麥巴比（Kylian Mbappe）。他認為麥巴比是安歷卡和亨利的混合體。\n\n在今季，麥巴比在摩納哥表現可用強勁來形容，一共取得15球，8個助攻，18歲年輕球員有如此高質素表現非常難得。不過，要得到麥巴比需要支付高昂的轉會費，現時出價高達1億歐元，阿仙奴購買到麥巴比亦與其他豪門競爭，如「銀河艦隊」皇家馬德里。",
"img":"http://i.imgur.com/Hsaxfpu.jpg",
"url":"http://football.fanpiece.com/m/arsenaldaily/c1287330.html"
},
{"title":"(官宣)終於醒返次？泰奧利880萬英鎊轉投里昂但車路士有回購條款",
"content":"筆者昨天才跟大家討論過車路士最新的收購目標，今天英國媒體天空體育也肯定了車路士會收購巴卡約高及辛迪路的消息，而另外一宗已經肯定的轉會就是車路士上季外偺到阿積士的小將泰奧利轉投法甲球隊里昂，而里昂也在官方TWITTER上證實了這個消息。相信這宗轉會對於車路士球迷並不會感到意外，此子應該在車路士看不見任何的將來才決定離開藍軍的。",
"img":"http://images1.minutemediacdn.com/production/912x516/595130000f1c400bee000001.jpg",
"url":"http://football.fanpiece.com/m/Blueischelsea/c1287357.html"
},
{"title":"里昂主席：已告知阿仙奴，馬體會願出6,500萬購拿卡錫迪",
"content":"關於阿仙奴有意以基奧特加錢向里昂換購拿卡錫迪的消息，里昂主席首次承認有就基奧特而與阿仙奴接觸，並大爆基奧特心中一個秘密願望。\n\n里昂主席奧拉斯在接受法國《隊報》訪問時透露，里昂對阿仙奴前鋒基奧特確有興趣，並且親自探聽過對方的口風。\n\n「他是我們喜歡的小夥子，有很多入球，對俄羅斯世界盃充滿雄心壯志。他親口對我說，想再等一下，與阿仙奴的事還未全部完成，他有個秘密願望，就是要在來季成為阿仙奴的首席前鋒。」",
"img":"https://s21.postimg.org/ub98e0kdz/alexandre-lacazette-.jpg",
"url":"http://football.fanpiece.com/m/extra-time/c1287316.html"
},
{"title":"再有日本球員加盟——法蘭克福的五大日援",
"content":"德甲法蘭克福周日官方宣佈，即將簽下日職球會鳥棲砂岩的中場球員鐮田大地。\n\n法蘭克福於官網透露，鐮田大地已完成了他在鳥棲砂岩的最後一場比賽，現正準備進行體測，預計在數天內完成簽約手續。\n\n法蘭克福董事局成員波碧表示：「我們與對方球會和鐮田大地都已達成了協議，相信在未來數日內可公佈轉會詳情。」\n\n現年20歲的鐮田大地司職攻擊中場，是又一名由日本高校系統投身日職球會的「足球小將」。他今季為鳥棲砂岩上陣16場，取得五個入球。",
"img":"https://s22.postimg.org/ub1p60d5t/Japan-v-_Sporting-.jpg",
"url":"http://football.fanpiece.com/m/extra-time/c1287304.html"
},
{"title":"哭泣的戰神 - 巴迪斯圖達",
"content":"能夠被譽為「戰神」者, 古今並無幾人。廿年前, 「紫百合」費倫天拿有個長髮披面, 身型碩大強壯, 踢法剛勁硬朗的阿根廷前鋒。他在中前場拿得皮球, 一直往前推進, 殺到二十多三十碼, 力大無窮的右腳, 拉弓抽射。球如箭, 例不虛發。當門將回神過來, 皮球早已轟入網窩。Winning Eleven 內的「巴迪高」, Shoot Power 9 並沒有誇大, 而是貨真價實的呈現。入球後, 兩手扮成機關槍, 「呯呯嘭嘭」, 一輪掃射, 對手倒地不起。",
"img":"http://cdn.caughtoffside.com/wp-content/uploads/2014/08/Gabriel-Batistuta-Fiorentina.jpg",
"url":"http://football.fanpiece.com/m/lifewinnergroup/c1287274.html"
},
{"title":"《繼續一年多星？與皇馬有關的四大轉會傳言》",
"content":"在拿下西甲和歐聯冠軍後，關於皇家馬德里的轉會傳言，大多數與賣人有關，涉及的球員包括莫拉達、占士洛迪古斯、巴爾和C.朗拿度，但事實上，施丹的球隊目前正醞釀數宗的重要收購，亦相當值得留意。（註：排名不分先後）\n\n1：求購「新亨利」麥巴比\n在摩納哥新星麥巴比人氣急升後，不少的媒體均表示，皇家馬德里正考慮於今夏，出手簽下這位「新亨利」，而相關的報價更可達高達1億歐元。",
"img":"http://cdn.images.express.co.uk/img/dynamic/67/590x/Kylian-Mbappe-780376.jpg",
"url":"http://football.fanpiece.com/m/footballhawkeye/c1287244.html"
}]
}}';
        echo $content;
    }
}
