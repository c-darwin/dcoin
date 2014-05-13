<?php
if (!defined('DC')) die("!defined('DC')");

if (empty($_SESSION['restricted'])) {
	// Выводим таблицу всех holidays юзера
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, 'SELECT * FROM `'.DB_PREFIX.MY_PREFIX.'my_holidays' );
	$row = $db->fetchArray($res);
	$tpl = $row;
}

$tpl['data']['type'] = 'new_holidays';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

require_once( ABSPATH . 'templates/new_holidays.tpl' );

?>