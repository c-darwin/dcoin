<?php

require_once( ABSPATH . 'includes/class-parsedata.php' );

$tpl['data']['type'] = 'mining';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/promised_amount_mining.tpl' );

?>