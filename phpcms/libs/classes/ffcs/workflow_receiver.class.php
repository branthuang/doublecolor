<?php
/**
 * 支持回调的方法必须以_开头
 */
class workflow_receiver {
	const ACTION_JOIN=1;
	const ACTION_CANCEL=2;
	const ACTION_FINISH=4;

	public function  beforeRouteMatched(){
		return true;
	}
	public function  afterRouteMatched(){
		return true;
	}
	public function  beforeOperatorFiltered(){
		return true;
	}
	public function  afterOperatorFiltered(){
		return true;
	}
        // 节点执行前 
        public function beforeNodeExecuted($result){
		return true;
	}
        // 中间某节点执行之后
	public function afterNodeExecuted($result){
		return true;
	}
        // 流程开始之前
	public function beforeFlowStart($flow_action,$objectid,&$newdata,&$olddata,$workflow,&$recv_act){
		return true;
	}
        // 流程开始之后
	public function afterFlowStarted($result){
		return true;
	}
        // 流程拒绝后
	public function afterFLowCanceled($result){
		return true;
	}
	public function afterExecutionEnded($result){
		return true;
	}
	public function beforeFLowEnded ($result){
		return true;
	}
        // 流程结束后
	public function afterFLowEnded ($result){
		return true;
	}
	// 流程结束中
        public function onFlowEnding($result) {
            return true;
        }
	
	public function onRoutesMatch($routes){
		
	}
	public function onOperatorsMatch($operators){
		
	}
	public function onJobNodeExecuting($result){
		$method=substr(__METHOD__,strrpos(__METHOD__,':')+1);
		$node_action=$result['current_node']['action'];
		$node_action=substr($node_action,  strrpos($node_action, '.')+1);
		$method_name='_'.$method.'_'.$node_action;
		if(method_exists($this, $method_name)){
			logs($method_name.'被调用',LEVEL_INFO);
			return $this->$method_name($result);
		}
		return true;
	}
}
