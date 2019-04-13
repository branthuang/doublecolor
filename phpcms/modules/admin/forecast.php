<?php

defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin', 'admin', 0);

/* 在数据源中，由上期中奖号码位置，预测下期中奖号码
 */

class forecast extends admin {

    private $db;

    function __construct() {
        parent::__construct();
        $this->db = pc_base::load_model('forecast_model');
        $this->data_source = pc_base::load_model("data_source_model");
        $this->data_table = pc_base::load_model("data_table_model");
        $this->forecast_plan = pc_base::load_model("forecast_plan_model");
        $this->forecast_plan_detail = pc_base::load_model("forecast_plan_detail_model");
    }
    
    public function init(){
        //期号列表
        $data_source_db = pc_base::load_model("data_source_model");
        $sql = "SELECT distinct `issue_num` FROM `d_data_source` ORDER BY issue_num desc ";
        $data_source_db->query($sql);
        $issue_num_arr = $data_source_db->fetch_array();

        //$issue_num = $_POST['issue_num'];
        $issue_num = $_POST['issue_num']?$_POST['issue_num']:$issue_num_arr[0]['issue_num'];
        $data_table_id = $_POST['data_table_id'];
        $where = "1=1 ";
        if($issue_num !='all' and !empty($issue_num)){
            $where .= "and a.issue_num='$issue_num' ";
        }
        if($data_table_id!='all' and !empty($data_table_id)){
            $where .= "and a.data_table_id='$data_table_id' ";
        }
        
        $order = " order by "
                . "a.data_table_id asc,a.issue_num asc ";
        $sql = "select * "
                . "from "
                . "d_forecast a "
                . "where $where "
                . "$order";
        //echo $sql;exit;
        $this->db->query($sql);
        $list = $this->db->fetch_array();
        
        //data table
        $data_table = $this->data_table->select();
        foreach($data_table as $val){
            $tables[$val['id']] = $val['name'];
        }
        include $this->admin_tpl('forecast_init');
    }
    /*添加方案
     */
    public function add(){
        $file_name = $_POST['file_name'];
        if(!$file_name){
            showmessage("请输入方案名称！");
        }
        
        $data_table_id = $_POST['data_table_id'];
        if(empty($data_table_id)){
            showmessage("请选择记录！");
        }
                
        $plan_data = array(
            'name' => $file_name,
            'addtime' => time()
        );
        $plan_id = $this->forecast_plan->insert($plan_data,true);
        
        foreach($data_table_id as $val){
            $detail_data = array(
                'plan_id' => $plan_id,
                'data_table_id' => $val
            );
            $this->forecast_plan_detail->insert($detail_data);
        }
        showmessage("添加成功！", "?m=admin&c=forecast&a=init");
    }
    
    public function add_bak(){
        $file_name = $_POST['file_name'];
        $file_name = return_py($file_name);
        $file_name = $file_name['pinyin'];
        if(!$file_name){
            showmessage("请输入导出文件名称！");
        }
        
        $id = $_POST['id'];
        if(empty($id)){
            showmessage("请选择导出记录！");
        }
        
        //data table
        $data_table = $this->data_table->select();
        foreach($data_table as $val){
            $tables[$val['id']] = $val['name'];
        }
        
        $ids = implode(',', $id);
        $sql = "select * from d_forecast where id in($ids)";
        $this->db->query($sql);
        $result = $this->db->fetch_array();
        
         //加载phpexcel
        $path = 'libs' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'PHPExcel';
        pc_base::load_sys_class('PHPExcel', $path, 0);

        //创建一个excel对象
        $objPHPExcel = new PHPExcel();

        // Set properties  设置文件属性
        $objPHPExcel->getProperties()->setCreator("system")
                ->setLastModifiedBy("system")
                ->setTitle("Office 2007 XLSX Test Document")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("Test result file");

        //set width  设置表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(5);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(5);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(5);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(5);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(5);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(5);
        
         // set table header content  设置表头名称
        $objPHPExcel->setActiveSheetIndex()
                ->setCellValue('A1', '排序表')
                ->setCellValue('B1', '期号')
                ->setCellValue('C1', '')
                ->setCellValue('D1', '')
                ->setCellValue('E1', '')
                ->setCellValue('F1', '')
                ->setCellValue('G1', '')
                ->setCellValue('H1', '');
        $i = 1;
        foreach($result as $val){
            $i++;
             $objPHPExcel->setActiveSheetIndex()
                ->setCellValue('A'.$i, $tables[$val['data_table_id']])
                ->setCellValue('B'.$i, $val['issue_num'])
                ->setCellValue('C'.$i, $val['num1'])
                ->setCellValue('D'.$i, $val['num2'])
                ->setCellValue('E'.$i, $val['num3'])
                ->setCellValue('F'.$i, $val['num4'])
                ->setCellValue('G'.$i, $val['num5'])
                ->setCellValue('H'.$i, $val['num6']);
        }
        $objPHPExcel->getActiveSheet()->setTitle('预测号');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function forecast_all(){
        $data_table = $this->data_table->select();
        $sql = "truncate d_forecast";
        $this->db->query($sql);
        foreach($data_table as $val){
            $this->forecast($val['id']);
        }
        echo 1;
        exit;
    }
    public function forecast($table_id=''){
        $table_id = $table_id?$table_id:$_GET['table_id'];
        if(!$table_id){
            exit;
        }
        
        $sql = "SELECT
                    a.*, b.num1, b.num2, b.num3, b.num4, b.num5, b.num6
            FROM
                    d_data_source a
            LEFT JOIN d_winning_numbers b ON a.issue_num = b.issue_num
            where a.data_table_id = $table_id  ";
        $this->db->query($sql);
        $result = $this->db->fetch_array();
        $position = array();//中奖号码位置
        foreach ($result as $key=>$val){
            for($i=1;$i<=6;$i++){
                $num = 'num'.$i;
                for($j=1;$j<=33;$j++){
                    $col = 'col'.$j;
                    if($val[$col] == $val[$num]){
                        $position[$val['issue_num']][] = $j;
                        break;
                    }
                }
            }
        }
        $forecast = array(); //预测号
        foreach ($result as $key=>$val){
            $last_issue_num = $val['issue_num'] -1; //上期期号
            $p = $position[$last_issue_num];
            if(isset($p)){
                $forecast[$val['issue_num']] = array(
                    '0' => $val['col'.$p[0]],
                    '1' => $val['col'.$p[1]],
                    '2' => $val['col'.$p[2]],
                    '3' => $val['col'.$p[3]],
                    '4' => $val['col'.$p[4]],
                    '5' => $val['col'.$p[5]],
                );
            }
        }
        
        $sql_begin = "insert into d_forecast
                (data_table_id, issue_num, num1, num2, num3, num4, num5, num6)
                VALUES ";
        $sql = '';
        if(!empty($forecast)){
            $i = 0;
            foreach ($forecast as $key=>$val){
                $i++;
                $num1 = $val['0'];
                $num2 = $val['1'];
                $num3 = $val['2'];
                $num4 = $val['3'];
                $num5 = $val['4'];
                $num6 = $val['5'];
                if($sql){
                    $sql .= ",($table_id,$key, $num1, $num2,$num3,$num4,$num5,$num6)";
                }else{
                    $sql .= "($table_id,$key, $num1, $num2,$num3,$num4,$num5,$num6)";
                }
                
                if($i == 30){
                    //每隔30条执行一次，防止sql语句过长
                    $this->db->query($sql_begin.$sql);
                    
                    //再次初始化
                    $i = 0;
                    $sql = ''; 
                }
            }
            if($i> 0){
                $this->db->query($sql_begin.$sql);
            }
        }
        return 1;
    }

}
