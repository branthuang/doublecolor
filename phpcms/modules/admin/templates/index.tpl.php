<?php defined('IN_ADMIN') or exit('No permission resources.'); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>D & C</title>
        <link href="<?php echo CSS_PATH ?>dialog.css" rel="stylesheet" type="text/css"/>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>jquery.min.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>styleswitch.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>dialog.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>hotkeys.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>jquery.sgallery.js"></script>
        <!--附加资源-->
        <link href="<?php echo B_CSS_PATH ?>main.css" rel="stylesheet" type="text/css"/>

        <script type="text/javascript">
            var pc_hash = '<?php echo $_SESSION['pc_hash'] ?>'
        </script>
    </head>
    <body id="index_body">

        <table style="width:100%;">
            <tr>
                <td class="left_menu_td">
                    <!-- 左侧菜单 -->
                    <div class="left_menu">
                        <div class="left_menu_list">
                            <ul id="lm_10" class="left_menu_ul ">
                                <li>
                                    <a href="?m=admin&c=data_source&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>数据源排序</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="?m=admin&c=winning_numbers&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>开奖号码</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="?m=admin&c=condition&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>缩水条件表</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="?m=admin&c=plan&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>方案管理</span>
                                    </a>
                                </li>                                
                                <li>
                                    <a href="?m=admin&c=data_table&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>排序表管理</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="?m=admin&c=forecast&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>跟随6码</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="?m=admin&c=forecast_plan&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>跟随6码方案</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="?m=admin&c=yuce&a=init">
                                        <span class="menu_icon_approve"></span>
                                        <span>重邻隔胆组</span>
                                    </a>
                                </li>
                            </ul>

                        </div>
                        <a href="javascript:void(0)" class="left_menu_btn"></a>
                    </div>
                </td>
                <td class="tab-content" style="vertical-align: top;">
                    <div class="main">
                        <div class="contain">
                            <iframe name="right" id="mainIframe" allowtransparency="true" frameborder="0"
                                    src="?m=admin&c=data_source&a=init"></iframe>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <script type="text/javascript" src="<?php echo B_JS_PATH ?>base.js"></script>
        <script type="text/javascript" src="<?php echo B_JS_PATH ?>jquery.enplaceholder.js"></script>
    </body>
</html>
