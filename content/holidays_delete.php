<?php
if (!defined('DC')) die("!defined('DC')");


$tpl['del_id'] = intval($_REQUEST['parameters']['del_id']);

$tpl['data']['type'] = 'holidays_del';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/holidays_delete.tpl' );

?>