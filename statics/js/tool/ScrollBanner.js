/*create by 潘生全 
*/
(function () {
    function ScrollBanner(op) {
        this.wrapId = op.wrapId;
        this.arrowLNormalCls = op.arrowLNormalCls;
        this.arrowRNormalCls = op.arrowRNormalCls;
        this.picCellCls = op.picCellCls;
        this.showCount = op.showCount || 4;

        this.arrowLFocusCls = op.arrowLFocusCls;
        this.arrowRFocusCls = op.arrowRFocusCls;
        this.type = op.type;//空或者0表示没有动画效果，1:水平方向滚动，2:垂直方向滚动
        this.bannerCls = op.bannerCls || "bannercls"//除去按钮后的外层class
        this.isAutoHideBtn = op.isAutoHideBtn || false;//是否智能影藏上一页，下一页按钮
        this.changeFn = op.changeFn;
        this.afterAnimateFn = op.afterAnimateFn;
        this.isUseMousewheel = op.isUseMousewheel;//针对于type=2
        if (this.isUseMousewheel === undefined) this.isUseMousewheel = true;
       
        this._currtenpicIndex = 0;
        this._CAN_CHANGE_PAGE = true;
        var select = "#" + this.wrapId + " ."+this.bannerCls+">." + this.picCellCls;
        this.$picCell = $(select);
        this._ini();
    }

    ScrollBanner.prototype._ini = function () {
        var me = this;
        if (me.isAutoHideBtn && me.showCount >= me.$picCell.length) $("#" + me.wrapId + " ." + me.arrowLNormalCls + ",#" + me.wrapId + " ." + me.arrowRNormalCls).hide();
        //left arrow
        var select = "#" + me.wrapId + " ." + me.arrowLNormalCls;
        $(select).mouseenter(function () {
            if(!isLeft()){
                $(this).addClass(me.arrowLFocusCls);
            }
        }).mouseleave(function () {
            $(this).removeClass(me.arrowLFocusCls);
        }).click(function () {
            var newCurrtenIndex = me._currtenpicIndex - me.showCount;
            if (newCurrtenIndex >= 0) {
                me.showCurrten(newCurrtenIndex);
            }
            if (isLeft()) {
                $(this).removeClass(me.arrowLFocusCls);
            }
        });
        //right arrow
        select = "#" + me.wrapId + " ." + me.arrowRNormalCls;
        $(select).mouseenter(function () {
            if (!isRight()) {
                $(this).addClass(me.arrowRFocusCls);
            }
        }).mouseleave(function () {
            $(this).removeClass(me.arrowRFocusCls);
        }).click(function () {
            var newCurrtenIndex = me._currtenpicIndex + me.showCount;
            if (newCurrtenIndex <= me.$picCell.length - 1) {
                me.showCurrten(newCurrtenIndex);
            }
            if (isRight()) {
                $(this).removeClass(me.arrowRFocusCls);
            }
        });

        function isLeft() {
            var newCurrtenIndex = me._currtenpicIndex - me.showCount;
            if (newCurrtenIndex >= 0) {
                return false;
            }
            return true;
        }
        function isRight() {
            var newCurrtenIndex = me._currtenpicIndex + me.showCount;
            if (newCurrtenIndex <= me.$picCell.length - 1) {
                return false;
            }
            return true;
        }

        if (me.type == 2 && me.isUseMousewheel && $(document).mousewheel) {
            //鼠标滚动事件
            $(document).mousewheel(function (event, delta) {
                var currentIndex = me.getCurrentIndex();
                if (delta < 0) {//向下
                    currentIndex++;
                } else {//向上
                    currentIndex--;
                }
                me.showCurrten(currentIndex);
            });
        }
        else if (me.type == 0) {
            $(document).keydown(function (event) {
                var currentIndex = me.getCurrentIndex();
                if (event.which == 39) {//右
                    currentIndex++;
                } else if (event.which == 37) {
                    currentIndex--;
                }
                me.showCurrten(currentIndex);
            })
        }
    }
    ScrollBanner.prototype.showCurrten = function (newCurrtenIndex) {
        var me = this;
        if (me._currtenpicIndex == newCurrtenIndex) return;
        if (newCurrtenIndex < 0) return;
        if (newCurrtenIndex > me.$picCell.length - 1) return;

        if (!me._CAN_CHANGE_PAGE) return;
        me._CAN_CHANGE_PAGE = false;

        //左或上为0，右或下为1
        var direction = newCurrtenIndex > me._currtenpicIndex ? 1 : 0;
        if (me.type==null||me.type===undefined) {//!me.type || me.type == 0
            for (var i = newCurrtenIndex; i <= newCurrtenIndex + me.showCount && i < me.$picCell.length; i++) {
                $(me.$picCell.get(i)).show();
            }
            for (var i = 0; i < newCurrtenIndex && i < me.$picCell.length; i++) {
                $(me.$picCell.get(i)).hide();
            }
            for (var i = newCurrtenIndex + me.showCount; i < me.$picCell.length; i++) {
                $(me.$picCell.get(i)).hide();
            }
            me._CAN_CHANGE_PAGE = true;
        } else if (me.type == 0) {
            me.$picCell.eq(newCurrtenIndex).addClass("active").fadeIn(600, function () {
                me._CAN_CHANGE_PAGE = true;
            });
            me.$picCell.eq(me._currtenpicIndex).removeClass("active").fadeOut(300);
           
        }else if (me.type == 1) {
            //var w = me.$picCell.eq(1).outerWidth(true);
            //$bannercls = $("#" + me.wrapId + " ." + me.bannerCls);
            //var marginLeft = window.parseInt($bannercls.css("margin-left"));
            //var sw = w * me.showCount;// - singleleft;
            //if (direction == 1) sw = -sw;
            //$bannercls.animate({
            //    marginLeft: marginLeft + sw
            //}, function () {
            //    if (me.afterAnimateFn) {
            //        me.afterAnimateFn();
            //    }
            //    me._CAN_CHANGE_PAGE = true;
            //});
            var newCurrtenIndex = window.parseInt(newCurrtenIndex / me.showCount) * me.showCount;
            $bannercls = $("#" + me.wrapId + " ." + me.bannerCls);
            var sw = me.$picCell.eq(1).outerWidth(true);// $("#" + me.wrapId).outerWidth(true);
            var marginLeft = window.parseInt($bannercls.css("margin-left"));
            sw = (me._currtenpicIndex - newCurrtenIndex) * sw;
            $bannercls.animate({
                marginLeft: marginLeft + sw
            }, function () {
                if (me.afterAnimateFn) {
                    me.afterAnimateFn();
                }
                me._CAN_CHANGE_PAGE = true;
            });
        } else if (me.type == 2) {
            $bannercls = $("#" + me.wrapId + " ." + me.bannerCls);
            var sw = $("#" + me.wrapId).outerHeight(true);
            var marginTop = window.parseInt($bannercls.css("margin-top"));
            sw = (me._currtenpicIndex - newCurrtenIndex) * sw;
            $bannercls.animate({
                marginTop: marginTop + sw
            }, function () {
                if (me.afterAnimateFn) {
                    me.afterAnimateFn();
                }
                me._CAN_CHANGE_PAGE = true;
            });
        }
        me._currtenpicIndex = newCurrtenIndex;
        if (me.changeFn) me.changeFn(newCurrtenIndex);
    }
    ScrollBanner.prototype.getCurrentIndex = function () {
        return this._currtenpicIndex;
    }
    ScrollBanner.prototype.isStart = function () {
        return this._currtenpicIndex == 0;
    }
    ScrollBanner.prototype.isEnd = function () {
        return this._currtenpicIndex == this.$picCell.length - 1;
    }
    ScrollBanner.prototype.next = function () {
        var me = this;
        me.showCurrten(me.getCurrentIndex() + 1);
    }
    window.ScrollBanner = ScrollBanner;
})();