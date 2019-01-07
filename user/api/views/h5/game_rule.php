<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>国彩玩法</title>
</head>
<body>

<div class="easyui-navpanel warp">
    <div class="main">
        <?php if ($code == 200):?>
            <p class="introduction"> <?php echo $introduction;?></p>
            <?php foreach ($rows as $key => $contents):?>
                <p class="title"> <?php echo $key;?> </p>
                <?php foreach ($contents as $content):?>
                    <p class="content"> <?php echo $content;?> </p>
                <?php endforeach;?>
            <?php endforeach;?>
        <?php else: ?>
            <div id="dlg" class="easyui-dialog" title="提示" data-options="iconCls:'icon-save'" style="width:200px;height:100px;padding:10px">
                异常
            </div>
        <?php endif ?>
    </div>
</div>

<style>
    body{
        background: #F5F5F9;
        margin:0;
    }

    p{
        margin: 0;
        padding: 5px;
    }
    .warp{
        background: #F5F5F9;
        margin:0;
    }

    .main{
        background: #FFFFFF;
        margin:5pt;
        border-radius: 10pt;
        padding: 10pt;
    }

    .title{
        font-family: .PingFang-SC-Regular;
        color: #d91d36;
        font-size: 12pt;
    }

    .introduction {
        font-family: .PingFang-SC-Regular;
        color: #353535;
        font-size: 10pt;
        letter-spacing: 0;
    }

    .content {
        font-family: .PingFang-SC-Regular;
        color: #353535;
        font-size: 10pt;
        letter-spacing: 0;
    }

</style>
</body>
</html>