<?php

defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin', 'admin', 0);
/* 数据源
 */

class data_source extends admin {

    private $db;
    public $siteid;

    function __construct() {
        parent::__construct();
        $this->db = pc_base::load_model('data_source_model');
        $this->data_table = pc_base::load_model("data_table_model");
    }

    public function init() {
        $order = "listorder desc, id desc";
        $table_list = $this->data_table->select('', '*', '', $order);

        $table_id = $_GET['table_id'];
        $table_id = $table_id ? $table_id : $table_list['0']['id'];
        if ($table_id) {
            //要显示的表数据
            $where = array('data_table_id' => $table_id);
            $order = "issue_num asc";
            $list = $this->db->select($where, '*', '', $order);
        }
        //中奖号码
        $win_num_model = pc_base::load_model("winning_numbers_model");
        $win_num = $win_num_model->select();
        $win_num_arr = array();
        foreach ($win_num as $val) {
            $win_num_arr[$val['issue_num']] = $val;
        }
        //缩水号
        $condition = pc_base::load_model("condition_model");
        $suoshui_list = $condition->select(array('data_table_id'=>$table_id));
        
        include $this->admin_tpl('data_source_list');
    }

    //excel导入
    public function excel_import() {
        if (isset($_POST['dosubmit'])) {
            $data_table_id = $_POST['data_table_id'];
            //上传excel
            pc_base::load_sys_class('attachment', 0);
            $attachment = new attachment('admin', 0, 0, 'import' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR);
            $excel = $attachment->upload_excel('excel', '');
            if (!$excel['filepath']) {
                showmessage('请选择正确的文件后再上传');
            }

            //读取excel
            $path = 'libs' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'PHPExcel';
            pc_base::load_sys_class('PHPExcel', $path, 0);
            $objReader = PHPExcel_IOFactory::createReaderForFile($excel['filepath']);
            $objPHPExcel = $objReader->load($excel['filepath']);
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $highestRow = $objWorksheet->getHighestRow();
            if ($highestRow <= 1) {
                showmessage('对不清，您导入的是空表，请重新导入');
            }
            //$highestColumn = $objWorksheet->getHighestColumn();
            //$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); //总列数

            $data_source_model = pc_base::load_model("data_source_model");
            //获取数据区域
            $dataStartRow = 2;
            for ($i = $dataStartRow; $i <= $highestRow; $i++) {
                $record = array();
                $issue_num = $objWorksheet->getCellByColumnAndRow(0, $i)->getCalculatedValue();
                $issue_num = intval($issue_num);
                for ($j = 1; $j <= 33; $j++) {
                    $data = $objWorksheet->getCellByColumnAndRow($j, $i)->getCalculatedValue();
                    $record['col' . $j] = intval($data);
                }
                $record['data_table_id'] = $data_table_id;
                $record['issue_num'] = intval($issue_num);
                $where = array('issue_num' => $issue_num,'data_table_id'=>$data_table_id);
                $isexist = $data_source_model->count($where);
                if ($isexist) {
                    $data_source_model->update($record, $where);
                } else {
                    $data_source_model->insert($record);
                }
            }
            showmessage('执行成功！', '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 1000, 'excel_import');
        } else {
            include $this->admin_tpl('excel_import');
        }
    }
    
    //清表
    function clean() {
        $table_id = $_GET['table_id'];
        if($table_id){
            $where = "data_table_id=".$table_id;
            $this->db->delete($where);
            showmessage('执行成功！', '?m=admin&c=data_source&a=init&table_id=' . $table_id);
        }
    }
    
    //缩水号添加
    function suoshui(){
        if (isset($_POST['dosubmit'])) {
            $data_table_id = $_POST['table_id'];
            $col_min = $_POST['col_min'];
            $col_max = $_POST['col_max'];
            $col_max = ($col_max>=$col_min)?$col_max:$col_min;
            $count_min = $_POST['count_min'];
            $count_max = $_POST['count_max'];
            if($col_min< 1 || $col_max < 1){
                showmessage("缩水条件范围不能小于1，添加失败！", '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 3000, 'suoshui');
            }
            if($count_min< 0 || $col_max < 1){
                showmessage("出号范围不能小于0，添加失败！", '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 3000, 'suoshui');
            }
            if($col_max>33 || $col_min>33){
                showmessage("缩水条件范围不能大于33，添加失败！", '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 3000, 'suoshui');
            }
            $datas = array(
                'data_table_id' => $data_table_id,
                'col_min' => $col_min,
                'col_max' => $col_max,
                'count_min' => $count_min,
                'count_max' => $count_max
            );
            $condition = pc_base::load_model("condition_model");
            $condition->insert($datas);
             showmessage('添加成功！', '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 1000, 'suoshui');
        }else{
            $table_id = $_GET['table_id']; 
            $begin_num = $_GET['begin_num'];//起始号
            
            include $this->admin_tpl('suoshui');
        }
    }
    
    //缩水号列表
    function condition_list(){
        $order = "listorder desc, id desc";
        $table_list = $this->data_table->select('', '*', '', $order);
        
        $table_id = $_GET['table_id'];
        if(!$table_id){
            $table_id = $table_list['0']['id'];
        }
        
        if($table_id){
            $condition = pc_base::load_model("condition_model");
            $list = $condition->select(array('data_table_id'=>$table_id));
        }
        
        include $this->admin_tpl('condition_list');
    }
    //缩水条件删除
    function c_delete(){
        $id = $_GET['id'];
        $data_table_id = $_GET['data_table_id'];
        $condition = pc_base::load_model("condition_model");
        $where = array('id' => $id);
        
        $condition->delete($where);
        showmessage('删除成功！', '?m=admin&c=data_source&a=condition_list&table_id=' . $data_table_id);
    }
    
    //缩水条件编辑
    function c_edit(){
        if (isset($_POST['dosubmit'])) {
            $id = $_POST['id'];
            $condition = pc_base::load_model("condition_model");
            $result = $condition->get_one(array('id'=>$id));
            $data_table_id = $result['data_table_id'];
            
            $col_min = $_POST['col_min'];
            $col_max = $_POST['col_max'];
            $col_max = ($col_max>=$col_min)?$col_max:$col_min;
            $count_min = $_POST['count_min'];
            $count_max = $_POST['count_max'];
            if($col_min< 1 || $col_max < 1){
                showmessage("缩水条件范围不能小于1，编辑失败！", '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 3000, 'suoshui');
            }
            if($count_min< 0 || $col_max < 1){
                showmessage("出号范围不能小于0，编辑失败！", '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 3000, 'suoshui');
            }
            if($col_max>33 || $col_min>33){
                showmessage("缩水条件范围不能大于33，编辑失败！", '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 3000, 'suoshui');
            }
            $datas = array(
                'col_min' => $col_min,
                'col_max' => $col_max,
                'count_min' => $count_min,
                'count_max' => $count_max
            );
            $where = array('id'=>$id);
            $condition->update($datas, $where);
             showmessage('编辑成功！', '?m=admin&c=data_source&a=init&table_id=' . $data_table_id, 1000, 'suoshui');
        }else{
            $id = $_GET['id'];
            $condition = pc_base::load_model("condition_model");
            $result = $condition->get_one(array('id'=>$id));
            
            include $this->admin_tpl('suoshui_edit');
        }
    }

}

?>