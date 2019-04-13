<?php

defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin', 'admin', 0);
/* 缩水条件管理
 */

class condition extends admin {

    private $db;
    public $siteid;

    function __construct() {
        parent::__construct();
        $this->condition = pc_base::load_model('condition_model');
        $this->data_table = pc_base::load_model("data_table_model");
    }

    public function init() {
        //期号列表
        $data_source_db = pc_base::load_model("data_source_model");
        $sql = "SELECT distinct `issue_num` FROM `d_data_source` ORDER BY issue_num desc ";
        $data_source_db->query($sql);
        $issue_num_arr = $data_source_db->fetch_array();

        $issue_num = $_POST['issue_num']?$_POST['issue_num']:$issue_num_arr[0]['issue_num'];
        $data_table_id = $_POST['data_table_id'];
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
        include $this->admin_tpl('condition_init');
    }
    
    //添加方案
    public function add_plan(){
        $plan_name = $_POST['plan_name'];
        $condition_id = $_POST['condition_id'];
        if(!$plan_name){
            showmessage("请输入方案名称！");
        }
        if(empty($condition_id)){
            showmessage("请选择缩水条件！");
        }
        $data = array(
            'plan_name' =>$plan_name,
            'addtime' => time()
        );
        $plan_model = pc_base::load_model("plan_model");
        $plan_detail_model = pc_base::load_model("plan_detail_model");
        $p_id = $plan_model->insert($data,true); //方案id
        foreach ($condition_id as $val){
            $data = array(
                'plan_id' => $p_id,
                'condition_id' => $val
            );
            $plan_detail_model->insert($data);
        }
        
        showmessage("增加成功！", "?m=admin&c=plan&a=init");
    }

}

?>