<?php
pc_base::load_sys_func('io');
class file_raid {

	protected $cfg,$basepath,$nodename;

	public function __construct($cfg,$nodename='') {
		$this->cfg = $cfg;
		$this->nodename=$nodename;
	}

	/**
	 * 当前这个操作类操作的是否为本地文件
	 * @return boolean
	 */
	public function islocal(){
		return true;
	}
	/**
	 * 当前操作类所使用的节点名，如draft，仅在由file_helper返回时有效
	 * @return string
	 */
	public function nodename(){
		return $this->nodename;
	}
	/**
	 * 当前操作类所使用的节点前缀，如@drafe/,仅在由file_helper返回时有效
	 * @return type
	 */
	public function prefix_path(){
		return $this->nodename? '@'.$this->nodename.'/':'';
	}
	/**
	 * 获取或设置当前操作类的基础路径，最终文件的操作路劲=配置里面的路径+这个路径+文件相对路径
	 * @param string $path
	 * @return string
	 */
	public function basepath($path=false){
		if($path!==FALSE){
			$this->basepath=trim(formatpath($path),'/').'/';
		}else{
			return $this->basepath;
		}
	}
	/**
	 * 尝试删除一个指定路径
	 * @param string $path
	 * @param bool $isfile 是否是文件
	 * @return bool
	 */
	public function delete($path,$isfile=1){
		$path=$this->_absolutepath($path,$isfile);
		
		return io_unlink($path);
	}
	/**
	 * 返回指定路径是否是一个有效的文件
	 * @param string $filepath
	 * @return bool
	 */
	public function exists_file($filepath) {
		return is_file($this->_absolutepath($filepath,1));
	}
	
	/**
	 * 返回指定路径是否是一个有效的文件夹
	 * @param string $dirpath
	 * @return bool
	 */
	public function exists_dir($dirpath) {
		return is_dir($this->_absolutepath($dirpath));
	}
	
	/**
	 * 取回文件并置于本地临时目录，同时返回本地临时文件路径
	 * @param string $filepath
	 * @return boolean
	 */
	public function getfile_to_local($filepath){
		$path=$this->_absolutepath($filepath,1);
		if(!is_file($path)){
			logs('对应文件'.$path.'不存在',LEVEL_WARN);
			return false;
		}
		return $path;		
	}
	
	/**
	 * 取回文件夹并置于本地临时目录，同时返回本地临时文件夹路径
	 * @param string $filepath
	 * @return boolean
	 */
	public function getall_to_local($filepath){
		$path=$this->_absolutepath($filepath,1);
		return $path;
	}
	
	/**
	 * 逐级扫描目录并返回清单
	 * @param string $dirpath 扫描的基准路径
	 * @param bool $sort 是否排序
	 * @param int $type 1文件 2目录 3文件+目录，返回的类型
	 * @param int $deep 递归深度 默认1即为当前目录
	 * @return array
	 */
	public function scandir($dirpath,$sort=null,$type=3,$deep=1){		
		$dirpath=$this->_absolutepath($dirpath);
		logs('开始扫描'.$dirpath.'目录',LEVEL_INFO);
		return io_scandir($dirpath,'',$sort,$type,$deep);
	}

	/**
	 * 创建一个文件夹
	 * @param string $dirpath
	 * @param type $mod
	 * @return bool
	 */
	public function mkdir($dirpath,$mod=0755){
		$dir=$this->_absolutepath($dirpath,0);
		return io_mkdirs($dir,$mod);
	}
	
	/**
	 * 返回指定文件大小
	 * @param string $filepath
	 * @return int
	 */
	public function filesize($filepath){
		return filesize($this->_absolutepath($filepath,1));
	}
	
	/**
	 * 将本地文件/文件夹拷贝到远程指定路径下并返回是否成功
	 * @param string $localpath 
	 * @param string $remotepath
	 * @param bool $isfile 
	 * @param bool $overwrite 是否覆盖
	 * @param bool $forcecopy 强制拷贝，该项设置为false时，系统会检测文件大小是否一致，一致则跳过
	 * @return boolean
	 */
	public function push_local_to_remote($localpath,$remotepath,$isfile=1,$overwrite=true,$forcecopy=true){
		if( ($isfile && !is_file($localpath)) || (!$isfile && !is_dir($localpath))){
			logs('执行push_local_to_remote时本地路径'.$localpath.'不存在',LEVEL_ERROR);
			return false;
		}
		if(!$isfile)$remotepath=  rtrim($remotepath,'/\\').DIRECTORY_SEPARATOR;
		$_remotepath=$this->_absolutepath($remotepath,$isfile);
		
		if($isfile) $dir=dirname($_remotepath);
		else $dir=$_remotepath;
		
		if(!is_dir($dir)){
			if(!io_mkdirs($dir)){
				logs('执行push_local_to_remote时创建目标路径'.$dir.'失败',LEVEL_FATAL);
				return false;
			}
		}
		if( !$overwrite && ($isfile && is_file($_remotepath))  ){			
			logs('执行push_local_to_remote时目标路径'.$_remotepath.'已存在',LEVEL_ERROR);
			return false;
		}
		if(!$isfile){
			$basepath=rtrim($localpath,'/\\').DIRECTORY_SEPARATOR;
			foreach(scandir($localpath) as $v){
				if($v=='.' || $v=='..')	continue;
				if(is_dir($basepath.$v)) {
					if(!$this->push_local_to_remote ($basepath.$v, $remotepath.$v,0)) return false;
				}
				else{
					if(!$this->push_local_to_remote ($basepath.$v, $remotepath.$v)) return false;
				}
			}
		}else{
			if($forcecopy && is_file($_remotepath) && filesize($localpath)==filesize($_remotepath)){
				logs('执行push_local_to_remote从'.$localpath.' =>'.$_remotepath.' 时因文件大小一致跳过',LEVEL_INFO);
				return true;
			}
			if (copy($localpath, $_remotepath)) {				
				logs('执行push_local_to_remote从'.$localpath.' =>'.$_remotepath.' 成功',LEVEL_INFO);
				return true;
			}else{
				logs('执行push_local_to_remote从'.$localpath.' =>'.$_remotepath.' 失败',LEVEL_ERROR);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * 移动本地文件夹到远程指定路径下，并删除本地文件
	 * @param string $localpath
	 * @param string $remotepath
	 * @param bool $overwrite
	 * @return boolean
	 */
	public function move_dir_to_remote($localpath,$remotepath,$overwrite=true){
		
		$localpath=  rtrim(formatpath($localpath),'\\/');
		if(! is_dir($localpath)){
			logs('执行move_local_to_remote时本地路径'.$localpath.'不存在',LEVEL_ERROR);
			return false;
		}
		
		$_remotepath=  rtrim($this->_absolutepath($remotepath,0),'\\/');
		$p_path=dirname($_remotepath);
		if(!io_mkdirs($p_path)){
			logs('执行move_local_to_remote时远程路径'.$p_path.'创建失败',LEVEL_ERROR);
			return false;
		}
		if(is_dir($_remotepath)&& !$overwrite){
			logs('执行move_local_to_remote时目标路径'.$_remotepath.'已存在',LEVEL_FATAL);
			return false;
		}
		if(is_dir($_remotepath)){
			$tmp=dirname($_remotepath).DIRECTORY_SEPARATOR.'__'.SYS_TIME.random(5);
			if(rename($_remotepath, $tmp)){
				if(rename($localpath,$_remotepath)){
					if(!io_unlink($tmp)){						
						logs('执行删除临时目录 '.$tmp.'时失败',LEVEL_ERROR);
					}
					logs('执行rename目录 '.$localpath.'=>'.$_remotepath.'成功',LEVEL_INFO);
					return true;
				}else{
					logs('执行rename目录 '.$localpath.'=>'.$_remotepath.'时失败',LEVEL_ERROR);
					if(!rename($tmp,$_remotepath)){
						logs('回滚移动目录操作 '.$tmp.'=>'.$_remotepath.'时失败',LEVEL_ERROR);						
					}
				}
			}else{
				logs('执行rename目录 '.$_remotepath.'=>'.$tmp.'时失败',LEVEL_ERROR);
			}
			exit;
		}else{
			if(rename($localpath,$_remotepath)){
				logs('执行rename目录 '.$localpath.'=>'.$_remotepath.'成功',LEVEL_INFO);
				return true;
			}else{
				logs('执行rename目录 '.$localpath.'=>'.$_remotepath.'时失败',LEVEL_ERROR);
			}
		}
		return false;
	}
	
	/**
	 * 返回指定文件的绝对路径
	 * @param string $path 相对路径，不含basepath
	 * @param bool $isfile 是否文件
	 * @return stirng
	 */
	protected  function _absolutepath($path, $isfile = 0){
		return formatpath(($this->cfg['base_phy_dir']?$this->cfg['base_phy_dir']. '/':'') . $this->basepath. $path,$isfile);
	}

	/**
	 * 重命名远程路径下的指定文件
	 * @param string $remote_source_path
	 * @param string $remote_target_path
	 * @return bool
	 * @throws Exception
	 */
	public function rename_file($remote_source_path,$remote_target_path){
		$s=$this->_absolutepath($remote_source_path,1);
		$t=$this->_absolutepath($remote_target_path,1);
		if(!is_file($s)){
			throw new Exception('源文件不存在');
		}
		logs('执行rename_file从'.$s.' =>'.$t,LEVEL_INFO);
		return rename($s,$t);
	}
}
