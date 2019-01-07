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
        <div data-role="header" data-position="fixed" data-tap-toggle="true" class="tab-list-content">
            <ul>
                <li><a href="#one" class="ui-btn-active" onclick="selectFun(0)">一</a></li>
                <li><a href="#two" onclick="selectFun(1)">二</a></li>
                <li><a href="#three" onclick="selectFun(2)">三</a></li>
                <li><a href="#four" onclick="selectFun(3)">四</a></li>
                <li><a href="#five" onclick="selectFun(4)">五</a></li>
                <li><a href="#six" onclick="selectFun(5)">六</a></li>
                <li><a href="#seven" onclick="selectFun(6)">七</a></li>
                <li><a href="#eight" onclick="selectFun(7)">八</a></li>
            </ul>
        </div>
        <div class="silder-warp">
            <div id="footer">
                <div data-role="footer" data-position="fixed" data-tap-toggle="false">
                    <table data-role="table" class="ui-responsive" border="1" data-mode="columntoggle:none"
                           id="footer-table">
                        <thead class="footer-show" id="footer-show"></thead>
                    </table>
                </div>
            </div>
            <div id="one" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="two" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="three" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="four" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="five" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="six" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="seven" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
                </table>
            </div>
            <div id="eight" class="kl10-scroll">
                <table data-role="table" data-mode="columntoggle:none" border="1" class="ui-responsive tab-data">
                    <thead>
                    <tr>
                        <th class="chinese">期数</th>
                        <th>01</th>
                        <th>02</th>
                        <th>03</th>
                        <th>04</th>
                        <th>05</th>
                        <th>06</th>
                        <th>07</th>
                        <th>08</th>
                        <th>09</th>
                        <th>10</th>
                        <th>11</th>
                        <th>12</th>
                        <th>13</th>
                        <th>14</th>
                        <th>15</th>
                        <th>16</th>
                        <th>17</th>
                        <th>18</th>
                        <th>19</th>
                        <th>20</th>
                    </tr>
                    </thead>
                    <tbody class="dataArea"></tbody>
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
    $(document).ready(function () {
        var gid = getUrlParam('gid');
        var url = "get_game_trend_list";
        $.ajax({
            url: url,
            data: {
                gid: gid
            },
            headers: {AuthGC: '<?php echo $sn;?>;'},
            type: 'get',
            cache: false,
            dataType: 'json',
            success: function (data) {
                objectRows = data.data.rows;
                if (data.code == 200) {
                    initTdData(objectRows, 20, 1);
                    tdHeightInit();
                    selectFun(0);
                    $(".tab-data").find("tr").css("background-color", "#ffffff");
                    $(".tab-data").find("tr:even").css("background-color", "#f0f8ff");
                }
            },
            error: function () {
                console.log('error')
            }
        });
    });
    // $(".silder-warp").on("swipeleft", function () {
    //     aTagIndex++;
    //     if (aTagIndex > 7) {
    //         aTagIndex = 7;
    //     } else {
    //         $("a:eq(" + aTagIndex + ")").click();
    //     }
    // });
    // $(".silder-warp").on("swiperight", function () {
    //     aTagIndex--;
    //     if (aTagIndex < 0) {
    //         aTagIndex = 0;
    //     } else {
    //         $("a:eq(" + aTagIndex + ")").click();
    //     }
    // });
    //根据选择table替换页脚
    function selectFun(e) {
        var ballsNumber = 20;
        var number = 1;
        var countText = countTimes(e, ballsNumber, number);
        var maxContinue = maxContinueTimes(e, ballsNumber, number);
        var maxUnselect = maxUnselectTimes(e, ballsNumber, number);
        var avgUnselect = avgUnselectTimes(e, ballsNumber, number);
        $('.footer-show').html(countText + maxContinue + maxUnselect + avgUnselect);
        aTagIndex = e;
        canvas_init(e);
    }
</script>

<style>

    .ui-navbar .ui-grid-duo .ui-btn {
        padding-left: 0;
        padding-right: 0;
    }

    .ui-navbar li:last-child .ui-btn {
        margin-right: 0;
    }

    html {
        font-size: 96%;
    }

    .ui-block-a {
        clear: none;
    }

    .ui-grid-a > .ui-block-a, .ui-grid-a > .ui-block-b {
        width: 12.5%;
    }

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
        line-height: 1.6em;
        font-weight: 400;
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

    .chinese {
        width: 27%
    }

    th {
        width: 7.2%;
        line-height: 2rem;
        height: 2rem;
        border-right: 1pt solid #8F8F8F;
    }

    td {
        width: 7.2%;
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

    .data-active {
        color: transparent;
        vertical-align: middle;
        border-radius: 50%;
        width: 1.1rem;
        height: 1.1rem;
        background-size: 100%;
        background-color: #D0011B;
        margin: 0 auto;
    }

    canvas {
        background: rgba(255, 255, 155, 0);
        position: absolute;
        left: 0;
        top: 0;
    }

    .kl10-scroll {
        overflow: scroll;
    }

</style>

