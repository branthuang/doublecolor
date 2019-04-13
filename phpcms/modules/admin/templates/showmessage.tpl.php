<?php defined('IN_ADMIN') or exit('No permission resources.'); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>"/>
    <meta http-equiv="X-UA-Compatible" content="IE=7"/>
    <title><?php echo L('message_tips'); ?></title>

    <link href="<?php echo B_CSS_PATH ?>main.css" rel="stylesheet" type="text/css"/>
    <script type="text/javaScript" src="<?php echo JS_PATH ?>jquery.min.js"></script>
    <script language="JavaScript" src="<?php echo JS_PATH ?>admin_common.js"></script>
</head>
<body class="iframe" style="background-color:rgb(228,235,239);">
<div class="bg-iframe">
    <div class="op-tips">
        <div class="header"><?php echo L('message_tips'); ?></div>
        <div class="line"></div>
        <div class="body">
                   <?php if (!$flag) { ?>
                   <div style="margin-right: 20%; padding-top: 65px; margin-left: 30%;">
                       <img style="float: left; display: block;" src="<?php echo B_IMG_PATH ?>i.png"/>
                       <span style="text-align: left; display: block; margin: 8px 10px 10px 60px;"><?php echo $msg ?></span>
                   </div>
                   <?php } else { ?>
                   <span class='largedata'>
                       <div class="flag_msg" style="margin:10px;"><?php echo $msg ?></div>
                   </span>
                   <script type="text/javascript">
                   (function () {
                       var $c = $(".largedata");
                       var w = $c.width(), h = $c.height(), ww = 600, wh = 200;
                       var dw = ww - w, dh = wh - h;
                       if (dw > 0) {
                           $c.css("margin-left", dw / 2);
                       }
                       if (dh > 0) {
                           $c.css("margin-top", dh / 2);
                       }
                   })();
              	  </script>
                   <?php } ?>
        </div>
        <div class="footer">
            <?php if ($url_forward == 'goback' || $url_forward == '') { ?>
                <a href="javascript:history.back();">[<?php echo L('return_previous'); ?>]</a>
            <?php } elseif ($url_forward == "close") { ?>
            <input type="button" name="close" value="<?php echo L('close'); ?> " onClick="window.close();">
            <?php } elseif ($url_forward == "blank") { ?>

            <?php }
            elseif ($url_forward) {
            if (strpos($url_forward, '&pc_hash') === false) $url_forward .= '&pc_hash=' . $_SESSION['pc_hash'];
            ?>
                <a href="<?php echo $url_forward ?>"><?php echo L('click_here'); ?></a>
                <script
                    language="javascript">setTimeout("redirect('<?php echo $url_forward?>');", <?php echo $ms?>);</script>
            <?php } ?>
            <?php if ($returnjs) { ?>
                <script style="text/javascript"><?php echo $returnjs; ?></script><?php } ?>
            <?php if ($dialog): ?>
                <script style="text/javascript">
                    <?php if($ms){ ?>
                        setTimeout("close_dialog();", <?php echo $ms?>);
                    <?php }else{ ?>
                    window.top.right.location.reload();
                    window.top.art.dialog({id: "<?php echo $dialog?>"}).close();
                    <?php } ?>
                                        </script><?php endif; ?>
        </div>
    </div>
</div>
<script style="text/javascript">
    function close_dialog() {
        window.top.right.location.reload();
        window.top.art.dialog({id: "<?php echo $dialog?>"}).close();
    }
</script>

</body>
</html>