<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>全网通营销管理后台-移动版</title>
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/bootstrap/easyui.css">
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/mobile.css">
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/icon.css">
    <script type="text/javascript" src="../../../static/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.mobile.js"></script>
    <script type="text/javascript" src="../../../static/js/layout/locale/easyui-lang-zh_CN.js"></script>
</head>
<body>

<div class="easyui-navpanel" style="position:relative">
    <header>
        <div class="m-toolbar">
            <span class="m-title">反馈问题</span>
        </div>
    </header>

    <div style="padding:20px">
        <div style="margin-bottom:10px">

            <input class="easyui-textbox" data-options="prompt:'请输入问题标题'" style="width:100%;height:38px">
        </div>
        <div>
            <input class="easyui-textbox" data-options="prompt:'请输入问题内容'" style="width:100%;height:38px">
        </div>

        <div style="text-align:center;margin-top:30px">
            <a href="javascript:void(0)" class="easyui-linkbutton" data-options="plain:true,outline:true" style="width:80%;height:35px" onclick="$('#dlg1').dialog('open').dialog('center')">提交</a>
        </div>

        <div id="dlg1" class="easyui-dialog" style="padding:20px 6px;width:80%;" data-options="inline:true,modal:true,closed:true,title:'温馨提示'">
            <p>提交成功</p>
            <div class="dialog-button">
                <a href="javascript:void(0)" class="easyui-linkbutton" style="width:100%;height:35px" onclick="$('#dlg1').dialog('close')">确认</a>
            </div>
        </div>
    </div>
</div>



