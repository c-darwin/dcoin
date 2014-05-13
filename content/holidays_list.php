<?php
if (!defined('DC')) die("!defined('DC')");

$status_list =
	array(
		'my_pending'=>'Еще не попало в FC-сеть',
		'approved'=>'Принято',
		'deleted'=>'Удалено'
	);

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

if (empty($_SESSION['restricted'])) {
	// те, что еще не попали в Dc-сеть
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_holidays`
			ORDER BY `id` DESC
			');
	while ($row = $db->fetchArray($res))
		$tpl['holidays_list']['my_pending'][] = $row;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."holidays`
		WHERE `user_id` = {$user_id}
		");
while ($row = $db->fetchArray($res))
	$tpl['holidays_list']['accepted'][] = $row;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_holidays', 'limit_holidays_period') );

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_holidays'], $tpl['periods'][$tpl['variables']['limit_holidays_period']]), $lng['holidays_limits_text']);

require_once(ABSPATH . 'templates/holidays_list.tpl');
?>