<?php
if (!defined('DC')) die("!defined('DC')");

require_once( ABSPATH . 'includes/class-parsedata.php' );

$tpl['data']['type'] = 'cf_project_change_category';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['project_id'] = intval($_REQUEST['parameters']['project_id']);
$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `category_id`,
					 `project_currency_name`
		FROM `".DB_PREFIX."cf_projects`
		WHERE `id`= {$tpl['project_id']}
		", 'fetch_array');
$tpl['category_id'] = $data['category_id'];
$tpl['project_currency_name'] = $data['project_currency_name'];

require_once( ABSPATH . 'templates/cf_project_change_category.tpl' );

?>