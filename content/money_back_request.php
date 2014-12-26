<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['credit_part_type'] = 'money_back_request';
$tpl['data']['credit_part_type_id'] = ParseData::findType($tpl['data']['credit_part_type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$order_id = intval($_REQUEST['parameters']['order_id']);
$tpl['order'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."orders`
		WHERE `id` = {$order_id}
		", 'fetch_array');

$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/money_back_request.tpl' );
?>