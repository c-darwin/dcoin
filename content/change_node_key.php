<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'change_node_key';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/change_node_key.tpl' );

?>