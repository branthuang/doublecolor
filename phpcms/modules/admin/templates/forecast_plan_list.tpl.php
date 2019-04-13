<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header','admin');
?>
<div class="pad-lr-10">
<form name="myform" id="myform" action="?m=admin&c=forecast_plan&a=listorder" method="post">
<div class="table-list">
 <table width="100%" cellspacing="0">
        <thead>
            <tr>
            <th>方案</th>
            <th >添加时间</th> 
             <th width="200">操作</th>
            </tr>
        </thead>
    <tbody>
<?php
foreach($list as $r) {
?>
<tr>
<td align="center"><?php echo $r['name']?></td>
<td align="center"><?php echo date('Y-m-d H:i:s',$r['addtime']);?></td>
<td align="center">
    <a href="?m=admin&c=forecast_plan&a=view&id=<?php echo $r['id'] ?>">查看</a>
    | <a href="?m=admin&c=forecast_plan&a=delete&id=<?php echo $r['id'] ?>" onclick="if(!confirm('删除无法恢复，确认删除么?')){return false;}">删除</a> 
</td>
</tr>
<?php } ?>
</tbody>
 </table>
 </div>
<div id="pages"><?php echo $pages?></div>

</div>

</form></div>
</body>
</html>