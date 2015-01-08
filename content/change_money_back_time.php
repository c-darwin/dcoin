<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_money_back_time';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['order_id'] = intval($_REQUEST['parameters']['order_id']);
$tpl['days'] = intval($_REQUEST['parameters']['days']);

$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/change_money_back_time.tpl' );
?>