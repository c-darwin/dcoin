<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'money_back';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['order_id'] = intval($_REQUEST['parameters']['order_id']);
$tpl['amount'] = (float) ($_REQUEST['parameters']['amount']);
if (isset($_REQUEST['parameters']['arbitrator'])) {
	$tpl['li'] = '<li><a href="#arbitration_arbitrator">'.$lng['i_arbitrator'].'</a></li>';
	$tpl['redirect'] = 'arbitration_arbitrator';
}
else {
	$tpl['li'] = '<li><a href="#arbitration_seller">'.$lng['i_seller'].'</a></li>';
	$tpl['redirect'] = 'arbitration_seller';
}

$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/money_back.tpl' );
?>