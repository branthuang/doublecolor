<?php defined('IN_PHPCMS') or exit('No permission resources.'); ?><?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_header=0;
include $this->admin_tpl('header', 'admin');?>
<script language="javascript" type="text/javascript" src="<?php echo JS_PATH;?>formvalidator.js" charset="UTF-8"></script>
<script language="javascript" type="text/javascript" src="<?php echo JS_PATH;?>formvalidatorregex.js" charset="UTF-8"></script>
<script type="text/javascript">
  $(document).ready(function() {
	$.formValidator.initConfig({autotip:true,formid:"myform",onerror:function(msg){}});
	$("#nodename").formValidator({onshow:"请输入名称",onfocus:"名称不能为空"}).inputValidator({min:1,max:50,onerror:"名称不能为空"});
	$("#nodeaction").formValidator({onshow:"请输入节点标识",onfocus:"节点标识不能为空"}).inputValidator({min:1,max:50,onerror:"节点标识不能为空"});
  })
</script>
<style>
.userinflow li{padding:6px;}
.user{padding:4px;background-color: #FAFCFD;border: 1px solid #92B0DD;border-radius: 5px;padding-left:24px;position: relative;}
.user img{position:absolute;left:4px;top:3px;}
.empty{border:none;padding:0px;border-radius:0px;background:none;}
.ui-state-highlight_user{height:24px;width:80px;display:inline-block;border: 1px solid #FCEFA1;background:#FBF9EE;}
</style>
<div class="pad_10">
	<div class="common-form">
		<form name="myform" action="" method="post" id="myform">
			<table width="100%" class="table_form contentWrap">
				<tr>
					<td>流程</td>
					<td><?php echo $flow['workname'];?></td>
				</tr>

				<tr>
					<td>节点名</td>
					<td><input name="nodename"  id="nodename" class="inputtext" type="text" value="<?php echo $node['nodename'];?>" /></td>
				</tr>

				<tr>
					<td>节点标识</td>
					<td><?php echo $flow['action'];?>.<input name="nodeaction"  id="nodeaction" class="inputtext" type="text" value="<?php echo $node['action'];?>" /></td>
				</tr>

				<tr>
					<td>节点类型</td>
					<td><select name="nodetype"  id="nodetype">
						<option value="2">人工节点</option>
						<option value="8">服务节点</option>
						</select>
					</td>
				</tr>
				
			</table>

			<input name="dosubmit" type="submit" value="下一步" class="button" id="dosubmit" style="display:none"></form>
	</div>
</div>
<script type="text/javascript">
<!--
function add(id,relid) {
	window.top.art.dialog({id:'add'}).close();
	window.top.art.dialog({title:'追加用户到流程:<?php echo $info['workname']?>',id:'add',iframe:'?m=workflow&c=workflow&a=append_user&id='+id+'&relid='+relid,width:'500',height:'150'}, function(){var d = window.top.art.dialog({id:'add'}).data.iframe;d.document.getElementById('dosubmit').click();return false;}, function(){window.top.art.dialog({id:'add'}).close()});
}
function _remove(relid){
	if(!confirm('你确定要删除这条记录吗')) return;
	alert(relid);
}
//-->
</script>
</body>
</html>