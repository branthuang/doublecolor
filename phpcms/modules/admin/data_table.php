<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin','admin',0);
/*数据表
 */
class data_table extends admin {
	private $db;
	public $siteid;
	function __construct() {
		parent::__construct();
		$this->db = pc_base::load_model('data_table_model');
	}
	
	public function init () {
            $order = "listorder desc, id desc";
            $list = $this->db->select('','*','',$order);
            include $this->admin_tpl('data_table_list');
	}
        public function edit(){
            if($_POST['dosubmit']){
                $id = $_POST['id'];
                $name = $_POST['name'];
                $data = array('name'=>$name);
                $where = array('id'=>$id);
                $this->db->update($data, $where);
                showmessage("编辑成功！",'index.php?m=admin&c=data_table');
            }else{
                $id = intval($_GET['id']);
                if(!$id){exit;}
                $where = "id = $id";
                $r = $this->db->get_one($where);
                include $this->admin_tpl('data_table_edit');
            }
        }
        public function delete(){
            $id = intval($_GET['id']);
            $where = array('id'=>$id);
            $this->db->delete($where);
            //关联缩水条件删除
            $condition_model = pc_base::load_model('condition_model');
            $where = array('data_table_id'=>$id);
            $condition_model->delete($where);
            showmessage("删除成功！",'index.php?m=admin&c=data_table');
        }
        public function listorder(){
            $listorder = $_POST['listorder'];
            foreach($listorder as $key=>$val){
                $data = array('listorder'=>$val);
                $where = array('id'=>$key);
                $this->db->update($data, $where);
            }
            showmessage("排序成功！",'index.php?m=admin&c=data_table');
        }
        public function add(){
            if($_POST['dosubmit']){
                $name = $_POST['name'];
                $data = array(
                    'name'=>$name,
                    'addtime'=>time()
                    );
                $id = $this->db->insert($data,true);
                showmessage("添加成功！",'index.php?m=admin&c=data_source&table_id='.$id,'','batch_picker');
            }else{
                include $this->admin_tpl('data_table_add');
            }
        }
}
?>