<?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header', 'admin');
?>
    <link href="<?php echo B_CSS_PATH ?>page/upload.css" type="text/css" rel="stylesheet">
    <link href="<?php echo B_CSS_PATH ?>form.css" type="text/css" rel="stylesheet">
        <div class="dl">
            <div class="dl-h">下载导入模版</div>
            <ul>
                <li>排序表模板
                    <input type="button" class="download" value="下载"
                           onclick="tem_download()"/>
                </li>
            </ul>
        </div>
        <div class="upl">
            <div class="upl-h">提交导入文件</div>
            <form name="import" action="?m=admin&c=data_source&a=excel_import"
                  method="post" id="import"
                  enctype="multipart/form-data">
                <input type="hidden" name="data_table_id" value="<?php echo $_GET['table_id'];?>" />
                <table>
                    <tr>
                        <td class="select-d" style="position:relative">
                            <div style="float:left">
                                <span class="select-file float" style="width:40px">选择</span>
                                <input type="text" style="height: 20px;" class="select-input input-text" id="excel_str"
                                       placeholder="请选择文件"/>
                            </div>
                            <div style="float:left;position:absolute">
                                <input type="file" onchange="excel_str.value=this.value"
                                       style="opacity: 0;height:30px;width:70px;filter:alpha(opacity=0);"
                                       id="upload_file" name="excel"/>
                            </div>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="dosubmit" id="dosubmit" value="导入数据" class="dialog"/>
            </form>
        </div>
    <script type="text/javascript">
        window.top.$('.aui_content').removeClass('aui_buttons_bg');
        window.top.$('.aui_buttons button.aui_state_highlight').removeAttr('disabled');
        function tem_download(){
            window.open("<?php echo APP_PATH;?>/uploadfile/tmp/template.xls");
        }
    </script>
<?php $is_dialog = true; ?>
<?php include $this->admin_tpl('footer', 'admin'); ?>
