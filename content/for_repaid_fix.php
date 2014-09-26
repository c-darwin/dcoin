<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'for_repaid_fix';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/for_repaid_fix.tpl' );

?>