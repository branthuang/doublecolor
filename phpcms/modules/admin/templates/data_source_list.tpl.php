<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>

<div class="pad-lr-10">
    <ul class="data_table" style="height:90px;background-color: white;padding:3px;">
        <?php foreach ($table_list as $key => $val) { ?>
            <li class="<?php if ($val['id'] == $table_id) { ?>selected<?php } ?>" style="height:20px;">
                <a href="?m=admin&c=data_source&a=init&table_id=<?php echo $val['id']; ?>">
                    [ <?php echo $val['name']; ?> ]     
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="###" onclick="add_source_table()" title="点击加表">&nbsp;&nbsp;&nbsp;&nbsp;[+]</a>
        </li>
    </ul>
    <div class="con_box" style="padding:5px;height: 25px;">
        <button class="btn green-btn icon-d" type="button" style='float:left; margin-right:5px;'
                onclick="show_import_iframe('导入');">
            <span title="说明：导入会更新现有期号数据！">排序表导入</span>
        </button>
        <button class="btn blue-btn icon-d" type="button" style='float:left; margin-right:5px;'
                onclick="location.href='?m=admin&c=data_source&a=condition_list&table_id=<?php echo $table_id; ?>'">
            <span>缩水条件管理</span>
        </button>
        <button class="btn gray-btn icon-d" type="button" style='float:left;'
                onclick="clean_table();">
            <span>清空排序表</span>
        </button>
        <div style="float:right;background-color:gray;padding:2px;color:white;cursor:pointer;" class='condition_show'>
        缩水条件：<a>显示/隐藏</a>    
        </div>
        
    </div>

    <form name="myform" id="myform" action="?m=admin&c=data_source" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th align="center" rowspan="2">期号</th>
                        <th colspan="6" rowspan="2">中奖号码</th>
                        <th colspan="33">排序号</th> 
                    </tr>
                    <tr>
                        <?php for ($i = 1; $i <= 33; $i++) { ?>
                            <th class='add_suoshui'><?php echo $i; ?></th> 
                        <?php } ?>
                    </tr>
                    
                        <?php
                    foreach ($suoshui_list as $r) {
                        ?>
                        <tr class='suoshui_condition' style='display: none;'>
                            <td colspan="7"></td>
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
                        </tr>
                    <?php } ?>                        
                </thead>
                <tbody>
                    <?php
                    foreach ($list as $r) {
                                $zhong_arr = array();
                        ?>
                        <tr>
                            <td align="center"><?php echo $r['issue_num']; ?></td>
                            <?php if($win_num_arr[$r['issue_num']]){ ?>
                                <?php 
                                for ($i = 1; $i <= 6; $i++) { 
                                    $zhong_arr[] = $win_num_arr[$r['issue_num']]['num'.$i];
                                    ?>
                                    <td align="center" style="color:red;"><?php echo $win_num_arr[$r['issue_num']]['num'.$i]; ?></td>
                                <?php } ?>
                            <?php }else{ ?>
                                <?php for ($i = 1; $i <= 6; $i++) { ?>
                                    <td align="center"></td>
                                <?php } ?>
                            <?php } ?>
                            <?php for ($i = 1; $i <= 33; $i++) { ?>
                                <td align="center" <?php if(in_array($r['col'.$i],$zhong_arr)){?>style="color:white;background-color: red;"<?php }?>><?php echo $r['col'.$i];?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>

    </form></div>

<script type="text/javascript">
    function add_source_table() {
        window.top.art.dialog(
                {
                    id: 'batch_picker',
                    iframe: '?m=admin&c=data_table&a=add',
                    title: '添加表',
                    width: '350',
                    height: '50'
                }, function () {
            var d = window.top.art.dialog({id: 'batch_picker'}).data.iframe;// 使用内置接口获取iframe对象                    
            var data_table_name = d.$("input[id='data_table_name']").val();
            if (!data_table_name) {
                alert('请输入表名');
                return false;
            }
            d.$('#data_table_form').submit();

            //parent.right.location.href = '?m=admin&c=data_source&table_id=';
            //window.top.art.dialog({id: 'batch_picker'}).close();
            return false;
        }, function () {
            window.top.art.dialog({id: 'batch_picker'}).close();
        });
    }
    
    function show_import_iframe(title) {
        window.top.art.dialog(
                {
                    id: 'excel_import',
                    iframe: '?m=admin&c=data_source&a=excel_import&table_id=<?php echo $table_id; ?>&pc_hash=<?php echo $_SESSION['pc_hash'] ?>',
                    title: title,
                    width: '800',
                    height: '500',
                    okVal: '导入'
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
    //清空表
    function clean_table(){
        if(confirm("清空表不可恢复！确认清空？")){
            location.href="?m=admin&c=data_source&a=clean&table_id="+<?php echo $table_id?$table_id:0;?>;
        }
    }
    
    $(document).ready(function() {
        $('.add_suoshui').dblclick(function(){
            var begin_num = $(this).html();
            window.top.art.dialog(
                    {
                        id: 'suoshui',
                        iframe: '?m=admin&c=data_source&a=suoshui&table_id=<?php echo $table_id; ?>&begin_num='+begin_num+'&pc_hash=<?php echo $_SESSION['pc_hash'] ?>',
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
        
        $('.condition_show').click(function(){
            
            if($('.suoshui_condition').css('display') == 'none'){
                $('.suoshui_condition').show();
            }else{
                $('.suoshui_condition').hide();
            }
            
        });
    }); 
</script>
</body>
</html>