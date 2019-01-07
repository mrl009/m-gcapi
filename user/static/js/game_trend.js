/**
 * Created by dragon on 2017/5/6.
 */

//获取url参数
function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r != null) return unescape(r[2]);
    return null; //返回参数值
}

//初始化球的每一位
function initBaseBallsArray(ballsNumber) {
    var baseBallsArray = new Array(ballsNumber);
    for (var y = 0; y < baseBallsArray.length; y++) {
        baseBallsArray[y] = 0;
    }
    return baseBallsArray;
}

//加载数据表
function initTdData(objectRows, ballsNumber, plusNum) {
    var tdText;
    var text;
    var tdDataArrayRecord = initBaseBallsArray(ballsNumber);

    for (var k = 0; k < objectRows.length; k++) {
        for (var i = 0; i < objectRows.length; i++) {

            for (var j = 0; j < ballsNumber; j++) {
                if (objectRows[i].number_arr[k] == (j + plusNum)) {
                    if( objectRows.length <= 20 ){
                        tdText += "<td ><div class=\"data-active\">" + (j + plusNum) + "</div></td>";
                    }else if(objectRows.length > 20 && i>=(objectRows.length-20)){
                        tdText += "<td ><div class=\"data-active\">" + (j + plusNum) + "</div></td>";
                    }else{

                    }
                    tdDataArrayRecord[j] = 0;
                } else {
                    tdDataArrayRecord[j]++;
                    if( objectRows.length <= 20 ) {
                        tdText += "<td >" + tdDataArrayRecord[j] + "</td>";
                    }else if(objectRows.length > 20 && i>=(objectRows.length-20)){
                        tdText += "<td >" + tdDataArrayRecord[j] + "</td>";
                    }else{

                    }
                }
            }
            if( objectRows.length <= 20 ) {
                text += "<tr>" + "<td>" + objectRows[i].kithe + "</td>" + tdText + "</tr>";
            }else if(objectRows.length > 20 && i>=(objectRows.length-20)){
                text += "<tr>" + "<td>" + objectRows[i].kithe + "</td>" + tdText + "</tr>";
            }else{

            }
            tdText = null;
        }
        $(".dataArea:eq(" + k + ")").append(text);
        text = null;
        tdDataArrayRecord = initBaseBallsArray(ballsNumber);
    }
}

function tdHeightInit() {
    tdHeight = $("th").first()[0].offsetHeight * (20 + 1);
}

//画布初始化
function canvas_init(e) {
    var c = document.getElementById("myCanvas");
    var cells = document.getElementsByClassName("data-active");

    var as = document.getElementsByTagName("a");
    var aHeight = as[0].getBoundingClientRect().height;

    c.height = getTagHeight("footer-show") + tdHeight + aHeight;
    // console.log(tdHeight);
    // console.log(c.height);
    var cxt = c.getContext("2d");
    cxt.strokeStyle = '#D0011B';
}

//获得目标中心横坐标
function getX(obj) {

    return parseInt((obj.getBoundingClientRect().right + obj.getBoundingClientRect().left) / 2);
}

//获得目标中心纵坐标
function getY(obj) {
    return parseInt((obj.getBoundingClientRect().top + obj.getBoundingClientRect().bottom) / 2);
}

//获得目标高度
function getTagHeight(str) {
    var div = document.getElementById(str).getBoundingClientRect();

    if (div.height) {
        height = div.height;
    } else {
        height = div.bottom - div.top;
    }
    return height;
}

function countTimes(e, ballsNumber, number) {
    var title = "<tr> <th class=\"chinese\">出现次数</th>";
    var dataArray = null;
    dataArray = initBaseBallsArray(ballsNumber);

    for (var i = 0; i < objectRows.length; i++) {
        dataArray[objectRows[i].number_arr[e] - number]++;
    }

    // console.log(dataArray)
    // console.log(dataArray.length)

    var text;
    for (var j = 0; j < ballsNumber; j++) {
        text += "<td>" + dataArray[j] + "</td>"
    }


    var final = title + text + "</tr>";
    return final;
}

function maxContinueTimes(e, ballsNumber, number) {
    var title = "<tr> <th class=\"chinese\">最大连出</th>";
    var dataArray = initBaseBallsArray(ballsNumber);
    var temp;
    var count = 1;

    temp = objectRows[0].number_arr[e];
    for (var i = 1; i < objectRows.length; i++) {

        if (temp == objectRows[i].number_arr[e]) {
            count++;
            if (count > dataArray[objectRows[i].number_arr[e] - number]) {
                dataArray[objectRows[i].number_arr[e] - number] = count;
            }
        } else {
            count = 1;
            temp = objectRows[i].number_arr[e];
        }
    }

    for (var k = 0; k < objectRows.length; k++) {
        if (dataArray[objectRows[k].number_arr[e] - number] == 0) {
            dataArray[objectRows[k].number_arr[e] - number] = 1;
        }
    }

    var text;
    for (var j = 0; j < dataArray.length; j++) {
        text += "<td>" + dataArray[j] + "</td>"
    }

    var final = title + text + "</tr>";
    return final;
}

//根据选择table计算最大遗漏
function maxUnselectTimes(e, ballsNumber, number) {
    var title = "<tr> <th class=\"chinese\">最大遗漏</th>";
    var dataArray = initBaseBallsArray(ballsNumber);
    var dataArrayTemp = initBaseBallsArray(ballsNumber);

    for (var i = 0; i < objectRows.length; i++) {
        for (var k = 0; k < dataArray.length; k++) {
            if ((objectRows[i].number_arr[e] - number) == k) {
                if (dataArray[k] < dataArrayTemp[k]) {
                    dataArray[k] = dataArrayTemp[k];
                    dataArrayTemp[k] = 0;
                } else {
                    dataArrayTemp[k] = 0;
                }
            } else {
                dataArrayTemp[k]++;
            }
        }
    }

    for (var y = 0; y < dataArray.length; y++) {
        if (dataArray[y] < dataArrayTemp[y]) {
            dataArray[y] = dataArrayTemp[y];
        }
    }

    var text;
    for (var j = 0; j < dataArray.length; j++) {
        text += "<td>" + dataArray[j] + "</td>"
    }

    var final = title + text + "</tr>";
    return final;
}

//根据选择table计算平均遗漏
function avgUnselectTimes(e, ballsNumber, number) {
    var title = "<tr> <th class=\"chinese\">平均遗漏</th>";
    var dataArray = initBaseBallsArray(ballsNumber);
    var dataArrayTemp = initBaseBallsArray(ballsNumber);

    var temp = [];
    for (var j = 0; j < dataArray.length; j++) {
        temp[j] = [];
    }

    for (var i = 0; i < objectRows.length; i++) {
        for (var k = 0; k < dataArray.length; k++) {
            if ((objectRows[i].number_arr[e] - number) == k) {
                if (dataArrayTemp[k] != 0) {
                    temp[k].push(dataArrayTemp[k]);
                    dataArrayTemp[k] = 0;
                }

            } else {
                dataArrayTemp[k]++;
            }
        }

    }

    for (var p = 0; p < dataArray.length; p++) {
        if (dataArrayTemp[p] != 0) {
            temp[p].push(dataArrayTemp[p]);
        }
    }

    for (var m = 0; m < dataArray.length; m++) {
        var value = 0;
        for (var y = 0; y < temp[m].length; y++) {
            value += temp[m][y];
        }
        if (dataArray[m] == 0 && temp[m].length == 0) {

        } else {
            dataArray[m] = Math.round(value / temp[m].length);
        }
    }

    var text;
    for (var n = 0; n < dataArray.length; n++) {
        text += "<td>" + dataArray[n] + "</td>"
    }

    var final = title + text + "</tr>";
    return final;

}



