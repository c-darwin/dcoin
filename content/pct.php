<?php
if (!defined('DC')) die("!defined('DC')");
/*
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."currency`
		");
while ($row = $db->fetchArray($res)) {
	$pct = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT *
			FROM `".DB_PREFIX."pct`
			WHERE `currency_id` = {$row['id']}
			ORDER BY `block_id` DESC
			LIMIT 1
			", 'fetch_array');
	$tpl['currency_pct'][$row['id']]['name'] = $row['name'];
	$tpl['currency_pct'][$row['id']]['miner'] = round((pow(1+$pct['miner'], 3600*24*365)-1)*100, 2);
	$tpl['currency_pct'][$row['id']]['user'] = round((pow(1+$pct['user'], 3600*24*365)-1)*100, 2);
}

require_once(ABSPATH . 'templates/pct.tpl');
*/
?>