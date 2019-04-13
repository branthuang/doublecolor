<?php
class workflow_regulation{
	protected  $vars=array();
	public function __construct($vars) {
		if(is_array($vars)){
			$this->vars=$vars;
		}
	}
	public function set_var($key,$val) {
		$this->vars[$key]=$val;
	}
	public function judge($role){
		$__result=false;
		extract($this->vars);
		@eval('$__result='.$role.';');
		return $__result;
	}
}