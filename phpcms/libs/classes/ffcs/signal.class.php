<?php
/**
 * 用于保存多个事件的回调列表
 */
class signal{
	private $signals=array();
	
	public function __construct($recv=null){
		if($recv){			
			$ref = new ReflectionClass(get_class($recv));
			foreach($ref->getMethods()  as $method){
				if(!$method->isPublic()) continue;
				$name=strtolower($method->name);
				if(!isset($this->signals[$name])) $this->signals[$name]=array();
				$this->signals[$name][]=array($recv,$name);
			}
		}
	}
	
	/**
	 * 为指定的事件添加一条回调
	 * @param string $signal 事件名
	 * @param callback $callback php中的callback
	 */
	public function add($signal,$callback){
		$signal=strtolower($signal);
		if(!isset($this->signals[$signal])) $this->signals[$signal]=array();
		$this->signals[$signal][]=$callback;
	}
	
	/**
	 * 执行指定事件上的所有回调
	 * @param string $signal_name 事件名
	 * @param variable arguments $_args 执行时的参数，这里是不定长参数
	 * @return object/bool 执行的情况，多个回调时true，单个回调返回执行结果
	 */
	public function invoke($signal_name,$_args=null){
		$signal_name=  strtolower($signal_name);
		if(!isset($this->signals[$signal_name]) || count($this->signals[$signal_name])==0){
			logs('invoke时信号'.$signal_name.'无注册',LEVEL_DEBUG);
			return true;
		}
		$stack = debug_backtrace();
		$args = array();
		if (isset($stack[0]["args"])){
			for($i=1; $i < count($stack[0]["args"]); $i++)
				$args[] = & $stack[0]["args"][$i];
		}
		foreach($this->signals[$signal_name] as $event){
			$result=call_user_func_array($event,$args);
		}
		//若为委托列表就不返还具体执行的结果
		return count($this->signals[$signal_name])==1 ? $result : true;
	}
	
	/**
	 * 查找指定事件上是否有定义回调
	 * @param string $signal_name
	 */
	public function isempty($signal_name){
		$signal_name=  strtolower($signal_name);
		return !isset($this->signals[$signal_name]) || count($this->signals[$signal_name])==0;
	}
	
	/**
	 * 返回已注册的事件列表
	 * @return array
	 */
	public function handlers(){
		return array_keys($this->signals);
	}
}
