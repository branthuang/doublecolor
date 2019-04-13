<?php
pc_base::load_sys_class('cache_mul','libs/classes/ffcs',0);
abstract class apinew {
	protected $config;
	protected $siteid=1;
	protected $url_pre='http://dm.189.cn/';
	protected $appid;
	
	final public function __construct() {
		$this->config = pc_base::load_config('apinew');
		
		$this->check_app_status();
		$this->init();
	}
	
	protected function check_app_status(){
		// 这里兼容EMP规范，要求强制携带appid
		$this->appid=httpcontext::current()->get_appid();
		if (! $this->appid) {
			throw new Exception('', 101); // api key无效
		}
		$appinfo=$this->get_appinfo();
		if(!$appinfo){
			throw new Exception('', 900); // 访问的应用不存在
		}
		if(intval($appinfo['status'])!==0 && intval($appinfo['status'])!==1){
			throw new Exception('',909); //应用状态不正确
		}
	}
	
	protected function init(){
		$appinfo=$this->get_appinfo();
		//客户端应用需要携带access_token
		if($appinfo['app_type']==1){
			$ticket=httpcontext::current()->get_userticket();
			if(!$ticket->isauthentic()){
				throw new Exception('',110);
			}
		}
	}
	
	/**
	 *
	 * @param unknown_type $name        	
	 * @param unknown_type $m        	
	 * @param string $check
	 *        	int,{6,10},no
	 * @return string
	 */
	protected static function get_param($name, $m = 'post', $check = '') {
		$str = trim($m == 'post' ? $_POST[$name] : $_GET[$name]);
		if ($check != 'no') {
			if ($check == 'int') {
				if (strval(intval($str)) != $str) {
					throw new Exception(':'.$name, ($name == 'timestamp' ? 107 : 100));
				}
			} elseif ($check{0} == '{') {
				if (! isset($str{0})){
					throw new Exception(':'.$name, 100);
				}
				$check = substr($check, 1, - 1);
				$i = strpos($check, ',');
				if ($i === false) {
					if (strlen($str) < $check){
						throw new Exception(':'.$name, 100);
					}
				} elseif ($i === 0) {
					if (strlen($str) > substr($check, 1)){
						throw new Exception(':'.$name, 100);
					}
				} else {
					if (strlen($str) < substr($check, 0, $i) || strlen($str) > substr($check, $i + 1)){
						throw new Exception(':'.$name, 100);
					}
				}
			}elseif ($check{0} == '/') {
				if (!preg_match($check,$str)){
					throw new Exception(':'.$name, 100);
				}
			}elseif(!isset($str{0})){
				throw new Exception(':'.$name, ($name == 'timestamp' ? 107 : 100));
			}
		}
		return $str;
	}

	/**
	 * 校验接口签名
	 * @throws Exception
	 * @return boolean
	 */
	public function check_sig() {
		if($sig=$_POST['sig']){
			$params = $_POST;
		}elseif($sig=$_GET['sig']){
			$params = $_GET;
			unset($params['a'], $params['op'], $params['format'],$params['m']);
		}
		if( $sig){
			
			$app = $this->get_appinfo($this->appid);
			if (! $app) {
				throw new Exception('', 900); // 访问的应用不存在
			}
			
			unset($params['sig']);
			ksort($params);
			foreach ( $params as $k => $v ){
				$txt .= $k . '=' . stripcslashes($v);
			}
			if(strtolower($sig)== md5($txt . $app['appid'] . $app['secret'])) return true;
		}
		if(substr($app['appid'],0,5) == 'dm_ff'){
			logs(($_POST?'POST ':'GET ') . ' - ' . $_SERVER['REQUEST_URI'] .  ' - ' . $txt,LEVEL_DEBUG,0,LOGS_PATH . 'check_sig.log');
		}
		
		throw new Exception('',104);//无效签名
	}

	public function get_ip(){
		return httpcontext::current()->get_ip();
	}
	
	/*
	 * 检查当前应用的调用权限
	 */
	public function check_permission(){
		$apiid=httpcontext::current()->get_apiid(); //获取接口唯一标示
		$key='app-api|'.$this->appid;
		//内部的账号先不做权限判断
		if(strpos($this->appid, 'dm_') === 0){return;}
		if(in_array($_SERVER['HTTP_X_REAL_IP'],$this->config['allow_ip'])){//来自emp的调用,通过ip进行校验
			$val = apinew::get_datacache_instance()->get($key);
			if($val){
				return;
			}
			$app = $this->get_appinfo();
			$api_apps = pc_base::load_model('api_apps_model');
			$sql = " id = '".$app['id']."' and status !=2 and devsign!=''";
			$rs = $api_apps->get_one($sql);
			if(!empty($rs)){
				apinew::get_datacache_instance()->set($key,'1',3600*24);
				return;
			}else{
				throw new Exception('',120);
			}
		}
		$val=apinew::get_datacache_instance()->get($key);
		if(!$val){
			$rel_db=pc_base::load_model('api_app_rel_model');
			$app=$this->get_appinfo();
			$rs=$rel_db->select(array('app_id'=>$app['id']),'apiid','','','','apiid');
			if($rs) {
				$apis_str=strtolower(implode(',',array_keys($rs)));
				apinew::get_datacache_instance()->set($key,implode(',',$apis_str),3600*24);
				$apis_str=','.$apis_str.',';
			}else{
				$apis_str='';
				apinew::get_datacache_instance()->set($key,'',3600*24);
			}
		}else{
			$apis_str=','.$val.',';
		}
		if(strpos($apis_str,','.strtolower($apiid).',')===false){
			throw new Exception('',120);
		}
	}
	
	protected function get_appinfo($appid='') {
		static $appinfo;
		$appdb = pc_base::load_model('api_apps_model');
		if(empty($appid) || $appid==$this->appid){
			if(!isset($appinfo)){
				//这里缓存应用到内存，注意和emp_support的应用签约信息同步接口配合
				$key='appinfo|'.$this->appid;
				if($val=self::get_datacache_instance()->get($key)){
					$appinfo=$val;
				}else{
					if($appinfo=$appdb->get_one(array(
							'appid' => $this->appid
						))){
						self::get_datacache_instance()->set($key, $appinfo, 3600*24);
					}
				}
			}
			return $appinfo;
		}else{
			return $appdb->get_one(array(
				'appid' => $appid
			));
		}
	}
	
	/**
	 * 日志记录
	 * @param string $msg 错误描述
	 * @param int $level 错误级别  1 debug 2 info 3 warn 4 error 5 fatal
	 * @param bool $isdetail 是否记录堆栈上的详细信息
	 * @param string $logpath 日志的位置
	 */
	protected static function log($str,$level = 4, $isdetail=false){
		/*$p[]='GET:'.$_SERVER['REQUEST_URI'];
		foreach($_SERVER as $k=>$v){ 
			if(strpos($k,'HTTP_')===0) $h[$k]=$v;
		}
		$p[]='HEADER:'.http_build_query($h);
		$p[]='POST:'.http_build_query($_POST);
		*/
		logs($str,$level,$isdetail,LOGS_PATH.'apinew.log');
	}
	
	protected function create_optoken($expires_in = 180) {
		$guid = strtr(guid(), '-', '');
		
		$cache_mul = new cache_mul($this->config['hashcache']);
		if (! $cache_mul->set('optoken|' . $guid, time() + $expires_in, $expires_in)) {
			throw new Exception('', 800);
		}
		return $guid;
	}
	protected function use_optoken($token) {
		$cache_mul = new cache_mul($this->config['hashcache']);
		$val=$cache_mul->get('optoken|' . $token);
		if($val){
			$cache_mul->delete('optoken|' . $token);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取会话中唯一的ua_key
	 * Enter description here ...
	 */
	protected function get_ua_key(){
		$ticket=httpcontext::current()->get_userticket();
		if($ticket){
			return $ticket['sid'];
		}
		return false;
	}
	
	
	/**
	 * @return cache_mul
	 */
	public static function get_datacache_instance(){
		static $cache;
		if(!isset($cache)){
			$config = pc_base::load_config('apinew');
			$cache = new cache_mul($config['hashcache']);
		}
		return $cache;
	}

	/**
	 * @return cache_mul
	 */
	public static function get_datacache_ua_instance(){
		static $ua_cache;
		if(!isset($ua_cache)){
			$config = pc_base::load_config('common');
			$ua_cache = new cache_mul($config['sid1']);  //配置 文件
		}
		return $ua_cache;
	}
	
	/**
	 * @return cache_mul
	 */
	public static function get_datacache_ua_instance2(){
		static $ua_cache2;
		if(!isset($ua_cache2)){
			//$config = pc_base::load_config('apinew');
			//$ua_cache = new cache_mul($config['hashcache']);  //配置 文件
			$config = pc_base::load_config('common');
			$ua_cache2 = new cache_mul($config['sid2']);  //配置 文件
		}
		return $ua_cache2;
	}
	
	/**
	 * 判断用户是否绑定
	 * Enter description here ...
	 * @throws Exception
	 */
	public static function get_user_bind(){
		$ticket=httpcontext::current()->get_userticket();
		$uid = $ticket['userid'];
		if (! $uid) {
			throw new Exception('',100);
		}
		pc_base::load_app_class('member_service','member',0);
		$memberinfo = member_service::selectbyid($uid);
		$ssodata = member_service::sso_str2data($memberinfo['ssodata'],false);
		$bind_mobile = 0;
		if($ssodata && is_array($ssodata) && count($ssodata) > 0){
			foreach ($ssodata as $v){
				if(($v['ssotype'] == member_service::TYPE_UDBUSERID && strlen($v['ssoid']) == 11 && strpos($v['ssoid'],'1') === 0)
					|| ($v['ssotype'] == member_service::TYPE_MPHONE) || ($v['ssotype'] == member_service::TYPE_UDBSUITE)){
					$bind_mobile = 1;
				}
			}
		}
		return $bind_mobile;
	}
	
	/**
	 * 获取ac的memcache
	 * Enter description here ...
	 */
	public static function get_datacache_ac_instance(){
		static $ac_cache;
		if(!isset($ac_cache)){
			$setting = pc_base::load_config('apinew','accesstoken');
			$ac_cache = new cache_mul($setting);
		}
		return $ac_cache;
	}
}

?>