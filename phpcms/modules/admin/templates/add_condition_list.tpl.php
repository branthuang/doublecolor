<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<div class="pad-lr-10">
    <div class="con_box search_box clearfix">
        <form name="search_form" id="search_form" action="?m=admin&c=plan&a=add_condition_list" method="POST">
            <input type="hidden" value="1" name="search">
            <input type="hidden" value="<?php echo $plan_id;?>" name="plan_id">
            <table class="search_tab">
                <tbody>

                    <tr>
                        <td class="search_tdLf" width="5%"> 期号：</td>
                        <td width="23%">                      
                            <select name="issue_num">
                                <?php foreach($issue_num_arr as $val){ ?>
                                <option value="<?php echo $val['issue_num']?>" <?php if($issue_num == $val['issue_num']){echo "selected";}?>>
                                    <?php echo $val['issue_num']?></option>
                                <?php } ?>
                            </select>
                        </td>
                        <td class="search_tdLf" width="8%">排序表：</td>
                        <td width="19%">
                            <select name="data_table_id">
                                <option value=''>请选择</option>
                                <?php foreach ($data_table as $val) { ?>
                                    <option value="<?php echo $val['id']; ?>" <?php if ($val['id'] == $data_table_id) {
                                    echo 'selected';
                                } ?>><?php echo $val['name']; ?></option>
<?php } ?>
                            </select>
                        </td>
                        <td>
                            <button class="btn blue-btn search_btn" type="button" onclick="$('#search_form').submit();" style="float:right;">搜索
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input name="pc_hash" type="hidden" value="4rShwh"></form>
    </div>

    <form name="myform" id="myform" action="?m=admin&c=plan&a=add_condition_list" method="post">
        <input type="hidden" value="<?php echo $plan_id;?>" name="plan_id">
        <input type="submit" value="1" name="dosubmit" id="dosubmit" style="diplay:hidden;">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>顺序号</th>
                        <th width="10%">排序表</th>
                        <th width="70%">缩水号码</th>
                        <th width="10%">出号范围</th>
                        <th width="5%">选择
                            <input type="checkbox" name="checkall" value="1" id="checkall" title="全选/取消"/>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $j = 0;
                    foreach ($list as $r) {
                        $j++;
                        ?>
                        <tr <?php if(in_array($r['condition_id'],$c_arr)){ ?>style="color:red"<?php }?>>
                            <td><?php echo $j;?></td>
                            <td><?php echo $tables[$r['data_table_id']] ?>-<?php echo $r['condition_id']?></td>
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
                                <?php if(!in_array($r['condition_id'],$c_arr)){ ?>
                                <input type="checkbox" class="check_option" name="condition_id[]" value="<?php echo $r['condition_id']; ?>"/>
                                <?php }?>
                                
                            </td>
                        </tr>
<?php } ?>

                </tbody>
            </table>

        </div>
        
    </form></div>

<script type="text/javascript">
    function form_submit(){
        $('#myform').submit();
    }
    $(function(){
        $('#checkall').click(function(){
            if(($("#checkall").attr("checked"))){
                $(".check_option").attr("checked", true);
            }else{
                $(".check_option").attr("checked", false);
            }
        });
    });
</script>