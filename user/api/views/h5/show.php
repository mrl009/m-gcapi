<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <title>优惠活动详情</title>
    <meta name="format-detection" content="telphone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="full-screen" content="yes">
    <meta name="x5-fullscreen" content="true">
</head>
<body>
<div id="content"><?php echo $content?></div>
</body>
<script>
    var max_width = document.body.offsetWidth
    var img = document.getElementsByTagName("img");
    for(var i=0; i<img.length; i++){
        img[i].style["max-width"] = max_width - 4 + "px";
    }
</script>
