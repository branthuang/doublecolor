<?php

/**
 * Description of usertick
 *
 * @author Administrator
 */
class userticket implements ArrayAccess {

	protected $ticketid;
	protected $datas;

	public function __construct($datas, $ticketid=null) {
		$this->datas = is_array($datas) ? $datas : array();
		$this->ticketid = empty($ticketid) ? hashcode($datas) : $ticketid;
	}

	public function offsetSet($offset, $value) {
		//$this->datas[$offset] = $value;
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->datas);
	}

	public function offsetUnset($offset) {
		//unset($this->datas[$offset]);
	}

	public function offsetGet($offset) {
		return array_key_exists($offset, $this->datas) ? $this->datas[$offset] : null;
	}

	public function isauthentic() {
		return isset($this->datas['userid']) && intval($this->datas['userid']) > 0;
	}
	
	public function isempty(){
		return count($this->datas)==0;
	}

	public function get_ticketid() {
		return $this->ticketid;
	}
	
	public function get_userid() {
		return $this->datas['userid'];
	}


	public function get_cpid() {
		
		return $this->datas['cpid'];	
	}

	public function toarray(){
		return $this->datas;
	}
}

