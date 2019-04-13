<?php
/**
 * OAuth协议的通讯辅助类
 */
class api_helper {
	private $client_id;
	private $client_secret;
	private $access_token;
	private $url;
	private $timeout = 5;
	private $connecttimeout = 3;
	private $format = 'json';
	private $decode_json = TRUE;
	private $http_info;
	private static $boundary = '';
	private $useragent ;

	/**
	 * 
	 * @param string $client_id 应用标识
	 * @param string $client_secret 通讯密钥
	 */
	public function __construct($client_id, $client_secret) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->useragent='WebService(FFCS/;'.$_SERVER['HTTP_USER_AGENT'].')';
	}
	
	/**
	 * 获取或设置返回的数据格式
	 * @param string $format 要返回的格式，可设置为json，为null时返回当前设置
	 * @return string
	 */
	public function format($format=null){
		if(isset($format)) $this->format=$format;
		else return $this->format;
	}
	
	/**
	 * 设置accesstoken
	 * @param string $token accesstoken
	 */
	public function set_token($token){
		$this->access_token=$token;
	}	
		
	/**
	 * 使用GET方式请求接口并返回结果
	 * @param string $url 接口的地址
	 * @param array $parameters 携带的参数数组
	 * @param bool $needauth 是否需要认证，如果需要将在header里发送先前设置的accesstoken
	 * @return object
	 * @throws Exception
	 */
	public function get($url, $parameters = array(),$needauth=true) {
		$response = $this->request($url, 'GET', $parameters,false,$needauth);
		if ($this->format === 'json' && $this->decode_json) {
			$obj=json_decode($response,true);
			if($response==='' || $obj===null){
				throw new Exception('响应内容不是合法的json数据');
			}
			return $obj;
		}
		return $response;
	}
		
	/**
	 * 使用POST方式请求接口并返回结果
	 * @param string $url 接口的地址
	 * @param array $parameters 携带的参数数组
	 * @param type $multi
	 * @param bool $needauth 是否需要认证，如果需要将在header里发送先前设置的accesstoken
	 * @return type
	 * @throws Exception
	 */
	public function post($url, $parameters = array(), $multi = false,$needauth=true) {
		$response = $this->request($url, 'POST', $parameters, $multi ,$needauth);
		if ($this->format === 'json' && $this->decode_json) {
			$obj=json_decode($response,true);
			if($response==='' || $obj===null){
				throw new Exception('响应内容不是合法的json数据');
			}
			return $obj;
		}
		return $response;
	}
	
	/**
	 * 获取或设置返回的超时时间
	 * @param int $timeout 从建立连接后到返回结果的超时时间，单位秒
	 * @return int
	 */
	public function timeout($timeout=null){
		if(isset($timeout)) $this->timeout=$timeout;
		else return $this->timeout;
	}
	
	/**
	 * 将单条header字符串拆分为键值对存于类变量中并返回长度
	 * @param type $ch
	 * @param string $header
	 * @return int
	 */
	protected function getHeader($ch, $header) {
		$i = strpos($header, ':');
		if (!empty($i)) {
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}
		return strlen($header);
	}
	
	/**
	 * 向指定地址发送请求
	 * @param string $url 请求地址
	 * @param string $method 请求方式，值有GET/POST
	 * @param array $postfields POST参数数组
	 * @param array $headers 附带的header信息
	 * @param bool $needauth 是否需要传递ac
	 * @return string http响应字符串
	 * @throws Exception
	 */
	protected function http($url, $method, $postfields = NULL, $headers = array(),$needauth=true) {		
		$ci = curl_init();
		
		curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_ENCODING, "");
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);

		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
					$this->postdata = $postfields;
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}

		$headers[] = "app_id: ".$this->client_id;
		if ( isset($this->access_token) && $this->access_token && $needauth )
			$headers[] = "access_token: ".$this->access_token;

		$headers[] = "API-RemoteIP: " . ip();
		curl_setopt($ci, CURLOPT_URL, $url );
		curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
		
		$response = curl_exec($ci);
		
		$http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		logs('API URL=['.$url.'] CONTENT=['.$response.'] POST=['. json_encode($postfields,1).'] ACC=['.$this->access_token.']',LEVEL_DEBUG);
		
		logs('API URL=['.$url.'] CONTENT=['.$response.'] ',LEVEL_INFO);
		
		if($http_code!=200 ){
			throw new Exception('HTTP请求响应错误代码'.$http_code);
		}
		return $response;
	}
	
	protected function request($url, $method, $parameters, $multi = false,$needauth=true) {
		$parameters['sig']=$this->getsign($parameters);
		switch ($method) {
			case 'GET':
				$url = $url . '?' . http_build_query($parameters);
				return $this->http($url, 'GET',null,array(),$needauth);
			default:
				$headers = array();
				if (!$multi && (is_array($parameters) || is_object($parameters)) ) {
					$body = http_build_query($parameters);
				} else {
					$body = self::build_http_query_multi($parameters);
					$headers[] = "Content-Type: multipart/form-data; boundary=" . self::$boundary;
				}
				return $this->http($url, 'POST', $body, $headers,$needauth);
		}
	}
	
	protected static function build_http_query_multi($params) {
		if (!$params) return '';

		uksort($params, 'strcmp');

		$pairs = array();

		self::$boundary = $boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';
		$multipartbody = '';

		foreach ($params as $parameter => $value) {

			if( in_array($parameter, array('pic', 'image')) && $value{0} == '@' ) {
				$url = ltrim( $value, '@' );
				$content = file_get_contents( $url );
				$array = explode( '?', basename( $url ) );
				$filename = $array[0];

				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
				$multipartbody .= "Content-Type: image/unknown\r\n\r\n";
				$multipartbody .= $content. "\r\n";
			} else {
				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
				$multipartbody .= $value."\r\n";
			}

		}

		$multipartbody .= $endMPboundary;
		return $multipartbody;
	}
	
	/**
	 * 获取指定参数数组的摘要
	 * @param array $params
	 * @return string
	 */
	public function getsign($params){
		$ps=$params;
		if(isset($ps['sig'])) unset($ps['sig']);
		ksort($ps);
		foreach($ps as $k=>$v){
			if($v{0}!='@') $txt.=$k.'='.$v;
		}
		return md5($txt.$this->client_id.$this->client_secret);
	}
}