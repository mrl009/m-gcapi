<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>全网通营销管理后台-移动版</title>

    <link rel="stylesheet" type="text/css" href="../../../static/js/jquery.mobile-1.4.5.min.css">
    <script type="text/javascript" src="../../../static/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.mobile-1.4.5.min.js"></script>
    <script type="text/javascript" src="../../../static/js/game_trend.js"></script>

</head>
<body>


<canvas id="myCanvas" width="500px" height="5000px"></canvas>
<div data-role="tabs" id="tabs">
    <div data-role="navbar">
        <div data-role="header" data-position="fixed" data-tap-toggle="false" class="tab-list-content">
            <ul>
                <li><a href="#one" class="ui-btn-active" onclick="selectFun(0)">百</a></li>
                <li><a href="#two" onclick="selectFun(1)">十</a></li>
                <li><a href="#three" onclick="selectFun(2)">个</a></li>
            </ul>
        </div>
        <div class="silder-warp">
        <div id="footer">
            <div data-role="footer" data-position="fixed" data-tap-toggle="false">
                <table data-role="table" class="ui-responsive" border="1" data-mode="columntoggle:none" id="footer-table">
                    <thead class="footer-show" id="footer-show">

                    </thead>
                </table>
            </div>
        </div>

            <div id="one">

                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data"  >
                    <thead>
                    <!--                <div data-role="header" data-position="fixed" data-tap-toggle="false">-->
                    <tr>
                        <th class="chinese">期数</th>
                        <th>0</th>
                        <th>1</th>
                        <th>2</th>
                        <th>3</th>
                        <th>4</th>
                        <th>5</th>
                        <th>6</th>
                        <th>7</th>
                        <th>8</th>
                        <th>9</th>
                    </tr>
                    <!--                </div>-->
                    </thead>
                    <tbody class="dataArea">

                    </tbody>
                </table>
            </div>
            <div id="two">


                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data" >
                    <thead>
                    <!--                <div data-role="header" data-position="fixed" data-tap-toggle="false">-->
                    <tr>
                        <th class="chinese">期数</th>
                        <th>0</th>
                        <th>1</th>
                        <th>2</th>
                        <th>3</th>
                        <th>4</th>
                        <th>5</th>
                        <th>6</th>
                        <th>7</th>
                        <th>8</th>
                        <th>9</th>
                    </tr>
                    <!--                </div>-->
                    </thead>
                    <tbody class="dataArea">

                    </tbody>
                </table>
            </div>
            <div id="three">


                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data" >
                    <thead>
                    <!--                <div data-role="header" data-position="fixed" data-tap-toggle="false">-->
                    <tr>
                        <th class="chinese">期数</th>
                        <th>0</th>
                        <th>1</th>
                        <th>2</th>
                        <th>3</th>
                        <th>4</th>
                        <th>5</th>
                        <th>6</th>
                        <th>7</th>
                        <th>8</th>
                        <th>9</th>
                    </tr>
                    <!--                </div>-->
                    </thead>
                    <tbody class="dataArea">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<script>

    var objectRows = null;
    var aTagIndex = 0;
    var tdHeight = 0;
    $(document).ready(function(){

        var gid = getUrlParam('gid');

        var url = "get_game_trend_list";
        $.ajax({
            url: url,// 跳转到 action
            data: {
                gid:gid
            },
            headers: {AuthGC: '<?php echo $sn;?>;'},
            type: 'get', //用get方法
            cache: false,
            dataType: 'json',//数据格式为json,定义这个格式data不用转成对象
            success: function (data) {
                objectRows = data.data.rows;
                if (data.code == 200) {

                    initTdData(objectRows,10,0);
                    tdHeightInit();
                    selectFun(0);
                    $(".tab-data").find("tr").css("background-color", "#ffffff");
                    $(".tab-data").find("tr:even").css("background-color", "#f0f8ff");

                }
            },
            error: function () {

            }
        });


    });

    $(".silder-warp").on("swipeleft", function () {

        aTagIndex++;

        if (aTagIndex > 2) {
            aTagIndex = 2;
        } else {
            $("a:eq("+ aTagIndex +")").click();
        }

    });

    $(".silder-warp").on("swiperight", function () {


        aTagIndex--;
        if (aTagIndex < 0) {
            aTagIndex = 0;
        } else {
            $("a:eq("+ aTagIndex +")").click();
        }

    });

    //根据选择table替换页脚
    function selectFun(e) {

        var ballsNumber = 10;
        var number = 0;
        var countText = countTimes(e,ballsNumber,number);
        var maxContinue = maxContinueTimes(e,ballsNumber,number);
        var maxUnselect = maxUnselectTimes(e,ballsNumber,number);
        var avgUnselect = avgUnselectTimes(e,ballsNumber,number);

        $('.footer-show').html(countText + maxContinue + maxUnselect + avgUnselect);

        aTagIndex = e;
        canvas_init(e);
    }
</script>

<style>
    html{
        font-size: 96%;
    }

    .ui-tabs {
        padding: 0;

    }

    .ui-page-theme-a .ui-btn, html .ui-bar-a .ui-btn, html .ui-body-a .ui-btn, html body .ui-group-theme-a .ui-btn, html head + body .ui-btn.ui-btn-a, .ui-page-theme-a .ui-btn:visited, html .ui-bar-a .ui-btn:visited, html .ui-body-a .ui-btn:visited, html body .ui-group-theme-a .ui-btn:visited, html head + body .ui-btn.ui-btn-a:visited {
        border: 0;
        background-color: #ffffff;
    }

    .ui-page-theme-a .ui-btn.ui-btn-active {
        color:#D0011B;
        background-color: #FFFFFF;
        text-shadow: none;
        border-bottom: 2pt solid #D0011B;

    }

    .ui-page-theme-a .ui-btn:hover{
        background-color: white;
    }

    .ui-page-theme-a .ui-btn:focus {
        box-shadow: none;
    }

    .ui-header, .ui-footer {
        border: 0;
        background-color: transparent;
    }

    .ui-page-theme-a {
        border: 0;
        background-color: transparent;
        font-family: PingFang-SC-Regular;
        font-size: 10px;
        color: #6B6B6B;
        letter-spacing: 0;
        text-align: center;
    }

    .ui-table th, .ui-table td{
        padding: 0;
        text-align: center;
        vertical-align:middle;
        line-height: 1.8em;
        font-weight: 400;
    }

    .ui-bar-a, .ui-page-theme-a .ui-bar-inherit, html .ui-bar-a .ui-bar-inherit, html .ui-body-a .ui-bar-inherit, html body .ui-group-theme-a .ui-bar-inherit{
        /*background: rgb(255,223,215);*/
    }

    .ui-footer .ui-bar-inherit .ui-footer-fixed .slideup{
        background-color: #D0011B;
    }

    #footer-table{
        background: rgba(255,223,215,0.74);
        border-top: 1px solid #FF8C94;
    }

    .chinese{
        width: 17%
    }

    th{
        width: 8.2%;
        line-height: 2rem;
        height: 2rem;
        border-right: 1pt solid #8F8F8F;
    }

    td{
        width: 8.2%;
        line-height: 2rem;
        height: 2rem;
        border-right: 1pt solid #8F8F8F;
    }


    th:last-child {
        border-right: 0;
    }

    td:last-child {
        border-right: 0;
    }

    .data-active{
        border-radius: 50%;
        background-color: #D0011B;
        width: 1.2rem;
        height: 1.2rem;
        background-size: 100%;
        margin:0 auto;
        color: transparent;
        vertical-align: middle;
    }

    canvas {
        background: rgba(255, 255, 155, 0);
        position: absolute;
        left: 0;
        top: 0;
    }

</style>

