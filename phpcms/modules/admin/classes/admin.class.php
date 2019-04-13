<?php
defined('IN_PHPCMS') or exit('No permission resources.');
$session_storage = 'session_' . pc_base::load_config('system', 'session_storage');
pc_base::load_sys_class($session_storage);
if (param::get_cookie('sys_lang')) {
    define('SYS_STYLE', param::get_cookie('sys_lang'));
} else {
    define('SYS_STYLE', 'zh-cn');
}
//定义在后台
define('IN_ADMIN', true);

class admin
{
    public $userid;
    public $username;

    public function __construct()
    {
        self::check_admin();
        self::check_priv();
        pc_base::load_app_func('global', 'admin');
        if (!module_exists(ROUTE_M)) showmessage(L('module_not_exists'));
        self::manage_log();
        //self::check_ip();
        //self::lock_screen();
        //self::check_hash();
        //if (pc_base::load_config('system', 'admin_url') && $_SERVER["HTTP_HOST"] != pc_base::load_config('system', 'admin_url')) {
        //    Header("http/1.1 403 Forbidden");
        //    exit('No permission resources.');
        //}
    }

    /**
     * 判断用户是否已经登陆
     */
    final public function check_admin()
    {
        if (ROUTE_M == 'admin' && ROUTE_C == 'index' && in_array(ROUTE_A, array('login', 'public_card', 'remote_login'))) {
            return true;
        } else {
            $userid = param::get_cookie('userid');
            if (!isset($_SESSION['userid']) || !isset($_SESSION['roleid']) || !$_SESSION['userid'] || !$_SESSION['roleid'] || $userid != $_SESSION['userid']){
                header("location: ?m=admin&c=index&a=login");
                exit;
            } 
        }
    }

    /**
     * 加载后台模板
     * @param string $file 文件名
     * @param string $m 模型名
     */
    final public static function admin_tpl($file, $m = '')
    {
        $m = empty($m) ? ROUTE_M : $m;
        if (empty($m)) return false;
        return PC_PATH . 'modules' . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $file . '.tpl.php';
    }

    final public static function admin_html($file = '', $m = '')
    {
        if (!$file) $file = ROUTE_C . '_' . ROUTE_A;
        if (!$m) $m = ROUTE_M;

        $path = PC_PATH . 'modules' . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $file . '.html';
        if (!is_file($path)) {
            exit('Template does not exist.' . $m . '/' . $file);
        }
        $style = '__admin__';

        $filepath = CACHE_PATH . 'caches_template' . DIRECTORY_SEPARATOR . $style . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR;
        $compiledtplfile = $filepath . str_replace(DIRECTORY_SEPARATOR, '-_-', $file) . '.php';

        if (!file_exists($compiledtplfile) || (@filemtime($path) > @filemtime($compiledtplfile))) {

            $content = @file_get_contents($path);

            if (!is_dir($filepath)) {
                mkdir($filepath, 0777, true);
            }
            $template_cache = pc_base::load_sys_class('template_cache');
            $content = $template_cache->template_parse($content);
            file_put_contents($compiledtplfile, $content);
            chmod($compiledtplfile, 0777);
        }
        return $compiledtplfile;
    }

    /**
     * 按父ID查找菜单子项
     * @param integer $parentid 父菜单ID
     * @param integer $with_self 是否包括他自己
     */
    final public static function admin_menu($parentid, $with_self = 0)
    {
        $parentid = intval($parentid);
        $menudb = pc_base::load_model('menu_model');
        $site_model = param::get_cookie('site_model');
        $where = array('parentid' => $parentid, 'display' => 1);
        if ($site_model && $parentid) {
            $where[$site_model] = 1;
        }
        $result = $menudb->select($where, '*', 1000, 'listorder ASC');
        if ($with_self) {
            $result2[] = $menudb->get_one(array('id' => $parentid));
            $result = array_merge($result2, $result);
        }
        //权限检查
        if ($_SESSION['roleid'] == 1) return $result;
        $array = array();
        $privdb = pc_base::load_model('admin_role_priv_model');
        $siteid = param::get_cookie('siteid');
        $roleids = self::role_check();
        foreach ($roleids as $roleid) {
            foreach ($result as $v) {
                $action = $v['a'];
                if (preg_match('/^public_/', $action)) {
                    $array[] = $v;
                } else {
                    if (preg_match('/^ajax_([a-z]+)_/', $action, $_match)) $action = $_match[1];
                    $r = $privdb->get_one(array('m' => $v['m'], 'c' => $v['c'], 'a' => $action, 'data' => $v['data'], 'roleid' => $roleid, 'siteid' => $siteid));
                    if ($r) $array[] = $v;
                }
            }
        }
        $menu_matcher = array();
        $return_menu = array();
        foreach ($array as $ar) {
            if (!in_array(implode(',', $ar), $menu_matcher)) {
                $menu_matcher[] = implode(',', $ar);
                $return_menu[] = $ar;
            }
        }
        $menuids = array();
        $listorder = array();
        foreach ($return_menu as $rm) {
            $listorder[] = $rm['listorder'];
            $menuids[] = $rm['id'];
        }

        array_multisort($return_menu, SORT_ASC, SORT_NUMERIC, $listorder, SORT_ASC, SORT_NUMERIC, $menuids);
        return $return_menu;
    }

    /**
     * 获取菜单 头部菜单导航
     *
     * @param $parentid 菜单id
     */
    final public static function submenu($parentid = '', $big_menu = false)
    {
        if (empty($parentid)) {
            $menudb = pc_base::load_model('menu_model');
            $r = $menudb->get_one(array('m' => ROUTE_M, 'c' => ROUTE_C, 'a' => ROUTE_A));
            $parentid = $_GET['menuid'] = $r['id'];
        }
        $array = self::admin_menu($parentid, 1);

        $numbers = count($array);
        if ($numbers == 1 && !$big_menu) return '';
        $string = '';
        $pc_hash = $_SESSION['pc_hash'];
        foreach ($array as $_value) {
            if (!isset($_GET['s'])) {
                $classname = ROUTE_M == $_value['m'] && ROUTE_C == $_value['c'] && ROUTE_A == $_value['a'] ? 'class="on"' : '';
            } else {
                $_s = !empty($_value['data']) ? str_replace('=', '', strstr($_value['data'], '=')) : '';
                $classname = ROUTE_M == $_value['m'] && ROUTE_C == $_value['c'] && ROUTE_A == $_value['a'] && $_GET['s'] == $_s ? 'class="on"' : '';
            }
            if ($_value['parentid'] == 0 || $_value['m'] == '') continue;
            if ($classname) {
                $string .= "<a href='javascript:;' $classname><em>" . L($_value['name']) . "</em></a><span>|</span>";
            } else {
                $string .= "<a href='?m=" . $_value['m'] . "&c=" . $_value['c'] . "&a=" . $_value['a'] . "&menuid=$parentid&pc_hash=$pc_hash" . '&' . $_value['data'] . "' $classname><em>" . L($_value['name']) . "</em></a><span>|</span>";
            }
        }
        $string = substr($string, 0, -14);
        return $string;
    }

    /**
     * 当前位置
     *
     * @param $id 菜单id
     */
    final public static function current_pos($id)
    {
        $menudb = pc_base::load_model('menu_model');
        $r = $menudb->get_one(array('id' => $id), 'id,name,parentid');
        $str = '';
        if ($r['parentid']) {
            $str = self::current_pos($r['parentid']);
        }
        return $str . L($r['name']) . ' > ';
    }

    /**
     * 获取当前的站点ID
     */
    final public static function get_siteid()
    {
        return get_siteid();
    }

    /**
     * 获取当前站点信息
     * @param integer $siteid 站点ID号，为空时取当前站点的信息
     * @return array
     */
    final public static function get_siteinfo($siteid = '')
    {
        if ($siteid == '') $siteid = self::get_siteid();
        if (empty($siteid)) return false;
        $sites = pc_base::load_app_class('sites', 'admin');
        return $sites->get_by_id($siteid);
    }

    final public static function return_siteid()
    {
        $sites = pc_base::load_app_class('sites', 'admin');
        $siteid = explode(',', $sites->get_role_siteid($_SESSION['roleid']));
        return current($siteid);
    }

    /**
     * 权限判断
     */
    final public function check_priv()
    {
        if (ROUTE_M == 'admin' && ROUTE_C == 'index' && in_array(ROUTE_A, array('login', 'init', 'public_card', 'remote_login'))) return true;
        if ($_SESSION['roleid'] != '') {
            $role_ids = explode(',', $_SESSION['roleid']);
            if (in_array('1', $role_ids)) {
                return true;
            }

        }
//         if ($_SESSION['roleid'] == 1) return true;
        $siteid = param::get_cookie('siteid');
        $action = ROUTE_A;
        $privdb = pc_base::load_model('admin_role_priv_model');
        if (preg_match('/^public_/', ROUTE_A)) return true;
        if (preg_match('/^ajax_([a-z]+)_/', ROUTE_A, $_match)) {
            $action = $_match[1];
        }
//         $r = $privdb->get_one(array('m' => ROUTE_M, 'c' => ROUTE_C, 'a' => $action, 'roleid' => $_SESSION['roleid'], 'siteid' => $siteid));
        $where = "m='" . ROUTE_M . "' and c='" . ROUTE_C . "' and a='" . ROUTE_A . "' and siteid =" . $siteid;
        if ($_SESSION['roleid'] != '') {
            $where .= " and roleid in (" . $_SESSION['roleid'] . ")";
            $r = $privdb->get_one($where);
        }
        if (!$r) showmessage('您没有权限操作该项', 'blank');
    }

    /**
     *
     * 记录日志
     */
    final private function manage_log()
    {
        //判断是否记录
        $setconfig = pc_base::load_config('system');
        extract($setconfig);
        if ($admin_log == 1) {
            $action = ROUTE_A;
            if ($action == '' || strchr($action, 'public') || $action == 'init' || $action == 'public_current_pos') {
                return false;
            } else {
                $ip = ip();
                $log = pc_base::load_model('log_model');
                $username = param::get_cookie('admin_username');
                $userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
                $time = date('Y-m-d H-i-s', SYS_TIME);
                $url = '?m=' . ROUTE_M . '&c=' . ROUTE_C . '&a=' . ROUTE_A;
                $log->insert(array('module' => ROUTE_M, 'username' => $username, 'userid' => $userid, 'action' => ROUTE_C, 'querystring' => $url, 'time' => $time, 'ip' => $ip));
            }
        }
    }

    /**
     *
     * 后台IP禁止判断 ...
     */
    final private function check_ip()
    {
        $this->ipbanned = pc_base::load_model('ipbanned_model');
        $this->ipbanned->check_ip();
    }

    /**
     * 检查锁屏状态
     */
    final private function lock_screen()
    {
        if (isset($_SESSION['lock_screen']) && $_SESSION['lock_screen'] == 1) {
            if (preg_match('/^public_/', ROUTE_A) || (ROUTE_M == 'content' && ROUTE_C == 'create_html') || (ROUTE_M == 'release') || (ROUTE_A == 'login') || (ROUTE_M == 'search' && ROUTE_C == 'search_admin' && ROUTE_A == 'createindex')) return true;
            showmessage(L('admin_login'), '?m=admin&c=index&a=login');
        }
    }

    /**
     * 检查hash值，验证用户数据安全性
     */
    final private function check_hash()
    {
        if (preg_match('/^public_/', ROUTE_A) || ROUTE_M == 'admin' && ROUTE_C == 'index' || in_array(ROUTE_A, array('login'))) {
            return true;
        }
        if (isset($_GET['pc_hash']) && $_SESSION['pc_hash'] != '' && ($_SESSION['pc_hash'] == $_GET['pc_hash'])) {
            return true;
        } elseif (isset($_POST['pc_hash']) && $_SESSION['pc_hash'] != '' && ($_SESSION['pc_hash'] == $_POST['pc_hash'])) {
            return true;
        } else {
            showmessage(L('hash_check_false'), HTTP_REFERER);
        }
    }

    /**
     * 后台信息列表模板
     * @param string $id 被选中的模板名称
     * @param string $str form表单中的属性名
     */
    final public function admin_list_template($id = '', $str = '')
    {
        $templatedir = PC_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $pre = 'content_list';
        $templates = glob($templatedir . $pre . '*.tpl.php');
        if (empty($templates)) return false;
        $files = @array_map('basename', $templates);
        $templates = array();
        if (is_array($files)) {
            foreach ($files as $file) {
                $key = substr($file, 0, -8);
                $templates[$key] = $file;
            }
        }
        ksort($templates);
        return form::select($templates, $id, $str, L('please_select'));
    }

    /**
     * 获取用户可操作的区域
     * @return array 区域id数组
     *
     */
    final public function get_area_priviledge($flag = 0)
    {
        $role_db = pc_base::load_model('role_linkage_model');
        $result_tmp = $role_db->select(array('userid' => $_SESSION['userid']));
        $result = '';
        $datas = getcache(3360, 'linkage');
        $infos = $datas['data'];
        if ($result_tmp['0']['linkageid'] == 3360) {
            $result_tmp = array(
                array('linkageid' => '3361'),
                array('linkageid' => '3362'),
                array('linkageid' => '3363'),
                array('linkageid' => '3364'),
                array('linkageid' => '3365'),
            );
        }
        foreach ($result_tmp as $value) {
            if ($flag == 0) {
                $result .= $infos[$value['linkageid']]['arrchildid'] . ',';
            } else {
                $result .= $infos[$value['linkageid']]['linkageid'] . ',';
            }
        }
        return substr($result, 0, -1);
    }

    //让array_column()函数兼容低版本PHP
    final public function i_array_column($input, $columnKey, $indexKey = null)
    {
        if (!function_exists('array_column')) {
            $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
            $indexKeyIsNull = (is_null($indexKey)) ? true : false;
            $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
            $result = array();
            foreach ((array)$input as $key => $row) {
                if ($columnKeyIsNumber) {
                    $tmp = array_slice($row, $columnKey, 1);
                    $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
                } else {
                    $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
                }
                if (!$indexKeyIsNull) {
                    if ($indexKeyIsNumber) {
                        $key = array_slice($row, $indexKey, 1);
                        $key = (is_array($key) && !empty($key)) ? current($key) : null;
                        $key = is_null($key) ? 0 : $key;
                    } else {
                        $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                    }
                }
                $result[$key] = $tmp;
            }
            return $result;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
    }

    //如果$roleid有值，则做roleid是否存在判断，为空则返回role数组
    final public function role_check($roleid = '')
    {
        $roleid_array = explode(',', $_SESSION['roleid']);
        if ($roleid == '') {
            return $roleid_array;
        } else {
            return in_array($roleid, $roleid_array);
        }
    }

    //返回拼接的工作流uid字符串，因为所有用到admin
    final public function uid_flow_sql($table_name = '')
    {
        $roleid_array = explode(',', $_SESSION['roleid']);
        $userid = $_SESSION['userid'];
        $sql_str = " uid = 'u-1-" . $userid . "' OR ";
        foreach ($roleid_array as $value) {
            $sql_str .= " uid = 'r-1-" . $value . "' OR ";
        }
        if ($table_name != '') {
            str_replace('uid', $table_name . '.uid', $sql_str);
        }
        //因“国有房产管理中心（id:30）”的角色需要拥有区房管局复审的查看权限，但又没有此角色流程的配置，故此做一个判断
        return $_SESSION['roleid']!='30'?'(' . substr($sql_str, 0, -3) . ')':'1=1';
    }

}
