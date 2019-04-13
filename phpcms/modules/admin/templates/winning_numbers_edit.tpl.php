<?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header');
?>
<script type="text/javascript">

</script>

<div class="pad_10">
    <table cellpadding="2" cellspacing="1" class="table_form" width="100%">
        <form action="?m=admin&c=winning_numbers&a=<?php echo $_GET['a'] ;?>" method="post" name="myform" id="myform">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>"/>
            <tr> 
                <th width="20%">期号</th>
                <td><input type="text" name="issue_num"  value="<?php echo $r['issue_num']; ?>"></td>
            </tr>
            <tr> 
                <th width="20%">号码1</th>
                <td><input type="text" name="num1"  value="<?php echo $r['num1']; ?>"></td>
            </tr>
            <tr> 
                <th width="20%">号码2</th>
                <td><input type="text" name="num2"  value="<?php echo $r['num2']; ?>"></td>
            </tr>
            <tr> 
                <th width="20%">号码3</th>
                <td><input type="text" name="num3"  value="<?php echo $r['num3']; ?>"></td>
            </tr>
            <tr> 
                <th width="20%">号码4</th>
                <td><input type="text" name="num4"  value="<?php echo $r['num4']; ?>"></td>
            </tr>
            <tr> 
                <th width="20%">号码5</th>
                <td><input type="text" name="num5"  value="<?php echo $r['num5']; ?>"></td>
            </tr>
            <tr> 
                <th width="20%">号码6</th>
                <td><input type="text" name="num6"  value="<?php echo $r['num6']; ?>"></td>
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