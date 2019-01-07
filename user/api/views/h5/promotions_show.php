<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title></title>
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/bootstrap/easyui.css">
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/mobile.css">
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/icon.css">
    <script type="text/javascript" src="../../../static/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.mobile.js"></script>
    <script type="text/javascript" src="../../../static/js/layout/locale/easyui-lang-zh_CN.js"></script>
</head>
<body>

<div class="easyui-navpanel warp">
    <div class="accordion">

    </div>
</div>


</body>

<script type="text/javascript">

    function openContent(e) {
        var flag = 0;
        for (var i = 0; i < $('.content').length; i++) {
            if ($('.content')[i].style.display == 'block') {
                flag = 1;
                break;
            }
        }
        $(e).next().slideToggle(500);
        $(e).css("border-bottom-left-radius", 0);
        $(e).css("border-bottom-right-radius", 0);
        $('.content').not($(e).next()).slideUp('normal');
        if (flag != 0) {
            $(e).css("border-bottom-left-radius", '5pt');
            $(e).css("border-bottom-right-radius", '5pt');
        }
    }
</script>

<script type="text/javascript">
    $("document").ready(function () {
        var fromway = getUrlParameters('FROMWAY')
        var url = "get_activity_list?fromway=" + fromway;
        $.ajax({
            url: url,// 跳转到 action
            data: {},
            headers: {AuthGC: '<?php echo $sn;?>;', FROMWAY: '<?php echo $from_way;?>;'},
            type: 'get', //用post方法
            cache: false,
            dataType: 'json',//数据格式为json,定义这个格式data不用转成对象
            success: function (data) {
                var text = null;
                if (data.code == 200) {
                    for (var i = 0; i < data.data.total; i++) {
                        text = '<div class="item" onclick="openContent(this)"><p><span class="item-header-title">' + data.data.rows[i].title + '</span><span class="item-header-time" >' +
                            '</span></p><img src="' + data.data.rows[i].img_base64 + '">' +
                            '</div>' +
                            '<div class="content">' + data.data.rows[i].content + '</div>';
                        $(".accordion").append(text);
                        $('.accordion .content').find('img').css('width', '100%');
                        text = null;
                    }
                }
            },
            error: function () {
            }
        });
    });
    function getUrlParameters(name) {
        var string = location.search.substr(1);
        var array = string.split('&');
        for (var i = 0; i < array.length; i++) {
            var v = array[i].split('=');
            if (v[0] == name) {
                return v[1];
            }
        }
        return '';
    }
</script>

<style>

    body, html {
        font-size: 100%;
        padding: 0;
        margin: 0;
    }

    body {
        font-weight: 500;
        font-size: 1.05em;
        /*font-family: "Microsoft YaHei","宋体","Segoe UI", "Lucida Grande", Helvetica, Arial,sans-serif, FreeSans, Arimo;*/
    }

    /*a{color: #2fa0ec;text-decoration: none;outline: none;}*/
    /*a:hover,a:focus{color:#74777b;}*/

    * {
        margin: 0;
        padding: 0;
    }

    .warp {
        background: #F5F5F9;
    }

    .accordion {
        margin: 10pt;
        /*width: 380px;*/
        /*background: #ccc;*/
        /*cursor: pointer;*/
        border: none;
        background: #F5F5F9;
    }

    .accordion .item {
        background-color: #FFFFFF;
        margin-top: 10pt;
        /*margin-bottom: 10pt;*/
        /*        border-radius: 5pt;
                padding-left: 5pt;
                padding-right: 5pt;*/
    }

    .accordion .item img {
        width: 100%;
        height: 100%;
        vertical-align: middle;
        border-radius: 5pt;

    }

    .accordion .item h3:before {
        content: "";
        display: inline-block;
        vertical-align: middle;
        height: 100%;
        margin-top: 5pt;
        margin-bottom: 5pt;
    }

    .accordion .content {
        /*font-weight: 400;*/
        padding-top: 15px;
        background: #ffffff;
        display: none;
        /*box-shadow: inset 0 3px 7px rgba(0, 0, 0, 0.2);*/
        border-bottom-left-radius: 5pt;
        border-bottom-right-radius: 5pt;
        /*margin-top: 5pt;*/
        margin-bottom: 5pt;

    }

    p {
        font-family: PingFang-SC-Regular;
        font-size: 12pt;
        color: #5A5A5A;
        letter-spacing: 0;
        line-height: 2.0;
    }

    .highlight_text {
        font-family: PingFang-SC-Regular;
        font-size: 12pt;
        color: #FF0000;
        letter-spacing: 0;
        text-decoration: underline;
    }

    .title-red {
        font-family: PingFang-SC-Regular;
        font-size: 12pt;
        color: #FF0000;
        letter-spacing: 0;

    }

    .header-title {
        font-family: PingFang-SC-Regular;
        font-size: 12pt;
        color: #FF0000;
        letter-spacing: 0;
        text-align: center;
    }

    .footer-summary {
        font-family: PingFang-SC-Regular;
        font-size: 12pt;
        color: #FF0000;
        letter-spacing: 0;
    }

    hr {

        margin-top: 10pt;
        margin-bottom: 10pt;
        height: 1px;
        border: none;
        border-top: 1pt solid #8b8b8b;
    }

    a {
        color: #0000ff;
    }

    .tab-container {
        table-layout: fixed;
        width: 100%;
        border: 1pt solid rgba(7, 17, 27, 0.1);
        border-collapse: collapse;
    }

    .tab-container .tab-item {
        text-align: center;
        padding: 2pt;
        border: 1pt solid rgba(7, 17, 27, 0.1);
    }

    .accordion .item .item-header-title {
        font-family: PingFang-SC-Regular;
        font-size: 0.8rem;
        color: #616161;
        letter-spacing: 0;
        float: left;
    }

    .accordion .item .item-header-time {
        font-family: PingFang-SC-Regular;
        font-size: 0.5rem;
        color: #616161;
        letter-spacing: 0;
        float: right;
    }

    .accordion .item .item-header-a {
        font-family: PingFang-SC-Regular;
        font-size: 0.5rem;
        color: #C81623;
        letter-spacing: 0;
        text-decoration: none;
    }

    .accordion .item:first-child {
        margin-top: 0;
    }

</style>



