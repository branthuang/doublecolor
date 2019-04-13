<?php defined('IN_PHPCMS') or exit('No permission resources.'); ?><?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_header=0;
include $this->admin_tpl('header', 'admin');?>
<script language="javascript" type="text/javascript" src="<?php echo JS_PATH;?>formvalidator.js" charset="UTF-8"></script>
<script language="javascript" type="text/javascript" src="<?php echo JS_PATH;?>formvalidatorregex.js" charset="UTF-8"></script>
<script type="text/javascript">
  $(document).ready(function() {
	$.formValidator.initConfig({autotip:true,formid:"myform",onerror:function(msg){}});
	$("#workname").formValidator({onshow:"请输入名称",onfocus:"名称不能为空"}).inputValidator({min:1,max:999,onerror:"名称不能为空"});
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
					<td colspan="2">
						<?php echo $last_node['nodename'];?>&nbsp;&nbsp;&nbsp;&nbsp;>>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $next_node['nodename'];?>
					</td>
				</tr>

				<tr>
					<td>路由名</td>
					<td>
						<input name="routename"  id="routename" class="inputtext" type="text" value="<?php echo $route['routename'];?>" /></td>
				</tr>

				<tr>
					<td>规则</td>
					<td>
						<input name="regulation"  id="regulation" class="inputtext" type="text" value="<?php echo $route['regulation'];?>"  /></td>
				</tr>
				<tr>
					<td colspan="2">以下设置代表操作员可以在节点“<?php echo $next_node['nodename'];?>”进行相应的操作</td>
				</tr>

				<tr>
					<td valign="top">操作员配置(<a href="javascript:void(0)" onclick="adduser('<?php echo $routeid;?>')">+</a>)</td>
					<td>
						<table width="100%" id="userlist">
							<tr><td></td><td width="26px">发起</td><td width="26px">回退</td><td width="26px">回收</td><td width="26px">审核</td><td width="26px">授权</td><td width="26px">驳回</td></tr>
						
						</table>
					</td>
				</tr>
			</table>

			<input name="dosubmit" type="submit" value="下一步" class="button" id="dosubmit" style="display:none"></form>
	</div>
</div>
<script type="text/javascript">
var rels=<?php echo json_encode($rels)?>;
$(document).ready(function(){
	for(var r in rels){
		adduser_callback(rels[r]);
	}
});
function adduser(id,relid) {
	window.top.art.dialog({id:'add'}).close();
	window.top.art.dialog({title:'追加用户到流程:<?php echo $info['workname']?>',id:'add',iframe:'?m=workflow&c=workflow&a=append_user&id='+id+'&relid='+relid,width:'300',height:'150'}, function(){var d = window.top.art.dialog({id:'add'}).data.iframe;d.document.getElementById('dosubmit').click();return false;}, function(){window.top.art.dialog({id:'add'}).close()});
}
function deluser(relid){
	if(!confirm('你确定要删除这条记录吗')) return;
	alert(relid);
}
function adduser_callback(user){
	var html='<tr><td style="line-height:26px">\
		<a rel="'+user['uid']+'" class="user" href="#"  onclick="deluser('+user['id']+')"><img src="<?php echo IMG_PATH?>icon/'+(user['uid'].substr(0,1)=='u'?'m2.gif':(user['uid'].substr(0,1)=='a'?'computer_key.png':'m1.gif'))+'" />'+user['uid']+'</a>\
		</td>\
	 					<td><input name="priv_'+user['id']+'_5" value="1" '+((parseInt(user['priv'])&32)==32?' checked':'')+' type="checkbox"/></td>\
							<td><input name="priv_'+user['id']+'_4" value="1" '+((parseInt(user['priv'])&16)==16?' checked':'')+' type="checkbox"/></td>\
							<td><input name="priv_'+user['id']+'_3" value="1" '+((parseInt(user['priv'])&8)==8?' checked':'')+' type="checkbox"/></td>\
							<td><input name="priv_'+user['id']+'_2" value="1" '+((parseInt(user['priv'])&4)==4?' checked':'')+' type="checkbox"/></td>\
							<td><input name="priv_'+user['id']+'_1" value="1" '+((parseInt(user['priv'])&2)==2?' checked':'')+' type="checkbox"/></td>\
							<td><input name="priv_'+user['id']+'_0" value="1" '+((parseInt(user['priv'])&1)==1?' checked':'')+' type="checkbox"/></td></tr>';
	$('#userlist').append(html);
}
</script>
</body>
</html>