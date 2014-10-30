<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'abuses';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['variables'] = ParseData::get_variables ($db,  array('limit_abuses', 'limit_abuses_period') );

$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_abuses'], $tpl['periods'][$tpl['variables']['limit_abuses_period']]), $lng['abuses_limits_text']);

require_once( ABSPATH . 'templates/abuse.tpl' );

?>