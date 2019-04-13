<?php
/**
 * 用户控件
 */
class userctrl {
	protected  $file,$m,$id;
	public function __construct($id=null){
		$this->file=ROUTE_C.'_'.ROUTE_A;
		$this->m=ROUTE_M;
		$this->id=$id;
	}
	public function id($id=null){
		if($id) $this->id=$id;
		else return $this->id;
	}
	public function load(){}
	protected function set_template($file,$m=null){
		$this->file=$file;
		if($m){
			$this->m=$m;
		}
	}
	public function ispostback(){return count($_POST)>0;}
	
	public function render(){}
	protected function get_compiled_file_path(){
		$file='ctrls'.DIRECTORY_SEPARATOR.$this->file;
		$m=$this->m;
		
		$path=PC_PATH . 'modules' . DIRECTORY_SEPARATOR . $m . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR .$file . '.html';
		if(!is_file($path)) {
			exit('Template does not exist.'.$m.'/'.$file);
		}		
		$style='__admin__';
		
		$filepath = CACHE_PATH.'caches_template'.DIRECTORY_SEPARATOR.$style.DIRECTORY_SEPARATOR.$m.DIRECTORY_SEPARATOR;
		$compiledtplfile = $filepath.str_replace(DIRECTORY_SEPARATOR,'-_-',$file).'.php';
		
		if(!file_exists($compiledtplfile) || (@filemtime($path) > @filemtime($compiledtplfile))) {
		
			$content = @file_get_contents ( $path );

			if(!is_dir($filepath)) {
				mkdir($filepath, 0777, true);
			}
			$template_cache = pc_base::load_sys_class('template_cache');
			$content = $template_cache->template_parse($content);
			file_put_contents ( $compiledtplfile, $content );
			chmod ( $compiledtplfile, 0777 );
		}
		return $compiledtplfile;
	}
}
