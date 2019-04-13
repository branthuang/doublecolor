<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin','admin',0);

class yuce extends admin {
	private $db;
	public $siteid;
	function __construct() {
		parent::__construct();
                $this->win_db = pc_base::load_Model("winning_numbers_model");
                $this->db = pc_base::load_model('yuce_model');
                $this->yuce_issue_num = 9999999;
	}
        /*预测胆组列表*/
        public function init(){
            $begin_issue_num = $_POST['begin_issue_num'];
            if(!$begin_issue_num){
                //默认显示最近30期
                $sql = "select * from d_winning_numbers 
                    order by issue_num desc
                    limit 19,1";
                $this->win_db->query($sql);
                $r = $this->win_db->fetch_array();
                $begin_issue_num = $r[0]['issue_num'];
            }
            $end_issue_num = $_POST['end_issue_num'];
            $where = " where 1=1 ";
            if($begin_issue_num){
                $where .= " and a.issue_num >= '$begin_issue_num' ";
            }
            if($end_issue_num){
                $where .= " and a.issue_num <= '$end_issue_num' ";
            }
            $issue_num_arr = $this->win_db->select('','id,issue_num','','id desc');            
            
            $sql = "select a.issue_num, a.num1,a.num2,a.num3,a.num4,a.num5,a.num6,"
                    . "b.d1,b.d2,b.d3,b.d4,b.d5,b.d6,b.d7,b.d8,b.d9,"
                    . "b.w1,b.w2,b.w3,b.w4,b.w5,b.w6,b.w7,b.w8,b.w9 "
                    . "from d_winning_numbers a "
                    . "left join d_yuce b on a.issue_num = b.issue_num "
                    . $where
                    . "order by a.id desc ";
            
            $this->db->query($sql);
            $list = $this->db->fetch_array();
            
            //预测号
            $yuce_issue_num = $this->yuce_issue_num;
            $where = array('issue_num' => $yuce_issue_num);
            $yuce = $this->db->get_one($where);
            
            include $this->admin_tpl('yuce_list');
        }
        
        //导出txt文件
        public function download(){
            $issue_num = intval($_GET['issue_num']);
            $where = array('issue_num' => $issue_num);
            $result = $this->db->get_one($where);
                        
            Header( "Content-type:   application/octet-stream "); 
            Header( "Accept-Ranges:   bytes "); 
            header( "Content-Disposition:   attachment;   filename=".$result['issue_num'].".txt "); 
            header( "Expires:   0 "); 
            header( "Cache-Control:   must-revalidate,   post-check=0,   pre-check=0 "); 
            header( "Pragma:   public "); 
            $line = '';
            for($i=1;$i<=9;$i++){
                $c_name = 'd'.$i;
                $val = json_decode($result[$c_name]);                
                foreach($val as $k=>$v){
                    if($k==0){
                        $line .= add_zero($v);
                    }else{
                        $line .= '	'.add_zero($v);
                    }
                }
                $line .= "\r\n";
            }
            echo $line;
        }
        
        //初始计算所有
        public function doit(){
            set_time_limit(0);
            $sql = "select a.issue_num from d_winning_numbers a
                left join d_yuce b on a.issue_num = b.issue_num 
                where b.id is null
                order by issue_num asc";
            $this->win_db->query($sql);
            $list = $this->win_db->fetch_array();
            foreach($list as $val){
                $issue_num = $val['issue_num'];
                $this->calculation($issue_num);
            }
            $this->calculation($this->yuce_issue_num);
            echo 1;
            exit;
        }
        
        /*胆组计算*/
        public function calculation($issue_num){
            if(!$issue_num){
                $issue_num = $_REQUEST['issue_num'];
            }
            
            if(!$issue_num){
                return;
            }
            if($issue_num == $this->yuce_issue_num){
                //未开奖期数预测
                $last_one = $this->win_db->get_one('','*','id desc'); //最新一期   
                $win = $last_one;
                $id = $last_one['id'] + 1;
            }else{
                $win = $this->win_db->get_one(array('issue_num'=> $issue_num)); //本期    
                $id = $win['id'];
            }
            
            $id1 = $id - 1;//上一期
            $id2 = $id - 2;//上二期
            $id3 = $id - 3;//上三期
            $id4 = $id - 4;//上四期
            $id5 = $id - 5;//上五期
            $id6 = $id - 6;//上六期
            $id7 = $id - 7;//上七期
            $id8 = $id - 8;//上八期
            $id9 = $id - 9;//上九期
            $id10 = $id - 10;//上十期
            $id11 = $id - 11;//上十一期
            
            $where = " id in ($id1,$id2,$id3,$id4,$id5,$id6,$id7,$id8,$id9,$id10,$id11) ";
            $list = $this->win_db->select($where,'*','','','','id');
                        
            $w = array(
                $win['num1'],$win['num2'],$win['num3'],$win['num4'],$win['num5'],$win['num6']
            );
            $w = intval_array($w);//本期中奖号
            /*前11期的中奖号码。名称分别是last1，last2，last3，last4……last11.
             */
            for($i=1;$i<=11;$i++){
                $name = 'last'.$i;
                $index = 'id'.$i;
                $id_v = $$index;
                
                $tmp = array(
                    $list[$id_v]['num1']?$list[$id_v]['num1']:0,
                    $list[$id_v]['num2']?$list[$id_v]['num2']:0,
                    $list[$id_v]['num3']?$list[$id_v]['num3']:0,
                    $list[$id_v]['num4']?$list[$id_v]['num4']:0,
                    $list[$id_v]['num5']?$list[$id_v]['num5']:0,
                    $list[$id_v]['num6']?$list[$id_v]['num6']:0
                );
                $$name = intval_array($tmp);
            }
            
            /*
             * 胆组一 重码
             * 等于上期中奖号码
             */       
            $d1 = $last1;
            $w1 = get_same_count($d1,$w); //两个数组重复项的数量
            /*
             * 胆组二 邻码
             * 假设要预测期数为第N期（2017059），那么邻号就是第N-1期的六个开奖号码A-F各加、减1，其计算结果理论上有12个号码，
             * 但是
             * 1、应先排除与重码相同的号码，
             * 2、当计算结果出现相同号码时仅取一个号，
             * 3、排除计算结果小于1和大于33的结果，
             * 4、表格从左到右依次填写号码从小到大。
             */
            $last_add_1 = $last_sub_1 = array();
            foreach($last1 as $val){
                $last_add_1[] = $val + 1;
                $last_sub_1[] = $val - 1;
            }
            $d2 = array();
            foreach ($last_add_1 as $val){
                if ($val >=1 && $val <=33){
                    $d2[] = $val;
                }
            }
            foreach ($last_sub_1 as $val){
                if ($val >=1 && $val <=33 && !in_array($val, $d2)){
                    $d2[] = $val;
                }
            }
            $d2 = arr1_sub_arr2($d2, $d1);//去除重码
            sort($d2);
            $w2 = get_same_count($d2, $w);
            /*
             * 胆组3 隔一码
             * 假设要预测期数为第N期（2017059），那么隔1号就是第N-1期的六个开奖号码A-F各加、减2，其计算结果理论上有12个号码，
             * 但是
             * 1、应先排除与重码和邻码相同的号码，
             * 2、当计算结果出现相同号码时仅取一个号，
             * 3、排除计算结果小于1和大于33的结果，
             * 4、表格从左到右依次填写号码从小到大。
             */
            $last_add_2 = $last_sub_2 = array();
            foreach($last1 as $val){
                $last_add_2[] = $val + 2;
                $last_sub_2[] = $val - 2;
            }
            $d3 = array();
            foreach ($last_add_2 as $val){
                if ($val >=1 && $val <=33){
                    $d3[] = $val;
                }
            }
            foreach ($last_sub_2 as $val){
                if ($val >=1 && $val <=33 && !in_array($val, $d3)){
                    $d3[] = $val;
                }
            }
            $d3 = arr1_sub_arr2($d3, $d1);//去除重码
            $d3 = arr1_sub_arr2($d3, $d2);//去除邻码
            sort($d3);
            $w3 = get_same_count($d3, $w);
            /*
             * 胆组4
             * 假设要预测期数为第N期（2017059），那么隔2号就是第N-1期的六个开奖号码A-F各加、减3，其计算结果理论上有12个号码，但是
             * 1、应先排除与重码和邻码以及隔1码相同的号码，
             * 2、当计算结果出现相同号码时仅取一个号，
             * 3、排除计算结果小于1和大于33的结果，
             * 3、表格从左到右依次填写号码从小到大。
             */
            $last_add_2 = $last_sub_2 = array();
            foreach($last1 as $val){
                $last_add_2[] = $val + 3;
                $last_sub_2[] = $val - 3;
            }
                $d4 = array();
            foreach ($last_add_2 as $val){
                if ($val >=1 && $val <=33){
                    $d4[] = $val;
                }
            }
            foreach ($last_sub_2 as $val){
                if ($val >=1 && $val <=33 && !in_array($val, $d4)){
                    $d4[] = $val;
                }
            }
            $d4 = arr1_sub_arr2($d4, $d1);//去除重码
            $d4 = arr1_sub_arr2($d4, $d2);//去除邻码
            $d4 = arr1_sub_arr2($d4, $d3);//去除隔一码
            sort($d4);
            $w4 = get_same_count($d4, $w);
            
            /*胆组5
             * 除胆组1-4以外剩下的所有号码。从左到右依次填写号码从小到大。
             */
            for($i=1;$i<=33;$i++){
                if(!in_array($i,$d1) && !in_array($i,$d2) && !in_array($i, $d3) && !in_array($i, $d4)){
                    $d5[] = $i;
                }
            }
            $w5 = get_same_count($d5, $w);
            
            /*胆组6
             * 遗漏1-2期号码=第N-2期和第N-3期开奖号码合并，扣除重码（第N-1期号码）后剩下的号码。从左到右依次填写号码从小到大。
             */
            $d6 = arr1_add_arr2($last2,$last3);
            $d6 = arr1_sub_arr2($d6, $d1);//去除重码
            sort($d6);
            $w6 = get_same_count($d6, $w);
            
            /*胆组7
             * 类似遗漏1-2期号码；遗漏3-5期号码=第N-4期~第N-6期开奖号码合并，扣除重码，遗漏1-2期号码后的剩余号码。
             */
            $d7 = arr1_add_arr2($last4,$last5);
            $d7 = arr1_add_arr2($d7,$last6);
            $d7 = arr1_sub_arr2($d7, $d1);//去除重码
            $d7 = arr1_sub_arr2($d7, $d6);//去除遗漏1-2期号码
            sort($d7);
            $w7 = get_same_count($d7, $w);
            
            /*胆组8
             * 遗漏6-10期号码=第N-7期~第N-11期开奖号码合并，扣除重码，遗漏1-2期号码，遗漏3-5期号码后的剩余号码。
             */
            $d8 = arr1_add_arr2($last7,$last8);
            $d8 = arr1_add_arr2($d8,$last9);
            $d8 = arr1_add_arr2($d8,$last10);
            $d8 = arr1_add_arr2($d8,$last11);
            
            $d8 = arr1_sub_arr2($d8, $d1);//去除重码
            $d8 = arr1_sub_arr2($d8, $d6);//去除遗漏1-2期号码
            $d8 = arr1_sub_arr2($d8, $d7);//去除遗漏3-5期号码
            sort($d8);
            $w8 = get_same_count($d8, $w);
            
            /*胆组9
             * 遗漏11期以外的号码=扣除重码、遗漏1-2期号码、遗漏3-5期号码、遗漏6-10期号码后的剩余号码
             */
            for($i=1;$i<=33;$i++){
                if(!in_array($i,$d1) && !in_array($i,$d6) && !in_array($i, $d7) && !in_array($i, $d8)){
                    $d9[] = $i;
                }
            }
            $w9 = get_same_count($d9, $w);
            
            $data = array(
                'issue_num' => $issue_num,
                'd1' => json_encode($d1),
                'd2' => json_encode($d2),
                'd3' => json_encode($d3),
                'd4' => json_encode($d4),
                'd5' => json_encode($d5),
                'd6' => json_encode($d6),
                'd7' => json_encode($d7),
                'd8' => json_encode($d8),
                'd9' => json_encode($d9),
                'w1' => $w1,
                'w2' => $w2,
                'w3' => $w3,
                'w4' => $w4,
                'w5' => $w5,
                'w6' => $w6,
                'w7' => $w7,
                'w8' => $w8,
                'w9' => $w9,
            );
            $where = array('issue_num' => $issue_num);
            $result = $this->db->get_one($where);
            if(!$result){
                $this->db->insert($data);
            }else{
                $this->db->update($data,$where);
            }
        }
}