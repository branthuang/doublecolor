<?php
/**
 * 消息队列的工厂
 */
class taskqueue_factory {
	private function __construct() {
		
	}
	
		
	/**
	 * 根据配置返回指定类型的消息队列实现
	 * @staticvar array $objs
	 * @param string $cfgname appbase下的配置节点名，该节点下需包含array('provider'=>'','config'=>array())
	 * @return taskqueue_mysql|boolean
	 */
	public static function get_provider($cfgname='taskqueue'){
		static $objs=array();
		$cfg=pc_base::load_config('appbase',$cfgname);
		if(!empty($cfg) && $cfg['provider'] ) {
			$key=$cfg['provider']. ($cfg['config']?hashcode($cfg['config']):'');
			if(!isset($objs[$key])) {
				$classname='taskqueue_'.$cfg['provider'];
				pc_base::load_sys_class($classname,'libs/classes/ffcs',0);
				$objs[$key]=new $classname($cfg['config']);
			}
			return $objs[$key];
		}
		logs('队列操作配置了无效的选项'.$cfg,LEVEL_FATAL);
		return false;
	}
}
