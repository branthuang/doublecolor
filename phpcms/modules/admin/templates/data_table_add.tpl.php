<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<form id="data_table_form" name="data_table" method="post" action="?m=admin&c=data_table&a=add">
    <input type="hidden" name="dosubmit" value="1"/>
    <b>表名：</b>
    <input type="text" name="name" id="data_table_name" value=""/>
</form>