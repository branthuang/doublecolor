<?php defined('IN_PHPCMS') or exit('No permission resources.'); ?><?php
defined('IN_ADMIN') or exit('No permission resources.');
include $this->admin_tpl('header', 'admin');?>
<script language="javascript" type="text/javascript" src="<?php echo JS_PATH;?>formvalidator.js" charset="UTF-8"></script>
<script language="javascript" type="text/javascript" src="<?php echo JS_PATH;?>formvalidatorregex.js" charset="UTF-8"></script>
<script type="text/javascript">
  $(document).ready(function() {
	$.formValidator.initConfig({formid:"myform",autotip:true});
	$("#workname").formValidator({onshow:"请输入名称",onfocus:"名称不能为空"}).inputValidator({min:1,max:50,onerror:"名称不能为空"});
	$("#action").formValidator({onshow:"请输入标识",onfocus:"标识不能为空"}).inputValidator({min:1,max:50,onerror:"标识不能为空"}).regexValidator({regexp:"^[\\w_]+\.[\\w]+$",onerror:"标识格式为\"对象类型.动作\""}).ajaxValidator({type : "get",url : "",data :"m=workflow&c=workflow&a=public_action",datatype : "html",async:'false',success : function(data){	if( data == "0" ){return true;}else{return false;}},buttons: $("#dosubmit"),onerror : "标识已存在",onwait : "连接中"});
  })
</script>

<div class="pad_10">
<form action="" method="post" name="myform" id="myform">
<table cellpadding="2" cellspacing="1" class="table_form" width="100%">
	<tr>
		<th width="100">所属应用</th>
		<td><select name="appid"><?php foreach($apps as $app){ echo '<option value="'.$app['id'].'" '.($app['id']==$flow['appid']?'selected':'').'>'.$app['appname'].'</option>';}?></select></td>
	</tr>
	<tr>
		<th width="100">流程名</th>
		<td><input name="workname"  id="workname" class="inputtext" type="text" value="<?php echo $flow['workname']?>" /></td>
	</tr>
	<tr>
		<th width="100">流程标识</th>
		<td><?php if ($flow['action'])echo $flow['action'];else{?><input name="action"  id="action" class="inputtext" type="text" value="<?php echo $flow['action']?>" /><?php }?></td>
	</tr>
	<tr>
		<th>描述</th>
		<td><textarea name="description" rows="2" cols="20" id="description" class="inputtext" style="height:60px;width:300px;"><?php echo $flow['description']?></textarea>
		</td>
	</tr>
</table>
<input type="submit" name="dosubmit" id="dosubmit" class="dialog"
		value=" <?php echo L('submit')?> "></td>
</form>
</div>
</body>
</html>
