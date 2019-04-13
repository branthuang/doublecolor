<?php

defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin', 'admin', 0);
/* 开奖号码
 */

class winning_numbers extends admin {

    private $db;
    public $siteid;

    function __construct() {
        parent::__construct();
        $this->db = pc_base::load_model('winning_numbers_model');
    }

    //网络更新
    public function get_numbers() {
        set_time_limit(0);
        $file = "http://www.17500.cn/getData/ssq.TXT";
        //方法1
        /*
          if ($f = fopen("$file", "r")) {
          while (!feof($f)) {
          $file_content .= fread($f, 1024);
          }
          }
          fclose($f);
         */
        //方法2
        /*
        $ch = curl_init();
        $timeout = 60;
        curl_setopt($ch, CURLOPT_URL, $file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0); //启用时会将头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_content = curl_exec($ch);
        curl_close($ch);
*/
        //方法3
        $file_content = file_get_contents($file);
	

        $qi = explode("\n", $file_content);
        $qi_arr = array();
        foreach ($qi as $val) {
            $co = explode(' ', $val);
            if (!empty(trim($co['0']))) {
                $qi_arr[$co['0']] = $co;
            }
        }
        $last_record = $this->db->get_one('', 'issue_num', 'issue_num desc');
        $issue_num = $last_record['issue_num'];
        foreach ($qi_arr as $key => $val) {
            if ($key > $issue_num) {
                $data = array(
                    'issue_num' => intval($val['0']),
                    'num1' => intval($val['2']),
                    'num2' => intval($val['3']),
                    'num3' => intval($val['4']),
                    'num4' => intval($val['5']),
                    'num5' => intval($val['6']),
                    'num6' => intval($val['7']),
                );
                $this->db->insert($data);
            }
        }
        echo 1;
        exit;
    }

    public function init() {
        $order = "issue_num desc";
        $list = $this->db->select('', '*', '', $order);
        include $this->admin_tpl('winning_numbers_list');
    }

    public function add() {
        if ($_POST['dosubmit']) {
            $id = $_POST['id'];
            $data = array(
                'issue_num' => intval($_POST['issue_num']),
                'num1' => intval($_POST['num1']),
                'num2' => intval($_POST['num2']),
                'num3' => intval($_POST['num3']),
                'num4' => intval($_POST['num4']),
                'num5' => intval($_POST['num5']),
                'num6' => intval($_POST['num6']),
            );
            $this->db->insert($data);
            showmessage("添加成功！", 'index.php?m=admin&c=winning_numbers');
        } else {
            include $this->admin_tpl('winning_numbers_edit');
        }
    }

    public function edit() {
        if ($_POST['dosubmit']) {
            $id = $_POST['id'];
            $data = array(
                'issue_num' => intval($_POST['issue_num']),
                'num1' => intval($_POST['num1']),
                'num2' => intval($_POST['num2']),
                'num3' => intval($_POST['num3']),
                'num4' => intval($_POST['num4']),
                'num5' => intval($_POST['num5']),
                'num6' => intval($_POST['num6']),
            );
            $where = array('id' => $id);
            $this->db->update($data, $where);
            showmessage("编辑成功！", 'index.php?m=admin&c=winning_numbers');
        } else {
            $id = intval($_GET['id']);
            if (!$id) {
                exit;
            }
            $where = "id = $id";
            $r = $this->db->get_one($where);
            include $this->admin_tpl('winning_numbers_edit');
        }
    }

    public function delete() {
        $id = intval($_GET['id']);
        $where = array('id' => $id);
        $this->db->delete($where);
        showmessage("删除成功！", 'index.php?m=admin&c=winning_numbers');
    }

    public function excel_import() {
        if (isset($_POST['dosubmit'])) {
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

            $winning_numbers_model = pc_base::load_model("winning_numbers_model");
            //获取数据区域
            $dataStartRow = 2;
            for ($i = $dataStartRow; $i <= $highestRow; $i++) {
                $record = array();
                $issue_num = $objWorksheet->getCellByColumnAndRow(0, $i)->getCalculatedValue();
                $issue_num = intval($issue_num);

                $num1 = $objWorksheet->getCellByColumnAndRow(1, $i)->getCalculatedValue();
                $num2 = $objWorksheet->getCellByColumnAndRow(2, $i)->getCalculatedValue();
                $num3 = $objWorksheet->getCellByColumnAndRow(3, $i)->getCalculatedValue();
                $num4 = $objWorksheet->getCellByColumnAndRow(4, $i)->getCalculatedValue();
                $num5 = $objWorksheet->getCellByColumnAndRow(5, $i)->getCalculatedValue();
                $num6 = $objWorksheet->getCellByColumnAndRow(6, $i)->getCalculatedValue();

                $record = array(
                    'issue_num' => $issue_num,
                    'num1' => intval($num1),
                    'num2' => intval($num2),
                    'num3' => intval($num3),
                    'num4' => intval($num4),
                    'num5' => intval($num5),
                    'num6' => intval($num6),
                );
                $where = array('issue_num' => $issue_num);
                $isexist = $winning_numbers_model->count($where);
                if ($isexist) {
                    $winning_numbers_model->update($record, $where);
                } else {
                    $winning_numbers_model->insert($record);
                }
            }
            showmessage('执行成功！', '?m=admin&c=winning_numbers&a=init', 1000, 'excel_import');
        } else {
            include $this->admin_tpl('winning_num_import');
        }
    }

}

?>