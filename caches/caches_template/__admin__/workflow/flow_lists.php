<?php defined('IN_PHPCMS') or exit('No permission resources.'); ?><?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header', 'admin');?>
<div class="pad_10">
<form name="myform" action="" method="post">
<div class="table-list">
    <table width="100%" cellspacing="0">
        <thead>
		<tr>
		<th width="10%">应用</th>
		<th width="25%">流程名</th>
		<th width="100">流程标识</th>
		<th>简介</th>
		<th width="100">管理操作</th>
		</tr>
        </thead>
        <tbody style="text-align:left">
		<?php foreach($flows as $flow) { ?>
		<tr>
			<td><?php echo $apps[$flow['appid']]['appname'];?></td>
			<td><?php echo $flow['workname'];?></td>
			<td><?php echo $flow['action'];?></td>
			<td><?php echo $flow['description'];?></td>
			<td align="center" style="width:200px">
			<a href="?m=workflow&c=workflow&a=node_lists&flowid=<?php echo $flow['id']?>">配置流程节点</a> 
			<a href="javascript:window.top.art.dialog({id:'edit',iframe:'?m=workflow&c=workflow&a=flow_edit&flowid=<?php echo $flow['id']?>', title:'修改流程', width:'500', height:'250'}, function(){var d = window.top.art.dialog({id:'edit'}).data.iframe;var form = d.document.getElementById('dosubmit');form.click();return false;}, function(){window.top.art.dialog({id:'edit'}).close()});void(0);">修改</a> 
			<a href="?m=workflow&c=workflow&a=flow_delete&flowid=<?php echo $flow['id']?>">删除</a> 
			</td>
		</tr>
		<?php }	?>
		</tbody>
	</table>
</div>
</form>
</div>
			
<script type="text/javascript">
<!--
function add(name,action) {
	window.top.art.dialog({id:'add'}).close();
	window.top.art.dialog({title:name,id:'add',iframe:'?m=workflow&c=workflow&a=append&action='+action,width:'500',height:'320'}, function(){var d = window.top.art.dialog({id:'add'}).data.iframe;d.document.getElementById('dosubmit').click();return false;}, function(){window.top.art.dialog({id:'add'}).close()});
}

//-->
</script>
</body>
</html>