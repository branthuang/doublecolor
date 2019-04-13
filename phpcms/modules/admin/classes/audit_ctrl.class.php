<?php

class audit_ctrl {
	public function history($params){
		extract($params);
		if( !$objectid || !$workflow) return false;
		$service=pc_base::load_sys_class('workflow_service','libs/classes/ffcs');
		if($proc=$workflow->processing($objectid)){
			$result=$service->get_op_history($proc['id'],$objectid);
		}else{	
			$result=$service->get_object_history($workflow->flow_action(),$objectid);
		}
		return $result;
	}
}

