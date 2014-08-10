<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'new_cf_project';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

asort($lng['cf_category']);

$tpl['currency_list'] = get_currency_list($db);
$tpl['geolocation'] = '39.94887, -75.15005';
$tpl['latitude'] = '39.94887';
$tpl['longitude'] = '-75.15005';
$tpl['city'] = 'Pennsylvania, USA';


require_once( ABSPATH . 'templates/new_cf_project.tpl' );

?>