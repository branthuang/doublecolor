<?php defined('IN_ADMIN') or exit('No permission resources.'); ?>
    <!DOCTYPE html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>"/>
        <title></title>
        
        <link href="<?php echo CSS_PATH ?>dialog.css" rel="stylesheet" type="text/css"/>
        <link rel="stylesheet" type="text/css" href="<?php echo CSS_PATH ?>style/<?php echo SYS_STYLE; ?>-styles1.css"
              title="styles1" media="screen"/>
        <link rel="alternate stylesheet" type="text/css"
              href="<?php echo CSS_PATH ?>style/<?php echo SYS_STYLE; ?>-styles2.css" title="styles2" media="screen"/>
        <link rel="alternate stylesheet" type="text/css"
              href="<?php echo CSS_PATH ?>style/<?php echo SYS_STYLE; ?>-styles3.css" title="styles3" media="screen"/>
        <link rel="alternate stylesheet" type="text/css"
              href="<?php echo CSS_PATH ?>style/<?php echo SYS_STYLE; ?>-styles4.css" title="styles4" media="screen"/>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>jquery.min.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>admin_common.js"></script>
        <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>styleswitch.js"></script>
        <?php if (isset($show_validator)) { ?>
            <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>formvalidator.js"
                    charset="UTF-8"></script>
            <script language="javascript" type="text/javascript" src="<?php echo JS_PATH ?>formvalidatorregex.js"
                    charset="UTF-8"></script>
        <?php } ?>
        <link href="<?php echo B_CSS_PATH ?>main.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo B_CSS_PATH ?>sysset.css" rel="stylesheet" type="text/css"/>
        <!--    <link href="--><?php //echo B_CSS_PATH ?><!--form.css" rel="stylesheet" type="text/css"/>-->
        <?php if (!$is_dialog) { ?>
            <script type="text/javascript" src="<?php echo B_JS_PATH ?>resize.js"></script>
            <script type="text/javascript" src="<?php echo B_JS_PATH ?>iframeBase.js"></script>
        <?php } ?>
        <script type="text/javascript">
            window.focus();
            var pc_hash = '<?php echo $_SESSION['pc_hash'];?>';
            <?php if(!isset($show_pc_hash)) { ?>
            window.onload = function () {
                var html_a = document.getElementsByTagName('a');
                var num = html_a.length;
                for (var i = 0; i < num; i++) {
                    var href = html_a[i].href;
                    if (href && href.indexOf('javascript:') == -1) {
                        if (href.indexOf('?') != -1) {
                            html_a[i].href = href + '&pc_hash=' + pc_hash;
                        } else {
                            html_a[i].href = href + '?pc_hash=' + pc_hash;
                        }
                    }
                }

                var html_form = document.forms;
                var num = html_form.length;
                for (var i = 0; i < num; i++) {
                    var newNode = document.createElement("input");
                    newNode.name = 'pc_hash';
                    newNode.type = 'hidden';
                    newNode.value = pc_hash;
                    html_form[i].appendChild(newNode);
                }

                $('select').addClass('select_text');
            }
            <?php } ?>
        </script>
    </head>
<body <?php if ($no_scroll){ ?>style="overflow: hidden"<?php } ?>>
<div class="bg-iframe clearfix">