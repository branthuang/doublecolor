<?php
//TODO 这里先实现为二级结构，不支持无限极
class task_checklist {

	private $taskid,$parentid,$lists = array();
	private $last_item_name='';
	private $db;
	const ITEM_FINISHED=1;
	const ITEM_NOEXECUTED=2;

	private function __construct($taskid) {
		$this->taskid = strlen($taskid) > 50 ? md5($taskid) : $taskid;
		$this->db = pc_base::load_model('task_checklist_model');
	}
	public static function resume($taskid){
		$obj = new task_checklist($taskid);
		$r = $obj->db->get_one(array('taskid' => $taskid));
		if($r) {
			$obj->lists = string2array($r['lists']);			
			$obj->parentid=$r['parentid'];
			return $obj;
		}else{
			return false;
		}
	}
	public static function create($taskid,$checklist=null,$pid=null) {
		$obj = new task_checklist($taskid);
		$r = $obj->db->get_one(array('taskid' => $taskid));
		if (!$r) {
			if(is_array($checklist)){
				$obj->lists=$checklist;
			}			
			$data=array('createtime' => SYS_TIME, 'taskid' => $obj->taskid,'lists'=>  array2string($obj->lists));
			if($pid){
				$obj->parentid=$pid;
				$data['parentid']=$pid;
			}
			
			$obj->db->insert($data,0,1);
			if($obj->db->affected_rows()==0){
				logs('[task_checklist] delete task fail; ID=' . $this->taskid, LEVEL_FATAL);
				return false;
			}
			return $obj ;
		}
		$obj->lists = string2array($r['lists']);
		$obj->parentid=$r['parentid'];
		
		return $obj;
	}
	public function parentid(){return $this->parentid;}
	public function delete($with_children=false) {
		if($with_children){
			$this->db->delete("taskid='{$this->taskid}' OR parentid='{$this->taskid}'");
		}else{
			$this->db->delete(array('taskid' => $this->taskid));
		}
		if ($this->db->affected_rows() ==0) {
			logs('[task_checklist] delete task fail; ID=' . $this->taskid, LEVEL_ERROR);
		}
	}
	public function is_finish(){
		foreach($this->lists as $item){
			if(!$item['exec']) return false;
		}
		return true;
	}
	public function is_all_finish(){
		if(!$this->is_finish()) return false;
		$rs = $this->db->select(array('parentid' => $this->taskid));	
		foreach($rs as $r){
			foreach(string2array($r['lists']) as $item){
				if(!$item['exec']) return false;
			}
		}
		return true;
	}
	/**
	 * 
	 * @param type $item_name
	 * @return int ITEM_FINISHED已执行 ITEM_NOEXECUTED未执行 false保存失败
	 */
	public function begin_item($item_name,$callbackstr=null){
		if(isset($this->lists[$item_name])){			
			$this->last_item_name=$item_name;
			return $this->lists[$item_name]['exec'] && ($this->last_item_name=$item_name) ? self::ITEM_FINISHED : self::ITEM_NOEXECUTED;
		}else{
			$this->lists[$item_name]=array('exec'=>0,'callback'=>$callbackstr);
			if($this->save2db() ){
				$this->last_item_name=$item_name;
				return self::ITEM_NOEXECUTED ;
			}else{
				logs('保存checklist的任务失败;taskid='.$this->taskid.';itemname='.$item_name.' ',LEVEL_FATAL);
				return false;
			}
		}
	}
	
	public function commit_item($item_name=null){
		if(!isset($item_name)) $item_name=$this->last_item_name;
		$this->lists[$item_name]['exec']=1;
		if($this->save2db()){			
			return true;
		}
		else {			
			unset($this->lists[$item_name]);
			logs('保存checklist的任务失败;taskid='.$this->taskid.';itemname='.$item_name.' ',LEVEL_FATAL);
			return false;
		}
	}
	
	public function count(){
		return count($this->lists);
	}
	
	public function create_child($taskid,$checklist=null){
		return self::create($taskid,$checklist,$this->taskid);
	}
	
	protected function save2db(){
		$this->db->update(array('lists'=>array2string($this->lists),'updatetime'=>SYS_TIME),array('taskid'=>$this->taskid));
		return $this->db->affected_rows()==1;
	}
}
