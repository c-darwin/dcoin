<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['credit_part_type'] = 'money_back_request';
$tpl['data']['credit_part_type_id'] = ParseData::findType($tpl['data']['credit_part_type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['my_orders'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."orders`
		WHERE `buyer` = {$user_id}
		ORDER BY `time` DESC
		LIMIT 20
		", 'all_data');

$tpl['currency_list'] = get_currency_list($db);

$tpl['last_tx'] = get_last_tx($user_id, types_to_ids(array('change_seller_hold_back', 'money_back')), 3);
if (!empty($tpl['last_tx']))
	$tpl['last_tx_formatted'] = make_last_txs($tpl['last_tx']);


require_once( ABSPATH . 'templates/arbitration_buyer.tpl' );
?>