<?php
/**
 * 提供多种回调形式保存及运行的辅助类
 */
class callback_parse {
	private $params;
	protected $retry=0;

	protected function __construct() {
	;
	}

	/**
	 * 返回一个模块类中指定方法的回调保存
	 * @param string $module 模块
	 * @param string $class 类名
	 * @param string $method 要调用方法
	 * @param bool $isstatic 是否静态调用
	 * @param array $args 调用方法时传递的参数，按顺序，无需键值
	 * @return \callback_parse
	 */
	public static function app_class_callback($module,$class,$method,$isstatic=0,$args=array()){
		$callback=new callback_parse();
		$callback->params=array(
			'type'=>'app_class',
			'module'=>$module,
			'class'=>$class,
			'method'=>$method,
			'static'=>$isstatic,
			'args'=>$args
		);
		return $callback;
	}
	
	/**
	 * 返回一个系统类中指定方法的回调保存
	 * @param string $path 系统类路径，相对于phpcms/,如 libs/classes/ffcs
	 * @param string $class 类名
	 * @param string $method 要调用方法
	 * @param bool $isstatic 是否静态调用
	 * @param array $args 调用方法时传递的参数，按顺序，无需键值
	 * @return \callback_parse
	 */
	public static function sys_class_callback($path,$class,$method,$isstatic=0,$args=array()){
		$callback=new callback_parse();
		$callback->params=array(
			'type'=>'sys_class',
			'path'=>$path,
			'class'=>$class,
			'method'=>$method,
			'static'=>$isstatic,
			'args'=>$args
		);
		return $callback;
	}
		
	/**
	 * 返回一个指定对象指定方法的回调保存
	 * @param type $obj 指定对象
	 * @param type $method 要调用方法
	 * @param type $args 调用方法时传递的参数，按顺序，无需键值
	 * @param type $loadpath 这个对象的加载路径，如果是系统类，则需提供array('class'=>'','path'=>''),模块类则提供array('class'=>'','module'=>'')
	 * @return \callback_parse
	 */
	public static function wakeup_class_callback($obj,$method,$args=array(),$loadpath=array()){
		$callback=new callback_parse();
		$callback->params=array(
			'type'=>'wakeup_class',
			'object'=> serialize($obj),
			'method'=>$method,
			'args'=>$args,
			'loadpath'=>$loadpath
		);
		return $callback;
	}
	
	/**
	 * 执行当前对象上的回调并返回执行结果，若执行结果为FALSE，则将重试计数加1
	 * @return object
	 */
	public function execute(){
		$data=$this->params;
		switch ($data['type']){
			case 'app_class':
				pc_base::load_app_class($data['class'],$data['module'],0);
				if($data['static']){
					$callback=array( $data['class'],$data['method']);
				}else{
					$class=new $data['class']();
					$callback=array( &$class,$data['method']);
				}
				break;				
			case 'sys_class':
				pc_base::load_sys_class($data['class'],$data['path'],0);
				if($data['static']){
					$callback=array( $data['class'],$data['method']);
				}else{
					$class=new $data['class']();
					$callback=array( &$class,$data['method']);
				}
				break;
			case 'wakeup_class':
				foreach($data['loadpath'] as $item){
					if(is_array($item)){
						if($item['module']) pc_base::load_app_class ($item['class'],$item['module'],0);
						else pc_base::load_sys_class ($item['class'],$item['path'],0);
					}else{
						if(is_file($item)) include $item ;
					}
				}
				$obj=  unserialize($data['object']);
				$callback=array( &$obj,$data['method']);
				break;
			default :
				logs('无效的回调类型设置'.$data['type']);
				return;
		}
		$this->retry++;//采用先加再减的形式，避免出现异常未减计数
		$res=call_user_func_array($callback, $data['args']);
		
		if($res!==FALSE) $this->retry--;
		return $res;
	}

	/**
	 * 获取或设置当前重试的次数
	 * @param int $retry
	 * @return int
	 */
	public function retry_count($retry=null){
		if(isset($retry)) $this->retry=intval($retry);
		else return $this->retry;
	}
}
