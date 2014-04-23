<?php
if (!defined('DC')) die("!defined('DC')");

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.'deamons`
		ORDER BY `script`
		');
while ($row = $db->fetchArray($res)) {
	$row['time'] = date('d-m-Y H:i:s', $row['time']);
	$row['memory'] = round($row['memory']/1024/1024, 2);
	$tpl['demons'][] = $row;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT `'.DB_PREFIX.'nodes_ban`.`ban_start`,
					  `'.DB_PREFIX.'nodes_ban`.`user_id`,
					  `'.DB_PREFIX.'miners_data`.`host`,
					  `'.DB_PREFIX.'nodes_ban`.`info`
		FROM `'.DB_PREFIX.'nodes_ban`
		LEFT JOIN `'.DB_PREFIX.'miners_data` ON `'.DB_PREFIX.'miners_data`.`user_id` = `'.DB_PREFIX.'nodes_ban`.`user_id`
		ORDER BY `ban_start`
		');
while ($row = $db->fetchArray($res)) {
	$row['ban_start'] = date('d-m-Y H:i:s', $row['ban_start']);
	$tpl['nodes_ban'][] = $row;
}


$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.'nodes_connection`
		ORDER BY `user_id`
		');
while ($row = $db->fetchArray($res)) {
	$tpl['nodes_connection'][] = $row;
}

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT *
		FROM `'.DB_PREFIX.'main_lock`
		');
while ($row = $db->fetchArray($res)) {
	$row['lock_time'] = date('d-m-Y H:i:s', $row['lock_time']);
	$tpl['main_lock'][] = $row;
}

$tpl['queue_tx'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT count(*)
		FROM `'.DB_PREFIX.'queue_tx`
		', 'fetch_one');

$tpl['transactions_testblock'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT count(*)
		FROM `'.DB_PREFIX.'transactions_testblock`
		', 'fetch_one');

$tpl['transactions'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
		SELECT count(*)
		FROM `'.DB_PREFIX.'transactions`
		', 'fetch_one');

require_once( ABSPATH . 'templates/db_info.tpl' );

?>