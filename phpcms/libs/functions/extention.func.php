<?php

/**
 *  extention.func.php 用户自定义函数库
 *
 * @copyright			(C) 2005-2010 PHPCMS
 * @license				http://www.phpcms.cn/license/
 * @lastmodify			2010-10-27
 */

/**
 * 提供跳转
 */
function redirect($url) {
    if (headers_sent()) {
        exit('<html><head><meta http-equiv="Refresh" content="0;url=' . $url . '"></meta></head></html>');
    } else {
        header('location:' . $url);
        exit();
    }
}

/**
 * 生成URL地址
 * @param <type> $action
 * @param <type> $controller
 * @param <type> $module
 * @param <type> $params
 * @param <type> $host
 */
function buildurl($action = '', $controller = '', $module = '', $params = array(), $urlpre = '') {

    static $_urlpre;
    static $content_cp, $cpinfos, $content_siteid;
    static $cats;

    if (empty($urlpre) && !isset($_urlpre)) {
        $_urlpre = rtrim(APP_PATH, '/') . '/';
    }

    if (empty($module) && empty($controller) && empty($action)) {
        return empty($urlpre) ? $_urlpre : $urlpre;
    }

    $controller = empty($controller) ? ROUTE_C : $controller;
    $module = empty($module) ? ROUTE_M : $module;


    $url = (empty($urlpre) ? $_urlpre : (rtrim($urlpre, '/') . '/') ) . 'index.php?';

    $params = array_merge(array(
        'm' => $module,
        'c' => $controller,
        'a' => $action
            ), $params);
    if (!isset($params['pc_hash']) && $_SESSION['pc_hash']) {
        $params['pc_hash'] = $_SESSION['pc_hash'];
    }

    $url .= http_build_query($params);
    return $url;
}

function get_thumb($path, $type = null) {
    static $cfg;

    $path = trim($path);
    if (empty($path)) {
        $default = array(
            'small' => 'cover-62.jpg',
            'middle' => 'cover-62.jpg',
            'large' => 'cover-115.jpg',
            'xlarge' => 'cover-165.jpg'
        );
        if (isset($default[$type])) {
            return IMG_PATH . $default[$type];
        } else {
            return IMG_PATH . reset($default);
        }
    } elseif (strpos($path, 'http://') === 0) {
        return $path; //已经是上传的地址，直接返回，临时实现！
    } elseif ($path{0} == '@') {
        $i = strpos($path, '/');
        $pre = substr($path, 1, $i - 1);
        if (!isset($cfg[$pre])) {
            $c = pc_base::load_config('substance', $pre);
            $cfg[$pre] = rtrim($c['config']['web_prefix'], '/') . '/';
        }
        return $cfg[$pre] . substr($path, $i + 1) . '?r=' . SYS_TIME;
    } elseif ($path{0} == '#') {
        $cfg = pc_base::load_config('appbase', 'audit_path');
        return $cfg['web_prefix'] . substr($path, 1) . '?r=' . SYS_TIME;
    }
    //TODO 临时实现，等待新cms上线后修改规则
    return 'http://dm.189.cn/uploadfile/portalone/' . ltrim($path, '/');
}

/**
 * 根据PHP各种类型变量生成唯一标识号 
 * @param mixed $mix 变量
 * @return string
 */
function hashcode($mix) {
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return crc32($mix);
}

/**
 * 判断当前页面
 * @param string $m	模型
 * @param string $c	控制器
 * @param string $a 动作
 * @param array $params	附加参数
 */
function is_current($m, $c, $a, $params) {
    if ($m == ROUTE_M && $c == ROUTE_C && $a == ROUTE_A) {
        if (!empty($params)) {
            foreach ($params as $k => $v) {
                if ($_GET[$k] != $v) {
                    return false;
                }
            }
        }
        return true;
    }
    return false;
}

define('LEVEL_DEBUG', 1);
define('LEVEL_INFO', 2);
define("LEVEL_WARN", 3);
define('LEVEL_ERROR', 4);
define('LEVEL_FATAL', 5);

/**
 * 日志记录
 * @param string $msg 错误描述
 * @param int $level 错误级别  1 debug 2 info 3 warn 4 error 5 fatal
 * @param int $isdetail 是否记录堆栈上的详细信息 0不记录 1记录 -1自动判断
 * @param string $logpath 日志的位置
 */
function logs($msg, $level = 4, $isdetail = -1, $logpath = false, $inline = true) {
    if (defined('DEBUG_LEVEL') && DEBUG_LEVEL > $level)
        return false;
    static $uuid;
    if (!isset($uuid))
        $uuid = session_id();

    $_date = date('Y-m-d H:i:s');

    switch ($level) {
        case LEVEL_DEBUG:
            $_level = 'DEBUG';
            break;
        case LEVEL_INFO:
            $_level = 'INFO';
            break;
        case LEVEL_WARN:
            $_level = 'WARN';
            break;
        case LEVEL_ERROR:
            $_level = 'ERROR';
            break;
        case LEVEL_FATAL:
            $_level = 'FATAL';
            break;
    }

    if ($isdetail == 1 || ($isdetail == -1 && defined('DEBUG_LEVEL') && DEBUG_LEVEL <= LEVEL_DEBUG)) {
        $inline = false;
        if ($msg instanceof Exception) {
            $trace = debug_backtrace();
            $ex = $trace[0]['args'][0];
            $trace = array(
                array(
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'function' => 'throw',
                    'class' => '',
                    'type' => '',
                    'args' => $msg
                )
            );

            $trace = array_merge($trace, $msg->getTrace());

            $e['message'] = $msg->getMessage();
        } else {
            $trace = debug_backtrace();
            unset($trace[0]);
            $e['message'] = $msg;
        }

        $end = end($trace);
        $e['file'] = $end['file'];
        $e['class'] = $end['class'];
        $e['function'] = $end['function'];
        $e['line'] = $end['line'];
        $traceInfo = '';
        foreach ($trace as $t) {
            if ($t['class'] == 'application' && $t['function'] == 'init')
                break; //针对PHPCMS的特殊设置，避免重复打印入口信息

            $traceInfo .= "\t" . $t['class'] . $t['type'] . $t['function'] . '(';
            if (!is_array($t['args']))
                $t['args'] = array($t['args']);
            $argstr = '';
            foreach ($t['args'] as $_args)
                $argstr .= ', ' . (is_object($_args) ? get_class($_args) : strtr(var_export($_args, 1), "\n", " "));

            if (strlen($argstr) > 0)
                $traceInfo .= substr($argstr, 2);

            $traceInfo .= ")\tin " . $t['file'] . "\tLINE:" . $t['line'] . "\n";
        }
        $e['trace'] = $traceInfo;

        $logInfo = "{$_level}\t[{$_date}]\t{$e['message']}\n{$e['trace']}\t[{$uuid}]";
    } else {
        $logInfo = "{$_level}\t[{$_date}]\t{$msg}\t[{$uuid}]";
    }

    $filepath = empty($logpath) ? LOG_PATH . 'applog.log' : $logpath;
    error_log(($inline ? str_replace(array("\t", "\r", "\n"), array("  ", '\x0A', '\x0A'), $logInfo) : $logInfo) . "\n" . ($traceInfo ? "\n" : ""), 3, $filepath);

    return true;
}

function guid() {
    list($usec, $sec) = explode("  ", microtime());
    $systime = $sec . substr($usec, 2, 3);
    $tmp = rand(0, 1) ? '-' : '';
    $value = strtolower($_ENV["COMPUTERNAME"] . '/' . $_SERVER["SERVER_ADDR"]) . ':' . $systime . ':' . $tmp . mt_rand(1000, 9999) . mt_rand(1000, 9999) . mt_rand(1000, 9999) . mt_rand(100, 999) . mt_rand(100, 999);
    $raw = md5($value);
    return substr($raw, 0, 8) . '-' . substr($raw, 8, 4) . '-' . substr($raw, 12, 4) . '-' . substr($raw, 16, 4) . '-' . substr($raw, 20);
}

function checkrobot($useragent = '') {
    static $kw_spiders = array('bot', 'crawl', 'spider', 'slurp', 'sohu-search', 'lycos', 'robozilla');
    static $kw_robots = array('sina_robot', 'sina_weibo');
    static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

    $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
    if (dstrpos($useragent, $kw_robots))
        return true;
    if (strpos($useragent, 'http://') === false && dstrpos($useragent, $kw_browsers))
        return false;
    if (dstrpos($useragent, $kw_spiders))
        return true;
    return false;
}

function dstrpos($string, &$arr, $returnvalue = false) {
    if (empty($string))
        return false;
    foreach ((array) $arr as $v) {
        if (strpos($string, $v) !== false) {
            $return = $returnvalue ? $v : true;
            return $return;
        }
    }
    return false;
}

/**
 * 是否天翼手机号码
 * @return
 * -1 不是有效的手机号码
 * 	0  未识别的手机号码
 * 	1  电信手机号码
 *  2 联通手机号码
 *  3 移动手机号码
 */
function is_surfing($num) {
    //return in_array(substr(trim($num),0,3),array('133', '153', '189', '180'));

    return checkMobilePhone(trim($num));
}

/**
 * 是否是PHP关联数组
 */
function is_hash_array($arr) {
    return array_keys($arr) !== range(0, sizeof($arr) - 1);
}

function contentmodel_txt2val($val, $struct) {
    switch ($struct['formtype']) {
        case 'radio':
        case 'box':
            $setting = string2array($struct['setting']);
            foreach (explode("\n", $setting['options']) as $opt) {
                $opt = trim($opt, "\r");
                if (strpos($opt, $val . '|') === 0) {
                    $val = substr($opt, strlen($val) + 1);
                    break;
                }
            }
            break;
    }
    return $val;
}

/**
 * 判断后缀是否在允许范围内
 * @param type $filename
 * @param type $allowlist 用，隔开
 * @return boolean
 */
function is_allow_ext($filename, $allowlist) {
    if ($allowlist == '*')
        return true;
    if ($i = strrpos($filename, '.')) {
        $ext = substr($filename, $i + 1);
        return in_array($ext, explode(',', $allowlist));
    }
    return false;
}

/**
 * 拆分如func arg1 arg2调用字符串为数组
 * @param type $str
 */
function split_fun_str($str) {
    $str = trim($str);
    $args = array();
    if ($i = strpos($str, ' ')) {
        $func = substr($str, 0, $i);
        $argstr = trim(substr($str, $i)) . ' ';
        $last = 0;
        $isclose = 1;
        $b = '';
        for ($i = 0; $i < strlen($argstr); ++$i) {
            if ($argstr{$i} == ' ' && $isclose) {
                $v = substr($argstr, $last, $b != '' ? $i - $last - 1 : $i - $last);
                $args[] = $v;
            } elseif ($argstr{$i} == $b) {
                if (substr($argstr, $i - 1, 1) == '\\') {
                    continue;
                } else {
                    $isclose = 1;
                }
            } elseif ($argstr{$i} == '"' || $argstr{$i} == '\'') {
                $b = $argstr{$i};
                $last = $i + 1;
                $isclose = 0;
            }
        }
    } else {
        $func = $str;
    }
    return array('func' => $func, 'args' => $args);
}

/**
 * 检查指定菜单权限判断
 */
function check_menu_priv($a, $c = ROUTE_C, $m = ROUTE_M, $param = array()) {
    if ($m == 'admin' && $c == 'index' && in_array($a, array('login', 'init', 'public_card')))
        return true;
    if ($_SESSION['roleid'] == 1)
        return true;
    $action = $a;
    if (preg_match('/^public_/', $a))
        return true;
    if (preg_match('/^ajax_([a-z]+)_/', $a, $_match)) {
        $action = $_match[1];
    }
    $ticket = httpcontext::current()->get_userticket();
    return in_array("{$m}.{$c}.{$action}", $ticket['privs']);
    /*
      $privdb = pc_base::load_model('admin_role_priv_model');
      $r = $privdb->get_one(array('m' => $m, 'c' => $c, 'a' => $a, 'roleid' => $_SESSION['roleid'], 'siteid' => $siteid));
      if (!$r){
      $privdb = pc_base::load_model('admin_priv_model');
      $r = $privdb->get_one(array('m' => $m, 'c' => $c, 'a' => $a, 'userid' => $_SESSION['userid'], 'siteid' => $siteid));
      }
     */
    //TODO 这里配合param做参数校验
    return $r ? true : false;
}

function get_replace_url($replace_arg, $add_post = false, $url = false) {
    if (!$url)
        $url = $_SERVER['REQUEST_URI'];
    $info = parse_url($url);
    $params = array();
    if ($add_post && $_POST)
        $params = $_POST;
    if ($info['query']) {
        parse_str($info['query'], $_params);
        $params = $params + $_params;
        foreach ($replace_arg as $k => $v) {
            $params[$k] = $v;
        }
    } else {
        $params = $params + $replace_arg;
    }
    return $info['path'] . '?' . http_build_query($params);
}

function make62num($num) {
    if (57731386986 < $num)
        return $num . '';
    $num_arr = array(56800235584, 916132832, 14776336, 238328, 3844, 62);
    $str = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    $num_str = '';
    foreach ($num_arr as $n) {
        if ($num > $n) {
            $num_idx = intval($num / $n);
            $num = $num % $n;
            $num_str.=$str[$num_idx];
        } else if ($num_str != '')
            $num_str.='0';
    }
    return $num_str . $str[$num];
}

//还原62进制的数  
function get62num($num) {
    $str = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $len = strlen($num);
    $rtn = 0;
    while ($len > 0) {
        $cur = substr($num, 0, 1);
        $idx = strpos($str, $cur);
        $rtn += pow(62, $len - 1) * $idx;
        $num = substr($num, 1);
        $len = strlen($num);
    }
    return $rtn;
}

/**
 * 发送信息方法
 * @param string $message	通知消息
 * @param string $subject	标题
 * @param member_model $to			接收方（username,email,phonenum）
 * @param member_model $from		发送方（username,email,phonenum）
 */
function send_message($message, $subject, $to, $from = null) {
    if (empty($message)) {
        $message = $subject;
    }
    $_from = pc_base::load_config('appbase', 'message_sender');
    if (!is_array($from)) {
        $from = $_from;
    } else {
        $from = $_from + $from;
    }
    foreach ($to as $k => $v) {
        if (empty($v)) {
            continue;
        }
        //站内短信
        if ($k == 'username') {
            $messagedb = pc_base::load_model('message_model');
            $messagedb->add_message($v, $from[$k], $subject, $message, true);
        } elseif ($k == 'email') {
            pc_base::load_sys_func('mail');
            sendmail($v, $subject, $message, $from[$k]);
        } elseif ($k == 'phonenum') {
            //pc_base::load_sys_class('mms_helper', 'libs/classes/ffcs', 0);
            //mms_helper::send_sms($v, $message);
        }
    }
}

function formatpath($path, $isfile = 0) {
    return str_replace(array('//', '\\\\', '\\'), '/', rtrim($path, '/')) . ($isfile ? '' : '/');
}

function object2array($obj) {
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($_arr as $key => $val) {
        $val = (is_array($val) || is_object($val)) ? object2array($val) : $val;
        $arr[$key] = $val;
    }
    return $arr;
}

if (!function_exists('get_called_class')) {

    /**
     *  兼容5.3写法，主要用于子类父类静态方法的获取，仅兼容了本项目目前出现的场景，其他地方有使用需测试
     */
    function get_called_class($bt = false, $l = 1, $lastclass = '') {

        if (!$bt) {
            $bt = debug_backtrace();
        }
        if (!isset($bt[$l])) {
            if ($lastclass)
                return $lastclass;
            else
                throw new Exception("Cannot find called class -> stack level too deep.");
        }
        if ($bt[$l]['type'] == '->' && is_object($bt[$l]['object'])) {
            $c = get_class($bt[$l]['object']);
            return is_subclass_of($c, $lastclass) ? $c : $lastclass;
        }
        if ($lastclass) {
            if (array_key_exists('function', $bt[$l]) && in_array($bt[$l]['function'], array('call_user_func', 'call_user_func_array'))) {
                //如果参数是数组
                if (is_array($bt[$l]['args'][0])) {
                    $class = $bt[$l]['args'][0][0];
                } else if (is_string($bt[$l]['args'][0])) {//如果参数是字符串
                    //如果是字符串且字符串中包含::符号，则认为是正确的参数类型，计算并返回类名
                    if (false !== strpos($bt[3]['args'][0], '::')) {
                        $toret = explode('::', $bt[3]['args'][0]);
                        $class = $toret[0];
                    }
                }
            } else {
                $class = $bt[$l]['class'];
            }
            if ($class && ($class == $lastclass || is_subclass_of($class, $lastclass))) {
                return get_called_class($bt, $l + 1, $class);
            } else {
                return $lastclass;
            }
        }
        switch ($bt[$l]['type']) {
            case '::':
                return get_called_class($bt, $l + 1, $bt[$l]['class']);
            case '->':
                return is_object($bt[$l]['object']) ? get_class($bt[$l]['object']) : $bt[$l]['class'];
        }
    }

}

//首位补零
function add_zero($str) {
    if (strlen($str) == 1) {
        return "0" . $str;
    }
    return $str;
}

function return_py($tag) {
    $array = array();
    pc_base::load_sys_func('pinyin');
    $pinyinclass = new my_Getpy();
    $pinyin = $pinyinclass->strs($tag);

    if (is_array($pinyin)) {
        $array['pinyin'] = implode('', $pinyin);
        $array['pinyin'] = preg_replace('/[^0-9a-zA-Z]/', '', $array['pinyin']); //过滤特殊符号,2013-3-20更新 http://www.15ms.com
    }
    $array['letter'] = strtolower(substr($array['pinyin'], 0, 1));
    return $array;
}

/*
 * 返回两数组重复号码
 */
function get_same($arr1, $arr2){
    $t = array();
    foreach($arr1 as $k1=>$v1){
        foreach($arr2 as $k2=>$v2){
            if($v1 == $v2){
                unset($arr1[$k1]);
                unset($arr2[$k2]);
                $t[] = $v1;
            }
        }
    }
    return $t;
}

/*返回两数组重复号码的数量
 */
function get_same_count($arr1, $arr2){
    $arr = get_same($arr1, $arr2);
    return count($arr);
            
}

/*数组项整形化*/
function intval_array($arr){
    foreach($arr as $key=>$val){
        $arr[$key] =  intval($val);
    }
    return $arr;
}
/*返回存在于数组1，不存在于数组2的数组*/
function arr1_sub_arr2($arr1,$arr2){
    $t = array();
    foreach ($arr1 as $v){
        if(!in_array($v,$arr2)){
            $t[] = $v;
        }
    }
    return $t;
}
/*两数组合并，去重*/
function arr1_add_arr2($arr1,$arr2){
    $t = $arr1;
    foreach ($arr2 as $key=>$val){
        if(!in_array($val, $arr2)){
            $t[] = $val;
        }
    }
    return $t;
}