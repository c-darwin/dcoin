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

// узнаем, когда последний раз была смена ключа, чтобы не показывать юзеру страницу смены
$tpl['last_change_key_time'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `time`
		FROM `".DB_PREFIX."log_time_primary_key`
		WHERE `user_id` = {$user_id}
		ORDER BY `time` DESC
		", 'fetch_one');

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_primary_key'], $tpl['periods'][$tpl['variables']['limit_primary_key_period']]), $lng['change_primary_key_limits_text']);

$tpl['last_tx'] = get_last_tx($user_id, $tpl['data']['type_id']);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_tx($tpl['last_tx']);

require_once( ABSPATH . 'templates/change_primary_key.tpl' );

?>