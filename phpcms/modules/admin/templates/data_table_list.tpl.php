<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header','admin');
?>
<div class="pad-lr-10">
<form name="myform" id="myform" action="?m=admin&c=data_table&a=listorder" method="post">
<div class="table-list">
 <table width="100%" cellspacing="0">
        <thead>
            <tr>
            <th width="55" align="center"><?php echo L('listorder');?></th>
            <th>表名</th>
            <th >添加时间</th> 
             <th width="120">操作</th>
            </tr>
        </thead>
    <tbody>
<?php
foreach($list as $r) {
?>
<tr>
<td align="center"><input type="text" name="listorder[<?php echo $r['id']?>]" value="<?php echo $r['listorder']?>" size="3" class='input-text-c'></td>
<td align="center"><?php echo $r['name']?></td>
<td align="center"><?php echo date('Y-m-d',$r['addtime']);?></td>
<td align="center">
    <a href="?m=admin&c=data_table&a=edit&id=<?php echo $r['id'] ?>"><?php echo L('edit');?></a> 
    | <a href="?m=admin&c=data_table&a=delete&id=<?php echo $r['id'] ?>" onclick="if(!confirm('删除无法恢复，确认删除么?')){return false;}"><?php echo L('delete')?></a> 
</td>
</tr>
<?php } ?>
</tbody>
 </table>
 <div class="btn"><input type="submit" class="button" name="dosubmit" value="<?php echo L('listorder')?>" /></div>  </div>
<div id="pages"><?php echo $pages?></div>

</div>

</form></div>
</body>
</html>