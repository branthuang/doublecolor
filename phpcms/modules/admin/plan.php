<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin','admin',0);
/*数据表
 */
class plan extends admin {
	private $db;
	public $siteid;
	function __construct() {
		parent::__construct();
		$this->db = pc_base::load_model('plan_model');
                $this->plan_detail = pc_base::load_model("plan_detail_model");
                $this->data_table = pc_base::load_model("data_table_model");
	}
	
	public function init () {
            $order = "listorder desc, id desc";
            $list = $this->db->select('','*','',$order);
            include $this->admin_tpl('plan_list');
	}
        
        //方案每期数据
        private function get_plan($plan_id,$issue_num){
            $where = " b.issue_num='$issue_num' ";
            $where .= " and c.plan_id=$plan_id ";
            //condition
            $order = " order by "
                . "a.data_table_id asc,a.id desc ";
            $sql = "select a.id as condition_id, a.col_min, a.col_max, a.count_min, a.count_max , b.*, c.id as plan_detail_id "
                    . "from "
                    . "d_condition a "
                    . "left join d_data_source b on a.data_table_id = b.data_table_id "
                    . "inner join d_plan_detail c on c.condition_id = a.id"
                    . " where $where "
                    . "$order";
            //echo $sql;exit;
            $this->db->query($sql);
            $list = $this->db->fetch_array();
            return $list;
        }
        
        public function view(){
            //期号列表
            $data_source_db = pc_base::load_model("data_source_model");
            $sql = "SELECT distinct `issue_num` FROM `d_data_source` ORDER BY issue_num desc ";
            $data_source_db->query($sql);
            $issue_num_arr = $data_source_db->fetch_array();
            //默认期号
            $issue_num = $_POST['issue_num']?$_POST['issue_num']:$issue_num_arr['0']['issue_num'];
        
            $id = $_REQUEST['id'];//方案id
            $one = $this->db->get_one(array('id'=>$id));
            
            //方案每期数据
            $list = $this->get_plan($id,$issue_num);
            
            //data table
            $data_table = $this->data_table->select();
            foreach($data_table as $val){
                $tables[$val['id']] = $val['name'];
            }
        
            include $this->admin_tpl('plan_view');
        }
        //编辑方案
        public function edit(){
            if($_POST['dosubmit']){
                $id = $_POST['id'];
                $plan_name = $_POST['plan_name'];
                $data = array('plan_name'=>$plan_name);
                $where = array('id'=>$id);
                $this->db->update($data, $where);
                
                $cols_num = $_POST['cols_num'];
                $count_min = $_POST['count_min'];
                $count_max = $_POST['count_max'];
                
                foreach($cols_num as $key=>$val){
                    $num = str_replace(',', ',', $val);
                    $num_arr = explode(',', $num);
                    $data = array(
                        'cols_num' => array2string($num_arr),
                        'count_min' => $count_min[$key],
                        'count_max' => $count_max[$key]
                    );
                    $this->plan_detail->update($data, array('id'=>$key));
                }
                showmessage("编辑成功！",'index.php?m=admin&c=plan');
            }else{
                $id = intval($_GET['id']);
                $one = $this->db->get_one(array('id'=>$id));
                $detail = $this->plan_detail->select(array('plan_id'=>$id));
            
                include $this->admin_tpl('plan_edit');
            }
        }
        public function delete(){
            $id = intval($_GET['id']);
            $where = array('id'=>$id);
            $this->db->delete($where);
            $this->plan_detail->delete(array('plan_id'=>$id));
            showmessage("删除成功！",'index.php?m=admin&c=plan');
        }
        
        //导出txt文件
        public function download(){
            $id = intval($_POST['id']);
            $issue_num = $_POST['issue_num'];
            
            $list = $this->get_plan($id,$issue_num);
            $one = $this->db->get_one(array('id'=>$id));
            
            $plan_name = return_py($one['plan_name']);
            $plan_name = $plan_name['pinyin'];
            
            Header( "Content-type:   application/octet-stream "); 
            Header( "Accept-Ranges:   bytes "); 
            header( "Content-Disposition:   attachment;   filename=".$issue_num.$plan_name.".txt "); 
            header( "Expires:   0 "); 
            header( "Cache-Control:   must-revalidate,   post-check=0,   pre-check=0 "); 
            header( "Pragma:   public "); 
            foreach ($list as $val){
                $col_min = $val['col_min'];
                $col_max = $val['col_max'];
                $count_min = $val['count_min'];
                $count_max = $val['count_max'];
                $line = '';
                for($i=$col_min;$i<=$col_max;$i++){
                    if($line){
                        $line .= '	'.add_zero($val['col'.$i]);
                    }else{
                        $line .= add_zero($val['col'.$i]);
                    }
                }
                $line .='='.$count_min.'-'.$count_max;
                $line .= "\r\n";
                echo $line;
            }
            
//            foreach($detail as $val){
//                $cols_num = $val['cols_num'];
//                $cols_num = string2array($cols_num);
//                $count_min = $val['count_min'];
//                $count_max = $val['count_max'];
//                
//                $line = '';
//                foreach ($cols_num as $v){
//                    if($line){
//                        $line .= '	'.add_zero($v);
//                    }else{
//                        $line .= add_zero($v);
//                    }
//                }
//                $line .='='.$count_min.'-'.$count_max;
//                $line .= "\r\n";
//                echo $line;
//            }
        }
        
        public function del_plan_detail(){
            $id = $_GET['id'];
            $plan_id = $_GET['plan_id'];
            if(empty($id)){
                showmessage("错误！");
            }
            $where = array('id'=>$id);
            $this->plan_detail->delete($where);
            showmessage("删除成功！",'index.php?m=admin&c=plan&a=view&id='.$plan_id);
        }
        
        public function listorder(){
            $listorder = $_POST['listorder'];
            foreach($listorder as $key=>$val){
                $data = array('listorder'=>$val);
                $where = array('id'=>$key);
                $this->db->update($data, $where);
            }
            showmessage("排序成功！",'index.php?m=admin&c=plan');
        }
        
        /*方案未选择的缩水条件列表*/
        public function add_condition_list(){
            $this->condition = pc_base::load_model('condition_model');
            $this->data_table = pc_base::load_model("data_table_model");
            
            $plan_id = $_REQUEST['plan_id'];//方案id
            
            if($_POST['dosubmit']){
                $condition_id = $_POST['condition_id'];
                foreach ($condition_id as $val){
                    $data = array(
                        'plan_id' => $plan_id,
                        'condition_id' => $val
                    );
                    $this->plan_detail->insert($data);
                }
                showmessage("新增成功！","index.php?m=admin&c=plan&a=view&id=$plan_id",'','excel_import');
            }
            
            //已选择列表
            $result = $this->plan_detail->select(array('plan_id'=>$plan_id));
            foreach($result as $val){
                $c_arr[] = $val['condition_id'];
            }
            
            //期号列表
            $data_source_db = pc_base::load_model("data_source_model");
            $sql = "SELECT distinct `issue_num` FROM `d_data_source` ORDER BY issue_num desc ";
            $data_source_db->query($sql);
            $issue_num_arr = $data_source_db->fetch_array();

            $issue_num = $_REQUEST['issue_num']?$_REQUEST['issue_num']:$issue_num_arr[0]['issue_num'];
            $data_table_id = $_REQUEST['data_table_id'];
            $where = "1=1 ";
            if($issue_num){
                $where .= "and b.issue_num='$issue_num' ";
            }
            if($data_table_id){
                $where .= "and b.data_table_id='$data_table_id' ";
            }

            $order = " order by "
                    . "a.data_table_id asc,a.id desc ";
            $sql = "select a.id as condition_id, a.col_min, a.col_max, a.count_min, a.count_max , b.* "
                    . "from "
                    . "d_condition a left join d_data_source b on a.data_table_id = b.data_table_id "
                    . "where $where "
                    . "$order";
            //echo $sql;exit;
            $this->condition->query($sql);
            $list = $this->condition->fetch_array();

            //data table
            $data_table = $this->data_table->select();
            foreach($data_table as $val){
                $tables[$val['id']] = $val['name'];
            }
            
            include $this->admin_tpl('add_condition_list');
        }
}
?>