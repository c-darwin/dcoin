<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'cf_project_data';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/add_cf_project_data.tpl' );

?>