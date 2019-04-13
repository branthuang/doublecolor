<?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header');
?>

<div class="pad_10">
    <table cellpadding="2" cellspacing="1" class="table_form" width="100%">
        <form action="?m=admin&c=plan&a=edit" method="post" name="myform" id="myform">
            <input type="hidden" name="id" value="<?php echo $one['id']; ?>"/>
            <tr> 
                <th width="20%">方案名称</th>
                <td>
                    <input type='text' name='plan_name' value='<?php echo $one['plan_name']; ?>'/>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <table width="100%" cellspacing="0">
                        <?php foreach ($detail as $val) { ?>
                            <tr>
                                <td><?php
                                    $cols_num = string2array($val['cols_num']);
                                    $num = '';
                                    foreach ($cols_num as $v) {
                                        if ($num) {
                                            $num .= ',' . $v;
                                        } else {
                                            $num .= $v;
                                        }
                                    }
                                    ?>
                                    <input type='text' name='cols_num[<?php echo $val['id']; ?>]' value='<?php echo $num; ?>' style='width:600px;'/>
                                </td>
                                <td>
                                    <input type='text' name='count_min[<?php echo $val['id']; ?>]' value='<?php echo $val['count_min']; ?>'style='width:20px;'/>
                                    ~
                                    <input type='text' name='count_max[<?php echo $val['id']; ?>]' value='<?php echo $val['count_max']; ?>' style='width:20px;'/>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td></td>
                            <td>
                                <input type="submit" name="dosubmit" id="dosubmit" value=" <?php echo L('submit') ?> " > 
                                (Tips: 缩水号码用逗号分隔)
                            </td>
                        </tr>  
                    </table>
                </td>
            </tr>            
        </form>
    </table>
</div>
</body>
</html>