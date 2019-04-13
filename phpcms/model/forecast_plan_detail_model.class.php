<?php

defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_sys_class('model', '', 0);

class forecast_plan_detail_model extends model {

    function __construct() {
        $this->db_config = pc_base::load_config('database');
        $this->db_setting = 'default';
        $this->table_name = 'forecast_plan_detail';
        parent::__construct();
    }

}

?>