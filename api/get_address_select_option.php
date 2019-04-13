<?php 
/*地区联动菜单 option串
 * */
defined('IN_PHPCMS') or exit('No permission resources.');
$parentid = $_GET['parentid'] ? intval($_GET['parentid']) : 0;
$keyid = $_GET['keyid']?$_GET['keyid']:3360;
$selectedid = $_GET['selectedid'];
var_dump($_GET);
$keyid = intval($keyid);
$datas = getcache($keyid,'linkage');
$infos = $datas['data'];
$option_str = "";
var_dump($infos);
foreach($infos AS $k=>$v) {
	if($v['parentid'] == $parentid) {
		$option_str .= "<option value='".$v['linkageid']."'";
		if ($selectedid && $selectedid == $v['linkageid']){
			$option_str .= "selected";
		}
		$option_str .= 		">".$v['name']."</option>";
	}
}
echo $option_str;