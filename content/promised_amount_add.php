<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'new_promised_amount';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

if (empty($tpl['navigate'])) {
	$tpl['navigate'] = 'promised_amount_list';
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `name`,
					 `full_name`,
					 `max_other_currencies`
		FROM `'.DB_PREFIX.'currency`
		ORDER BY `full_name`
		');
while ($row = $db->fetchArray($res)) {
	$tpl['currency_list'][$row['id']] = $row;
	$tpl['currency_list_name'][$row['id']] = $row['name'];
}

$tpl['payment_systems'] =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `id`,
					 `name`
		FROM `'.DB_PREFIX.'payment_systems`
		ORDER BY `name`
		', 'list', array('id', 'name'));

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					 `amount`
		FROM `".DB_PREFIX."max_promised_amounts`
		WHERE `block_id` = 1
		");
while ($row = $db->fetchArray($res)) {
	$tpl['max_promised_amounts'][$row['currency_id']] = $row['amount'];
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					 `amount`
		FROM `".DB_PREFIX."max_promised_amounts`
		WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."max_promised_amounts` ) OR `block_id` = 0
		");
while ($row = $db->fetchArray($res))
	$tpl['max_promised_amounts'][$row['currency_id']] = $row['amount'];

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_promised_amount', 'limit_promised_amount_period') );

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_promised_amount'], $tpl['periods'][$tpl['variables']['limit_promised_amount_period']]), $lng['limits_text']);

// валюта, которая выбрана с селект-боксе
$tpl['currency_id'] = 72;

require_once( ABSPATH . 'templates/promised_amount_add.tpl' );

?>