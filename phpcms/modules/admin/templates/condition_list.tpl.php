<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<style>
    .data_table{
        font-size: 14px;
    }
    .data_table li{float:left; padding: 5px;}
    .data_table .selected{
        font-size: 18px;
        font-weight: bold;

    }
    .data_table a{
        color: gray;
    }
    .data_table .selected a{
        color: green;
    }
</style>
<div class="pad-lr-10">
    <ul class="data_table" style="height:90px;background-color: white;padding:3px;">
        <?php foreach ($table_list as $key => $val) { ?>
            <li class="<?php if ($val['id'] == $table_id) { ?>selected<?php } ?>" style="height:20px;">
                <a href="?m=admin&c=data_source&a=condition_list&table_id=<?php echo $val['id']; ?>">
                    [ <?php echo $val['name']; ?> ]     
                </a>
            </li>
        <?php } ?>
    </ul>
    <br/><br/>
    <div class="con_box" style="padding:5px;height: 25px;">
        <button class="btn blue-btn icon-d" id="add_suoshui_condition" type="button" style='float:left; margin-right:5px;'>
            <span>添加</span>
        </button>
        
    </div>

    <form name="myform" id="myform" action="?m=admin&c=data_source" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th rowspan="2">ID</th>
                        <th colspan="33">排序号</th> 
                        <th rowspan="2">操作</th>
                    </tr>
                    <tr>
                        <?php for ($i = 1; $i <= 33; $i++) { ?>
                            <th class='add_suoshui'><?php echo $i; ?></th> 
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($list as $r) {
                        ?>
                        <tr>
                            <td><?php echo $r['id'];?></td>
                            <?php 
                            $col_min = $r['col_min'];
                            $col_max = $r['col_max'];
                            $col_max = ($col_max >= $col_min) ? $col_max : $col_min;
                            if($col_min <= 0 || $col_max <= 0){continue;}
                            ?>
                            <?php for ($i = 1; $i < $col_min; $i++) { ?>
                                <td></td>
                            <?php } ?>
                                <td colspan="<?php 
                                $colspan = $col_max-$col_min+1;
                                echo $colspan;  ?>" style="text-align: center;background-color:greenyellow">
                                <?php echo $r['count_min'].'~'.$r['count_max'];?>
                                </td>
                            <?php for ($i = $col_max+1; $i <= 33; $i++) { ?>
                                <td></td>
                            <?php } ?>
                                <td>
                                    <a href="###" id="edit_suoshui_condition<?php echo $r['id'];?>" class="edit_suoshui_condition">编辑</a> | 
                                    <a href="?m=admin&c=data_source&a=c_delete&id=<?php echo $r['id']; ?>&data_table_id=<?php echo $table_id;?>" onclick="if(!confirm('删除不可恢复，确认删除么？')){return false;}">删除</a>
                                </td>
                        </tr>
                    <?php } ?>
                        
                </tbody>
            </table>

        </div>

    </form></div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#add_suoshui_condition').click(function(){
            window.top.art.dialog(
                    {
                        id: 'suoshui',
                        iframe: '?m=admin&c=data_source&a=suoshui&table_id=<?php echo $table_id; ?>&pc_hash=<?php echo $_SESSION['pc_hash'] ?>',
                        title: '缩水设置',
                        width: '500',
                        height: '200',
                        okVal: '提交'
                    }, function () {
                var d = window.top.art.dialog({id: 'suoshui'}).data.iframe;// 使用内置接口获取iframe对象
                var form = d.document.getElementById('dosubmit');
                form.click();
                window.top.$('.aui_buttons button.aui_state_highlight').attr('disabled', 'disabled');
                return false;
            }, function () {
                window.top.art.dialog({id: 'suoshui'}).close();
            });
        });
        $('.edit_suoshui_condition').click(function(){
            var id = this.id.substr(22);
            window.top.art.dialog(
                    {
                        id: 'suoshui',
                        iframe: '?m=admin&c=data_source&a=c_edit&id='+id+'&pc_hash=<?php echo $_SESSION['pc_hash'] ?>',
                        title: '缩水设置',
                        width: '500',
                        height: '200',
                        okVal: '提交'
                    }, function () {
                var d = window.top.art.dialog({id: 'suoshui'}).data.iframe;// 使用内置接口获取iframe对象
                var form = d.document.getElementById('dosubmit');
                form.click();
                window.top.$('.aui_buttons button.aui_state_highlight').attr('disabled', 'disabled');
                return false;
            }, function () {
                window.top.art.dialog({id: 'suoshui'}).close();
            });
        });
    }); 
</script>
</body>
</html>