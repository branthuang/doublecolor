<?php
pc_base::load_sys_class('httpcontext','libs/classes/ffcs',0);
pc_base::load_sys_class('cache_mul','libs/classes/ffcs',0);
class apicontext implements Ihttpcontext{
	private $terminal_adapter;
	private $portal;
	private $is_changed_ticket = false;
	private $userticket;
	private $ticket_key;

	public function __construct(){
		pc_base::load_sys_class('userticket','libs/classes/ffcs',0);
		$this->ticket_key='at|'.$this->get_access_token();
	}
	
	private function get_memcache(){
		static $cache;
		if(!isset($cache)){
			$setting = pc_base::load_config('apinew','accesstoken');
			$cache = new cache_mul($setting);
		}
		return $cache;
	}

	/**
	 * 获取门户的实例
	 * @return portal
	 */
	public function get_portal() {
		if (!isset($this->portal)) {
			pc_base::load_sys_class('portal', 'libs/classes/ffcs', 0);
			$this->portal = new portal('api');
		}
		return $this->portal;
	}

	/**
	 * 取得当前http上下文中的有效票据
	 * @return userticket
	 */
	public function get_userticket() {
		if(!isset($this->userticket))	{
			$data= $this->get_memcache()->get($this->ticket_key);
			
			$key=substr($this->ticket_key,3);
			$appid=$this->get_appid();
			if($data){
				if ($data['expires_in'] < time()) {
					// 过期的ac如果不是emp的就提示过期,过期的还是要去EMP查询下是否有用刷新令牌更新
					if (! $this->is_emp_ac($appid)) {
						throw new Exception('', 111); // access token过期
					}
				} else {
					if($data['appid']!=$appid){
						throw new Exception('', 110); // access token无效						
					}
					$this->userticket = new userticket($data,$key);
					return $this->userticket;
				}
			}
			
			throw new Exception('', 110); // 无效的access token
		}
		return $this->userticket;
	}

	/**
	 * 判断是否是EMP的应用标识
	 *
	 * @param string $appid
	 */
	public function is_emp_ac($appid=false) {
		$appid = $appid ? $appid : $this->get_appid();
		return $appid && strpos($appid, 'dm_') !== 0;
	}

	/**
	 * 获取当前会话对应的UDB用户标识
	 */
	private function get_userinfo_from_emp($ac) {
		$apiid=$this->get_apiid();
		$rel_db=pc_base::load_model('api_ability_rel_model');
		$abids=$rel_db->select(array('apiid'=>$apiid),'abid');
		if($abids){
			foreach($abids as $ab){
				$xml.="<ei_id>{$ab['abid']}</ei_id>";
			}
		}else{
			logs('找不到接口 '.$apiid.' 对应的能力标识',5,false,CACHE_PATH.'apinew.log');
		}
		$cfg=pc_base::load_config('apinew');
		
		$request = '<?xml version="1.0" encoding="UTF-8"?><token_verify_req><access_token>' . $ac . '</access_token>
		<app_id>' . $this->get_appid() . '</app_id>
		<enabler_id>' . $cfg['enabler_id'] . '</enabler_id>
		<ei_id_array>' . $xml . '</ei_id_array>
		</token_verify_req>';
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $cfg['token_verify_url']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		
		return simplexml_load_string($response);
	
	}
	
	/**
	 * 获取当前会话的ac
	 */
	public function get_access_token() {
		static $access_token;
		if (! isset($access_token)) {
			$access_token = trim($_SERVER['HTTP_ACCESS_TOKEN']);
		}
	
		return $access_token;
	}
	
	/**
	 * 获取当前会话的appid
	 */
	public function get_appid() {
		static $appid;
		if (! isset($appid)) {
			$appid = trim($_SERVER['HTTP_APP_ID']);
		}
	
		return $appid;	
	}
	
	public function get_ip(){
		$ip=$_SERVER['HTTP_API_REMOTEIP'];
		if(preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$ip)) return $ip;
		else return ip();
	}
	
	public function get_apiid(){
		$act = strtolower(trim($_GET['a']));
		$op = trim($_GET['op']);
		return $op . ($act && $act!='lists'? '.' . $act:'');
	}
	
	/**
	 * 返回当前终端的适配对象
	 * @return terminal_adapter
	 */
	public function get_terminal_adapter() {
		if (!isset($this->terminal_adapter)) {
			pc_base::load_sys_class('terminal_adapter2', 'libs/classes/ffcs', 0);
			if (is_array($_SESSION['apdp_info'])) {
				//用保存的数据实例化一个适配器对象
				$this->terminal_adapter = new terminal_adapter2($_SESSION['apdp_info']);
			} else {
				//根据当前终端自动获取一个适配器对象
				$this->terminal_adapter = terminal_adapter2::current();
			}

			if ($this->terminal_adapter->has_modify()) {
				//保存适配信息
				$_SESSION['apdp_info'] = $this->terminal_adapter->get_result();
			}
		}

		return $this->terminal_adapter;
	}

	/**
	 * 将当前用户票据持久化
	 * @return null
	 */
	public function save_userticket($ticket,$ttl=1800) {
		if (!$ticket instanceof userticket) {
			return false;
		}
		$ttl=intval($ttl);
		$ttl = $ttl ? $ttl : 1800;
		$datas=$ticket->toarray();		
		$datas['expires_in']=time()+$ttl;
		if (! $this->get_memcache()->set('at|'.$ticket->get_ticketid(), $datas, $ttl)) {
			throw new Exception('', 800);
		}
			
			
		// 记录at到表以方便报表查找，该表保存一段后可被清空
		$acdb = pc_base::load_model('api_accesstoken_histroy_model');
		$dat = array(
			'ua' => new_addslashes($_SERVER['HTTP_USER_AGENT']), 
			'ip' => $this->get_ip(), 
			'ssoid' => $datas['ssoid'], 
			'ssotype' => $datas['ssoattr']['type'], 
			'udbuserid' => $datas['ssoattr']['userid']
		);
		$acdb->insert(array(
			'access_token' => $ticket->get_ticketid(), 
			'userid' => $datas['userid'], 
			'addtime' => SYS_TIME, 
			'appid' => $datas['appid'], 
			'data' => json_encode($dat),
			'expiretime'=>$datas['expires_in'],
		));
		
		return true;
	}

	/**
	 * 销毁当前http上下文中的票据
	 */
	public function destory_ticket() {
		$this->userticket = false;
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
?>