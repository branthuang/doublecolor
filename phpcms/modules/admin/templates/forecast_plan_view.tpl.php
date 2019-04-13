<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<div class="pad-lr-10">
    <div class="con_box search_box clearfix">
        <form name="search_form" id="search_form" action="?m=admin&c=forecast_plan&a=view" method="POST">
            <input type="hidden" value="<?php echo $id;?>" name="id">
            <input type="hidden" value="1" name="search">
            <table class="search_tab">
                <tbody>
                    <tr>
                        <td style="font-weight:bold;">方案： <?php echo $one['name'];?>   </td>
                        <td class="search_tdLf" width="20%"> 期号：</td>
                        <td width="23%">                      
                            <select name="issue_num">
                                <?php foreach ($issue_num_arr as $val) { ?>
                                    <option value="<?php echo $val['issue_num'] ?>" <?php
                                    if ($issue_num == $val['issue_num']) {
                                        echo "selected";
                                    }
                                    ?>>
    <?php echo $val['issue_num'] ?></option>
<?php } ?>
                            </select>
                        </td>

                        <td>
                            <button class="btn blue-btn search_btn" type="button" onclick="$('#search_form').submit();" style="float:right;">查看
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input name="pc_hash" type="hidden" value="4rShwh"></form>
    </div>

    <form name="myform" id="myform" action="?m=admin&c=forecast_plan&a=download" method="post">
        <input type="hidden" value="<?php echo $id;?>" name="id" />
        <input type="hidden" value="<?php echo $issue_num;?>" name="issue_num" />
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="70%">缩水号码</th>
                        <th>操作</th>
                    </tr>
                    
                </thead>
                <tbody>
                    <?php
                    foreach ($list as $val) {
                        ?>
                        <tr>
                            <td>
                                
                                <?php
                                $line = add_zero($val['num1'])
                                        .'	'.add_zero($val['num2'])
                                        .'	'.add_zero($val['num3'])
                                        .'	'.add_zero($val['num4'])
                                        .'	'.add_zero($val['num5'])
                                        .'	'.add_zero($val['num6']);
                                echo $line;
                                ?>
                            </td>
                            <td>
                                <a href="?m=admin&c=forecast_plan&a=del_plan_detail&id=<?php echo $val['plan_detail_id'];?>&plan_id=<?php echo $id;?>"  onclick="if(!confirm('删除无法恢复，确认删除么?')){return false;}">删除</a>
                            </td>
                        </tr>
<?php } ?>

                </tbody>
            </table>
        </div>
        <button class="btn green-btn icon-d" type="button" style='float:right; margin-right:5px;'
                onclick="form_submit()">
            <span>导出</span>
        </button>
    </form></div>

<script type="text/javascript">
    function form_submit() {
        $('#myform').submit();
    }
</script>
</body>
</html>