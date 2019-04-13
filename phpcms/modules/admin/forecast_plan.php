<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin','admin',0);

class forecast_plan extends admin {
	private $db;
	public $siteid;
	function __construct() {
		parent::__construct();
		$this->db = pc_base::load_model('forecast_plan_model');
                $this->plan_detail = pc_base::load_model("forecast_plan_detail_model");
	}
	
	public function init () {
            $order = "id desc";
            $list = $this->db->select('','*','',$order);
            include $this->admin_tpl('forecast_plan_list');
	}
        
        public function view(){
            $id = $_REQUEST['id'];//方案id
            $one = $this->db->get_one(array('id'=>$id));
            
            //期号列表
            $data_source_db = pc_base::load_model("data_source_model");
            $sql = "SELECT distinct `issue_num` FROM `d_data_source` ORDER BY issue_num desc ";
            $data_source_db->query($sql);
            $issue_num_arr = $data_source_db->fetch_array();

            //$issue_num = $_POST['issue_num'];
            $issue_num = $_POST['issue_num']?$_POST['issue_num']:$issue_num_arr[0]['issue_num'];
        
            if($issue_num){
                $sql = "select a.id as plan_detail_id, b.* from "
                        . "d_forecast_plan_detail a "
                        . "left join d_forecast b on a.data_table_id = b.data_table_id "
                        . "where a.plan_id = $id and b.issue_num = '".$issue_num."'";
                //方案每期数据
                $this->plan_detail->query($sql);
                $list = $this->plan_detail->fetch_array();
            }
            
        
            include $this->admin_tpl('forecast_plan_view');
        }
        
        public function delete(){
            $id = intval($_GET['id']);
            $where = array('id'=>$id);
            $this->db->delete($where);
            $this->plan_detail->delete(array('plan_id'=>$id));
            showmessage("删除成功！",'index.php?m=admin&c=forecast_plan');
        }
        
        //导出txt文件
        public function download(){
            $id = intval($_POST['id']);
            $issue_num = intval($_POST['issue_num']);
            
            if($id && $issue_num){
                $sql = "select a.id as plan_detail_id, b.* from "
                        . "d_forecast_plan_detail a "
                        . "left join d_forecast b on a.data_table_id = b.data_table_id "
                        . "where a.plan_id = $id and b.issue_num = '".$issue_num."'";
                //方案每期数据
                $this->plan_detail->query($sql);
                $list = $this->plan_detail->fetch_array();
            }
            
            $one = $this->db->get_one(array('id'=>$id));
            
            $plan_name = return_py($one['name']);
            $plan_name = $plan_name['pinyin'];
            
            Header( "Content-type:   application/octet-stream "); 
            Header( "Accept-Ranges:   bytes "); 
            header( "Content-Disposition:   attachment;   filename=".$plan_name.$issue_num.".txt "); 
            header( "Expires:   0 "); 
            header( "Cache-Control:   must-revalidate,   post-check=0,   pre-check=0 "); 
            header( "Pragma:   public "); 
            foreach ($list as $val){
                $line = add_zero($val['num1'])
                        .'	'.add_zero($val['num2'])
                        .'	'.add_zero($val['num3'])
                        .'	'.add_zero($val['num4'])
                        .'	'.add_zero($val['num5'])
                        .'	'.add_zero($val['num6'])
                        ."\r\n";
                echo $line;
            }
        }
        
        public function del_plan_detail(){
            $id = $_GET['id'];
            $plan_id = $_GET['plan_id'];
            if(empty($id)){
                showmessage("错误！");
            }
            $where = array('id'=>$id);
            $this->plan_detail->delete($where);
            showmessage("删除成功！",'index.php?m=admin&c=forecast_plan&a=view&id='.$plan_id);
        }
}
?>