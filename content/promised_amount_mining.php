<?php

$tpl['data']['type'] = 'mining';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['amount'] = floor($_REQUEST['parameters']['amount']*100)/100;
$tpl['promised_amount_id'] = intval($_REQUEST['parameters']['promised_amount_id']);

require_once( ABSPATH . 'templates/promised_amount_mining.tpl' );

?>