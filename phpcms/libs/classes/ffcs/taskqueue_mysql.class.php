<?php
/**
 * 消息队列的mysql实现
 */
class taskqueue_mysql {
	private $key='default';
	private $db;
	
	public function __construct() {
		$this->db=pc_base::load_model('task_queue_model');
	}
	/**
	 * 获取或设置本消息队列的名字
	 * @param string $key
	 * @return string
	 */
	public function queue_name($key=null){		
		if(isset($key)) $this->key=$key;
		else return $this->key;
	}

	/**
	 * 从 Queue 中移除所有对象
	 */
	public function clear() {
		$this->db->delete(array('qname'=>$this->key));
	}

	/**
	 * 移除并返回位于 Queue 开始处的对象
	 * @return object
	 */
	public function dequeue() {
		if($item=$this->peek()){
			$this->delete($item['id']);
			return $item['data'];
		}
		return FALSE;
	}

	/**
	 * 将对象添加到 Queue 的结尾处
	 * @param int $level 优先级，0最高，这里为延迟实现，时间为$level*10
	 */
	public function enqueue($obj,$level=0) {
		$data=array(
			'data'=> addslashes(addslashes(serialize($obj))), //这里因为PHP对protected等序列化时用到\0导致mysql插入截断
			'createtime'=>SYS_TIME,
			'schedule'=>SYS_TIME+$level*10,
			'qname'=>$this->key
		);
		return $this->db->insert($data,1)>0;
	}

	/**
	 * 返回位于 Queue 开始处的对象但不将其移除。
	 * @return array('data'=>object,'id'=>id)
	 */
	public function peek() {
		$table=$this->db->tablename();
		$sql="UPDATE {$table}, (SELECT id FROM {$table} WHERE status='0' AND schedule<CURRENT_TIMESTAMP AND qname='{$this->key}' ORDER BY schedule ASC LIMIT 1) tmp SET status='1' WHERE {$table}.id=LAST_INSERT_ID(tmp.id)";

		$this->db->query($sql);
		$this->db->query("SELECT data,id FROM {$table} WHERE ROW_COUNT()>0 and id=LAST_INSERT_ID()");
		if($rs=$this->db->fetch_array()){
			$rs=reset($rs);
			$rs['data']=unserialize(stripslashes($rs['data']));
			return $rs;
		}else{
			return false;
		}
	}
	
	/**
	 * 删除指定的单元，和peek搭配使用
	 * @param type $id
	 * @return type
	 */
	public function delete($id){
		$this->db->delete(array('id'=>$id));
		return $this->db->affected_rows()==1;
	}
}
