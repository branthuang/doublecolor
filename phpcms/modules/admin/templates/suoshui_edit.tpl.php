<?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header', 'admin');
?>
    <link href="<?php echo B_CSS_PATH ?>page/upload.css" type="text/css" rel="stylesheet">
    <link href="<?php echo B_CSS_PATH ?>form.css" type="text/css" rel="stylesheet">
        <div class="upl">
            <div class="upl-h">设置范围</div>
            <form name="import" action="?m=admin&c=data_source&a=c_edit"
                  method="post" id="import">
                <input type="hidden" name="id" value="<?php echo $id;?>" />
                <table border="1">
                    <tr>
                        <td class="select-d">
                                缩水条件：
                        </td>
                        <td class="select-d">
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="select-d">
                                <input type='text' name='col_min' value='<?php echo $result['col_min'];?>'/>  
                        </td>
                        <td>~</td>
                        <td class="select-d">
                                <input type='text' name='col_max' value='<?php echo $result['col_max'];?>'/>
                        </td>
                    </tr>
                    <tr>
                        <td class="select-d">
                                出号范围：
                        </td>
                        <td class="select-d">
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="select-d">
                                <input type='text' name='count_min' value='<?php echo $result['count_min'];?>'/>  
                        </td>
                        <td>~</td>
                        <td class="select-d">
                                <input type='text' name='count_max' value='<?php echo $result['count_max'];?>'/>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="dosubmit" id="dosubmit" value="导入数据" class="dialog"/>
            </form>
        </div>
    <script type="text/javascript">
        window.top.$('.aui_content').removeClass('aui_buttons_bg');
        window.top.$('.aui_buttons button.aui_state_highlight').removeAttr('disabled');
    </script>
<?php $is_dialog = true; ?>
<?php include $this->admin_tpl('footer', 'admin'); ?>