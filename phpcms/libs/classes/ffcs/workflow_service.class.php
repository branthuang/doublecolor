<?php
pc_base::load_sys_class('workflow_node','libs/classes/ffcs',0);
pc_base::load_sys_class('signal','libs/classes/ffcs',0);
pc_base::load_sys_class('workflow_receiver','libs/classes/ffcs',0);
pc_base::load_sys_class('workflow_api_helper','libs/classes/ffcs',0);
pc_base::load_sys_class('httpcontext','libs/classes/ffcs',0);
/**
 * 工作流的服务类
 */
class workflow_service{
	const FLOW_STATUS_NOINFLOW =0;
	const FLOW_STATUS_INFLOW = 1;
	const FLOW_STATUS_FINISH = 2;
	const FLOW_STATUS_CANCEL = 4;
	const FLOW_STATUS_DELETE = 8;

	const FLOW_ACTION_BACK = 1; //打回
	const FLOW_ACTION_RECYCLE = 2; //回收、取消
	const FLOW_ACTION_ACCEPT = 4; //审核确认
	const FLOW_ACTION_AUTHORIZE = 8; //授权
	const FLOW_ACTION_DENY = 16; //拒绝
	const FLOW_ACTION_APPLY = 32; //发起

	protected $operatorid=0, $operator, $roles=array();
	/**
	 *
	 * @var workflow_api_helper 
	 */
	protected $api;
	
	public static function load_system(){
		static $obj;
		if(!isset($obj)){
			$obj=new workflow_service();
			$obj->operatorid='SYSTEM';
			$obj->operator='';
			$obj->roles=array();
		}
		return $obj;
	}
	
	public function __construct() {
		$ticket=httpcontext::current()->get_userticket();               
		if(!$ticket->isempty()){
			$this->operatorid=$ticket['userid'];
			$this->operator=$ticket['username'];
                        $this->realname= param::get_cookie('realname');
			$this->roles= explode(',',$ticket['roleid']);
		}else{			
			$this->operatorid='ANONYMOUS';
			$this->roles=array();
		}
                
		$cfg=pc_base::load_config('appbase','workflow');
		$this->api=new workflow_api_helper($cfg['app_id'],$cfg['secret_key'],$cfg['callback_uri']);
	}
	
	public function get_api_helper(){
		return $this->api;
	}
	
	public function get_audit_action($flow_action,$objectid){
		return $this->api->processing_action($flow_action, $objectid);
	}
	
	/**
	 * 
	 * @return workflow_api_helper
	 */
	public function api_instance(){
		return $this->api;
	}

	/**
	 * 获取指定对象指定动作的审核相关信息
	 * @param workflow_node $workflow
	 * @param int $objectid
         * @param int $routeid //路由id，多路由时可指定路由。
	 * @return array array('flowinfo','nodeinfo','objectid','processing_action','priv','status')
	 */
	public function get_audit_info(workflow_node $workflow, $objectid,$routeid=null) {
		$flow_action = $workflow->flow_action();
		$processing_action = $workflow->processing($objectid , $routeid);
                $result = array();
		if ($processing_action) { 
                    foreach($processing_action as $pa){
                        $flow_info = $this->api->get_flowinfo($pa['flow_action']);
                        $node_info = $this->api->get_nodeinfo($pa['node_action']);
                        $route_info = $this->api->get_routeinfo($pa['nodeid']); //连线信息
                        $node_action = $pa['node_action'];
			$status = self::FLOW_STATUS_INFLOW;
			$priv=$this->api->priv_in_processing_node($pa['id'], $this->operatorid,$this->roles);
						
			$result[] = array(
				'flowinfo' => $flow_info,
				'nodeinfo' => $node_info,
                                'routeinfo' => $route_info,
				'priv' => $priv,
			);
                    }			
		} else {
			//不在流程中，获取是否能进行发起操作
			$privs = $this->api->priv_in_flow($flow_action, $this->operatorid,$this->roles);
			$result = array();
			foreach ($privs as $r) {
				if ($r['nodetype'] == 1) {
					$flow_info = $this->api->get_flowinfo($r['flow_action']);
					$node_info = $this->api->get_nodeinfo($r['node_action']);
                                        $route_info = $this->api->get_routeinfo($node_info['id']); //连线信息
					$result[] = array(
						'flowinfo' => $flow_info,
						'nodeinfo' => $node_info,
                                                'routeinfo' => $route_info,
						'priv' => $r['priv'],
					);
				}
			}
			$processing_action = array();
			$status = self::FLOW_STATUS_NOINFLOW;
		}

		return array(
			'nodes' => $result,
			'objectid' => $objectid,
			'processing_action' => $processing_action,
			'status' => $status,
		);
	}

	/**
	 * 执行流程
	 * @param workflow_node $workflow 使用的流程
	 * @param string $objectid 审核的对象
	 * @param string $info 审核动态表单数据
	 * @param const $act 审核动作
	 * @param array $newdata 变更后数据
	 * @param array $olddata 变更前数据
         * @param int $routeid 路由id。多路由选择时主动选择
	 * @return bool
	 * @throws Exception
	 */
	public function execute(workflow_node $workflow,$objectid,$info,$act,$newdata=null,$olddata=null,$routeid=null) {
		$flow_action=$workflow->flow_action($objectid);
		$flag=false;
		if($proc=$this->api->processing_action($flow_action, $objectid, $routeid)){
                        if(count($proc)>1){
                            throw new Exception('存在多个并行子流程，请确认所运行子流程', 10000);
                        }
                        $proc = $proc[0];
			if( $proc['flow_action']!=$flow_action){
                            throw new Exception('操作的对象已处在流程中',10000);
			}
			if($act!=self::FLOW_ACTION_APPLY){
				if(!$workflow->signals()->isempty('beforeNodeExecute')){
					$nodes=array();
					foreach($this->api->get_nodes($flow_action) as $node) {
						$nodes[$node['id']]=$node;
					}
					$result=array(
						'affected' => false,
						'objectid'=>$objectid,
						'flowaction'=>$flow_action,
						'olddata' => $olddata,
						'newdata' => $newdata,
						'current_node'=>$nodes[$proc['nodeid']],
						'last_node'=>$nodes[$proc['lastnodeid']],
						'operator'=>array('id'=>$this->operatorid,'name'=>$this->operator,'realname'=>$this->realname),
						'current_status'=>self::FLOW_STATUS_INFLOW,
						'act'=>$act,
					);
					$workflow->signals()->invoke('beforeNodeExecute',$result,$act);
				}
				
				$result= $this->api->execute($flow_action, $objectid,$this->operatorid,$this->roles,$this->operator, $info,$act,$workflow->get_variables(),0,$workflow->signals()->handlers(),$newdata,$routeid);
				//FIXME 各种审核事件回调
				if($flag=$result['affected']){
					unset($result['affected']);
					if($result['current_status']==self::FLOW_STATUS_FINISH){
                                                $workflow->signals()->invoke('afterNodeExecuted',$result);
						$workflow->signals()->invoke('afterFLowEnded',$result);
					}elseif($result['current_status']==self::FLOW_STATUS_CANCEL || $result['current_status']==self::FLOW_STATUS_NOINFLOW){
                                                $workflow->signals()->invoke('afterNodeExecuted',$result);
                                                $workflow->signals()->invoke('afterFLowCanceled',$result);
					}else{
						$workflow->signals()->invoke('afterNodeExecuted',$result);
					}
				}
			}
		}else{
			//FIXME 这里有个事件用来填充数据，新添和审核动作的分开		
			if($act==self::FLOW_ACTION_APPLY){
				($newdata==null || !is_array($newdata)) && $newdata=array();
				($olddata==null || !is_array($olddata)) && $olddata=array();
				$recv_act=  workflow_receiver::ACTION_JOIN;
				$workflow->signals()->invoke('beforeFlowStart',$flow_action,$objectid,$newdata,$olddata,$workflow,$recv_act);
				
				switch ($recv_act){
					case workflow_receiver::ACTION_JOIN :
						$result= $this->api->join_flow($flow_action, $objectid,$this->operatorid,$this->roles,$this->operator, $newdata,$olddata,$msg,$workflow->get_variables(),$workflow->signals()->handlers(),$routeid);
						//FIXME 这里存在没有中间节点的流程，会先执行onflowending，再执行after的情况，有问题
						if($result['affected']){	
							unset($result['affected']);
							if($result['current_status']==self::FLOW_STATUS_FINISH){
								//刚开始就直接结束，不执行afterFlowStarted、beforeFLowExecute、afterFLowExecuted事件
								$workflow->signals()->invoke('afterFLowEnded',$result);
								$flag=true;
							}else{								
								$workflow->signals()->invoke('afterFlowStarted',$result);							
								if($result['current_status']==self::FLOW_STATUS_INFLOW){
									$flag=true;
								}
							}	
						}	
						return $flag;
					case workflow_receiver::ACTION_CANCEL : return false;
					case workflow_receiver::ACTION_FINISH :
						$result = array(
							'affected' => 1,
							'newdata' => $newdata, 
							'olddata' => $olddata, 
							'objectid' => $objectid,
							'flowaction'=>$flow_action,
							'operator'=>array('id'=>$this->operatorid,'name'=>$this->operator,'realname'=>$this->realname),
							'current_status'=>workflow_service::FLOW_STATUS_NOINFLOW,
							'act'=>$act,
							);
						$workflow->signals()->invoke('onFlowEnding',$result);
						$workflow->signals()->invoke('afterFLowEnded',$result);
						return true;
				}
			}
		}
		return $flag;
	}
	
	/**
	 * 仅用在任务节点执行失败时错误记录到流程日志里
	 * @param workflow_node $workflow
	 * @param type $objectid
	 * @param type $msg
	 * @param type $act
	 * @throws Exception
	 */
	public function async_execute_result(workflow_node $workflow, $objectid, $msg, $last_act, $next_act,$next_msg) {
		$flow_action = $workflow->flow_action();

		$proc = $this->api->processing_action($flow_action, $objectid);
		if (!$proc){
			throw new Exception('操作的对象当前不在流程中', 10000);
		}

		if ($proc['flow_action'] != $flow_action) {
			throw new Exception('操作的对象已处在流程中', 10000);
		}
		if ($last_act == self::FLOW_ACTION_APPLY) return;
		
		$result=$this->api->execute($flow_action, $objectid, $this->operatorid, $this->roles, $this->operator, $msg, $last_act, $workflow->get_variables(), 1);
		
		switch ($next_act){
			case self::FLOW_ACTION_BACK:
			case self::FLOW_ACTION_DENY:
				$result= $this->api->execute($flow_action, $objectid, $this->operatorid, $this->roles, $this->operator, $next_msg, $next_act, $workflow->get_variables());
		}
		return $result;
	}

	/**
	 * 获取指定操作的操作历史
	 *
	 * @param int $opid
	 * @param string $objectid
	 * @param string $limit 
	 */
	public function get_op_history($opid, $objectid,$start=0,$limit=0) {
		return $this->api->get_op_history($opid, $objectid,$start,$limit);
	}
	
	/**
	 * 获取指定对象的操作历史
	 *
	 * @param string $flow_action
	 * @param string $objectid
	 * @param string $limit 
	 */
	public function get_object_history($flow_action, $objectid,$start=0,$limit=0) {
		return $this->api->get_object_history($flow_action, $objectid,$start,$limit);
	}
	
	public function get_todo_list($flow_action = '',$pageindex=0,$pagesize=10){
		return $this->api->get_todo_list($this->operatorid,$this->roles,$flow_action,'', $pageindex,$pagesize);		
	}
	/**
	 * 保存工作流程
	 *
	 *return   -1  工作流不存在
	 *             -2  结点数错误
	 *             -3 结点信息错误
	 *             -4 结点action不唯一
	 *             -5 结点没有连线
	 *             -6 连线指向自身
	 *             -7 有回流
	 *				-8 起点，终点不唯一
	 */
public  function save_workflow($flowId,$flow_json = '')
	{
		$flowdb       = pc_base::load_model('config_flow_model');
		$nodedb       = pc_base::load_model('config_flow_node_model');
		$routedb      = pc_base::load_model('config_flow_node_route_model');
		$rel          = pc_base::load_model('config_flow_rel_model');
		$node_array   = $flow_json['node'];
		$route_array  = $flow_json['connection'];
		$nodeids = array();
	    //判断工作流程是否存在
	    $flow_data    = $flowdb->get_one(array('id' => $flowId));
	    if (!$flow_data)
	    {
	        return -1;//bucinzai
	    }
	    $flow = $flowdb->select(array('id' => $flowId));
        // 校验结点数据是否有问题
		if(!count($node_array)||count($node_array)< 2)//结点数目校验
		{
			//结点数错误
			return -2 ;
		}
		 foreach ($node_array as $node)				//结点信息完整新校验
        {
            if ($node['id'] ==null||$node['name']==null)
            {
				//结点信息不完整
                return -3;
            }
        }
        for ($i =0 ;$i<count($node_array);$i++)		//判断action是否唯一
        {
            $tmp_nodeaction1 = $node_array[$i];
            for ($j=$i+1;$j<count($node_array);$j++)
            {
                $tmp_nodeaction2 = $node_array[$j];
                if ($tmp_nodeaction1['nodeaction'] == $tmp_nodeaction2['nodeaction'])
                {	//action不唯一
                    return -4;
                }
            }
        }
		if(!count($route_array))
		{
			//缺少连线
			return -5;
		}
        //校验路径数据是否有问题,校验哪些路径有问题
		$route_error = array();
        foreach ($route_array as $route)
        {
			if($route['sourceId']==$route['targetId'])
			{//不允许连线指向自己
				return -6;
			}
			if( !is_array($route['roleId'])||!count($route['roleId']))
			{
				$route_error[] = $route;
			}
        }
		if(count($route_error))
		{
			return $route_error ;
		}
		 //校验数据，不允许回流
	    $connections = $flow_json['connection'];
		$count = count($connections);
	    for ($i =0 ; $i < $count; $i++)
	    {
	      $connection = $connections[$i];
	      $sourceId = $connection['sourceId'];  
	      $targetId = $connection['targetId'];
	      for ( $j = $i+1 ; $j < $count ; $j++)
	      {
	          $con = $connections[$j];
	          $source = $con['sourceId'];
	          $target = $con['targetId'];
	          if ($sourceId == $target && $targetId == $source)
	          {
	              return -7;
	          }
	      }
	    }
         //数据格式化，$nodetype,起点，终点，中间点,如果是起点，插入，记录下id，作为非起点的rootid
         $rootid = 0;
         $start_count = 0;
         $end_count = 0;
	    foreach ($node_array as &$node)
	    {
	        //$node去查找所有的路径，
			if($node['action']!=3)
			{
	            $start = 0 ;
	            $end = 0 ;
	            foreach ($route_array as $route)
	            {
	                if ($route['sourceId'] == $node['id'])
	                {
						if($route['action']!=3)
						{
	                    $start++;
						}
	                }elseif ($route['targetId']==$node['id'])
	                {
						if($route['action']!=3)
						{
	                    $end++;
						}
	                }
	            } 
	            if($start == 0&&$end == 0)
	            {
	                return -5;
	            }
	            //判断起点,终的个数是否正确
	            $node['nodetype'] = 2;
	            if ($start ==0)//末节点
	            {
	                $node['nodetype'] = 4;
	                $end_count++;
	            }
	            if ($end == 0)//头结点
	            {
	                $node['nodetype']=1;
	                $start_count++;
	              }
			}
				unset($node);
	    }
	    if ($start_count!=1||$end_count!=1)
	    {
	        return -8;//起点，终点不唯一
	    }
	    //重新插入所有的结点,action=1,2,3新增，修改，删除， 首先插入起点
	    foreach ($node_array as $node)
	    {
	        if ($node['nodetype']==1)
	        {
				$rootnode = $nodedb->get_one(array('id'=>$node['id']));
				$rootid = $rootnode['id'];
				if($rootnode)
				{
					if($node['action'] == 2)
					{
						$nodedb->update(array('nodename' => $node['name'],
						'steps'=>0,
						'flowid'=>$flowId,
						'rootid'=>0,
						'nodetype' => $node['nodetype'],
						'action'=>$flow[0]['action'].'.'.$node['nodeaction'],
						'description'=>$node['descp'],
						'nodepos' => $node['left'].','.$node['top']
					),array('id'=>$rootnode['id']));
					}
					$nodeids[$rootid] = $rootid ;
					if($node['action'] ==3)
					{
						$nodedb->delete(array('id'=>$rootnode['id']));
					}
				}else
				{
					$nodeid = $nodedb->insert(array('nodename' => $node['name'],
	                'steps'=>0,
	                'flowid'=>$flowId,
	                'rootid'=>0,
	                'nodetype' => $node['nodetype'],
	                'action'=>$flow[0]['action'].'.'.$node['nodeaction'],
	                'description'=>$node['descp'],
	                'nodepos' => $node['left'].','.$node['top']
	            ),1);	
				$nodeids[$node['id']] = $nodeid ;
				}
	        }
	    }
	    $step = 1;
	    foreach ($node_array as $node)
	    {
			$node_tmp = $nodedb->get_one(array('id'=>$node['id']));
			if($node_tmp)
			{
				if ($node['nodetype']==2)
				{
					if($node['action'] == 2 )
					{
					$nodeid = $nodedb->update(array('nodename' => $node['name'],
										   'steps'=>$step,
										  'flowid'=>$flowId,
											'rootid'=>$rootid,
									   'nodetype' => $node['nodetype'],
					 'action'=>$flow[0]['action'].'.'.$node['nodeaction'],
								  'description'=>$node['descp'],
										'nodepos' =>$node['left'].','.$node['top']
									 ),array('id'=>$node_tmp['id']));
					}
					$nodeids[$node_tmp['id']] = $node_tmp['id'] ;
				}
				if ($node['nodetype']==4)
				{
					if($node['action'] == 2 )
					{
					 $nodedb->update(array('nodename' => $node['name'],
										   'steps'=>99,
										  'flowid'=>$flowId,
											'rootid'=>$rootid,
									   'nodetype' => $node['nodetype'],
					 'action'=>$flow[0]['action'].'.'.$node['nodeaction'],
								  'description'=>$node['descp'],
										'nodepos' => $node['left'].','.$node['top']
									 ),array('id'=>$node_tmp['id']));
					}
					$nodeids[$node_tmp['id']] = $node_tmp['id'] ;
				}
				if($node['action'] == 3 )
				{
					$nodedb->delete(array('id' => $node_tmp['id']));
				}
			}else
			{
				if($node['nodetype'] == 2)
				{
					$nodeid = $nodedb->insert(array('nodename' => $node['name'],
										   'steps'=>$step,
										  'flowid'=>$flowId,
											'rootid'=>$rootid,
									   'nodetype' => $node['nodetype'],
					 'action'=>$flow[0]['action'].'.'.$node['nodeaction'],
								  'description'=>$node['descp'],
										'nodepos' => $node['left'].','.$node['top']
									 ),1);
				   $nodeids[$node['id']] = $nodeid ;
				   $step++;	
				}
				if($node['nodetype'] == 4)
				{
					$nodeid = $nodedb->insert(array('nodename' => $node['name'],
										   'steps'=>99,
										  'flowid'=>$flowId,
											'rootid'=>$rootid,
									   'nodetype' => $node['nodetype'],
					 'action'=>$flow[0]['action'].'.'.$node['nodeaction'],
								  'description'=>$node['descp'],
										'nodepos' => $node['left'].','.$node['top']
									 ),1);
				   $nodeids[$node['id']] = $nodeid ;
				}
			}
        }
        //插入所有的路径
	    foreach ($route_array as $route)
	    {
            $data = $routedb->get_one(array('id' => $route['id']));	
            $roleid_array = $route['roleId'];
            $priv = 0 ;
            //this.isAllowExist = true;//是否允许退出流程
            //this.isCreate = true;//是否允许发起
            //this.isAllowRollback = false;//是否允许回退
            //this.isRecycling = true;//是否允许回收
            //this.isCheck = true;//是否允许审核
            //this.isAuth = true;//是否允许授权
            //this.isReject = true;//是否允许驳回
            $isAllowExist = $isCreate = $isAllowRollback = $isRecycling = $isCheck = $isAuth = $isReject = 0;
            if ($route['isAllowExist']==true)
            {
                $isAllowExist = 1;
            }
            if ($route['isCreate']==true)
            {
                $isCreate = 1;
            }
            if ($route['isAllowRollback']==true)
            {
                $isAllowRollback =1 ;
            }
            if ($route['isRecycling']==true)
            {
                $isRecycling= 1;
            }
            if ($route['isCheck']==true)
            {
                $isCheck  =1 ;
            }
            if ($route['isAuth']==true)
            {
                $isAuth =1 ;
            }
            if ($route['isReject']==true)
            {
                $isReject =1 ;
            }
            $priv = $isAllowExist*64 + $isCreate*32 + $isAllowRollback*16 + $isRecycling*8+$isCheck*4+$isAuth*2+$isReject*1;
            $formIds = implode(',',$route['formIdList']);
			if($data)
			{
			  	if ($route['action'] == 2)
				{
				 $routedb->update(array('nodeid' =>$route['sourceId'],
                                 'next_nodeid' =>$route['targetId'],
                                     'process_id'=>$route['process_id'],
                                    'model_ids'=>$formIds),array('id'=>$data['id']));
					foreach ($roleid_array as $roleid)
					{
					$rel->update(array('uid'=>'r-'.$flow[0]['appid'].'-'.$roleid,
                                     'addtime'=>time(),
                                     'priv'=> $priv),array('routeid'=>$data['id']));
					}
				}
				if ($route['action'] == 3)
				{
					foreach ($roleid_array as $roleid)
					{
					$rel->delete(array('routeid'=>$data['id']));
					}	
					$routedb->delete(array('id'=>$data['id']));
				}
			}else
			{
				$route_insert =  $routedb->insert(array('nodeid' =>$nodeids[$route['sourceId']],
                                 'next_nodeid' =>$nodeids[$route['targetId']],
                                     'process_id'=>$route['process_id'],
                                    'model_ids'=>$formIds),1);
				foreach ($roleid_array as $roleid)
				{
                $rel->insert(array('routeid'=>$route_insert,
                                        'nodeid'=>$nodeids[$route['sourceId']],
                                       'uid'=>'r-'.$flow[0]['appid'].'-'.$roleid,
                                        'addtime'=>time(),
                                      'priv'=> $priv),1);
				}
			}
	    }
		return 0;
	}
	
	/**
	 * 获取工作流节点、连线及配置等
	 * @param string $flowid 工作流id
	 * @return array $return 返回数组
	 * {
    "node": [{
        "id": "83",
        "shape": "Circle",
        "name": "发起",
        "type": 0,
        "left": "50",
        "top": "50",
        "descp": null,
        "nodeaction": "start"
    }, {
        "id": "84",
        "shape": "Circle",
        "name": "结束",
        "type": 0,
        "left": "50",
        "top": "250",
        "descp": null,
        "nodeaction": "end"
    }, {
        "id": "85",
        "shape": "Rectangle",
        "name": "审核",
        "type": 0,
        "left": "50",
        "top": "150",
        "descp": null,
        "nodeaction": "check"
    }],
    "connection": [{
        "sourceId": "83",
        "targetId": "85",
        "id": "81",
        "roleId": ["8"],
        "timeLimit": "5",
        "formIdList": ["12", "13"],
        "isAllowRollback": false,
        "isCreate": true,
        "isRecycling": false,
        "isCheck": true,
        "isAuth": true,
        "isAllowExist": true,
        "isReject": true
    }, {
        "sourceId": "85",
        "targetId": "84",
        "id": "82",
        "roleId": ["9"],
        "timeLimit": "5",
        "formIdList": ["15", "16", "17"],
        "isAllowRollback": true,
        "isCreate": true,
        "isRecycling": true,
        "isCheck": true,
        "isAuth": false,
        "isAllowExist": true,
        "isReject": false
    }]
}
	 * 
	 */
	public function read_workflow($flowid = '') {
		// 读取节点
		$nodedb = pc_base::load_model ( 'config_flow_node_model' );
		$routedb = pc_base::load_model ( 'config_flow_node_route_model' );
		$rel = pc_base::load_model ( 'config_flow_rel_model' );
		$where = array (
				'flowid' => $flowid 
		);
		// 查询工作流上的所有节点
		$node_array = $nodedb->select ( $where );
		// 结点去查询所有的路径
		$routes = array ();
		foreach ( $node_array as $node ) {
			$tmp_routes = $routedb->select ( array (
					'nodeid' => $node ['id'] 
			) );
			foreach ( $tmp_routes as $route ) {
				$routes [] = $route;
			}
		}
		$return = array ();
		foreach ( $node_array as $v ) {
			$shape = $v ['nodetype'] == '2' ? 'Rectangle' : 'Circle';
			$nodepos = explode ( ',', $v ['nodepos'] );
			$return ['node'] [] = array (
					'id' => $v ['id'],
					'shape' => $shape,
					'name' => $v ['nodename'],
					'type' => 0,
					'left' => $nodepos [0],
					'top' => $nodepos [1],
					'action' => $v ['action'],
					'descp' => $v ['description'] 
			);
		}
		// 读取路径
		foreach ( $routes as $v ) {
			// 查询所有的uid,截取roleid
			$roleId = array ();
			$uids = $rel->select ( array (
					'routeid' => $v ['id'] 
			), 'uid,priv' );
			foreach ( $uids as $value ) {
				$rol = explode ( '-', $value ['uid'] );
				$roleId [] = $rol [count ( $rol ) - 1];
			}
			// 查询所有表单id
			$modelids = $v ['model_ids'];
			$formIdList = explode ( ',', $modelids );
			$priv = sprintf('%08d',decbin ( $value ['priv'] ));
			 // 二进制数
                                      // while (strlen($priv)<7)
                                      // {
                                      // $priv = '0'.$priv;
                                      // }
                                      // $indexs = str_split($priv);
			$isReject = ! empty ( $priv [7] ) ? true : false;
			$isAuth = ! empty ( $priv [6] ) ? true : false;
			$isCheck = ! empty ( $priv [5] ) ? true : false;
			$isRecycling = ! empty ( $priv [4] ) ? true : false;
			$isAllowRollback = ! empty ( $priv [3] ) ? true : false;
			$isCreate = ! empty ( $priv [2] ) ? true : false;
			$isAllowExist = ! empty ( $priv [1] ) ? true : false;
			// 首位暂无权限，为空
			$return ['connection'] [] = array (
					'sourceId' => $v ['nodeid'],
					'targetId' => $v ['next_nodeid'],
					'id' => $v ['id'],
					'roleId' => $roleId,
					'timeLimit' => '5',
					'formIdList' => $formIdList,
					'isAllowRollback' => $isAllowRollback,
					'isCreate' => $isCreate,
					'isRecycling' => $isRecycling,
					'isCheck' => $isCheck,
					'isAuth' => $isAuth,
					'isAllowExist' => $isAllowExist,
					'isReject' => $isReject ,
					'process_id' => $v['process_id'] 
			);
		}
		// echo json_encode($return);
		return $return;
	}
        
        //实例流程
        public function read_workflow_real($flowid){
            // 读取节点
		$nodedb = pc_base::load_model ( 'object_flow_node_model' );
		$routedb = pc_base::load_model ( 'object_flow_node_route_model' );
		$rel = pc_base::load_model ( 'object_flow_rel_model' );
		$where = array (
				'flowid' => $flowid 
		);
		// 查询工作流上的所有节点
		$node_array = $nodedb->select ( $where );
		// 结点去查询所有的路径
		$routes = array ();
		foreach ( $node_array as $node ) {
			$tmp_routes = $routedb->select ( array (
					'nodeid' => $node ['id'] 
			) );
			foreach ( $tmp_routes as $route ) {
				$routes [] = $route;
			}
		}
		$return = array ();
		foreach ( $node_array as $v ) {
			$shape = $v ['nodetype'] == '2' ? 'Rectangle' : 'Circle';
			$nodepos = explode ( ',', $v ['nodepos'] );
			$return ['node'] [] = array (
					'id' => $v ['id'],
					'shape' => $shape,
					'name' => $v ['nodename'],
					'type' => 0,
					'left' => $nodepos [0],
					'top' => $nodepos [1],
					'action' => $v ['action'],
					'descp' => $v ['description'] 
			);
		}
		// 读取路径
		foreach ( $routes as $v ) {
			// 查询所有的uid,截取roleid
			$roleId = array ();
			$uids = $rel->select ( array (
					'routeid' => $v ['id'] 
			), 'uid,priv' );
			foreach ( $uids as $value ) {
				$rol = explode ( '-', $value ['uid'] );
				$roleId [] = $rol [count ( $rol ) - 1];
			}
			// 查询所有表单id
			$modelids = $v ['model_ids'];
			$formIdList = explode ( ',', $modelids );
			$priv = sprintf('%08d',decbin ( $value ['priv'] ));
			 // 二进制数
                                      // while (strlen($priv)<7)
                                      // {
                                      // $priv = '0'.$priv;
                                      // }
                                      // $indexs = str_split($priv);
			$isReject = ! empty ( $priv [7] ) ? true : false;
			$isAuth = ! empty ( $priv [6] ) ? true : false;
			$isCheck = ! empty ( $priv [5] ) ? true : false;
			$isRecycling = ! empty ( $priv [4] ) ? true : false;
			$isAllowRollback = ! empty ( $priv [3] ) ? true : false;
			$isCreate = ! empty ( $priv [2] ) ? true : false;
			$isAllowExist = ! empty ( $priv [1] ) ? true : false;
			// 首位暂无权限，为空
			$return ['connection'] [] = array (
					'sourceId' => $v ['nodeid'],
					'targetId' => $v ['next_nodeid'],
					'id' => $v ['id'],
					'roleId' => $roleId,
					'timeLimit' => '5',
					'formIdList' => $formIdList,
					'isAllowRollback' => $isAllowRollback,
					'isCreate' => $isCreate,
					'isRecycling' => $isRecycling,
					'isCheck' => $isCheck,
					'isAuth' => $isAuth,
					'isAllowExist' => $isAllowExist,
					'isReject' => $isReject ,
					'process_id' => $v['process_id'] 
			);
		}
		// echo json_encode($return);
		return $return;
        }
}
