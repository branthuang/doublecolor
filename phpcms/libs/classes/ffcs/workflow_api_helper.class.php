<?php
pc_base::load_sys_class('taskqueue_factory','libs/classes/ffcs',0);
pc_base::load_sys_class('callback_parse','libs/classes/ffcs',0);
pc_base::load_app_class('communication','workflow',0);
pc_base::load_sys_class('workflow_regulation','libs/classes/ffcs',0);
class workflow_api_helper{
	const FLOW_STATUS_NOINFLOW =0;
	const FLOW_STATUS_INFLOW = 1;
	const FLOW_STATUS_FINISH = 2;
	const FLOW_STATUS_CANCEL = 4;
	const FLOW_STATUS_DELETE = 8;
	
	const FLOW_ACTION_BACK = 1; //打回
	const FLOW_ACTION_RECYCLE = 2; //回收
	const FLOW_ACTION_ACCEPT = 4; //审核确认
	const FLOW_ACTION_AUTHORIZE = 8; //授权
	const FLOW_ACTION_DENY = 16; //拒绝
	const FLOW_ACTION_APPLY = 32; //发起
	
	protected $appid,$uri,$key;
	public function __construct($appid,$key,$uri) {
		$this->appid=$appid;
		$this->key=$key;
		$this->uri=$uri;
		$this->logpath=LOG_PATH.'workflow.log';
	}
	
	//获取流程信息
	public function get_flowinfo($action){
		static $items=array();
		if(!isset($items[$action])){
			$flow_db = pc_base::load_model('object_flow_model');
			$items[$action]=$flow_db->get_one(array('isdelete'=>0,'appid'=>$this->appid,'action'=>$action));
		}
		return $items[$action];
	}
	
	//获取流程节点信息
	public function get_nodeinfo($action){
		$node_db = pc_base::load_model('object_flow_node_model');
		$rs=$node_db->get_one(array('action'=>$action));
		return $rs;
	}	
        //获取节点连线信息
        public function get_routeinfo($nodeid){
            $node_db = pc_base::load_model('object_flow_node_route_model');
            $rs=$node_db->get_one(array('nodeid'=>$nodeid));
            return $rs;
        }
	public function get_nodes($flow_action){
		static $items;
		if(!isset($items[$flow_action])){
			$flow= $this->get_flowinfo($flow_action);
			if(!$flow) return false;
			$node_db = pc_base::load_model('object_flow_node_model');
			$rs=$node_db->select(array('isdelete'=>0,'flowid'=>$flow['id']));
			foreach($rs as &$r){
				$r['node_action']=$r['action'];
				unset($r['action']);
			}
			$items[$action]=$rs;
		}
		return $items[$action];
	}
	
	public function get_first_node($flow_action){
		$rs=$this->get_nodes($flow_action);
		if(is_array($rs)){ 
			foreach($rs as $r){
				if($r['nodetype']==1) return $r;
			}
		}
		return false;
	}
	
        //@brant 返回多op信息
	public function processing_action($flow_const, $objectid, $routeid=null){
		$caches=array();
		if(!isset($caches[$flow_const.$objectid])){
			self::split_flow_const($flow_const, $action, $objecttype);
			$where = "status>=0 and status !=99 AND objectid='{$objectid}' AND objecttype='{$objecttype}'";
			if ($action != '*')
				$where .= " AND action='{$action}'";
                        $opdb = pc_base::load_model('object_op_model');
                        $rs= $opdb->select($where);
                        if(count($rs)>1){
                            //多个代办, 表示有多个子流程
                            if($routeid){
                                //指定一个子流程
                                //由连线id获得“后”节点id
                                $route_db = pc_base::load_model("object_flow_node_route_model");
                                $route = $route_db->get_one(array('id'=>$routeid));
                                $nodeid = $route['nodeid'];
                                if ($nodeid){
                                    //指定路由
                                    foreach($rs as $key=>$val){
                                        if($val['nodeid'] == $nodeid){
                                            $slt_rs = $val;
                                            break;
                                        }
                                    }
                                    if ($slt_rs){
                                        $rs = array($slt_rs); //指定流程
                                    }else{
                                        //指定流程不存在, 返回所有存在的流程
                                        $rs = $rs;
                                    }
                                }
                                
                                
                            }
                        }
                        
			if($rs){
                            foreach($rs as $key=>$val){
                                $flow_db = pc_base::load_model('object_flow_model');
				$flow=$flow_db->get_one(array('isdelete'=>0,'appid'=>$this->appid,'id'=>$val['flowid']));
				$rs[$key]['flow_action']=$flow['action'];
				$node_db = pc_base::load_model('object_flow_node_model');
				$node=$node_db->get_one(array('id'=>$val['nodeid']));
				$rs[$key]['node_action']=$node['action'];
				$rs[$key]['olddata']=  string2array($val['olddata']);
				$rs[$key]['newdata']=  string2array($val['newdata']);
				
				//unset($rs['flowid'],$rs['nodeid']);
                            }
				
			}
			$caches[$flow_const.$objectid]=$rs;
		}
		return $caches[$flow_const.$objectid];
	}
	
	/**
	 * 获取指定用户/角色在指定工作流程下的所有节点中，已配置且有效的权限数组
	 * @param string $flow_action 流程标识，支持flow.*的写法
	 * @param string $uid 用户id
	 * @param string/array $roleids 角色id，用逗号隔开或使用数组
	 * @return array
	 */
	public function priv_in_flow($flow_action,$uid,$roleids){
		self::split_flow_const($flow_action, $action, $objecttype);
		$operators=$this->get_operator_flag($uid, $roleids);
		$where=  $action != '*' ? "action='{$flow_action}'" : "action like '{$objecttype}.%'";
		$where.=' and isdelete=0';
		$flow_db = pc_base::load_model('object_flow_model');
		$node_db = pc_base::load_model('object_flow_node_model');
		$reldb = pc_base::load_model('object_flow_rel_model');
		$rs=$flow_db->select($where,'id,action','','','','id');
		$sql="SELECT DISTINCT r.priv,n.action as node_action,n.nodetype,n.flowid FROM ".$node_db->tablename()." n INNER JOIN ".$reldb->tablename()." r ON n.id=r.nodeid WHERE  ".to_sqls(array_keys($rs),'','n.flowid')." and r.isenable=1 and  ".to_sqls($operators,'','r.uid');
		$flow_db->query($sql);
		$rs1=$flow_db->fetch_array();
		foreach($rs1 as &$v){
			$v['flow_action']=$rs[$v['flowid']]['action'];
		}
		return $rs1;
	}
	
	/**
	 * 获取指定用户/角色在指定工作流程下的起始节点中，已配置且有效的权限数组
	 * @param string $flow_action 流程标识，支持flow.*的写法
	 * @param string $uid 用户id
	 * @param string/array $roleids 角色id，用逗号隔开或使用数组
	 * @return array
	 */
	public function priv_in_join_flow($flow_action,$uid,$roleids){	
		$operators=$this->get_operator_flag($uid, $roleids);
		$rs=$this->priv_in_flow($flow_action, $operators);
		$result=array();
		foreach($rs as $r){
			if($r['nodetype']==1){
				$result[$r['flow_action']]=$r['priv'];
			}
		}
		return $result;
	}
	
	/**
	 * 获取指定用户/角色在指定工作流程节点中，已配置且有效的权限
	 * @param string $node_action 流程节点标识
	 * @param string $uid 用户id
	 * @param string/array $roleids 角色id，用逗号隔开或使用数组
	 * @return int
	 */
	public function priv_in_node($node_action,$uid,$roleids){		
		$operators=$this->get_operator_flag($uid, $roleids);
		$reldb = pc_base::load_model('object_flow_rel_model');
		$node_db = pc_base::load_model('object_flow_node_model');
		$rs=$node_db->get_one(array('action'=>$node_action));
		$priv=0;
		if($operators && $rs && $info=$reldb->select("nodeid='{$rs['id']}' and ".to_sqls($operators,'','uid'),'priv')){		
			foreach($info as $item){
				$priv=$priv | $item['priv'];
			}
		}
		return $priv;
	}
	
	/**
	 * 获取指定用户/角色在指定操作项中，已配置且有效的权限
	 * @param string $opid 操作项id
	 * @param string $uid 用户id
	 * @param string/array $roleids 角色id，用逗号隔开或使用数组
	 * @return int
	 */
	public function priv_in_processing_node($opid,$uid,$roleids){		
		$operators=$this->get_operator_flag($uid, $roleids);
		$todo_db = pc_base::load_model('object_flow_rel_todo_model');
		$priv=0;
		if ($todos = $todo_db->select("opid='$opid' AND ".to_sqls($operators,'','uid'))) {
			foreach ( $todos as $t ) {
				$priv=$priv | $t['priv'];
			}
		}
		if(($priv & self::FLOW_ACTION_RECYCLE)==self::FLOW_ACTION_RECYCLE){
			$opdb = pc_base::load_model('object_op_model');
			$rs= $opdb->get_one(array('id'=>$opid));
			if(!$rs || $rs['apply_uid']!=$this->get_operator_flag($uid,'',0)){
				
			}
		}
		return $priv;
	}
	
	/**
	 * 添加指定动作到流程中并返回结果数组
	 * @param string $flow_action
	 * @param string $objectid
	 * @param string $operatorid
	 * @param string/array $roles
	 * @param string $operator
	 * @param array $newdata
	 * @param array $olddata
	 * @param string $reason
	 * @param array $vars
	 * @return array
	 * @throws Exception
	 */
	public function join_flow($flow_action, $objectid,$operatorid,$roles, $operator, $newdata, $olddata = array(), $reason = '',$vars=null,$handlers=array(),$routeid) {
		if (! is_array($newdata) || ! is_array($olddata) || empty($objectid)) {
			throw new Exception('无效的objectid或是空的newdata/olddata',10000);
		}

		self::split_flow_const($flow_action, $action, $objecttype);
		if ($this->processing_action($objecttype . '.*', $objectid)) {
			throw new Exception('操作的对象已处在流程中',10000);
		}

		if ($action == '*' || ! $flow = $this->get_flowinfo($flow_action, false)) {
			throw new Exception('未指定有效的流程');
		}

		$flowid=$flow['id'];
		// 取出第一个流程节点,每个流程的根节点只会有一个
		$node = $this->get_first_node($flow_action);

		if (! $node) {
			throw new Exception('未配置有效的流程节点');
		}

		$opdb = pc_base::load_model('object_op_model');
		$flow_db = pc_base::load_model('object_flow_model');
		$rel_db = pc_base::load_model('object_flow_rel_model');
		$todo_db = pc_base::load_model('object_flow_rel_todo_model');
		$oplog_db = pc_base::load_model('object_op_log_model');
                $admin_db = pc_base::load_model("admin_model");
                
		$flow = $flow_db->get_one(array(
			'id' => $flowid
		));
		$token = random(3);
		$routes = $this->get_next_routes($node['id']); //下一个节点信息
                //FIXME 调取事件过滤多余的流程节点
		if($routes){
                    $tmp_key_id = array();
                    foreach ($routes as $key=>$route){
                        //同一起始节点的，op表只插入一条记录
                        if (in_array($route['id'],$tmp_key_id)){
                            continue;
                        }else{
                            $tmp_key_id[] = $route['id'];
                        }
                        
                        //20150508 支持多流程 @brant
			$routeid=$route['routeid'];//当前路由
			//检查权限				
			$operators=$this->get_operator_flag($operatorid, $roles);
                        $admin_info = $admin_db->get_one("userid=".$operatorid);
                        $real_name = $admin_info['realname'];//真实姓名
			$rs=$rel_db->select("routeid='{$routeid}' and ".  to_sqls($operators,'','uid'));
			$priv=$relid=0;
			foreach($rs as $r){
				$priv=$priv | $r['priv'];
				$relid=$r['id'];
			}
			if(($priv & self::FLOW_ACTION_APPLY) ==0){				
				throw new Exception('您在该流程没有执行此项操作的权限',10004);	
			}
			
			if($route['nodetype']==4){
				$status = 99;
				$flow_status = self::FLOW_STATUS_FINISH;
			}else{
                            $next_operators =$this->get_operators_in_parent_route($routeid);
				//FIXME 调取事件来改变节点中的操作员

				if(!$next_operators){
					throw new Exception('该流程下未配置有效的操作人员:'.$route['nodename']);
				}

				$status = 1;
				$flow_status = self::FLOW_STATUS_INFLOW;
			}

                        $opdb->transaction();
                        $opid = $opdb->insert(array(
                                'objectid' => $objectid,
                                'objecttype' => $objecttype,
                                'action' => $action,
                                'olddata' => array2string($olddata),
                                'newdata' => array2string($newdata),
                                'status' => $status,
                                'create_time' => SYS_TIME,
                                'token' => $token,
                                'flowid' => $flowid,
                                'nodeid' => $route['id'], //当前要进入的流程节点id
                                'lastnodeid' => $node['id'],//上一所处的流程节点id
                                'appid'=>$this->appid,
                                'apply_uid'=>$this->get_operator_flag($operatorid, '',0),//这里记录到数据库，要用系统标识
                                'last_uid'=>$this->get_operator_flag($operatorid, '',0),
                                'lastrouteid'=>$routeid
                        ), 1);
                        $flag = $opid;
                        if($next_operators){ //上面已做过校验，这里没有下一办理人即为流程结束
                                $sql = array();
                                foreach ( $next_operators as $u ) {
                                        $sql[] = "('{$opid}','{$flowid}','{$u['nodeid']}','{$u['uid']}','{$u['nickname']}','{$u['groupid']}','" . SYS_TIME . "','{$u['priv']}','{$u['relid']}','{$routeid}')";
                                }
                                $sql = 'INSERT INTO ' . $todo_db->tablename() . ' (opid,flowid,nodeid,uid,nickname,groupid,addtime,priv,relid,routeid) VALUES ' . implode(',', $sql);
                                $todo_db->query($sql);
                                $flag = $flag && $todo_db->affected_rows();
                        }
                        if ($flag) {
                                $logdata=array(
                                        'relid' => $relid,
                                        'objectid' => $objectid,
                                        'opid' => $opid,
                                        'oldstatus' => 0,
                                        'status' => $status,
                                        'create_time' => SYS_TIME,
                                        'reason' => $reason,
                                        'uid' => $this->get_operator_flag($operatorid, '',0),
                                        'nickname' => $operator,
                                        'lastnodeid' => $node['id'],
                                        'nodeid' => $route['id'],
                                        'flowid' => $flowid,
                                        'token' => $token,
                                        'cache_workname' => $flow['workname'],
                                        'cache_nodename' => $node['nodename'],
                                        'cache_action' => $flow['action'],
                                        'routeid'=>$routeid,
                                        'exec_act'=>self::FLOW_ACTION_APPLY,	
                                        'objecttype'=>$objecttype,
                                        'datas'=>  array2string(array('newdata'=>$newdata)),
                                );
                                $oplog_db->insert( $logdata);
                                if($flow_status == self::FLOW_STATUS_FINISH){
                                        $opdb->delete(array('id'=>$opid));
                                        $logdata['cache_nodename']=  '结束';
                                        $logdata['nodeid']=$logdata['exec_act']=0;
                                        $oplog_db->insert($logdata);
                                }
                                //删除之前用户取消、驳回等的操作数据
                                $opdb->delete("status<0 AND objectid='{$objectid}' AND objecttype='{$objecttype}'");
                                
                                $result[]=array(
                                    'objectid'=>$objectid,
                                    'flowaction'=>$flow_action,
                                    'olddata' => $olddata,
                                    'newdata' => $newdata,
                                    'operator'=>array('id'=>$operatorid,'name'=>$operator,'realname'=>$real_name),//这里返回给客户端代码，只需要用原始的用户标识，怎么传怎么回
                                    'current_status'=>$flow_status,
                                    'act'=> self::FLOW_ACTION_APPLY,
                                    'affected' => true,
                                    'current_node' => $route,
                                    'last_node' => $node
                                );

                        } else {
                            $opdb->rollback();  
                        }
                    }
                    
                    if ($opdb->commit()){
                        return array(
                            'affected' => true,
                            'current_status'=>$flow_status, //这里只取最后一个子流程状态
                            'result' => $result
                        );
                    }else{
                        $opdb->rollback();
                    }
		}else{
			throw new Exception('该流程下未配置有效的流程节点路由');
		}
		
		
		
	}

	/**
	 * 执行指定的工作事项
	 * @param type $flow_action
	 * @param type $objectid
	 * @param type $operatorid
	 * @param type $roles
	 * @param type $operator
	 * @param type $reason
	 * @param type $act
	 * @param type $vars
	 * @param type $onlylog
	 * @return array('affected'=>bool....)，保存失败返回false  其中affected为是否影响流程级数
	 * @throws Exception
	 */
	public function execute($flow_action, $objectid, $operatorid,$roles,$operator, $reason = '', $act = self::FLOW_ACTION_ACCEPT,$vars=null,$onlylog=0,$handlers=array(),$newdata,$routeid) {
		$opdb = pc_base::load_model('object_op_model');
		$node_db = pc_base::load_model('object_flow_node_model');
		$todo_db = pc_base::load_model('object_flow_rel_todo_model');
		$oplog_db = pc_base::load_model('object_op_log_model');
		$flow_db = pc_base::load_model('object_flow_model');
                $route_db = pc_base::load_model('object_flow_node_route_model');
                $admin_db = pc_base::load_model('admin_model');
		
		if (! $flow = $flow_db->get_one(array('action'=>$flow_action))) {
			throw new Exception('未指定有效的流程');
		}
		$flowid=$flow['id'];

		self::split_flow_const($flow_action, $action, $objecttype);
		if ($action == '*') {
			throw new Exception('未指定具体的流程',10001);
		}
		
		$operators=$this->get_operator_flag($operatorid, $roles);
                $admin_info = $admin_db->get_one("userid=".$operatorid);
                $real_name = $admin_info['realname'];//真实姓名
                $op_where = "objectid='{$objectid}' AND objecttype='{$objecttype}' AND action='{$action}' AND status>0 AND status<99";
                if ($routeid){
                    //指明执行路由
                    $route = $route_db->get_one("id=$routeid");
                    $nodeid = $route['nodeid'] ;//起始节点id
                    if ($nodeid){
                        $op_where .= " and nodeid=$nodeid ";
                    }
                }
                $op = $opdb->select($op_where);//操作记录
		if (!$op ) {
			throw new Exception('对象未在指定的流程中,或者操作流程已结束或被取消',10002);
		}
                if (count($op)>1){
                    throw new Exception('操作对象多个，请指明执行流程！',10002);
                }
                $op = $op[0]; //
                
                if (!$routeid){
                    $begin_nodeid = $op['nodeid'];
                    $route = $route_db->get_one("nodeid=$begin_nodeid");
                    $routeid = $route['id'];  //无分枝流程，查询出当前流程id
                }
                
		// 这里认为一个人在一个流程中只能有一条待办事项，但是由于可以属于多个角色，故可能有多条待办
		
		if ($todos = $todo_db->select(array('opid' => $op['id'],'flowid' => $flowid), '*', '', '', '', 'id')) {
			foreach ( $todos as $t ) {
				if (in_array($t['uid'], $operators)) {
					$_todos[$t['id']] = $t;
				}
			}
		}
		if (! $_todos) {
			throw new Exception('未在该流程有需要操作的事项',10003);
		}

		// 循环比较得出有执行权限的记录
		foreach ( $_todos as $k => $v ) {
			if (($v['priv'] & $act) != $act) {
				unset($_todos[$k], $todos[$k]); // 对于自己没有权限的项，同时在操作待办里面暂时移除，以便不妨碍下面判断
			}
		}
		if (! $_todos) {
			throw new Exception('您在该流程没有执行此项操作的权限',10004);
		}

		if(!($nodes=$node_db->select(array('flowid'=>$flowid,'isdelete'=>0),'*','','steps ASC','','id')) || !$node=$nodes[$op['nodeid']]){
			throw new Exception('该流程无有效的流程配置信息',10005);
		}

		$to_status =$from_status = intval($op['status']);

		$is_final_user=true;
		
		//规则判断类
		$workflow_reg=new workflow_regulation($vars);
		
		if($act==  self::FLOW_ACTION_DENY){
                        //多分枝时，流程禁止拒绝操作。 
                        $lastnodes = $route_db->select("next_nodeid=".$node['id']);
                        if (count($lastnodes)>1){
                            showmessage("系统不支持多子流程时回退！");
                        }
                        
			//TODO 目前为回退上一级，后续需要实现逆向路由回退，是否有权限执行回退也在这里判断是否有对应路由权限，priv上的回退将调整作为冗余位
			
			//驳回时随便选一个有权限的就行
			$todo=reset($_todos);
                        
			//从历史表追溯上面所有流程操作,该操作必须是上一级节点,防止上一步操作也是退回，这里不支持多节点并行操作的回溯
			//TODO 这里需要改造实现，大数据记录下性能差
			//$rs=$oplog_db->get_one("opid='{$op['id']}' AND status>oldstatus AND status<{$op['status']} AND uid not like 'a-%' ",'nodeid,routeid','id desc');
                        //$rs=$oplog_db->select("opid='{$op['id']}' AND status>oldstatus AND status<{$op['status']} ",'nodeid,routeid,uid','','id desc');
			
                        //从route表回溯上一流程 brant@2015-5-21
                        $node_begin_now = $op['nodeid']; //当前流程起始节点
                        $route_last = $route_db->get_one("next_nodeid = $node_begin_now","id,nodeid,next_nodeid");//上一流程
                        if($route_last){
                            $node_info = $node_db->get_one("id=".$route_last['nodeid']);//起始节点信息
                        }
                        if(!$route_last || $node_info['rootid']==0){
				// 起始节点是第一节点，则认为是驳回
				foreach($nodes as $n){
					if($n['nodetype']==1) {
						$_lastnode=$n;
						break;
					}
				}
				$_nodeid=$op['nodeid'];
				$_nextnodeid=$_lastnode['id'];
				$to_status=-99;
				$routeid=$op['lastrouteid'];//原路返回，认为路径不变				
			}else{
                                $_lastnodeid = $route_last['nodeid'];
                                $_lastnode=$nodes[$_lastnodeid];
                                $_nodeid=$op['nodeid'];
                                $_nextnodeid=$_lastnodeid;
                                $routeid=$route_last['id'];
                                
				$to_status=$_lastnode['steps'];
				//取出上一流程需要参与的人员，放入待办事件
				//TODO 这里认为退回后，上一流程的所有人员都可再操作，可以做成配置，只有上一处理人可操作
				$next_operators=$this->get_operators_in_route($routeid);
				//FIXME 调取事件来改变节点中的操作员
				
				//这里记录在log表中的的routeid和上面的不一样，需要重新获取
				//$rs=$oplog_db->get_one("opid='{$op['id']}' AND status>oldstatus AND status={$op['status']} AND uid not like 'a-%'",'routeid','id desc');
				//$routeid=$rs['routeid'];
			}
		}
		elseif($act==self::FLOW_ACTION_RECYCLE){
			//TODO 未实现
		}
		elseif($act==self::FLOW_ACTION_ACCEPT){
			$is_final_user = true;
			// 判断是否需要组操作
			foreach ( $_todos as $item ) {
				if (in_array($item['uid'], $operators)) {
					if (!empty($item['groupid'])) {
						$groupid = $item['groupid'];
						//选出同组的其他操作人员,选不出来就代表没有其他人要干活了
						foreach($todos as $v){
							if($v['groupid']==$groupid && $v['id']!=$item['id']){
								$is_final_user=false;
								break;
							}
						}
						$todo=$item;
					}else{
						$todo=$item;
						break;
					}
				}
			}

			$_nodeid=$op['nodeid'];
			$_lastnodeid=$op['lastnodeid'];
			$routeid=0;
                        
			if($is_final_user){
				$nextnodes = $this->get_next_node_routes($_nodeid);//下一个节点信息
				if(!$nextnodes || count($nextnodes)==0){
					throw new Exception('该流程未配置有效流程',10006);
				}
				//FIXME 调取事件过滤多余的流程节点
				//判断路由规则中的条件语句
				foreach($nextnodes as $k=>$v){
					if($v['regulation'] && !$workflow_reg->judge($v['regulation'])){
						unset($nextnodes[$k]);
					}
				}
                                
                                //op表中是否还存在objectid，flowid相同，nodeid不同的记录，
                                $other_op_exist = $opdb->get_one(" objectid='".$op['objectid']."' and flowid=".$op['flowid']. " and nodeid !=".$op['nodeid']);
                                
                                //修改支持多子流程 @brant 
                                $opdb->transaction();
                                foreach ($nextnodes as $key=>$nextnode){
                                    $_nextnodeid=$nextnode['id'];
                                    $to_status=$nextnode['steps'];
                                    $next_routeid=$nextnode['routeid'];//下一流程routeid
                                    $route_result = $route_db->get_one("nodeid= ".$op['nodeid']." and next_nodeid=$_nextnodeid");  
                                    $routeid = $route_result['id'];//当前流程id
                                    
                                    if($nextnode['nodetype']!=4){
                                            //取出下一流程需要参与的人员，放入待办事件
                                            $next_operators = $this->get_operators_in_route($nextnode['routeid']);
                                            //FIXME 调取事件来改变节点中的操作员
                                            if(!$next_operators){
                                                    throw new Exception('该流程下未配置有效的操作人员:'.$nextnode['nodename'],10007);
                                            }
                                    }
                                    //=========================================
                                    $result[$key]=array(
                                            'affected' => false,
                                            'objectid'=>$objectid,
                                            'flowaction'=>$flow_action,
                                            'olddata' => $op['olddata'],
                                            'newdata' => $newdata,
                                            'current_node'=>$nodes[$_nextnodeid],
                                            'last_node'=>$nodes[$op['nodeid']],
                                            'operator'=>array('id'=>$operatorid,'name'=>$operator,'realname'=> $real_name),
                                            'current_status'=>self::FLOW_STATUS_INFLOW,
                                            'route' =>$this->get_routeinfo($op['nodeid']),
                                            'last_log'=>$this->get_object_history($flow_action,$objectid,0,1),
                                            'act'=>$act,
                                    );
                                    if($to_status<0){
                                            $result[$key]['current_status'] = self::FLOW_STATUS_CANCEL;
                                    }elseif($to_status==0){			
                                            $result[$key]['current_status'] = self::FLOW_STATUS_NOINFLOW;
                                    }elseif($to_status==99){			
                                            $result[$key]['current_status'] = self::FLOW_STATUS_FINISH;
                                    }else{			
                                            $result[$key]['current_status'] = self::FLOW_STATUS_INFLOW;
                                    }
                                    $result[$key]['op']['steps']=$to_status;


                                    //保存流程操作记录
                                    if (!$logid){
                                        //进入多个子流程只保存一次
                                        $logdata=array(
                                                'relid' => $todo['relid'],
                                                'flowid' => $todo['flowid'],
                                                'objectid' => $objectid,
                                                'opid' => $op['id'],
                                                'oldstatus' => $from_status,
                                                'status' =>  $onlylog ? $from_status:$to_status,
                                            	'create_time' => SYS_TIME,
                                                'reason' => is_array($reason)?array2string($reason):$reason,
                                                'uid' => $this->get_operator_flag($operatorid, '',0),
                                                'nickname' => $operator,
                                                'nodeid' => $_nextnodeid,
                                                'lastnodeid' => $_nodeid,
                                                'token' => $op['token'],
                                                'cache_workname' => $flow['workname'],
                                                'cache_nodename' => $node['nodename'],
                                                'cache_action' => $flow['action'],
                                                'routeid'=>$routeid,
                                                'exec_act'=>$act,
                                                'objecttype'=>$objecttype,
                                                'datas'=>  array2string(array('newdata'=>$newdata)),
                                        );
                                        $logid = $oplog_db->insert($logdata,1);
                                    }
                                    
                                    if (! $logid) {
                                            throw new Exception('保存流程记录失败',10009);
                                    }

                                    if($onlylog){
                                            return $result;
                                    }

                                    
                                    //删除改操作的所有相关待办
                                    $todo_db->delete(array('opid'=>$op['id']));

                                    $cond = array(
                                            'id'=>$op['id'],
                                            'status' => $from_status,
                                    );
                                    //已完成则删除从对象操作表删除
                                    if($to_status==99 || $to_status==-99){
                                            $opdb->delete($cond);
                                            $logdata['cache_nodename']= $to_status==99 ? '结束':'取消';
                                            $logdata['nodeid']=$logdata['exec_act']=0;
                                            $oplog_db->insert($logdata);
                                    }else{
                                            if (empty($other_op_exist)){
                                                //不存在其他子流程
                                                
                                                if (count($nextnodes)>1){
                                                    //分流开始，删除原op记录，新增新op记录
                                                    $opdb->delete(array('id'=>$op['id']));
                                                    $opid = '';
                                                    $opdb->insert(array(
                                                            'objectid' => $objectid,
                                                            'objecttype' => $objecttype,
                                                            'action' => $action,
                                                            'olddata' => array2string($olddata),
                                                            'newdata' => array2string($newdata),
                                                            'status' => $to_status,
                                                            'create_time' => SYS_TIME,
                                                            'token' => random(5),
                                                            'flowid' => $flowid,
                                                            'nodeid' => $nextnode['id'], //当前要进入的流程节点id
                                                            'lastnodeid' => $node['id'],//上一所处的流程节点id
                                                            'appid'=>$this->appid,
                                                            'apply_uid'=>$this->get_operator_flag($operatorid, '',0),//这里记录到数据库，要用系统标识
                                                            'last_uid'=>$this->get_operator_flag($operatorid, '',0),
                                                            'lastrouteid'=>$routeid
                                                    ), 1);
                                                    $opid = $opdb->insert_id();
                                                }else{
                                                    //无分流，更新op记录                                                    
                                                    //扭转流程状态					
                                                    $data = array(
                                                            'status' => $to_status,
                                                            'lastnodeid'=>$op['nodeid'],
                                                            'nodeid'=>$_nextnodeid,
                                                            'lastrouteid'=>$routeid,
                                                            'last_uid'=>$this->get_operator_flag($operatorid, '',0),
                                                    );
                                                    if( $to_status < $from_status){
                                                            $data['token'] = random(5);
                                                    }
                                                    $opdb->update($data, $cond);
                                                }
                                            }else{
                                                //存在其他子流程，删除已经执行子流程
                                                $opdb->delete($cond);
                                            }

                                    }
                                    
                                    // op表中是否还存在objectid，flowid相同，nodeid不同的记录，如果存在，表示还有其他子流程在跑，不会进入下一步                                   
                                    if (empty($other_op_exist)){
                                        //不存在子流程时，进入下一步。添加待办
                                        if( $next_operators){
                                                $opid = $opid?$opid:$op['id']; //op记录有可能新生成
                                                $sql = array();
                                                foreach ( $next_operators as $u ) {
                                                        $sql[] = "('{$opid}','{$flowid}','{$u['nodeid']}','{$u['uid']}','{$u['nickname']}','{$u['groupid']}','" . SYS_TIME . "','{$u['priv']}','{$u['relid']}','{$routeid}')";
                                                }
                                                //重新填充新待办
                                                $sql = 'INSERT INTO ' . $todo_db->tablename() . ' (opid,flowid,nodeid,uid,nickname,groupid,addtime,priv,relid,routeid) VALUES ' . implode(',', $sql);
                                                $todo_db->query($sql);
                                                $todo_db->affected_rows();
                                        }
                                    }   
                                }
                                if ($opdb->commit()){
                                    if($to_status<0){
                                            $current_status = self::FLOW_STATUS_CANCEL;
                                    }elseif($to_status==0){			
                                            $current_status = self::FLOW_STATUS_NOINFLOW;
                                    }elseif($to_status==99){			
                                            $current_status = self::FLOW_STATUS_FINISH;
                                    }else{			
                                            $current_status = self::FLOW_STATUS_INFLOW;
                                    }
                                    return array(
                                        'affected' => true,
                                        'current_status'=>$current_status, //这里只取最后一个子流程状态
                                        'result' => $result
                                    );
                                }else{
                                    $opdb->rollback();
                                }
			}
                        return ;
		}
		elseif($act==self::FLOW_ACTION_BACK){
			//直接退回到原始发起人
			$todo=reset($_todos);
			foreach($nodes as $n){
				if($n['nodetype']==1) {
					$_lastnode=$n;
					break;
				}
			}
			$to_status=-99;
			//$routeid=0;
			$_nodeid=$op['nodeid'];
			$_nextnodeid=$_lastnode['id'];
		}
		else{
			throw new Exception('错误的流程操作指令',10008);
		}
                
                
                //兼容多子流程情况，统一返回格式
		$result[0]=array(
			'affected' => false,
			'objectid'=>$objectid,
			'flowaction'=>$flow_action,
			'olddata' => $op['olddata'],
			'newdata' => $newdata,
			'current_node'=>$nodes[$_nextnodeid],
			'last_node'=>$nodes[$op['nodeid']],
			'operator'=>array('id'=>$operatorid,'name'=>$operator,'realname'=> $real_name),
			'current_status'=>self::FLOW_STATUS_INFLOW,
			'route' =>$this->get_routeinfo($op['nodeid']),
			'last_log'=>$this->get_object_history($flow_action,$objectid,0,1),
			'act'=>$act,
		);
		if($to_status<0){
			$result[0]['current_status'] = self::FLOW_STATUS_CANCEL;
		}elseif($to_status==0){			
			$result[0]['current_status'] = self::FLOW_STATUS_NOINFLOW;
		}elseif($to_status==99){			
			$result[0]['current_status'] = self::FLOW_STATUS_FINISH;
		}else{			
			$result[0]['current_status'] = self::FLOW_STATUS_INFLOW;
		}
		$result[0]['op']['steps']=$to_status;
		
		$communication=new communication($this->appid,$this->uri);
		if($is_final_user && $to_status==99){	
			//TODO 这个判断事件是否存在的太挫了，后面改
                        //先不启用 brant
			//in_array('onflowending',$handlers) && $communication->onFlowEnding($result);
		}
		
		//保存流程操作记录
		$logdata=array(
				'relid' => $todo['relid'],
				'flowid' => $todo['flowid'],
				'objectid' => $objectid,
				'opid' => $op['id'],
				'oldstatus' => $from_status,
				'status' =>  $onlylog ? $from_status:$to_status,
				'create_time' => SYS_TIME,
				'reason' => is_array($reason)?array2string($reason):$reason,
				'uid' => $this->get_operator_flag($operatorid, '',0),
				'nickname' => $operator,
				'nodeid' => $_nextnodeid,
				'lastnodeid' => $_nodeid,
				'token' => $op['token'],
				'cache_workname' => $flow['workname'],
				'cache_nodename' => $node['nodename'],
				'cache_action' => $flow['action'],
				'routeid'=>$routeid,
				'exec_act'=>$act,
				'objecttype'=>$objecttype,
                                'datas'=>  array2string(array('newdata'=>$newdata)),
			);
		$logid = $oplog_db->insert($logdata,1);
		if (! $logid) {
			throw new Exception('保存流程记录失败',10009);
		}
		
		if($onlylog){
			return $result;
		}
		
		// 判断是该流程的最后一个执行者就扭转流程
		if ($is_final_user) {

			$opdb->transaction();
			//删除改操作的所有相关待办
			$todo_db->delete(array('opid'=>$op['id']));
			$flag = $todo_db->affected_rows();
                        
                        // op表中是否还存在objectid，flowid相同，nodeid不同的记录，
                        // 如果存在，表示还有其他子流程在跑，不会进入下一步
                        $other_op_exist = $opdb->get_one(" objectid='".$op['objectid']."' and flowid=".$op['flowid']. " and nodeid !=".$op['nodeid']);
                        if (empty($other_op_exist)){
                            //不存在子流程时，进入下一步。添加待办
                            if( $flag && $next_operators){
                                    $sql = array();
                                    foreach ( $next_operators as $u ) {
                                            $sql[] = "('{$op['id']}','{$flowid}','{$u['nodeid']}','{$u['uid']}','{$u['nickname']}','{$u['groupid']}','" . SYS_TIME . "','{$u['priv']}','{$u['relid']}','{$routeid}')";
                                    }
                                    //重新填充新待办
                                    $sql = 'INSERT INTO ' . $todo_db->tablename() . ' (opid,flowid,nodeid,uid,nickname,groupid,addtime,priv,relid,routeid) VALUES ' . implode(',', $sql);
                                    $todo_db->query($sql);
                                    $flag = $flag && $todo_db->affected_rows();
                            }
                        }                        
			
			if($flag){
				$cond = array(
					'id'=>$op['id'],
					'status' => $from_status,
				);
				//已完成则删除从对象操作表删除
				if($to_status==99 || $to_status==-99){
					$opdb->delete($cond);
					$logdata['cache_nodename']= $to_status==99 ? '结束':'取消';
					$logdata['nodeid']=$logdata['exec_act']=0;
					$oplog_db->insert($logdata);
				}else{
                                        if (empty($other_op_exist)){
                                            //不存在其他子流程
                                            //扭转流程状态					
                                            $data = array(
                                                    'status' => $to_status,
                                                    'lastnodeid'=>$op['nodeid'],
                                                    'nodeid'=>$_nextnodeid,
                                                    'lastrouteid'=>$routeid,
                                                    'last_uid'=>$this->get_operator_flag($operatorid, '',0),
                                            );
                                            if( $to_status < $from_status){
                                                    $data['token'] = random(5);
                                            }
                                            $opdb->update($data, $cond);
                                        }else{
                                            //存在其他子流程，删除已经执行子流程
                                            $opdb->delete($cond);
                                        }
					
				}
				$flag = $flag && $opdb->affected_rows() > 0;
			}

			if ($flag ) {
				//如果下一节点是系统任务的话，排入任务队列
				//FIXME 仅考虑了同意，拒绝回退什么的都还没做
//				if($nextnode['nodetype']==8){
//					$args=$result;
//					$args['affected']=false;
//					//TODO 这里最好补上重试次数
//					$callback_obj=  callback_parse::wakeup_class_callback($communication, 'onJobNodeExecuting', array($args), array(array('module' => 'workflow', 'class' => 'communication')));
//					if(!taskqueue_factory::get_provider()->enqueue($callback_obj)){
//						logs("流程结束后写入任务队列失败,".  var_export($callback_obj,1),LEVEL_FATAL,false,$this->logpath);
//						$opdb->rollback();
//						throw new Exception('保存回调任务失败',10019);
//					}
//				}
				$opdb->commit();
				$result[0]['affected'] = true;		
				
			}else{
				$opdb->rollback();
				$oplog_db->delete(array(
					'id' => $logid
				));
				return false;
			}
			// 保存当前最新作为返回
		} else{
			//删除本次操作的待办
			$uids=array();
			foreach($this->roles as $r){
				$uids[]="'{$r}'";
			}
			$uids[]="'{$this->operatorid}'";
			$todo_db->delete("uid IN (".implode(',',$uids).") and opid=".$op['id']);
			if($todo_db->affected_rows()!=1){
				$oplog_db->delete(array(
					'id' => $logid
				));
				return false;
			}
		}
                return array(
                    'affected' => true,
                    'current_status'=>$result[0]['current_status'], 
                    'result' => $result
                );
	}

	
	/**
	 * 获取指定对象的指定操作历史
	 *
	 */
	public static function get_op_history($opid, $objectid,$start=0,$limit=0) {
		$oplog_db = pc_base::load_model('object_op_log_model');
		$sql=array('opid'=>$opid,'objectid'=>$objectid);
		$count=$oplog_db->count($sql);
		if($start>0 || $limit>0){
			$limit_str= "$start,$limit";		
		}
		return !$count ? array('count'=>0,'items'=>array()) : array('count'=>$count,'items'=>$oplog_db->select($sql,'*',$limit_str,'id DESC'));
	}
	
	public static function get_object_history($flow_action,$objectid,$start=0,$limit=0){
		self::split_flow_const($flow_action, $action, $objecttype);
		$sql=array('objectid'=>$objectid);
		if ($action == '*') {
			$sql['objecttype']=$objecttype;
		}else{
			$sql['cache_action']=$flow_action;
		}
		$oplog_db = pc_base::load_model('object_op_log_model');		
		$count=$oplog_db->count($sql);
		if($start>0 || $limit>0){
			$limit_str= "$start,$limit";		
		}
		return !$count ? array('count'=>0,'items'=>array()) : array('count'=>$count,'items'=>$oplog_db->select($sql,'*',$limit_str,'id deSC'));

	}
		
	/**
	 * 获取当前用户的代办事项
	 *
	 * @param const $flow_const  获取所有时留空
	 * @param string $limit
	 * @return array
	 */
	public function get_todo_list($uid,$roleids,$flow_const = '',$objectid=false,$pageindex=1,$pagesize=10){
	
		$operator_flags=$this->get_operator_flag($uid, $roleids);
		$todo_db = pc_base::load_model('object_flow_rel_todo_model');
		$opdb = pc_base::load_model('object_op_model');
		$node_db = pc_base::load_model('object_flow_node_model');
		$flow_db = pc_base::load_model('object_flow_model');

		$where=array();

		self::split_flow_const($flow_const, $action, $objecttype);
		$flow_db = pc_base::load_model('object_flow_model');
		if($objecttype){
			if ($action == '*') {
				$rs=$flow_db->select("isdelete=0 and appid='{$this->appid}' and action like '{$objecttype}.%'",'id','','','','id');
				$where[]=to_sqls(array_keys($rs),'','flowid');
			}else if($r=$flow_db->get_one(array('isdelete'=>0,'appid'=>$this->appid,'action'=>$flow_const),'id')){				
				$where[]='flowid =\''.$r['id'].'\'';
			}
		}

		$where[]=  to_sqls($operator_flags,'','uid');

		if($objectid){
			if($ops=$opdb->select("objectid='{$objectid}'",'id,create_time,objectid,lastnodeid','','','','id')){
				$where[]=to_sqls(array_keys($ops),'','opid');
			}
		}
		
		$count=$todo_db->count(implode(' AND ',$where));
		if(!$count) return array('count'=>0,'items'=>array());
		if($pageindex>0 ){
			$limit_str=($pageindex-1)*$pagesize.','.$pagesize;		
		}
		$rs= $todo_db->select(implode(' AND ',$where),'*',$limit_str,'id DESC');

		$opids=$nodeids=$flowids=array();
		foreach($rs as $r){
			!in_array($r['opid'],$opids) && $opids[]=$r['opid'];
			!in_array($r['nodeid'],$nodeids) && $nodeids[]=$r['nodeid'];
			!in_array($r['flowid'],$flowids) && $flowids[]=$r['flowid'];
		}

		!isset($ops) && $ops=$opdb->select(to_sqls($opids,'','id'),'id,create_time,objectid,lastnodeid','','','','id');
		$nodes=$node_db->select(to_sqls($nodeids,'','id'),'id,steps,nodename,`description` as nodedesc,`action` as nodeaction','','','','id');
		$flows=$flow_db->select(to_sqls($flowids,'','id'),'id,workname,`description` as workdesc,`action` as flowaction','','','','id');

		$todo_list=array();
		foreach($rs as $r){
			if(!isset($todo_list[$r['opid']])){
				$t=array(
					'nodeid'=>$r['nodeid'],
					'opid'=>$r['opid'],
					'flowid'=>$r['flowid'],
					'operators'=>array()
				);
				$ops[$t['opid']] && $t=array_merge($ops[$t['opid']],$t);
				$nodes[$t['nodeid']] && $t=array_merge($nodes[$r['nodeid']],$t);
				if($flows[$t['flowid']]){
					self::split_flow_const($flows[$t['flowid']]['flowaction'], $action, $objecttype);
					$flows[$r['flowid']]['objecttype']=$objecttype;
					$t=array_merge($flows[$r['flowid']],$t);
				}
				$todo_list[$r['opid']]=$t;
			}
			$todo_list[$r['opid']]['operators'][]=array(
				'uid'=>$r['uid'],
				'nickname'=>$r['nickname'],
				'priv'=>$r['priv']);
		}
		return array('count'=>$count,'pageindex'=>$pageindex,'pagesize'=>$pagesize,'items'=>$todo_list);
	}

	
	/**
	 * 取得下一步流程所有的流程分支节点
	 * @param int $parent_node_id
	 */
	protected function get_next_routes($parent_node_id){
		$node_db = pc_base::load_model('object_flow_node_model');
		$node_route_db = pc_base::load_model('object_flow_node_route_model');
		$sql='SELECT n.*,r.id as routeid,r.regulation , r2.id as next_routeid, r2.next_nodeid as next_nodeid '
                        .' FROM '.$node_route_db->tablename().' r'
                        .' INNER JOIN '.$node_db->tablename().' n ON r.next_nodeid=n.id '
                        .' inner join '.$node_route_db->tablename().' r2 on r2.nodeid = n.id'
                        .' WHERE r.nodeid=\''.$parent_node_id.'\' and n.isdelete=0 and r.isdelete=0';
		$node_db->query($sql);
		$rs=$node_db->fetch_array();
		return $rs;
	}

	/**
	 * 取得下一步流程所有的流程分支节点
	 * @param int $parent_node_id
	 */
	protected function get_next_node_routes($parent_node_id){
		$node_db = pc_base::load_model('object_flow_node_model');
		$node_route_db = pc_base::load_model('object_flow_node_route_model');
		//$sql='SELECT n.*,r.id as routeid,r.regulation FROM '.$node_route_db->tablename().' r INNER JOIN '.$node_db->tablename().' n ON r.next_nodeid=n.id WHERE r.nodeid=\''.$parent_node_id.'\' and n.isdelete=0 and r.isdelete=0';
		$sql='SELECT n.*,nr.id AS routeid,nr.regulation '
			.' FROM '.$node_db->tablename().' n'
			.' INNER JOIN '.$node_route_db->tablename().' r ON r.next_nodeid=n.id'
			.' LEFT JOIN '.$node_route_db->tablename().' nr ON nr.nodeid=r.next_nodeid '
			.' WHERE r.nodeid=\''.$parent_node_id.'\' AND n.isdelete=0 AND r.isdelete=0';
		$node_db->query($sql);
		$rs=$node_db->fetch_array();
		return $rs;
	}
	
	/**
	 * 取出指定流程节点上配置及授权的人员列表
	 * @param unknown_type $nodeid
	 */
	protected function get_operators_in_route($routeid){
		$rel_db = pc_base::load_model('object_flow_rel_model');
		$reltran_db = pc_base::load_model('object_flow_rel_transfer_model');
		$route_db = pc_base::load_model('object_flow_node_route_model');

		$rs = $rel_db->select(array('routeid'=>$routeid,'isenable'=>1),'`id` as relid,routeid,uid,nickname,groupid,addtime,priv');

		if ($rs) {
			foreach ( $rs as $r ) {
				$uids[$r['uid']] = $r;
				$rel_ids[] = $r['relid'];
				$route_ids[]=$r['routeid'];
			}
			$rs_route=$route_db->select('isdelete=0 AND '. to_sqls($route_ids, '', 'id'),'next_nodeid,id','','','','id');
			foreach($uids as &$u){
				$u['nodeid']=$rs_route[$u['routeid']]['next_nodeid'];
			}
			// 选出被授权的用户
			$rs = $reltran_db->select('isenable=1 AND ' . to_sqls($rel_ids, '', 'relid'), 'from_uid,to_uid,to_uname');
			
			foreach ( $rs as $r ) {
				$from_user = $uids[$r['from_uid']];
				$route=$rs_route[$r['routeid']];
				if($from_user && $route){
					$uids[$r['to_uid']] = array(
						'uid' => $r['to_uid'],
						'nickname' => $r['to_uname'],
						'groupid' => $from_user['groupid'],
						'relid' => $from_user['relid'],
						'nodeid' => $route['next_nodeid'],
						'priv' => $from_user['priv']
					);
				}
			}
		}
		return $uids;
	}

	
	/**
	 * 取出指定流程节点上配置及授权的人员列表
	 * @param unknown_type $nodeid
	 */
	protected function get_operators_in_parent_route($parent_routeid){
		$route_db = pc_base::load_model('object_flow_node_route_model');
		$route_sql = 'SELECT nr.id AS routeid,nr.regulation '
		.' FROM '.$route_db->tablename().' r '
		.' LEFT JOIN '.$route_db->tablename().' nr ON nr.nodeid=r.next_nodeid '
		.' WHERE r.id=\''.$parent_routeid.'\' AND r.isdelete=0 AND nr.isdelete=0';
		$route_db->query($route_sql);
		$rs=$route_db->fetch_array();
		$uids = array();
		foreach($rs as $r){
			$uids = array_merge($uids,$this->get_operators_in_route($r['routeid']));
		}

		return $uids;
	}
	
	/**
	 * 
	 * @param string $uid
	 * @param array $roleids
	 * @param bool $iscompose 是否组合返回用户和角色标识
	 * @return string/array
	 */
	protected function get_operator_flag($uid,$roleids,$iscompose=1){
		if($uid=='SYSTEM'){
			$uid= 'a-'.$this->appid;
			return $iscompose ? array($uid): $uid;
		}
		//这里如果使用统一用户，就不要拼接appid
		$ops=array();
		if($uid){
			$ops[]='u-'.$this->appid.'-'.$uid;
			if(!$iscompose) return reset($ops);
		}
		if($roleids){
			if(!is_array($roleids) ) $roleids=  explode (',', $roleids);
			foreach($roleids as $u){
				$ops[]='r-'.$this->appid.'-'.$u;
			}
			if(!$iscompose) return $ops;
		}
		return $ops;
	}


	protected static function split_flow_const($flow_const, &$action, &$objecttype) {
		$i = strpos($flow_const, '.');
		if ($i !== false) {
			$objecttype = substr($flow_const, 0, $i);
			$action = substr($flow_const, $i + 1);
		} else {
			$objecttype = $flow_const;
			$action='*';
		}
	}
	
	
}



