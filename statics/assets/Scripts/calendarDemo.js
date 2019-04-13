function ini(year) {
    var date = new Date(year, 0, 1);
    $('#calOne').jCal({
        dow: ['日', '一', '二', '三', '四', '五', '六'],
        ml: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
        day: date,
        days: 1,
        showMonths: 12,
        isMultiSelect: true, isSelectWeekend: true
    });
    //确保左边的月份靠在最左
    var height = 0;
    var $list = $(".jCalMo");
    $list.each(function () {
        var item = this;
        var t = $(item).height();
        if (t > height) {
            height = t;
        }
    });
    $list.css("min-height", height);
    loadData(year);
    setParentHeight();
}

function selectWeekend() {
    var opt = $('#calOne').data('opt');
    var sDate = opt.day;// new Date(2015, 0, 1);
    //var eDate = new Date(2016, 0, 1);
    var nextYear = sDate.getFullYear() + 1;
    for (; ; sDate.setDate(sDate.getDate() + 1)) {
        if (sDate.getFullYear() >= nextYear) break;

        var year = sDate.getFullYear(),
            month = sDate.getMonth() + 1,
            date = sDate.getDate(),
            day = sDate.getDay();

        if (day == 6 || day == 0) {
            var strId = opt.cID + "d_" + month + "_" + date + "_" + year;//"c12d_"
            $("#" + strId).addClass("selectedDay");
        }
    }
}
function selectDays(days) {
    var opt = $('#calOne').data('opt');
    for (var i = 0; i < days.length; i++) {
        var arry = days[i].split("/");
        var strId = opt.cID + "d_" + parseInt(arry[1]) + "_" + parseInt(arry[2]) + "_" + arry[0];
        $("#" + strId).addClass("selectedDay");
    }
}
function loadData(year) {
    $.ajax({
        type: "post",
        url: "index.php?m=admin&c=holiday&a=load_data&pc_hash=" + $("#hash").val(),
        dataType: 'json',
        async: false,
        data: {"year": year},
        success: function (data) {
            if (data.data != '') {
            	var obj = JSON.parse(data.data);
                selectDays(obj.days);
            } else {
                selectWeekend();
            }
        }, error: function (data) {
            alert("加载失败！");
        }
    });
}


$(document).ready(function () {
    var year = $("#calOneDays").val();
    ini(year);
    $("#save").click(function () {
        var strs = '';
        $(".jCalMo .selectedDay").each(function () {
            var sday = this;
            var id = $(sday).attr("id"),
                arry = id.split('_');
            var daystr = arry[3] + '/' + arry[1] + '/' + arry[2];
            strs = strs + daystr + ';';
        });
        $.ajax({
            type: "post",
            url: "index.php?m=admin&c=holiday&a=add_data&pc_hash=" + $("#hash").val(),
            dataType: 'json',
            data: {"year": year, 'selected': strs},
            success: function (data) {
                if (data.data != '') {
                    var obj = JSON.parse(data.data);
                    selectDays(obj.days);
                } else {
                    selectWeekend();
                }
                alert('操作成功');
            }, error: function () {
                alert("操作失败！");
            }
        });
    })
    $("#calOneDays").change(function () {
        year = $("#calOneDays").val();
        ini(year);
    });
    $("#init").click(function () {
        if(confirm('初始化会清除所有非工作日记录，请确认是否执行？')){
            $.ajax({
                type: "post",
                url: "index.php?m=admin&c=holiday&a=init_data&pc_hash=" + $("#hash").val(),
                dataType: 'json',
                data: {"year": year},
                success: function (data) {
                	var obj = JSON.parse(data.data);
                    if (obj == '1') {
                        selectWeekend();
                        location.href = "index.php?m=admin&c=holiday&a=init&pc_hash=" + $("#hash").val();
                    }
                }, error: function () {
                    alert("初始化失败！");
                }
            });
        }
    });
});