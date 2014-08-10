<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'cf_comment';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['project_id'] = $_REQUEST['parameters']['project_id'];

require_once( ABSPATH . 'templates/add_cf_comment.tpl' );

?>