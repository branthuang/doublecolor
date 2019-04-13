<?php
/**
 * 基础ActiveRecord模型实现
 * 一种领域模型模式，特点是一个模型类对应关系型数据库中的一个表，而模型类的一个实例对应表中的一行记录
 */
abstract class ar_model implements ArrayAccess{

	const SAVEACTION_NONE = 0;
	const SAVEACTION_INSERT = 1;
	const SAVEACTION_UPDATE = 2;
	const SAVEACTION_DELETE = 4;

	protected $datas = array();
	protected $changedProperties = array();
	protected $errors = array();
	private $isNew = true;
	private $isDeleted = false;
	private $isChanged = true;

	

	/**
	 * 模型保存更新时的抽象实现
	 */
	abstract protected function dataUpdate();

	/**
	 * 模型保存插入时的抽象实现
	 */
	abstract protected function dataInsert();

	/**
	 * 模型保存删除时的抽象实现
	 */
	abstract protected function dataDelete();

	/**
	 * 校验字段规则并返回是否成功
	 * @param <type> $saveAction
	 * @return bool
	 */
	abstract protected function validationRules($saveAction,$dbsave=true);
	
	
	public function offsetSet($offset, $value) {
		if ($this->datas[$offset] !== $value) {
			if (!$this->isDeleted && !$this->isNew)
				$this->markChange($offset);
			$this->datas[$offset] = $value;//这里为了保留原始值，所以先标记再变更
		}
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->datas);
	}

	public function offsetUnset($offset) {
	}

	public function offsetGet($offset) {
		return array_key_exists($offset, $this->datas) ? $this->datas[$offset] : null;
	}
	
	/**
	 * 返回变更的键值，包含newval和oldval两个数组
	 * @return array
	 */
	public function changedValues(){
		$vals=array();
		foreach($this->changedProperties as $k=>$v){
			$vals[$k]=array('newval'=>$this->datas[$k],'oldval'=>$v);
		}
		return $vals;
	}
	
	/**
	 * 指定当前对象的删除，但实际的删除将等待Save动作
	 *
	 * @return void
	 *
	 */
	public function delete() {
		$this->isDeleted = true;
		$this->isChanged = true;
		$this->isNew = false;
		return $this;
	}

	/**
	 * 标记修改字段
	 *
	 * @param mixed 修改的模型属性名
	 * @return void
	 *
	 */
	protected function markChange($propertyName) {
		$this->isChanged = true;
		if(!array_key_exists($propertyName, $this->changedProperties)){
			$this->changedProperties[$propertyName] = $this->datas[$propertyName];
		}
	}

	/**
	 * 清空复原模型的修改状态
	 *
	 * @return void
	 *
	 */
	public function markOld() {
		$this->isChanged = false;
		$this->isNew = false;
		$this->changedProperties = array();
	}

	protected function isChanged(){
		return $this->isChanged;
	}
	/**
	 * 获取当前对象的错误信息，以数组返回
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * 将当前对象的属性以数组返回
	 * @return array
	 */
	public function toArray() {
		return $this->datas;
	}

	/**
	 * 保存数据前的校验，通常使用于异步保存
	 * @return bool
	 */
	public function validation(){
		$action = self::SAVEACTION_NONE;
		if ($this->isChanged) {
			if ($this->isDeleted && !$this->isNew)
				$action = self::SAVEACTION_DELETE;
			else
				$action = $this->isNew ? self::SAVEACTION_INSERT : self::SAVEACTION_UPDATE;
		}else{
			return true;
		}
		return $this->validationRules($action,false);
	}
	
	/**
	 * 保存当前对象的修改，修改类型由系统自动判断
	 * @return const 返回值有SAVEACTION_NONE ,SAVEACTION_INSERT,SAVEACTION_UPDATE,SAVEACTION_DELETE 
	 */
	public final function save() {
		$action = self::SAVEACTION_NONE;
		if ($this->isChanged) {
			if ($this->isDeleted && !$this->isNew)
				$_action = self::SAVEACTION_DELETE;
			else
				$_action = $this->isNew ? self::SAVEACTION_INSERT : self::SAVEACTION_UPDATE;

			if ($this->validationRules($_action)) {
				switch ($_action) {
					case self::SAVEACTION_INSERT:
						if ($this->dataInsert()) {
							$action = $_action;
							$this->markOld();
						}
						break;

					case self::SAVEACTION_DELETE:
						if ($this->dataDelete())
							$action = $_action;
						break;

					case self::SAVEACTION_UPDATE:
						if ($this->dataUpdate()) {
							$action = $_action;
							$this->markOld();
						}
						break;
				}
			}

			if ($action != self::SAVEACTION_NONE)
				$this->changedProperties = array();
		}

		return $action;
	}
}
