<?php

/*
 * 内容对象依赖控制
 * 
 * 场景描述：
 * 如 运营CMS中的精选、推荐位等已经使用了某内容作品，则不允许下架，
 * 或必须先询问（api）再根据结果允许或禁止动作。
 * 
 * 数据模型：
 * （content_depend+content_depend_rule）
 *	唯一KEY 应用 模块 内容类型 内容id 动作 规则
	唯一KEY：		uuid （ md5(appid+appmodule+ctype+cid)  ) 
	应用：		appid
	模块：		appmodule
	内容类型：		ctype（0漫画、1漫画单集、2动画、3动画单集）
	内容id：		cid  
	动作：		action（publish商用、online上线、logout下线）
	规则：		rule_type（0：禁止执行->1:询问是否执行->2:执行后通知）
	规则数据：		rule_data（类型0的提示文字；1的询问api；2的通知api）
 * 
 * FIXME：目前仅支持动漫画下线前判断：单集=精选、系列=4.0推荐位（要标记不能被消息推送，待下线列表）、热门特辑
 *	action：仅支持下线logout
 *	rule_type： 仅支持0：禁止执行
 *	其他类型在用到时开发
 */

/**
 * 内容依赖对象
 *
 * @author 
 */
class content_depend {
	
	/*
	 * 内容依赖规则添加
	 * 
	 *@param  $data=array(
		    'appid'=>'1',
		    'appmodule'=>'picks',
		    'ctype'=>'1',
		    'cid'=>'200068488',
		    'action'=>'logout',
		    'rule'_type=>'0',
		    'rule_data'=>'已加入精选，不允许下线'
		);
	 * @return boolean
	 */
	public static function add($data){
		$db=pc_base::load_model('content_depend_model');
		//生成uuid
		$data['uuid']=md5($data['appid'].'-'.$data['appmodule'].'-'.$data['ctype'].'-'.$data['cid'].'-'.$data['action']);
		//print_r($data);
		//Replace insert
		return $db->insert($data,false, true);
	}
	
	/*
	 * 内容依赖规则取消
	 * @param $uuid
	 * @return boolean
	 */
	public static function remove($uuid){
		$db=pc_base::load_model('content_depend_model');
		return $db->delete(array('uuid'=>$uuid));
	}
	
	/*
	 * 内容依赖检查
	 * 
	 * 执行顺序：
	 * 0：禁止执行->1:询问是否执行->2:执行后通知
	 * @return array(“是否执行”，“提示消息”)
	 */
	public static function check($ctype,$cid,$action){
		$db=pc_base::load_model('content_depend_model');
		$where=array(
		    'ctype'	=>	$ctype,
		    'cid'	=>	$cid,
		    'action'	=>	$action
		);
		$list=$db->select($where,'*','','rule_type');
                if(!empty($list)) {
                        foreach ($list as $k => $v) {
                                if($v['rule_type']===0){
                                        //禁止执行
                                        if($v['appmodule']=='position4_system') {
                                                $c = ($v['ctype']==0 || $v['ctype'] == 1) ? 'comicset' : 'animeset';
                                                echo "<script>if(confirm('".$v['rule_data']."')){window.location.href='?m=substance&c=comic&a=wait_logout_record&id=".$v['cid']."&content_type=".$v['ctype']."';}else{window.location.href='?m=substance&c=".$c."&a=show&comic_id=".$v['cid']."';}</script>";
                                                exit();
                                        }
                                        return array(false,$v['rule_data']);
                                }elseif($v['rule_type']===1){
                                        //FIXME：执行rule_data提供的api
                                        $api=$v['rule_data'];
                                        //... ...
                                        $oldcms_content_depend_url = pc_base::load_config('oldcms', 'oldcms_content_depend_url');
                                        $http = pc_base::load_sys_class('http');
                                        $data = array('appmodule'=>$v['appmodule'], 'contentid'=>$v['cid'], 'content_type'=>$v['ctype']);
                                        $http->post($oldcms_content_depend_url, $data, '', 0, 10); //设置10秒超时
                                        $result = json_decode($http->get_data(), true);
                                        if(empty($result)) {
                                               return array(true); 
                                        }else {
                                               return array(false,$result['notice']);
                                        }
                                        //$rtn_api=true;
                                        //根据api结果判断是否执行
                                        //return array($rtn_api,'');	
                                }elseif($v['rule_type']===2){
                                        //先执行，后通知
                                        return array(true,'');
                                }
                        }
                }
		//其他情况，直接执行
		return array(true,'');
	}
	/*
	 * 内容依赖通知
	 * 
	 * 规则2:执行后通知
	 * @return boolean
	 */
	public static function notify($ctype,$cid,$action){
		$db=pc_base::load_model('content_depend_model');
		$where=array(
		    'ctype'	=>	$ctype,
		    'cid'	=>	$cid,
		    'action'	=>	$action
		);
		$list=$db->select($where,'uuid,rule_type','','rule_type');
		foreach ($list as $k => $v) {
			if($v['rule_type']===2){
				//调用通知
				$api=$v['rule_data'];
				//FIXME：调用api
				/*
				 * 异步队列调用参考
				pc_base::load_sys_class('taskqueue_factory','libs/classes/ffcs',0);
				pc_base::load_sys_class('callback_parse','libs/classes/ffcs',0);
				$data=callback_parse::app_class_callback('substance/notify','notify_'.$this->domain,'published',0,array($result['objectid']));
				 */
				//... ...
                $oldcms_content_depend_url = pc_base::load_config('oldcms', 'oldcms_content_depend_url');
                $http = pc_base::load_sys_class('http');
                $data = array('appmodule'=>$v['appmodule'], 'contentid'=>$v['cid'], 'content_type'=>$v['ctype']);
                $http->post($oldcms_content_depend_url, $data, '', 0, 10); //设置10秒超时
                logs('http_result='.$http->get_data());
                return true;
			}
		}
        return true;
	}
}
