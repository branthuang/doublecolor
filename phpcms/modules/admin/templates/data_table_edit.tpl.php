<?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header');
?>
<script type="text/javascript">

</script>

<div class="pad_10">
    <table cellpadding="2" cellspacing="1" class="table_form" width="100%">
        <form action="?m=admin&c=data_table&a=edit" method="post" name="myform" id="myform">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>"/>
            <tr> 
                <th width="20%">名称</th>
                <td><input type="text" name="name"  value="<?php echo $r['name']; ?>"></td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" name="dosubmit" id="dosubmit" value=" <?php echo L('submit') ?> " > 
                </td>
            </tr>            
        </form>
    </table>
</div>
</body>
</html>