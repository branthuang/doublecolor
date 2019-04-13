<?php
pc_base::load_sys_class('callback_parse','libs/classes/ffcs',0);
pc_base::load_sys_class('workflow_serivce','libs/classes/ffcs',0);
//用于代为调用方法，并将异常作为工作流执行情况回送给工作流服务
class workflow_communication {
	/**
	 * 执行一个任务，并返回执行的结果，true为成功，false失败，同时，对于异常将上报到工作流服务
	 * @param callback_parse $callback_obj
	 * @param type $result 工作流返回的result
	 * @return boolean
	 */
	public function execute($callback_obj,$result) {
		try {
			$flag=$callback_obj->execute();
		} catch (Exception $ex) {
			logs($ex->getMessage(), LEVEL_FATAL);
		}
		$service=  workflow_service::load_system();
		$workflow=  workflow_node::loadbyname($result['flowaction']);	
		$last_act=$result['act'];
		if(!$flag){
			$msg=  $ex ? addslashes($ex->getMessage()) : "执行失败"; //这里由于转义是在上层的，这里需要人为转义一把			
			if($callback_obj->retry_count()>=3){
				$msg.="尝试次数超过3次，系统取消";
				$service->async_execute_result($workflow, $result['objectid'], $msg, $last_act, workflow_service::FLOW_STATUS_CANCEL);
				return true;				
			}else{
				$service->async_execute_result($workflow, $result['objectid'], $msg, $last_act, $last_act);
			}
		}
		if($flag || ($ex && $ex->getCode()==-1)) return true;
		else return false;
	}
	
	/**
	 * 
	 * @param type $callback_obj
	 * @param type $result 工作流返回的result
	 * @return callback_parse 
	 */
	public static function delegate($callback_obj,$result){
		return callback_parse::sys_class_callback('libs/classes/ffcs', __CLASS__, 'execute', 0, array($callback_obj,$result));
	}
}
