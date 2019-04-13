<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<div class="pad-lr-10">
    <div class="con_box search_box clearfix">
        <form name="search_form" id="search_form" action="?m=admin&c=plan&a=view" method="POST">
            <input type="hidden" value="<?php echo $id;?>" name="id">
            <input type="hidden" value="1" name="search">
            <table class="search_tab">
                <tbody>
                    <tr>
                        <td style="font-weight:bold;">方案： <?php echo $one['plan_name'];?>   </td>
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

    <form name="myform" id="myform" action="?m=admin&c=plan&a=download" method="post">
        <input type="hidden" value="<?php echo $id;?>" name="id" />
        <input type="hidden" name="issue_num" value="<?php echo $issue_num;?>" />
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">顺序号</th>
                        <th width="10%">排序表</th>
                        <th width="70%">缩水号码</th>
                        <th width="10%">出号范围</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $k = 0;
                    foreach ($list as $r) {
                        $k++;
                        ?>
                        <tr>
                            <td><?php echo $k;?></td>
                            <td><?php echo $tables[$r['data_table_id']] ?>-<?php echo $r['condition_id'] ?></td>
                            <td>
                                <?php
                                $col_min = $r['col_min'];
                                $col_max = $r['col_max'];
                                for ($i = $col_min; $i <= $col_max; $i++) {
                                    $cols_name = 'col' . $i;
                                    echo $r[$cols_name] . ' , ';
                                }
                                ?>
                            </td>
                            <td><?php echo $r['count_min'] . '~' . $r['count_max']; ?></td>
                            <td>
                                <a href="?m=admin&c=plan&a=del_plan_detail&id=<?php echo $r['plan_detail_id'];?>&plan_id=<?php echo $id;?>"  onclick="if(!confirm('删除无法恢复，确认删除么?')){return false;}">删除</a>
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
        
        <button class="btn green-btn icon-d" type="button" style='float:right; margin-right:5px;'
                onclick="add_condition();">
            <span title="说明：导入会更新现有期号数据！">新增缩水条件</span>
        </button>
    </form></div>

<script type="text/javascript">
    function form_submit() {
        $('#myform').submit();
    }
    function add_condition() {
        window.top.art.dialog(
                {
                    id: 'excel_import',
                    iframe: '?m=admin&c=plan&a=add_condition_list&plan_id=<?php echo $id; ?>&pc_hash=<?php echo $_SESSION['pc_hash'] ?>',
                    title: "缺水条件列表",
                    width: '800',
                    height: '500',
                    okVal: '新增'
                }, function () {
            var d = window.top.art.dialog({id: 'excel_import'}).data.iframe;// 使用内置接口获取iframe对象
            var form = d.document.getElementById('dosubmit');
            form.click();
            window.top.$('.aui_buttons button.aui_state_highlight').attr('disabled', 'disabled');
            return false;
        }, function () {
            window.top.art.dialog({id: 'excel_import'}).close();
        });
    }
</script>
</body>
</html>