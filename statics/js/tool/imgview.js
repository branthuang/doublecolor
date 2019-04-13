/*create by PANSHQ 
依赖于ScrollBanner.js
*/
(function () {
    function imgview(op) {
        this.wrapId = op.wrapId;
        this.data = op.data;//[{ "imgurl": "1.jpg" }, { "imgurl": "2.jpg" }]
        if (!this.data) this.data = [];

        this._sb = null;
        this._ini();
    }

    imgview.prototype._ini = function () {
        var me = this;
        var tpl = '<div class="imageview"><ul class="bannercls clearfix">{bigimghtml}</ul></div>   <a class="arrows_left_b"></a><a class="arrows_right_b"></a> <div class="thumbnail"><ul class="clearfix">{smallimghtml}</ul></div>';
        var bigimgtpl = '<li class="sb_cell {tmp_active}" style="{tmp_style}"><div><img src="{imgurl}" /><a href="{imgurl}" target="_blank" class="slideBtn"></a></div></li>';
        var smallimgtp = '<li class="{tmp_active}"><div><a><img src="{imgurl}"></a></div><div class="mask"></div></li>';

        var str = "", bigimgStr = "", smallimgStr = "";
        for (var i = 0; i < me.data.length; i++) {
            var item = me.data[i];
            var tmp_active = "", tmp_style = "";
            if (i == 0) { tmp_active = "active"; tmp_style = "display:block;"; }
            bigimgStr += bigimgtpl.replace(/\{imgurl\}/gi, item.imgurl).replace("{tmp_active}", tmp_active).replace("{tmp_style}", tmp_style);
            smallimgStr += smallimgtp.replace("{imgurl}", item.imgurl).replace("{tmp_active}", tmp_active)
        }
        str = tpl.replace("{bigimghtml}", bigimgStr).replace("{smallimghtml}", smallimgStr);
        $("#" + me.wrapId).addClass("imageview_warp").html(str);

        //滚动
        me._sb = new ScrollBanner({
            wrapId: me.wrapId, bannerCls: 'bannercls',
            arrowLNormalCls: "arrows_left_b", arrowRNormalCls: "arrows_right_b",
            arrowLFocusCls: "arrows_hover", arrowRFocusCls: "arrows_hover", isAutoHideBtn: false,
            showCount: 1, picCellCls: "sb_cell", type: 0,
            changeFn: function (newCurrtenIndex) {
                $("#" + me.wrapId + " .thumbnail li").removeClass("active").eq(newCurrtenIndex).addClass("active");
            }
        });
        //略缩图
        $("#" + me.wrapId + " .thumbnail").click(function (e) {
            var $li = null, $target = $(e.target);
            if (e.target.tagName.toLowerCase() == "li") $li = $target;
            else if ($target.parent().get(0).tagName.toLowerCase() == "li") $li = $target.parent();
            else if ($target.parent().parent().get(0).tagName.toLowerCase() == "li") $li = $target.parent().parent();
            else if ($target.parent().parent().parent().get(0).tagName.toLowerCase() == "li") $li = $target.parent().parent().parent();

            if (!$li) return;
            $("#" + me.wrapId + " .thumbnail li").each(function (i) {
                if (this == $li.get(0)) {
                    me._sb.showCurrten(i);
                    return false;
                }
            });
        });
        $("#" + me.wrapId + " .imageview>ul>li>div>img").click(function () {
            me._sb.next();
        });
    }

    window.imgview = imgview;
})();