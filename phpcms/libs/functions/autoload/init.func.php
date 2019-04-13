<?php

/**
 * 自定义系统初始化内容
 */
//加载上下文信息类

//日志路径
define('LOG_PATH',pc_base::load_config('system','log_path') ? pc_base::load_config('system','log_path') : CACHE_PATH);
define('CURRENT_URL','http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);


