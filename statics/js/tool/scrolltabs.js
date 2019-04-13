/*create by PANSHQ 
依赖于ScrollBanner.js,签iframe,动态加载
*/
(function () {
    function scrolltabs(op) {
        this.wrapId = op.wrapId;
        //this.sbop = op.sbop;
        this.useIframe = op.useIframe;
        this.data = op.data;//[{ "param": { "param1": "1", "param2": "test" }, "title": "test title" }, { "param": "test2.html", "title": "test2 title" }]

        if (this.useIframe === undefined) this.useIframe = true;
        if (!this.data) this.data = [];

        this.tabclickHandle = op.tabclickHandle;//

        this._sb = null;
        this._ini();
    }

    scrolltabs.prototype._ini = function () {
        var me = this;
        //模版
        var tpl = '<div class="tabbable tab-fresh"><div id="{sb_tab_warp_id}" class="sb_tab_warp"><div class="borderline"></div><div class="sb_tab"><ul class="nav nav-tabs bannercls clearfix">{lisHtml}</ul></div><div class="arrows"><a href="javascript:void(0);" class="arrows_left"><i class="icon-arrow-left icon-on-left"></i></a><a href="javascript:void(0);" class="arrows_right"><i class="icon-arrow-right icon-on-right"></i></a></div></div><div class="tab-content">{tabpaneHtml}</div></div>';
        var litpl = '<li class="sb_cell"><a id="{navtabid}" data-toggle="tab" data-target="#{tabpaneid}" data-src="{src}">{title}</a><i class="icon-remove">×</i></li>'
        var panetpl = '<div id="{tabpaneid}" class="tab-pane"></div>';
        var str = "", listr = "", panestr = "";
        for (var i = 0; i < me.data.length; i++) {
            var item = me.data[i];
            var tabpaneid = "tabpaneid_" + i;
            var navtabid = "navtab" + i;
            listr += litpl.replace("{navtabid}", navtabid).replace("{src}", item.src).replace("{title}", item.title).replace("{tabpaneid}", tabpaneid);
            panestr += panetpl.replace("{tabpaneid}", tabpaneid);
        }
        var sb_tab_warp_id = me.wrapId + "_sb_tab_warp";
        str = tpl.replace("{lisHtml}", listr).replace("{tabpaneHtml}", panestr).replace("{sb_tab_warp_id}", sb_tab_warp_id);
        $("#" + me.wrapId).html(str);
        $("#" + sb_tab_warp_id + " li.sb_cell>a").each(function (i) {
            $(this).data("param", me.data[i].param);
        });
        //me.sbop.wrapId = me.wrapId;
        me._sb = new ScrollBanner({
            wrapId: sb_tab_warp_id,
            bannerCls: 'bannercls',
            arrowLNormalCls: "arrows_left", arrowRNormalCls: "arrows_right",
            arrowLFocusCls: "arrows_hover", arrowRFocusCls: "arrows_hover", isAutoHideBtn: true,
            showCount: 7, picCellCls: "sb_cell", type: 1
        });

        $("#" + me.wrapId + " .nav-tabs").click(function (e) {
            var $target = $(e.target);
            //关闭事件
            if (e.target.tagName.toUpperCase() == "I") {
                var $p = $target.parent();//li
                if ($p.parent().children("li").length <= 1) return;//只剩一个则不关闭
                var $near = null;
                if ($p.hasClass("active")) {
                    var $next = $p.next();
                    if ($next.length >= 1) {
                        $near = $next;
                    } else {
                        var $prev = $p.prev();
                        if ($prev.length >= 1) {
                            $near = $prev;
                        }
                    }
                    //if ($near) $near.children("a").click();
                }
                var str = $p.children("a").attr("data-target");
                $(str).remove();
                $p.remove();
                if ($near) {
                    var id = $near.children("a").attr("id");
                    me.activeTab(id);
                }
            }
            //单击事件
            if (e.target.tagName.toUpperCase() == "A") {
                var str = $target.attr("data-target");
                var $pane = $(str);
                //页签切换
                var $a = $(e.target);
                $a.parent().parent().children("li").removeClass("active");
                $a.parent().addClass("active");
                $pane.parent().children(".tab-pane").hide();
                $pane.show();

                if (me.useIframe && $pane.children("iframe").length <= 0) {//动态添加iframe
                    var src = $target.attr("data-src");
                    $pane.append('<iframe class="iframe" src="' + src + '"></iframe>');
                } else if (!me.useIframe) {
                    if (me.tabclickHandle) me.tabclickHandle(e.target);
                }
            }
        });

        //选择默认页卡
        var $id = $(location.hash), id = $id.length >= 1 ? $id.attr("id") : "";
        me.activeTab(id);
    }
    scrolltabs.prototype.closeTab = function (index) {

    }
    scrolltabs.prototype.activeTab = function (id) {//ul.nav-tabs>li>a
        var me = this;
        if (!id) {
            $("#" + me.wrapId + " .nav-tabs>li:first-child>a").click();
        } else {
            var $a = $("#" + id);
            var index = $a.parent().prevAll().length;
            //var showSbIndex = parseInt(index / me._sb.showCount);
            me._sb.showCurrten(index);
            $a.click();
        }
    }

    window.scrolltabs = scrolltabs;
})();