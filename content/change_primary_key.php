<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_primary_key';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_primary_key', 'limit_primary_key_period') );

if (empty($_SESSION['restricted'])) {
	// выводим все запросы
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_keys`
			ORDER BY `id` DESC
			');
	//print $db->printsql();
	while ($row = $db->fetchArray($res))
		$tpl['my_keys'][] = $row;
}

$status_array =
	array(
		'my_pending'=>$lng['local_pending'],
		'approved'=>$lng['status_approved']
	);

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_primary_key'], $tpl['periods'][$tpl['variables']['limit_primary_key_period']]), $lng['change_primary_key_limits_text']);

require_once( ABSPATH . 'templates/change_primary_key.tpl' );

?>