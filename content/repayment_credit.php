<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['data']['type'] = 'repayment_credit';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$tpl['credit_id'] = intval($_REQUEST['parameters']['credit_id']);

require_once( ABSPATH . 'templates/repayment_credit.tpl' );

?>