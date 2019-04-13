;
(function () {
    $(document).ready(function () {
        //顶部菜单
        $(".top_nav .top_nav_ul .item").click(function () {
            var tmp_arr = $(this).attr('id').split('_');
            var menuid = tmp_arr[1];
            $(".top_nav .top_nav_ul .item").removeClass("on");
            $(this).addClass("on");
            $(".left_menu .left_menu_ul ").addClass("hide");
            $("#lm_" + menuid).removeClass("hide");
            expand(true);
        });
        //顶部按钮
        $(".message li a").click(function (e) {
            e.preventDefault();
            var url = $(this).attr("href");
            if (url && url != "#" && url != "javascript:void(0)") {
                $("#mainIframe").attr("src", url);
            }
        });
        //左侧菜单
        $(".left_menu .left_menu_ul li a").click(function (e) {
            e.preventDefault();
            var url = $(this).attr("href");
            if (url && url != "#" && url != "javascript:void(0)") {
                $(".left_menu .left_menu_ul li a").removeClass('active');
                $(this).addClass('active');
                $("#mainIframe").attr("src", url);
            }
            var $sub = $(this).parent().find(".left_li_ul");
            if ($sub.length >= 1) {
                if ($sub.is(':hidden')) {
                    $sub.show();
                    $(this).find(".arrow_spread").addClass("arrow_draw");
                } else {
                    $sub.hide();
                    $(this).find(".arrow_spread").removeClass("arrow_draw");
                }
            }
        });

        //左侧菜单
        $(".menu2").click(function (e) {
            e.stopPropagation();
            $(this).addClass("open");
        });
        $(document).click(function () {
            closeMenu();
        });

        function expand(isExpand) {
            if (isExpand) {
                $(".left_menu_btn").removeClass("left_menu_draw");
                $(".left_menu").removeClass("left_menu_none");
                $(".left_menu_list").show();
                $(".left_menu_td").width(220);
            } else {
                $(".left_menu_btn").addClass("left_menu_draw");
                $(".left_menu").addClass("left_menu_none");
                $(".left_menu_list").hide();
                $(".left_menu_td").width(10);
            }
        }
        $(".left_menu_btn").click(function (e) {
            expand($(this).hasClass("left_menu_draw"));
        });

        get_todu_count();
    });
    function closeMenu() {
        if ($(".menu2").hasClass("open")) {
            $(".menu2").removeClass("open");
        }
    }

    function setMainPageHeight(h) {
        $("#mainIframe").height(h);
    }

    function get_todu_count(){
        $.ajax({
            type : "GET",
            url : "?m=admin&c=index&a=public_todo_count",
            datatype : "html",
            async: false,
            success : function(data){  
                if(/^\d+$/.test(data)){
                    $(".badge").text(data);
                }
            },
            error : function(XMLHttpRequest, textStatus, errorThrown){
                alert('获取用户待办信息异常！');
                return false;
            }
        });
    }

    $("#mainIframe").load(function(){ 
        get_todu_count();
    }); 

    window.closeMenu = closeMenu;
    window.setMainPageHeight = setMainPageHeight;

})();