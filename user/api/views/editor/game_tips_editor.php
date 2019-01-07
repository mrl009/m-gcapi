<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>全网通营销管理后台-移动版</title>
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/bootstrap/easyui.css">
    <!--    <link rel="stylesheet" type="text/css" href="/static/js/layout/themes/mobile.css">-->
    <link rel="stylesheet" type="text/css" href="../../../static/js/layout/themes/icon.css">
    <script type="text/javascript" src="../../../static/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.easyui.min.js"></script>
    <!--    <script type="text/javascript" src="/static/js/layout/jquery.easyui.mobile.js"></script>-->
    <script type="text/javascript" src="../../../static/js/layout/locale/easyui-lang-zh_CN.js"></script>
</head>
<body>


<table id="dg" title="游戏规则列表" class="easyui-datagrid" style="width:100%;height:700px"
       url="/editor/get_game_tips_search_data"
       toolbar="#toolbar"
       rownumbers="true" fitColumns="true" singleSelect="true">
    <thead>
    <tr>
        <th field="id" width="50">id</th>
        <th field="first" width="100">游戏名称</th>
        <th field="second" width="100">一级子菜单</th>
        <th field="third" width="100">二级子菜单</th>
        <th field="forth" width="100">三级子菜单</th>
        <th field="paly_intro" width="550">内容</th>
    </tr>
    </thead>
</table>
<div id="toolbar">
    <a href="#" class="easyui-linkbutton" iconCls="icon-edit" plain="true" onclick="editGameRule()">编辑规则</a>

    <select id="selectBox" class="easyui-combobox" name="state" style="width:200px;" data-options="
					url:'/editor/get_game_tips_id_list',
					method:'get',
					valueField:'id',
					textField:'value',
					panelHeight:'auto'
			">

    </select>
    <a href="#" class="easyui-linkbutton" iconCls="icon-search" plain="true" onclick="searchGameRule()">查询</a>
</div>


<div id="dlg" class="easyui-dialog" style="width:400px;height:480px;padding:10px 20px"
     closed="true" buttons="#dlg-buttons">
    <div class="ftitle">游戏规则</div>
    <form id="fm" method="post">
        <div class="fitem">
            <label>玩法规则:</label>
            <textarea name="game_tips" style="width: 100%;height: 80px"></textarea>
        </div>
        <div class="fitem">
            <label>中奖说明:</label>
            <textarea name="win_tips" style="width: 100%;height: 80px"></textarea>
        </div>
        <div class="fitem">
            <label>范例1:</label>
            <textarea name="example1" style="width: 100%;height: 80px"></textarea>
        </div>
        <div class="fitem">
            <label>范例2:</label>
            <textarea name="example2" style="width: 100%;height: 80px"></textarea>
        </div>
    </form>
</div>
<div id="dlg-buttons">
    <a href="#" class="easyui-linkbutton" iconCls="icon-ok" onclick="updateGameRule()">Save</a>
    <a href="#" class="easyui-linkbutton" iconCls="icon-cancel" onclick="javascript:$('#dlg').dialog('close')">Cancel</a>
</div>

</body>
</html>

<script>

    $(document).ready(function () {
//        $.ajax({
//            url: '/editor/get_gid_list',// 跳转到 action
//            data: {},
//            type: 'post', //用post方法
//            cache: false,
//            dataType: 'json',//数据格式为json,定义这个格式data不用转成对象
//            success: function (data) {
//
//                data.unshift({id:0,value:'全部'});
//                $("#selectBox").combobox(
//                    {
//                        valueField:'id',
//                        textField:'value',
//                        data:data,
//                        onSelect:{
//                            $('#dg').datagrid({
//                            queryParams: {
//                                name: 'easyui',
//                                subject: 'datagrid'
//
//                            });
//                        }
//               })
//            },
//            error: function () {
//                alert(2);
//            }
//        });
    });

</script>


<script>


    var url = null;

    function editGameRule() {
        var row = $('#dg').datagrid('getSelected');
        if (row) {
            $('#dlg').dialog('open').dialog('setTitle', '编辑游戏规则');
            $('#fm').form('load', row);
            url = '/editor/update_game_tips_data?id=' + row.id;
        }
    }

    function searchGameRule() {
        var data = $("#selectBox").combobox('getValue');
//       alert(data);
        $('#dg').datagrid('load',{
            gid:data
        });

    }

    function updateGameRule() {
        $('#fm').form('submit', {
            url: url,
            onSubmit: function () {
                return $(this).form('validate');
            },
            success: function (data) {

                data = eval('(' + data + ')');
                console.log(data);
                if (data.code != 200) {
                    $.messager.show({
                        title: 'Error',
                        msg: '异常'
                    });
                } else {
                    $('#dlg').dialog('close');		// close the dialog
                    $('#dg').datagrid('reload');	// reload the user data
                }
            }
        });
    }


</script>