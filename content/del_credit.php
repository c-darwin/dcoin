<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_creditor';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_change_host', 'limit_change_host_period') );

$tpl['credit_id'] = intval($_REQUEST['parameters']['credit_id']);

require_once( ABSPATH . 'templates/change_creditor.tpl' );

?>