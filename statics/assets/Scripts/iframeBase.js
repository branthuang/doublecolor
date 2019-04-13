(function () {
    /*create by PSHQ 简单tab*/
    function simpleTabs(opt) {
        //必要的
        this.$el = opt.$el;
        this.onClass = opt.onClass;
        this.normalClass = opt.normalClass;//on normal 是互斥的
        this.containClass = opt.containClass;//containClass的个数是onClass和normalClass的总和
        //end

        this.hoverClass = opt.hoverClass;
        this.onClick = opt.onClick;// this.onClick=function(itemEl,conEl){}
        this._index = 0;
        this._ini();
    }
    simpleTabs.prototype.isSelect = function (el) {
        var me = this;
        if ($(el).hasClass(me.onClass)) return true;
        else return false;
    }
    simpleTabs.prototype._ini = function () {
        var me = this;
        $items = me.$el.find(".nav-tabs").find(" ." + me.normalClass + ",." + me.onClass);

        $items.mouseenter(function () {
            if (me.isSelect(this)) return;
            $(this).addClass(me.hoverClass);
        }).mouseleave(function () {
            $(this).removeClass(me.hoverClass);
        }).click(function () {
            for (var i = 0; i < $items.length; i++) {
                if (this == $items[i]) { me._index = i; break; }
            }
            me.selectIndex(me._index);
        });
        //当前选中的序号
        var i = 0;
        me.$el.find(" ." + me.normalClass + ",." + me.onClass).each(function (x) {
            if ($(this).hasClass(me.onClass)) {
                i = x;
                return false;
            }
        });
        me._index = i;
        me.selectIndex(i);
    }
    simpleTabs.prototype.selectIndex = function (index) {
        var me = this;
        $items = me.$el.find(".nav-tabs").find(" ." + me.normalClass + ",." + me.onClass);
        var item = $items.get(index);
        if (item) {
            me._index = index;
            $(item).removeClass(me.normalClass).removeClass(me.hoverClass).addClass(me.onClass);

            for (var i = 0; i < $items.length; i++) {
                if (i == index) $($items[i]).removeClass(me.normalClass).addClass(me.onClass);
                else $($items[i]).removeClass(me.onClass).addClass(me.normalClass);
            }

            var $con = $("." + me.containClass);
            for (var i = 0; i < $con.length; i++) {
                if (i == index) $($con[i]).show();
                else $($con[i]).hide();
            }
            if (me.onClick) {
                me.onClick(item, $con[me._index]);
            }
        }
    }
    window.simpleTabs = simpleTabs;
})();

;(function () {
    function setParentHeight() {
        if (window.parent && window.parent.setMainPageHeight) {
            var h = $('.bg-iframe').height() + 40;
            $("body").off("resize");
            window.parent.setMainPageHeight(h);
            setTimeout(function () {
                $("body").resize(setParentHeight);
            }, 550);
        } 
    }
    $(document).ready(function () {
        $(document).click(function () {
            if (window.parent && window.parent.closeMenu) {
                window.parent.closeMenu();
            }
        });
        //
        setParentHeight();
        $("body").resize(function () {
            setParentHeight();
        })
        //搜索框
        if ($.fn.placeholder) {
            //$(".search-input").placeholder();
            $("input[type=text],textarea").each(function () {
                var $el = $(this);
                var ph = $el.attr("placeholder");
                if (ph) {
                    $el.placeholder();
                }
            })
        }
        //页签
        $(".tabtable").each(function () {
            var el = this;
            new simpleTabs({
                onClass: 'active', normalClass: 'normal', containClass: 'tab-pane', hoverClass: 'hover', $el: $(el),
                onClick: function () {
                    setParentHeight();
                }
            });
        });
        if (window.top.go_top) {
            var isDialog = false;
            var thisUrl=window.location.href;
            $(window.top.document).find("iframe").each(function () {
                var iframeUrl=$(this).attr("src")
                if (thisUrl.indexOf(iframeUrl) != -1) {
                    if ($(this).parent().hasClass("aui_content")) {
                        isDialog = true;
                        return false;
                    }
                }
            });
            if (!isDialog) {
                window.top.go_top();
            }
        }

        var $con = $(".bg-iframe .content");
        if ($con.length <= 0) {
            $(".bg-iframe").css("padding", "0px 15px");
        }

        //IE7特殊处理
        if ($.browser.msie && ($.browser.version == "7.0")) {
            $(".con_table tbody tr:odd").addClass("con_tabOdd");
            $(".table-list tbody tr:odd").addClass("even");
        }
        //IE8特殊处理
        if ($.browser.msie && ($.browser.version == "8.0")) {
            $(".con_table tbody tr:odd").addClass("con_tabOdd");
            $(".table-list tbody tr:odd").addClass("even")
        }
    });

    window.setParentHeight = setParentHeight;
})();