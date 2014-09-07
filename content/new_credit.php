<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'new_credit';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['currency_list'] = get_currency_list($db);

require_once( ABSPATH . 'templates/new_credit.tpl' );

?>