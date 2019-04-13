<?php
return array (
  'judgement' => 
  array (
    'info' => 
    array (
      'formtype' => 'textarea',
      'field' => 'judgement',
      'name' => '审核意见',
      'tips' => '',
      'formattribute' => '',
      'css' => '',
      'minlength' => '1',
      'maxlength' => '',
      'pattern' => '',
      'errortips' => '请输入或者选择审核意见',
      'modelid' => '0',
      'setting' => 'array (
  \\\'width\\\' => \\\'70\\\',
  \\\'height\\\' => \\\'46\\\',
  \\\'defaultvalue\\\' => \\\'\\\',
  \\\'enablehtml\\\' => \\\'0\\\',
)',
      'siteid' => '1',
      'unsetgroupids' => '',
      'unsetroleids' => '',
      'listorder' => '0',
    ),
    'sql' => 'ALTER TABLE `formguide_table` ADD `judgement` MEDIUMTEXT NOT NULL',
  ),
  'trustee' => 
  array (
    'info' => 
    array (
      'formtype' => 'text',
      'field' => 'trustee',
      'name' => '经办人',
      'tips' => '',
      'formattribute' => '',
      'css' => '',
      'minlength' => '1',
      'maxlength' => '',
      'pattern' => '',
      'errortips' => '',
      'modelid' => '0',
      'setting' => 'array (
  \\\'size\\\' => \\\'20\\\',
  \\\'defaultvalue\\\' => \\\'\\\',
  \\\'ispassword\\\' => \\\'0\\\',
)',
      'siteid' => '1',
      'unsetgroupids' => '',
      'unsetroleids' => '',
      'listorder' => '1',
    ),
    'sql' => 'ALTER TABLE `formguide_table` ADD `trustee` VARCHAR( 255 ) NOT NULL DEFAULT \'\'',
  ),
  'responsible' => 
  array (
    'info' => 
    array (
      'formtype' => 'text',
      'field' => 'responsible',
      'name' => '负责人',
      'tips' => '',
      'formattribute' => '',
      'css' => '',
      'minlength' => '1',
      'maxlength' => '',
      'pattern' => '',
      'errortips' => '',
      'modelid' => '0',
      'setting' => 'array (
  \\\'size\\\' => \\\'20\\\',
  \\\'defaultvalue\\\' => \\\'\\\',
  \\\'ispassword\\\' => \\\'0\\\',
)',
      'siteid' => '1',
      'unsetgroupids' => '',
      'unsetroleids' => '',
      'listorder' => '2',
    ),
    'sql' => 'ALTER TABLE `formguide_table` ADD `responsible` VARCHAR( 255 ) NOT NULL DEFAULT \'\'',
  ),
  'objectid' => 
  array (
    'info' => 
    array (
      'formtype' => 'text',
      'field' => 'objectid',
      'name' => '对象id',
      'tips' => '',
      'formattribute' => '',
      'css' => '',
      'minlength' => '1',
      'maxlength' => '',
      'pattern' => '',
      'errortips' => '',
      'modelid' => '0',
      'setting' => 'array (
  \\\'size\\\' => \\\'20\\\',
  \\\'defaultvalue\\\' => \\\'\\\',
  \\\'ispassword\\\' => \\\'0\\\',
)',


      'siteid' => '1',
      'unsetgroupids' => '',
      'unsetroleids' => '',
    ),
    'sql' => 'ALTER TABLE `formguide_table` ADD `objectid` VARCHAR( 255 ) NOT NULL DEFAULT \'\'',
  ),
);
?>