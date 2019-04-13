<?php

return array(
    //工作流相关设置，用于和工作流服务对接，以及本地接收器配置
    'workflow' => array(
        'app_id' => '1', //应用标识
        'secret_key' => '12312312312312312312312312312312', //通讯密钥(未启用)
        'callback_uri' => APP_PATH . '/api.php?op=workflow_client', //工作流回调的地址
        //以下为接收器设置，标准为array(module=>string,class=>string,params=>array) ,在只提供一个module时class按流程名自动匹配。未配置此项则不能正常使用工作审核
        'receiver' => array(
            'bzf.shouli' => array('module' => 'business', 'class' => 'street_accept_receiver', 'params' => array('domain' => '')),//受理
            'bzf.chushen' => array('module' => 'business', 'class' => 'street_check_receiver', 'params' => array('domain' => '')),//初审
            'bzf.qfgfushen' => array('module' => 'business', 'class' => 'area_housing_receiver', 'params' => array('domain' => '')),//区房管复审
            'bzf.qmzfushen' => array('module' => 'business', 'class' => 'area_civil_receiver', 'params' => array('domain' => '')),//区民政复审
            'bzf.qfghuizong' => array('module' => 'business', 'class' => 'area_housing_gather_receiver', 'params' => array('domain' => '')),//区房管汇总
            'bzf.sfgrending' => array('module' => 'business', 'class' => 'city_housing_receiver', 'params' => array('domain' => '')),//省房管认定
            'bzf.change' => array('module' => 'business', 'class' => 'apply_change_receiver', 'params' => array('domain' => '')),//省房管认定
        ),
    )
);
