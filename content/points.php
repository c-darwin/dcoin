<?php
if (!defined('DC')) die("!defined('DC')");

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."points_status`
		WHERE `user_id` = {$user_id}
		ORDER BY `time_start` DESC
		");
while ($row = $db->fetchArray($res))
	$tpl['points_status'][] = $row;


$tpl['my_points'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `points`
		FROM `".DB_PREFIX."points`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');

$tpl['variables'] = ParseData::get_variables ($db,  array('points_factor', 'limit_votes_complex_period') );

// среднее значение
$tpl['mean'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT sum(`points`)/count(`points`)
		FROM `".DB_PREFIX."points`
		WHERE `points` > 0
		", 'fetch_one');
$tpl['mean'] = round($tpl['mean']*$tpl['variables']['points_factor']);

// есть ли тр-ия с голосованием votes_complex за послдение 4 недели
$count = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT count(`user_id`)
		FROM `".DB_PREFIX."votes_miner_pct`
		WHERE `user_id` = {$user_id} AND
					 `time` > ".(time()-$tpl['variables']['limit_votes_complex_period']*2)."
		LIMIT  1
		", 'fetch_one');
if ($count>0)
	$tpl['votes_ok'] = 'YES';
else
	$tpl['votes_ok'] = 'NO';


require_once(ABSPATH . 'templates/points.tpl');
?>