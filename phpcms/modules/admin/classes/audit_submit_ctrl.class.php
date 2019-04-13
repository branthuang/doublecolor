<?php

pc_base::load_sys_class('userctrl', 'libs/classes/ffcs', 0);

class audit_submit_ctrl extends userctrl {

    private $workflow, $objectid, $disables = array(), $return_url = CURRENT_URL;
    private $buttons = array(), $audit_info, $routeid;

    /*
         * submit提交
         * 或者加载工作流信息
         */
    public function load($routeid = null) {

        $this->routeid = $routeid;
        if ($this->ispostback()) {
            $this->dopostback();
        } else {
            if (!$this->objectid || !$this->workflow) return;

            $service = pc_base::load_sys_class('workflow_service', 'libs/classes/ffcs');
            $this->audit_info = $service->get_audit_info($this->workflow, $this->objectid, $this->routeid);

            $result = $this->audit_info;
            if (count($result['nodes']) > 1) {
                //同时在跑有多个子流程
                foreach ($result['nodes'] as $val) {
                    $inf .= '<br/> ' . $val['nodeinfo']['nodename'] . ',路由id：' . $val['routeinfo']['id'];
                }
                showmessage("下一步有多个子流程。请指明运行哪个子流程！" . $inf);
            } elseif (count($result['nodes']) == 1) {
                $routeinfo = $result['nodes'][0]['routeinfo']; //在跑流程
                $nodeinfo = $result['nodes'][0]['nodeinfo']; //在跑流程节点
                if ($this->routeid && $routeinfo['id'] != $this->routeid) {
                    showmessage("指定流程不等于在跑流程，请正确指定！ <br/>在跑流程：ID:" . $routeinfo['id'] . " " . $nodeinfo['nodename']);
                }
            }
        }
    }

    /*节点信息*/
    public function audit_info() {
        return $this->audit_info;
    }

    public function return_url($val) {
        $this->return_url = $val;
    }

    /**
     * 在对象不在流程中时，为表单设置提交按钮
     * @param array $buttons array(array('action'=>'','text'=>'','confirm'=>''),'html')
     * @return audit_submit_ctrl
     */
    public function flow_buttons($buttons) {
        if (!is_array($buttons)) return;
        foreach ($buttons as $v) {
            if (is_array($v)) {
                $this->buttons[$v['action']] = $v;
            } else {
                $this->buttons[] = $v;
            }
        }
        return $this;
    }

    public function objectid($objectid) {
        $this->objectid = $objectid;
        return $this;
    }

    public function workflow($workflow) {
        $this->workflow = $workflow;
        return $this;
    }

    public function ctrl_border_enable($enable) {
        $this->disables['ctrl_border'] = 1;
        return $this;
    }

    public function message_input_enable($enable) {
        $this->disables['ctrl_msg'] = 1;
        return $this;
    }

    public function render($data = array()) {

        $objectid = $this->objectid;

        if (!$objectid || !$this->workflow)
            return false;

        $ctrl_prefix = $this->get_ctrl_prefix();
        $result = $this->audit_info;
        $privs = 0;
        if (count($result['nodes']) > 1) {
            //同时在跑有多个子流程
            foreach ($result['nodes'] as $val) {
                $inf .= '<br/> ' . $val['nodeinfo']['nodename'] . ',路由id：' . $val['routeinfo']['id'];
            }
            showmessage("下一步有多个子流程。请指明运行哪个子流程！" . $inf);
        }

        //这里循环只可能有一次。
        foreach ($result['nodes'] as $k => &$v) {
            $v['sig'] = md5("{$v['flowinfo']['action']}|{$result['status']}|$objectid");
            if ($result['status'] == workflow_service::FLOW_STATUS_NOINFLOW && !array_key_exists($v['flowinfo']['action'], $this->buttons)) {
                unset($result['nodes'][$k]);
            } else {
                $privs = $privs | $v['priv'];
            }
            //关联表单
            $model_ids = $v['routeinfo']['model_ids'];
        }

        $next_node_str = $this->next_node_tips($result['nodes']['0']['nodeinfo']['id'], $result['nodes']['0']['flowinfo'], $objectid);

        $buttons = $this->buttons;
        extract($result);
        if ($model_ids) {
            $model_id_array = explode(',', $model_ids);
            $form_render = pc_base::load_app_class('form_render', 'formguide');
        }
        $this->set_template('audit.submit', 'admin');
        include $this->get_compiled_file_path();
    }

    public function ispostback() {
        $ctrl_prefix = $this->get_ctrl_prefix();
        //var_dump($_REQUEST[$ctrl_prefix.'postback'],$_REQUEST[$ctrl_prefix.'action'],$_REQUEST[$ctrl_prefix.'flow_status'],$_REQUEST[$ctrl_prefix.'objectid']);exit;
        return $_REQUEST[$ctrl_prefix . 'postback']
        && $_REQUEST[$ctrl_prefix . 'postback'] == md5("{$_REQUEST[$ctrl_prefix.'action']}|{$_REQUEST[$ctrl_prefix.'flow_status']}|{$_REQUEST[$ctrl_prefix.'objectid']}");
    }

    private function get_ctrl_prefix() {
        return (isset($this->id) ? $this->id : __CLASS__) . '_';
    }

    private function dopostback() {
        $ctrl_prefix = $this->get_ctrl_prefix();
        $service = pc_base::load_sys_class('workflow_service', 'libs/classes/ffcs');
        $objectid = $_REQUEST[$ctrl_prefix . 'objectid'];
        $action = $_REQUEST[$ctrl_prefix . 'action'];
        $status = $_REQUEST[$ctrl_prefix . 'flow_status'];
        //$msg = $_REQUEST[$ctrl_prefix.'message'];
        $info = $_REQUEST['info'];
        $info['objectid'] = $objectid; //操作对象id
        $returnurl = $_REQUEST[$ctrl_prefix . 'returnurl'];
        $nodeaction = $_REQUEST['nodeaction'];
//var_dump($_REQUEST);exit;
        if (!$this->workflow) return false;

        if (strpos($this->workflow->flow_action($objectid), '.*')) {
            $this->workflow = workflow_node::loadbyname($action);
        } elseif ($action != $this->workflow->flow_action($objectid)) {
            showmessage('审核动作无法匹配');
        }
        if ($_POST[$ctrl_prefix . 'dosubmit_apply']) {
            $act = workflow_service::FLOW_ACTION_APPLY;
        } elseif ($_POST[$ctrl_prefix . 'dosubmit_accept']) {
            $act = workflow_service::FLOW_ACTION_ACCEPT;
        } elseif ($_POST[$ctrl_prefix . 'dosubmit_deny']) {
            $act = workflow_service::FLOW_ACTION_DENY;
        } elseif ($_POST[$ctrl_prefix . 'dosubmit_back']) {
            $act = workflow_service::FLOW_ACTION_BACK;
        } elseif ($_POST[$ctrl_prefix . 'dosubmit_recycle']) {
            $act = workflow_service::FLOW_ACTION_RECYCLE;
        } else {
            showmessage('未知的审核动作');
        }

        //表单数据保存
        $result = $service->get_audit_info($this->workflow, $objectid, $this->routeid);
        //$total_info['nodeinfo'] = $result['nodes']['0']['nodeinfo'];
        $model_ids = $result['nodes'][0]['routeinfo']['model_ids'];
        $total_info = array();//表单信息汇总
        $total_info['info'] = $info;
        $model_field = pc_base::load_model("sitemodel_field_model");
        if (!empty($model_ids)) {
            $model_ids_array = explode(',', $model_ids);
            $formguide_model = pc_base::load_model("formguide_model");
            foreach ($model_ids_array as $val) {
                //查询字段类型为多图片
                $fields = $model_field->select(array('modelid'=>$val, 'formtype'=>'images'),'field');
                if(!empty($fields)){
                    $images_fields = array(); 
                    foreach($fields as $f){
                        $images_fields[] = $f['field'];
                    }
                }
                
                $row_num = $_REQUEST['row_num' . $val]; //是否多行记录标识
                $formguide_model->set_model_id($val);
                $formguide_model->delete(array('objectid' => $objectid)); //删除旧记录
                //表信息
                $table_name = $formguide_model->tablename();
                if ($table_name) {
                    //查询表字段
                    $sql = "select column_name from information_schema.columns where table_name='$table_name'";
                    $formguide_model->query($sql);
                    $fields = $formguide_model->fetch_array();
                }

                //检查多行记录，是否传空记录过来。
                foreach ($fields as $c_f) {
                    if ($c_f['column_name'] != 'dataid' && $c_f['column_name'] != 'objectid' && !empty($info[$c_f['column_name']])) {
                        $is_add = true;   //有传值，说明有记录
                        //$bb[] = $c_f['column_name'];
                        break;
                    }
                }
                if ($is_add) {//非空记录才进行添加操作（允许系统传空记录）
                    $id = $formguide_model->add($info);
                    if ($id) {
                        $res = $formguide_model->get_one(array('dataid' => $id));
                        $total_info[$val][] = $res;
                    }
                }
                //多记录
                if ($row_num && $row_num >= 2) {
                    for ($i = 2; $i <= $row_num; $i++) {
                        $check_set = 0;
                        $info_ = array();
                        foreach ($fields as $field) {
                            $field_name = $field['column_name'];
                            if ($field_name != 'dataid' && $field_name != 'objectid') {
                                
                                if(!empty($images_fields) && in_array($field_name, $images_fields)){
                                    //字段类型为多图片，特殊处理
                                    $_POST[$field_name. '_url'] = $_POST[$field_name . '_' . $i. '_url'];
                                    $_POST[$field_name. '_alt'] = $_POST[$field_name . '_' . $i. '_alt'];
                                    
                                    $info_[$field_name] = $info[$field_name . '_' . $i];
                                }else{
                                    //过略空记录
                                    if (isset($info[$field_name . '_' . $i])) {
                                        $check_set = 1;
                                    }

                                    $info_[$field_name] = $info[$field_name . '_' . $i];
                                }
                            }
                        }
                        if (!$check_set) {
                            continue;
                        }
                        $info_['objectid'] = $objectid;
                        $id = $formguide_model->add($info_);
                        if ($id) {
                            $res = $formguide_model->get_one(array('dataid' => $id));
                            $total_info[$val][] = $res;
                        }
                    }
                }

            }
        }

        try {
            $flag = $service->execute($this->workflow, $objectid, $total_info, $act, $total_info, '', $this->routeid);

        } catch (Exception $e) {
            showmessage($e->getMessage());
        }

        if ($flag) {
            if (strpos($this->workflow->flow_action(), '.delete')) { //对删除操作做特殊处理
                if (strpos($this->workflow->flow_action(), 'set')) { //单集
                    $returnurl = str_replace('set', '', $returnurl);
                }
                $returnurl = str_replace('show', 'lists', $returnurl);
            }
            //HJL
            $msg = '';
            if ($action == 'bzf.change') {
                $msg = '变更申请结束';
            } elseif ($action == 'bzf.sfgrending' && $nodeaction == 'bzf.sfgrending.lunhou') {
                $msg = '进入配租配售';
            } else {
                $msg = '审核信息已提交';
            }
            showmessage($msg, $returnurl);
        } else {
            showmessage('审核信息保存失败，请重试');
        }
    }

    //直接调用流程，用于无审核的流程启动
    public function direct_start() {
        $info = '';
        $service = pc_base::load_sys_class('workflow_service', 'libs/classes/ffcs');
        $act = workflow_service::FLOW_ACTION_APPLY;
        try {
            $flag = $service->execute($this->workflow, $this->objectid, $info, $act, $info);
        } catch (Exception $e) {
            showmessage($e->getMessage());
        }
        return $flag;
    }


    //直接执行流程动作，用于跳过特殊步骤
    public function direct_audit($act,$info='',$routeid='') {
        $service = pc_base::load_sys_class('workflow_service', 'libs/classes/ffcs');
        if (empty($act)) {
            $act = workflow_service::FLOW_ACTION_ACCEPT;
        }
        try {
            $flag = $service->execute($this->workflow, $this->objectid, $info, $act, $info,'',$routeid);
        } catch (Exception $e) {
            showmessage($e->getMessage());
        }
        return $flag;
    }

    public function next_node_tips($nodeid, $flowinfo, $objectid) {
        if(!$nodeid){
            showmessage("无下一节点信息！参数错误");
        }
        $object_flow_node_route_db = pc_base::load_model('object_flow_node_route_model');
        $object_flow_node_db = pc_base::load_model('object_flow_node_model');
        $object_op_db = pc_base::load_model('object_op_model');

        $op_info = $object_op_db->select("objectid='" . $objectid . "'", '*');
        if (count($op_info) > 2) {
            return '';
        }

        $next_sql = "SELECT b.* FROM sop_gzf_object_flow_node_route a, sop_gzf_object_flow_node b
			WHERE a.nodeid = " . $nodeid . " AND a.next_nodeid = b.id";
        $object_flow_node_route_db->query($next_sql);
        $next_nodes = $object_flow_node_route_db->fetch_array();
        
        pc_base::load_app_func('flow_exec', 'business');                
        $config_action = config_action($flowinfo['action']);
        $next_node_names = '';
        $flag = 0;
        //获取下一步的所有可能节点名,跨流程的显示下一个流程启动后的节点
        foreach ($next_nodes as $next_node) {
            if ($next_node['nodetype'] != 4 || $config_action == 'bzf.sfgrending') {
                $next_node_names .= '　' . $flowinfo['workname'] . '-' . $next_node['nodename'] . '\r\n';
            } else {
                $flag = 1;
                break;
            }
        }
        //下一步为结束，跨流程
        if ($flag) {
            $next_flow_array = array(
                'bzf.shouli' => 'bzf.chushen',
                'bzf.chushen' => 'bzf.qfgfushen',
                'bzf.qfgfushen' => 'bzf.qmzfushen',
                'bzf.qmzfushen' => 'bzf.qfghuizong',
                'bzf.qfghuizong' => 'bzf.sfgrending'
            );
            $next_flow = $next_flow_array[$config_action];

            $next_flow_start = "SELECT e.*,d.* FROM
				(SELECT object_id FROM sop_gzf_flow_instantiation 
					WHERE config_action = '" . $next_flow . "' AND active=1 GROUP BY config_action ORDER BY id DESC) a
				LEFT JOIN sop_gzf_object_flow e ON e.id = a.object_id
				LEFT JOIN 
					(SELECT id ,flowid FROM sop_gzf_object_flow_node WHERE nodetype = 1) b ON b.flowid = a.object_id
				LEFT JOIN sop_gzf_object_flow_node_route c ON c.nodeid = b.id
				LEFT JOIN sop_gzf_object_flow_node d ON d.id = c.next_nodeid";
            $object_flow_node_route_db->query($next_flow_start);
            $next_nodes = $object_flow_node_route_db->fetch_array();

            //获取下一步的所有可能节点名,跨流程的显示下一个流程启动后的节点
            foreach ($next_nodes as $next_node) {
                $next_node_names .= '　' . $next_node['workname'] . '-' . $next_node['nodename'] . '\r\n';
            }

        }
        $next_node_str = '\r\n提交后，流程将流转至以下环节：\r\n' . $next_node_names;
//        var_dump($next_nodes);
        if ($flowinfo['action'] == 'bzf.change') {
            $next_node_str = '确认后变更申请将结束。';
        } elseif ($flowinfo['action'] == 'bzf.sfgrending' && $next_nodes[0]['action'] == 'bzf.sfgrending.end') {
            $next_node_str = '\r\n提交后，流程将流转至以下环节：\r\n配租配售';
        }
//        var_dump($next_nodes);
        return $next_node_str;
    }

    protected function config_action($action) {
        $action_array = explode('.', $action);
        $action_length = count($action_array);
        if ($action_length == 2 || $action_length == 3) {
            $config_db = pc_base::load_model('flow_instantiation_model');
            $config_info = $config_db->get_one(array('object_action' => $action_array['0'] . '.' . $action_array['1']));
            if ($action_length == 3) {
                return $config_info['config_action'] . '.' . $action_array['2'];
            } elseif ($action_length == 2) {
                return $config_info['config_action'];
            }
        }

        return $action;
    }

}
