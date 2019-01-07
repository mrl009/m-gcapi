<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>全网通营销管理后台-移动版</title>

    <link rel="stylesheet" type="text/css" href="../../../static/js/jquery.mobile-1.4.5.min.css">
    <script type="text/javascript" src="../../../static/js/jquery.min.js"></script>
    <script type="text/javascript" src="../../../static/js/jquery.mobile-1.4.5.min.js"></script>


</head>
<body>


<div data-role="tabs" id="tabs">
    <div data-role="navbar">
        <div data-role="header" data-position="fixed" data-tap-toggle="false" class="tab-list-content">
            <ul>
                <li><a href="#num" class="ui-btn-active" onclick="selectFun(0)">号码</a></li>
                <li><a href="#sum" onclick="selectFun(1)">总和</a></li>
                <li><a href="#special" onclick="selectFun(2)">特码</a></li>
            </ul>
        </div>

        <div class="silder-warp">
            <div id="num">

                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <!--                <div data-role="header" data-position="fixed" data-tap-toggle="false">-->
                    <tr>
                        <th class="chinese">期数</th>
                        <th>正一</th>
                        <th>正二</th>
                        <th>正三</th>
                        <th>正四</th>
                        <th>正五</th>
                        <th>正六</th>
                        <th>特码</th>

                    </tr>
                    <!--                </div>-->
                    </thead>
                    <tbody class="dataArea">

                    </tbody>
                </table>
            </div>
            <div id="sum">


                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <!--                <div data-role="header" data-position="fixed" data-tap-toggle="false">-->
                    <tr>
                        <th class="chinese">期数</th>
                        <th>总数</th>
                        <th>单双</th>
                        <th>大小</th>
                        <th>七色波</th>
                    </tr>
                    <!--                </div>-->
                    </thead>
                    <tbody class="dataArea">

                    </tbody>
                </table>
            </div>
            <div id="special">


                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <!--                <div data-role="header" data-position="fixed" data-tap-toggle="false">-->
                    <tr>
                        <th class="chinese">期数</th>
                        <th>单双</th>
                        <th>大小</th>
                        <th>和单双</th>
                        <th>和大小</th>
                        <th>大小尾</th>
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

    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
        var r = window.location.search.substr(1).match(reg);  //匹配目标参数
        if (r != null) return unescape(r[2]);
        return null; //返回参数值
    }

    function addTdData(objectRows) {
        var tdText = null;
        var text = null;
        var textContent = null;
        var j = 0;

        for (var i = 0; i < objectRows.length; i++) {
            tdText = null;
            text = null;
            j = 0;

            for (var k=0; k < objectRows[i].number_arr.length; k++) {
                j++;
                if (objectRows[i].number_arr[k].sb == 'red') {
                    tdText += "<td >" + '<span class="red">' + objectRows[i].number_arr[k].num + '</span>';
                } else if (objectRows[i].number_arr[k].sb == 'green') {
                    tdText += "<td >" + '<span class="green">' + objectRows[i].number_arr[k].num + '</span>';
                } else if (objectRows[i].number_arr[k].sb == 'blue') {
                    tdText += "<td >" + '<span class="blue">' + objectRows[i].number_arr[k].num + '</span>';
                } else {
//                    tdText += "<td >" + '<span class="red">' + key + '</span>';
                }
                tdText += objectRows[i].number_arr[k].sx + "</td>";
            }
            text += "<tr>" + "<td>" + objectRows[i].kithe + "</td>" + tdText + "</tr>";

            textContent += text;
        }


//        for (var i = 0; i < objectRows.length; i++) {
//            for (var j = 0; j < objectRows[i].number_arr.length; j++) {
//                if (objectRows[i].number_arr[j] / 10 != 0) {
//                    tdText += "<td >" + objectRows[i].number_arr[j] + "</td>";
//                } else {
//                    tdText += "<td >" + "0" + objectRows[i].number_arr[j] + "</td>";
//                }
//
//            }
//            text += "<tr>" + "<td>" + objectRows[i].kithe + "</td>" + tdText + "</tr>";
//            tdText = null;
//        }
        return textContent;
    }

    function sumTable(objectRows) {
        var tdText = null;
        var sum = null;
        var oddflag = null;
        var bigflag = null;
        var colorflag = null;
        var text = null;
        var color = [0, 0, 0];
        var maxColorValue = 0;
        var maxColorIndex = -1;
        for (var i = 0; i < objectRows.length; i++) {
            color = [0, 0, 0];
            for (var j = 0; j < objectRows[i].number_arr.length; j++) {

                sum += parseInt(objectRows[i].number_arr[j].num);

                if (objectRows[i].number_arr[j].sb == 'red') {
                    color[0]++;
                } else if (objectRows[i].number_arr[j].sb == 'green') {
                    color[1]++;
                } else if (objectRows[i].number_arr[j].sb == 'blue') {
                    color[2]++;
                } else {

                }
            }


            oddflag = sum % 2;
            if (oddflag == 0) {
                oddflag = '双';
            } else {
                oddflag = '单';
            }


            if (sum > 174) {
                bigflag = '大';
            } else {
                bigflag = '小';
            }

            maxColorValue = Math.max.apply(Math, color);
            for (var k = 0, len = color.length; k < len; k++) {
                if (color[k] == maxColorValue) {
                    maxColorIndex = k;
                    break;
                }
            }

            if (maxColorIndex == 0) {
                colorflag = '红波';
            } else if (maxColorIndex == 1) {
                colorflag = '绿波';
            } else if (maxColorIndex == 2) {
                colorflag = '蓝波';
            } else {

            }

            tdText = "<td>" + sum + "</td>" + "<td>" + oddflag + "</td>" + "<td>" + bigflag + "</td>" + "<td>" + colorflag + "</td>";

            text += "<tr>" + "<td>" + objectRows[i].kithe + "</td>" + tdText + "</tr>";
            tdText = null;
            sum = 0;
        }
        return text;
    }

    function specialTable(objectRows) {
        var oddflag = null;
        var bigflag = null;
        var sumOddFlag = null;
        var sumBigFlag = null;
        var bigRearFlag = null;
        var specialNum = null;
        var text = null;
        var tdText = null;

        for (var i = 0; i < objectRows.length; i++) {

            specialNum = objectRows[i].number_arr[6].num;

            oddflag = specialNum % 2;
            if (oddflag == 0) {
                oddflag = '双';
            } else {
                oddflag = '单';
            }


            if (specialNum > 24) {
                bigflag = '大';
            } else {
                bigflag = '小';
            }

            var tensDigit = specialNum / 10;
            var unitsDigit = specialNum % 10;

            var sum = parseInt(tensDigit + unitsDigit);
            if (sum % 2 == 0) {
                sumOddFlag = '和双';
            } else {
                sumOddFlag = '和单';
            }

            if (sum > 6) {
                sumBigFlag = '和大';
            } else {
                sumBigFlag = '和小';
            }

            if (specialNum % 10 > 4) {
                bigRearFlag = '尾大';
            } else {
                bigRearFlag = '尾小';
            }

            tdText = "<td>" + oddflag + "</td>" + "<td>" + bigflag + "</td>" + "<td>" + sumOddFlag + "</td>" + "<td>" + sumBigFlag + "</td>" + "<td>" + bigRearFlag + "</td>";

            text += "<tr>" + "<td>" + objectRows[i].kithe + "</td>" + tdText + "</tr>";
            tdText = null;
            sum = 0;
        }

        return text;

    }

    $(document).ready(function () {

        var gid = getUrlParam('gid');

        var url = "get_game_trend_list";
        $.ajax({
            url: url,// 跳转到 action
            data: {
                gid: gid
            },
            headers: {AuthGC: '<?php echo $sn;?>;'},
            type: 'get', //用get方法
            cache: false,
            dataType: 'json',//数据格式为json,定义这个格式data不用转成对象
            success: function (data) {

                objectRows = data.data.rows;

                if (data.code == 200) {

                    var text = addTdData(objectRows);
                    $(".dataArea:eq(0)").append(text);

                    text = sumTable(objectRows);
                    $(".dataArea:eq(1)").append(text);

                    text = specialTable(objectRows);
                    $(".dataArea:eq(2)").append(text);
                }

                $(".tab-data").find("tr").css("background-color", "#ffffff");
                $(".tab-data").find("tr:even").css("background-color", "#f0f8ff");
            },
            error: function () {

            }
        });

    });


    var aTagIndex = 0;

    $(".silder-warp").on("swipeleft", function () {

        aTagIndex++;

        if (aTagIndex > 2) {
            aTagIndex = 2;
        } else {
            $("a:eq(" + aTagIndex + ")").click();
        }

    });

    $(".silder-warp").on("swiperight", function () {


        aTagIndex--;
        if (aTagIndex < 0) {
            aTagIndex = 0;
        } else {
            $("a:eq(" + aTagIndex + ")").click();
        }

    });

    function selectFun(e) {
        aTagIndex = e;
    }
</script>

<style>
    .ui-tabs {
        padding: 0;

    }

    .ui-page-theme-a .ui-btn, html .ui-bar-a .ui-btn, html .ui-body-a .ui-btn, html body .ui-group-theme-a .ui-btn, html head + body .ui-btn.ui-btn-a, .ui-page-theme-a .ui-btn:visited, html .ui-bar-a .ui-btn:visited, html .ui-body-a .ui-btn:visited, html body .ui-group-theme-a .ui-btn:visited, html head + body .ui-btn.ui-btn-a:visited {
        border: 0;
        background-color: #ffffff;
    }

    .ui-page-theme-a .ui-btn.ui-btn-active {
        color: #D0011B;
        background-color: #FFFFFF;
        text-shadow: none;
        border-bottom: 2pt solid #D0011B;

    }

    .ui-page-theme-a .ui-btn:hover {
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

    .ui-table th, .ui-table td {
        padding: 0;
        text-align: center;
        vertical-align: middle;
    }

    .ui-bar-a, .ui-page-theme-a .ui-bar-inherit, html .ui-bar-a .ui-bar-inherit, html .ui-body-a .ui-bar-inherit, html body .ui-group-theme-a .ui-bar-inherit {
        /*background: rgb(255,223,215);*/
    }

    .ui-footer .ui-bar-inherit .ui-footer-fixed .slideup {
        background-color: #D0011B;
    }

    #footer-table {
        background: rgba(255, 223, 215, 0.74);
        border-top: 1px solid #FF8C94;
    }

    /*.chinese {*/
    /*width: 16%*/
    /*}*/

    th {
        width: 12%;
        line-height: 2.5rem;
        height: 2.5rem;
        border-right: 1pt solid #8F8F8F;
    }

    td {
        width: 12%;
        line-height: 2.5rem;
        height: 2.5rem;
        border-right: 1pt solid #8F8F8F;
    }

    th:last-child {
        border-right: 0;
    }

    td:last-child {
        border-right: 0;
    }

    /*th:first-child {*/
        /*width: 16%*/
    /*}*/

    /*td:first-child {*/
        /*width: 16%*/
    /*}*/

    .red {
        border-radius: 50%;
        background-color: #D0011B;
        color: transparent;
        width:1.05rem;
        height: 1.05rem;
        display: inline-block;
        margin-right: 0.1rem;

    }

    .green {
        border-radius: 50%;
        background-color: #0db00c;
        color: transparent;
        width:1.05rem;
        height: 1.05rem;
        display: inline-block;
        margin-right: 0.1rem;

    }

    .blue {
        border-radius: 50%;
        background-color: #1989dd;
        color: transparent;
        width: 1.05rem;
        height: 1.05rem;
        display: inline-block;
        margin-right: 0.1rem;

    }
</style>

