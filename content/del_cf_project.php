<?php
if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

$tpl['data']['type'] = 'del_cf_project';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['del_id'] = intval($_REQUEST['parameters']['del_id']);
$tpl['project_currency_name'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `project_currency_name`
		FROM `".DB_PREFIX."cf_projects`
		WHERE `id`= {$tpl['del_id']}
		", 'fetch_one');

require_once( ABSPATH . 'templates/del_cf_project.tpl' );

?>