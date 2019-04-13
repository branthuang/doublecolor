<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<div class="pad-lr-10">
    <div class="con_box" style="padding:5px;height: 25px;">
        <button class="btn green-btn search_btn" type="button" onclick="location.href = '?m=admin&c=winning_numbers&a=add'" style='float:left; margin-right:5px;' >
            新增
        </button>
        <button class="btn green-btn search_btn" type="button" style='float:left;' onclick="show_import_iframe('导入');">
            导入
        </button>
        <button class="btn blue-btn search_btn" type="button" onclick="update_numbers();" style='float:left; margin-left:5px;' id="update_button">
            点击自动更新
        </button>
        <img src="<?php echo IMG_PATH; ?>waiting.gif" style="height:25px;display:none;" id="waiting_img"/>
    </div>     
    <div class="table-list">
        <table width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th width="55" align="center">期号</th>
                    <th>中奖号1</th>
                    <th>中奖号2</th>
                    <th>中奖号3</th>
                    <th>中奖号4</th>
                    <th>中奖号5</th>
                    <th>中奖号6</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($list as $r) {
                    ?>
                    <tr>
                        <td align="center"><?php echo $r['issue_num'] ?></td>
                        <td align="center"><?php echo $r['num1']; ?></td>
                        <td align="center"><?php echo $r['num2']; ?></td>
                        <td align="center"><?php echo $r['num3']; ?></td>
                        <td align="center"><?php echo $r['num4']; ?></td>
                        <td align="center"><?php echo $r['num5']; ?></td>
                        <td align="center"><?php echo $r['num6']; ?></td>
                        <td align="center">
                            <a href="?m=admin&c=winning_numbers&a=edit&id=<?php echo $r['id']; ?>">编辑</a> |
                            <a href="?m=admin&c=winning_numbers&a=delete&id=<?php echo $r['id']; ?>" onclick="if (!confirm('确认删除么？')) {
                                        return false;
                                    }">删除</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    function update_numbers(){
        $('#update_button').attr('disabled',true);
        $('#waiting_img').show();
        $.ajax({
            type: "get",
            url: "index.php",
            data:   "m=admin&c=winning_numbers&a=get_numbers",
            success: function(msg){
                if(msg == 1){
                    location.reload();
                }
            } 
        });
    }
    function show_import_iframe(title) {
        window.top.art.dialog(
                {
                    id: 'excel_import',
                    iframe: '?m=admin&c=winning_numbers&a=excel_import&pc_hash=<?php echo $_SESSION['pc_hash'] ?>',
                    title: title,
                    width: '800',
                    height: '500',
                    okVal: '导入'
                }, function () {
            var d = window.top.art.dialog({id: 'excel_import'}).data.iframe;// 使用内置接口获取iframe对象
            var form = d.document.getElementById('dosubmit');
            form.click();
            window.top.$('.aui_buttons button.aui_state_highlight').attr('disabled', 'disabled');
            return false;
        }, function () {
            window.top.art.dialog({id: 'excel_import'}).close();
        });
    }
</script>
</body>
</html>