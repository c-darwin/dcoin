<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_arbitrator_conditions';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_commission', 'limit_commission_period') );

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
	SELECT `id`,
				 `name`
	FROM `'.DB_PREFIX.'currency`
	ORDER BY `name`
	');
while ($row = $db->fetchArray($res)) {
	$tpl['currency_list'][$row['id']] = $row['name'];
	//$tpl['currency_min'][$row['id']] = $min_commission_array[$row['name']];
}

if (empty($_SESSION['restricted'])) {
	$res= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_commission`
			');
	while ($row = $db->fetchArray($res)) {
		$my_commission[$row['currency_id']] = array($row['pct'], $row['min'], $row['max']);
	}
}

$res= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.'currency`
		');
while ($row = $db->fetchArray($res)) {
	if (isset($my_commission[$row['id']]))
		$tpl['commission'][$row['id']] = $my_commission[$row['id']];
	else
		$tpl['commission'][$row['id']] = array(0.1, $tpl['currency_min'][$row['id']], 0);
}

// для CF-проектов
$tpl['currency_list'][1000] = 'Crowdfunding';
if (isset($my_commission[1000]))
	$tpl['commission'][1000] = $my_commission[1000];
else
	$tpl['commission'][1000] = array(0.1, 0.01, 0);

$tpl['conditions'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `conditions`
		FROM `".DB_PREFIX."arbitrator_conditions`
		WHERE `user_id` = {$user_id}
		LIMIT 1
		", 'fetch_one');
$tpl['conditions'] = json_decode($tpl['conditions'], true);
if (!$tpl['conditions']) {
	$tpl['conditions'][72] = array('0.01', '0', '0.01', '0', '0.1');
	$tpl['conditions'][23] = array('0.01', '0', '0.01', '0', '0.1');
}

require_once( ABSPATH . 'templates/change_arbitrator_conditions.tpl' );

?>