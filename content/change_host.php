<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_host';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_change_host', 'limit_change_host_period') );

$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `host`, `host_status`
		FROM `'.DB_PREFIX.'my_table`
		', 'fetch_array');

$status_array =
	array(
		'my_pending'=>$lng['local_pending'],
		'approved'=>$lng['status_approved']
	);

$tpl['host'] = $data['host'];
$tpl['host_status'] = $status_array[$data['host_status']];

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_change_host'], $tpl['periods'][$tpl['variables']['limit_change_host_period']]), $lng['change_host_limits_text']);

require_once( ABSPATH . 'templates/change_host.tpl' );

?>