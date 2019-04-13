<?php
pc_base::load_sys_class('signal','libs/classes/ffcs',0);
pc_base::load_sys_class('workflow_api_helper','libs/classes/ffcs',0);
class workflow_node{
	protected $flow_action,$info,$signal,$vars=array();
	
	private function __construct(){
	}
		
	public function flowinfo(){
		if(!isset($this->info)){
			$cfg=pc_base::load_config('appbase','workflow');
			$api=new workflow_api_helper($cfg['app_id'],$cfg['secret_key'],$cfg['callback_uri']);
			$this->info=$api->get_flowinfo($this->flow_action);
		}
		return $this->info;
	}
	public static function loadbyname($flow_const){
		$obj= new workflow_node();
		$obj->flow_action= substr_count($flow_const,'.')>1 ? substr($flow_const,0,strrpos($flow_const,'.')):$flow_const;
		return $obj;
	}
	public function firstnode(){
		$nodes=$this->nodes();
		foreach($this->nodes() as  $node){
			if($node['steps']===0) return $node;
		}
	}
        //多路由时，增加routeid参数
	public function processing($objectid, $routeid=null){
		$cfg=pc_base::load_config('appbase','workflow');
		$api=new workflow_api_helper($cfg['app_id'],$cfg['secret_key'],$cfg['callback_uri']);
		return $api->processing_action($this->flow_action, $objectid, $routeid);
	}
	public function flow_action($objectid){
            $flow_action = $this->flow_action;
            $i = strpos($flow_action, '.');
            if ($i !== false) {
                    $objecttype = substr($flow_action, 0, $i);
                    $action = substr($flow_action, $i + 1);
            } else {
                    $objecttype = $flow_action;
                    $action='*';
            }
                
            /*如果有传入业务对象id，检查该业务对象是否是在跑流程
             * 是： 则按在跑流程继续后续流程
             * 否： 按最新流程跑后续流程
             */
            if ($objectid){
                $opdb = pc_base::load_model('object_op_model');
                $op_result = $opdb->get_one(array(
                    'objectid' => $objectid,
                    'objecttype' => $objecttype
                ));
                if ($op_result){
                    //在跑流程
                    return $op_result['objecttype'].'.'.$op_result['action'];
                }
            }
            
            //流程按最新流程配置跑
            $config_flow_model = pc_base::load_model("config_flow_model");
            $sql = "select a.*, c.action as real_action from ".$config_flow_model->db_tablepre."flow_instantiation a "
                    . "left join ".$config_flow_model->db_tablepre."config_flow b on a.config_id = b.id "
                    . "left join ".$config_flow_model->db_tablepre."object_flow c on a.object_id = c.id "
                    . "where b.action = '$flow_action' "
                    . "order by a.id desc "
                    . "limit 1";
            $config_flow_model->query($sql);
            $result = $config_flow_model->fetch_array();
            if (empty($result)){
                return '';
            }
            return $result[0]['real_action'];
            
	}
	/**
	 * 获取或设置信号 
	 * @param signal $signal
	 * @return signal
	 */
	public function signals($signal=null){
		if($signal==null){
			if(!isset($this->signal)){
				$cfg=pc_base::load_config('appbase','workflow');
                                //实例化后action名称有加序号
                                $check_action = explode('_',$this->flow_action);
                                if($check_action > 1){
                                    $real_flow_action = $check_action[0];
                                }else{
                                    $real_flow_action = $this->flow_action;
                                }
				if(array_key_exists($real_flow_action, $cfg['receiver']) && $_cfg=$cfg['receiver'][$real_flow_action]){
					$classname=isset($_cfg['class']) ?$_cfg['class'] :('receiver_' . str_replace('.', '_', $real_flow_action));
					if(pc_base::load_app_class($classname,$_cfg['module'].'/receiver',0) && class_exists($classname)){
						$recv=new $classname($_cfg?$_cfg['params']:null);
						$signal = new signal($recv);
						$this->signal=$signal;
					}
				}
				if(!$this->signal) {
					logs('审核动作'.$this->flow_action.'未指定通知接收',LEVEL_WARN);
					$this->signal=new signal();
				}
			}
			return $this->signal;
		}else{
			$this->signal=$signal;		
		}
	} 
	public function variable($name,$value){
		$this->vars[$name]=$value;
	}	
	public function get_variables($name=null){
		return $name ? $this->vars[$name] : $this->vars;
	}
}

