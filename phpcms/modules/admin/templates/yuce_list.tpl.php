<?php
defined('IN_ADMIN') or exit('No permission resources.');
$show_dialog = 1;
include $this->admin_tpl('header', 'admin');
?>
<div class="pad-lr-10" style="overflow-x:scroll;">
    <div class="con_box search_box clearfix">
        <form name="search_form" id="search_form" action="?m=admin&c=yuce&a=init" method="POST">
            <input type="hidden" value="1" name="search">
            <table class="search_tab">
                <tbody>

                    <tr>
                        <td class="search_tdLf" width="10%"> 开始期号：</td>
                        <td width="10%">                      
                            <select name="begin_issue_num">
                                <option value=''>请选择</option>
                                <?php foreach($issue_num_arr as $val){ ?>
                                <option value="<?php echo $val['issue_num']?>" <?php if($begin_issue_num == $val['issue_num']){echo "selected";}?>>
                                    <?php echo $val['issue_num']?></option>
                                <?php } ?>
                            </select>
                        </td>
                         <td class="search_tdLf" width="10%"> 结束期号：</td>
                        <td width="23%">                      
                            <select name="end_issue_num">
                                <option value=''>请选择</option>
                                <?php foreach($issue_num_arr as $val){ ?>
                                <option value="<?php echo $val['issue_num']?>" <?php if($end_issue_num == $val['issue_num']){echo "selected";}?>>
                                    <?php echo $val['issue_num']?></option>
                                <?php } ?>
                            </select>
                        </td>
                        <td>
                            <button class="btn blue-btn search_btn" type="button" onclick="$('#search_form').submit();" style="float:right;">搜索
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>     
    <div class="table-list">
        <table width="2800" cellspacing="0">
            <thead>
                <tr>
                    <th rowspan="2">
                        操作
                        <br/>
                        <a href="javascript:void(0);"  id="create_new" style="color:red" title="点击更新胆组">
                            刷新
                        </a>
                    </th>
                    <th rowspan="2">期号</th>
                    <th colspan="6">开奖号码</th>
                    <th colspan="2">重码</th>
                    <th colspan="2">邻码</th>
                    <th colspan="2">隔1码</th>
                    <th colspan="2">隔2码</th>
                    <th colspan="2">远码</th>
                    <th colspan="2">遗漏1-2期号码</th>
                    <th colspan="2">遗漏3-5期号码</th>
                    <th colspan="2">遗漏6-10期号码</th>
                    <th colspan="2">遗漏11期以外的号码</th>
                </tr>
                <tr>
                    <th>A</th>
                    <th>B</th>
                    <th>C</th>
                    <th>D</th>
                    <th>E</th>
                    <th>F</th>
                    <th>胆组1</th>
                    <th>中奖个数</th>
                    <th>胆组2</th>
                    <th>中奖个数</th>
                    <th>胆组3</th>
                    <th>中奖个数</th>
                    <th>胆组4</th>
                    <th>中奖个数</th>
                    <th>胆组5</th>
                    <th>中奖个数</th>
                    <th>胆组6</th>
                    <th>中奖个数</th>
                    <th>胆组7</th>
                    <th>中奖个数</th>
                    <th>胆组8</th>
                    <th>中奖个数</th>
                    <th>胆组9</th>
                    <th>中奖个数</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td align="center">
                        <a href="<?php echo buildurl('download','yuce','admin',array('issue_num'=>$this->yuce_issue_num)); ?>" class="download" >下载</a> 
                    </td>
                    <td align="center">预测胆组</td>
                    <td align="center"><?php echo $yuce['num1']; ?></td>
                    <td align="center"><?php echo $yuce['num2']; ?></td>
                    <td align="center"><?php echo $yuce['num3']; ?></td>
                    <td align="center"><?php echo $yuce['num4']; ?></td>
                    <td align="center"><?php echo $yuce['num5']; ?></td>
                    <td align="center"><?php echo $yuce['num6']; ?></td>
                    <?php for($i=1;$i<=9;$i++){ 
                        $col = 'd'.$i;
                        ?>
                    <td align="center" style="<?php if($i%2==1){ ?>background-color: yellow;<?php } ?>"> 
                        <ul class="num_format">
                            <?php 
                            $t = json_decode($yuce[$col]);
                            foreach ($t as $val){
                            ?>
                                <li>
                                    <?php echo $val;?>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>
                        
                    </td>
                    <td align="center" style="width:20px;<?php if($i%2==1){ ?>background-color: yellow;<?php } ?>">
                        0
                    </td>
                    <?php } ?>
                </tr>
                <?php
                foreach ($list as $r) {
                    ?>
                    <tr>
                        <td align="center">
                            <?php if(!empty($r['d1'])){ ?>
                            <a href="<?php echo buildurl('download','yuce','admin',array('issue_num'=>$r['issue_num'])); ?>"  class="download" >下载</a> 
                            <?php } ?>
                        </td>
                        <td align="center"><?php echo $r['issue_num']; ?></td>
                        <td align="center"><?php echo $r['num1']; ?></td>
                        <td align="center"><?php echo $r['num2']; ?></td>
                        <td align="center"><?php echo $r['num3']; ?></td>
                        <td align="center"><?php echo $r['num4']; ?></td>
                        <td align="center"><?php echo $r['num5']; ?></td>
                        <td align="center"><?php echo $r['num6']; ?></td>
                        <?php for($i=1;$i<=9;$i++){ ?>
                        <td align="center" style="<?php if($i%2==1){ ?>background-color: #ffff00;<?php } ?>">
                            <ul class="num_format">
                            <?php 
                            $index = 'd'. $i;
                            $t = json_decode($r[$index]);
                            foreach ($t as $val){
                            ?>
                                <li <?php if($val == $r['num1'] || $val == $r['num2'] || $val == $r['num3'] || $val == $r['num4'] || $val == $r['num5'] || $val == $r['num6']){ ?>
                                    style="color:white;background-color: red;"
                                        <?php } ?>>
                                    <?php echo $val;?>
                                </li>
                            <?php
                            }
                            ?>
                            </ul>
                        </td>
                        <td align="center" style="width:20px;<?php if($i%2==1){ ?>background-color: yellow;<?php } ?>">
                            <?php 
                            $index_w = 'w'. $i;
                            ?>
                            <?php echo $r[$index_w];?>
                        </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<style>
    .table-list table td{height:10px;}
    .num_format li{float:left;width:20px;}
</style>
<script>
   $('#create_new').click(function(){
       $('#create_new').html("计算中……");
       $.ajax({
        type: "POST",
        url: "index.php",
        data:   "m=admin&c=yuce&a=doit",
        success: function(msg){
            if(msg == 1){
                window.location.reload();
            }
        } 
       }); 
   });
</script>
</body>
</html>