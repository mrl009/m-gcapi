<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>支付信息</title>
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/bootstrap/easyui.css">
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/mobile.css">
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/icon.css">
    <script type="text/javascript" src="../../../static/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.mobile.js"></script>
    <script type="text/javascript" src="../../../static/js/layout/locale/easyui-lang-zh_CN.js"></script>
</head>
<body>

<div id="warp" style="line-height:20px;">

</div>


</body>


<script type="text/javascript">

    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = window.location.search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]);
        return null; //返回参数值
    }

    $("document").ready(function () {

        var id = getUrlParam('id');
        var url = "get_game_article_content";
        $.ajax({
            url: url,// 跳转到 action
            data: {
                id:id
            },
            headers: {AuthGC: '<?php echo $sn;?>;'},
            type: 'get', //用post方法
            cache: false,
            dataType: 'json',//数据格式为json,定义这个格式data不用转成对象
            success: function (data) {
                console.log(data);
                var text = null;

                if(data.code == 200){
                    text = data.data[0].content;
                    $("#warp").append(text);
                }
            },
            error: function () {

            }
        });
    });
</script>


