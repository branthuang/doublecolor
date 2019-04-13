<?php
/**
 * 对实体文件操作的工厂类
 */
class file_helper {
	protected $cfg;
	private function __construct() {
		$this->cfg=pc_base::load_config('substance');
	}
	
	/**
	 * 
	 * @return \file_helper
	 */
	public static function get_instance(){
		static $obj;
		if(!isset($obj)){
			$obj=new file_helper();
		}
		return $obj;
	}
	
		
	/**
	 * 
	 * @param type $cfg_node_name
	 * @return \file_raid|boolean
	 */
	public function get_provider($cfg_node_name){
		//static $objs=array();
		if($cfg=pc_base::load_config('substance',$cfg_node_name)) {
			//$key=$cfg['provider']. ($cfg['config']?hashcode($cfg['config']):'');
			//if(!isset($objs[$key])) {
				$classname='file_'.$cfg['provider'];
				pc_base::load_sys_class($classname,'libs/classes/ffcs',0);
				$objs=new $classname($cfg['config'],$cfg_node_name);
			//}
			return $objs;
		}
		logs('文件操作配置了无效的选项',LEVEL_FATAL);
		return false;
	}
	
	public static function get_nodename($path){
		return $path{0}=='@' ? substr($path,1,strpos($path,'/')-1) :'';
	}
	
	public static function get_fullpath($path){
		if($node=self::get_nodename($path)){
			if($cfg=pc_base::load_config('substance',$node)){
				return formatpath($cfg['config']['base_phy_dir'].'/'.substr($path,strlen($node)+2),1);
			}
		}
		return $path;
	}
}
