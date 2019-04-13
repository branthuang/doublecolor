<?php

class httpcontext {

	private function __construct() {
		
	}

	/**
	 * 单例方法实现
	 * @staticvar httpcontext $instance
	 * @return Ihttpcontext
	 */
	public static function current() {
		static $instance;
		if (!isset($instance)) {
			$instance = defined('IS_API_HANDLER') ? new apicontext(): new portalcontext();
		}
		return $instance;
	}

}
interface Ihttpcontext{

	/**
	 * 取得当前http上下文中的有效票据，无有效票据则返回false
	 * @return userticket
	 */
	public function get_userticket();
	
	/**
	 * 将当前用户票据持久化
	 * @return null
	 */
	public function save_userticket($ticket);
	/**
	 * 销毁当前http上下文中的票据
	 */
	public function destory_ticket();
	/**
	 * 为当前http上下文设置票据
	 */
	public function set_userticket($ticket);
	public function is_changed_ticket();
}
class portalcontext implements Ihttpcontext{

	private $is_changed_ticket = false;
	private $userticket;
	public function __construct(){
		//启动session，终端适配信息依赖session
		$session_storage = 'session_' . pc_base::load_config('system', 'session_storage');
		pc_base::load_sys_class($session_storage);
	}

	/**
	 * 取得当前http上下文中的有效票据，无有效票据则返回false
	 * @return userticket
	 */
	public function get_userticket() {
		
		if (!isset($this->userticket)) {
			pc_base::load_sys_class('userticket', 'libs/classes/ffcs', 0);
			pc_base::load_sys_class('param', '', 0);
			$userid = param::get_cookie('userid');
			if(isset($_SESSION['userid']) && isset($_SESSION['roleid']) && $_SESSION['userid'] && $_SESSION['roleid'] && $userid == $_SESSION['userid']) 
			{
				$this->userticket = new userticket(array(
					'userid'=>$_SESSION['userid'],
					'username'=>$_SESSION['username'],
					'cpid'=>$_SESSION['cpid'],
					'is_cp_admin'=>$_SESSION['is_cp_admin'],
					'privs'=>$_SESSION['privs'],
                                        'roleid' => $_SESSION['roleid']
				));
				return $this->userticket;
			}
			$this->userticket = new userticket(array());
		}

		return $this->userticket;
	}

	/**
	 * 将当前用户票据持久化
	 * @return null 
	 */
	public function save_userticket($ticket) {	
		pc_base::load_sys_class('param', '', 0);
		
		$_SESSION['logouted']=0;
		$_SESSION['userid'] = $ticket['userid'];
		$_SESSION['username'] = $ticket['username'];
		$_SESSION['cpid']=$ticket['cpid'];
		$_SESSION['is_cp_admin']=$ticket['cpstatus']==2;
		$roleids=array_keys($ticket['rolelist']);
		$_SESSION['roleid'] = reset($roleids);//兼容原来程序
		$_SESSION['rolelist']=$ticket['rolelist'];
		$_SESSION['pc_hash'] = random(6,'abcdefghigklmnopqrstuvwxwyABCDEFGHIGKLMNOPQRSTUVWXWY0123456789');
		$_SESSION['lock_screen'] = 0;
		$_SESSION['privs']=$ticket['privs'];
		$cookie_time = SYS_TIME+86400*30;
		if(!$ticket['lang']) $_SESSION['lang'] = 'zh-cn';
		param::set_cookie('admin_username',$ticket['username'],$cookie_time);
		param::set_cookie('siteid', 1,$cookie_time);
		param::set_cookie('userid', $ticket['userid'],$cookie_time);
		param::set_cookie('admin_email', $ticket['email'],$cookie_time);
		param::set_cookie('sys_lang', $ticket['lang'],$cookie_time);
	}

	/**
	 * 销毁当前http上下文中的票据
	 */
	public function destory_ticket() {

		pc_base::load_sys_class('param', '', 0);
		$this->userticket = false;
		$_SESSION=array('logouted'=>1);
		param::set_cookie('admin_username','');
		param::set_cookie('userid',0);
	}

	/**
	 * 为当前http上下文设置票据
	 */
	public function set_userticket($ticket) {
		$this->userticket = $ticket;
		$this->is_changed_ticket = false;
	}

	/**
	 * 返回当前票据是否修改过
	 * @return bool 
	 */
	public function is_changed_ticket() {
		return $this->is_changed_ticket;
	}

}