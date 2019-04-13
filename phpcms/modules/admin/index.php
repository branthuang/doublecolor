<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin','admin',0);

class index extends admin {
	private $db;
	public $siteid;
	function __construct() {
		parent::__construct();
                $this->db = pc_base::load_model('admin_model');
	}
	
	public function init () {
            include $this->admin_tpl('index');
	}
        
	public function login() {
		if(isset($_GET['dosubmit'])) {
			
			//不为口令卡验证
			if (!isset($_GET['card'])) {
				$username = isset($_POST['username']) ? trim($_POST['username']) : showmessage(L('nameerror'),HTTP_REFERER);
				$code = isset($_POST['code']) && trim($_POST['code']) ? trim($_POST['code']) : showmessage(L('input_code'), HTTP_REFERER);
				if ($_SESSION['code'] != strtolower($code)) {
					$_SESSION['code'] = '';
					showmessage(L('code_error'), HTTP_REFERER);
				}
				$_SESSION['code'] = '';
			} else { //口令卡验证
				if (!isset($_SESSION['card_verif']) || $_SESSION['card_verif'] != 1) {
					showmessage(L('your_password_card_is_not_validate'), '?m=admin&c=index&a=public_card');
				}
				$username = $_SESSION['card_username'] ? $_SESSION['card_username'] :  showmessage(L('nameerror'),HTTP_REFERER);
			}
			
			//密码错误剩余重试次数
			$this->times_db = pc_base::load_model('times_model');
			$rtime = $this->times_db->get_one(array('username'=>$username,'isadmin'=>1));
			$maxloginfailedtimes = getcache('common','commons');
			$maxloginfailedtimes = (int)$maxloginfailedtimes['maxloginfailedtimes'];

			if($rtime['times'] >= $maxloginfailedtimes) {
				$minute = 60-floor((SYS_TIME-$rtime['logintime'])/60);
				if($minute>0) showmessage(L('wait_1_hour',array('minute'=>$minute)));
			}
			//查询帐号
			$r = $this->db->get_one(array('username'=>$username));
			if(!$r) showmessage(L('user_not_exist'),'?m=admin&c=index&a=login');
			$password = md5(md5(trim((!isset($_GET['card']) ? $_POST['password'] : $_SESSION['card_password']))).$r['encrypt']);
			
			if($r['password'] != $password) {
				$ip = ip();
				if($rtime && $rtime['times'] < $maxloginfailedtimes) {
					$times = $maxloginfailedtimes-intval($rtime['times']);
					$this->times_db->update(array('ip'=>$ip,'isadmin'=>1,'times'=>'+=1'),array('username'=>$username));
				} else {
					$this->times_db->delete(array('username'=>$username,'isadmin'=>1));
					$this->times_db->insert(array('username'=>$username,'ip'=>$ip,'isadmin'=>1,'logintime'=>SYS_TIME,'times'=>1));
					$times = $maxloginfailedtimes;
				}
				showmessage(L('password_error',array('times'=>$times)),'?m=admin&c=index&a=login',3000);
			}
			$this->times_db->delete(array('username'=>$username));
			
			//查看是否使用口令卡
			if (!isset($_GET['card']) && $r['card'] && pc_base::load_config('system', 'safe_card') == 1) {
				$_SESSION['card_username'] = $username;
				$_SESSION['card_password'] = $_POST['password'];
				header("location:?m=admin&c=index&a=public_card");
				exit;
			} elseif (isset($_GET['card']) && pc_base::load_config('system', 'safe_card') == 1 && $r['card']) {//对口令卡进行验证
				isset($_SESSION['card_username']) ? $_SESSION['card_username'] = '' : '';
				isset($_SESSION['card_password']) ? $_SESSION['card_password'] = '' : '';
				isset($_SESSION['card_password']) ? $_SESSION['card_verif'] = '' : '';
			}
			
			$this->db->update(array('lastloginip'=>ip(),'lastlogintime'=>SYS_TIME),array('userid'=>$r['userid']));
			$_SESSION['userid'] = $r['userid'];
			$_SESSION['roleid'] = $r['roleid'];
			$_SESSION['pc_hash'] = random(6,'abcdefghigklmnopqrstuvwxwyABCDEFGHIGKLMNOPQRSTUVWXWY0123456789');
			$_SESSION['lock_screen'] = 0;
			$default_siteid = self::return_siteid();
			$cookie_time = SYS_TIME+86400*30;
			if(!$r['lang']) $r['lang'] = 'zh-cn';
			param::set_cookie('admin_username',$username,$cookie_time);
			param::set_cookie('siteid', $default_siteid,$cookie_time);
			param::set_cookie('userid', $r['userid'],$cookie_time);
			param::set_cookie('admin_email', $r['email'],$cookie_time);
			param::set_cookie('sys_lang', $r['lang'],$cookie_time);
			showmessage(L('login_success'),'?m=admin&c=index');
			//同步登陆vms,先检查是否启用了vms
			$video_setting = getcache('video', 'video');
			if ($video_setting['sn'] && $video_setting['skey']) {
				$vmsapi = pc_base::load_app_class('ku6api', 'video');
				$vmsapi->member_login_vms();
			}
		} else {
			pc_base::load_sys_class('form', '', 0);
			include $this->admin_tpl('login');
		}
	}
}
?>