<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['currency_list'] = get_currency_list($db, true);

$variables = ParseData::get_all_variables($db);
$time = time();

/*
 * Голосование за размер обещанной суммы
 */
$tpl['max_promised_amount_votes'] = array();
// берем все голоса
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							  `amount`,
							    count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_max_promised_amount`
				GROUP BY  `currency_id`, `amount`
				");
while ( $row = $db->fetchArray( $res ) )
	$tpl['max_promised_amount_votes'][$row['currency_id']][$row['amount']] = $row['votes'];

$total_count_currencies = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`id`)
				FROM `".DB_PREFIX."currency`
				", 'fetch_one' );
$tpl['new_max_promised_amounts'] = array();
foreach ( $tpl['max_promised_amount_votes'] as $currency_id => $amounts_and_votes ) {
	$tpl['new_max_promised_amounts'][$currency_id] = get_max_vote($amounts_and_votes, 0, $total_count_currencies, 10);
}

/*
 * Голосование за кол-во валют в обещанных суммах
 */
$tpl['max_other_currencies_votes'] = array();
// берем все голоса
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							  `count`,
							    count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_max_other_currencies`
				GROUP BY  `currency_id`, `count`
				");
while ( $row = $db->fetchArray( $res ) )
	$tpl['max_other_currencies_votes'][$row['currency_id']][$row['count']] = $row['votes'];

$tpl['new_max_other_currencies'] = array();
foreach ( $tpl['max_other_currencies_votes'] as $currency_id => $count_and_votes ) {
	$tpl['new_max_other_currencies'][$currency_id] = get_max_vote($count_and_votes, 0, $total_count_currencies, 10);
}

/*
 * Голосование за ручное сокращение объема монет
 * */
// получаем кол-во обещанных сумм у разных юзеров по каждой валюте. start_time есть только у тех, у кого статус mining/repaid
$tpl['promised_amount'] = array();
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `currency_id`, count(`user_id`) as `count`
					FROM (
							SELECT `currency_id`, `user_id`
							FROM `".DB_PREFIX."promised_amount`
							WHERE `start_time` < ".(time() - $variables['min_hold_time_promise_amount'])." AND
										 `del_block_id` = 0 AND
										 `del_mining_block_id` = 0 AND
										 `status` IN ('mining', 'repaid')
							GROUP BY  `user_id`, `currency_id`
							) as t1
					GROUP BY  `currency_id`
					");
while ( $row = $db->fetchArray( $res ) )
	$tpl['promised_amount'][$row['currency_id']] = $row['count'];

// берем все голоса юзеров по данной валюте
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					  `pct`,
					    count(`currency_id`) as `votes`
		FROM `".DB_PREFIX."votes_reduction`
		WHERE `time` > ".(time() - $variables['reduction_period'])." AND
					 `pct` > 0
		GROUP BY `currency_id`, `pct`
		");
while ( $row = $db->fetchArray( $res ) ) {
	$tpl['votes_reduction'][$row['currency_id']][$row['pct']] = $row['votes'];
}

/*
 * Голосование за реф. бонусы
 * */
$ref_levels = array('first', 'second', 'third');
$tpl['new_referral_pct'] = array();
$tpl['votes_referral'] = array();
for ($i=0; $i<sizeof($ref_levels); $i++) {
	$level = $ref_levels[$i];
	// берем все голоса
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
						SELECT `{$level}`,
									  count(`user_id`) as `votes`
						FROM `".DB_PREFIX."votes_referral`
						GROUP BY  `{$level}`
						");
	while ( $row = $db->fetchArray( $res ) )
		$tpl['votes_referral'][$level][$row[$level]] = $row['votes'];
	$tpl['new_referral_pct'][$level] = get_max_vote($tpl['votes_referral'][$level], 0, 30, 10);
}

/*
 * Голосоваие за майнеркие и юзерские %
 * */
// берем все голоса miner_pct
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							 `pct`,
							  count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_miner_pct`
				GROUP BY  `currency_id`, `pct`
				");
while ( $row = $db->fetchArray( $res ) ) {
	$pct_votes[$row['currency_id']]['miner_pct'][$row['pct']] = $row['votes'];
	$tpl['pct_votes'][$row['currency_id']]['miner_pct'][round((pow(1+$row['pct'], 3600*24*365)-1)*100, 2)] = $row['votes'];
}

// берем все голоса user_pct
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `currency_id`,
							 `pct`,
							  count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_user_pct`
				GROUP BY  `currency_id`, `pct`
				");
while ( $row = $db->fetchArray( $res ) ) {
	$pct_votes[$row['currency_id']]['user_pct'][$row['pct']] = $row['votes'];
	$tpl['pct_votes'][$row['currency_id']]['user_pct'][round((pow(1 + $row['pct'], 3600 * 24 * 365) - 1) * 100, 2)] = $row['votes'];
}

$PctArray = ParseData::getPctArray();
$new_pct = array();
foreach ( $pct_votes as $currency_id => $data ) {

	// определяем % для майнеров
	$pct_arr = ParseData::makePctArray($data['miner_pct']);
	$key = get_max_vote($pct_arr, 0, 390, 100);
	$new_pct['currency'][$currency_id]['miner_pct'] = ParseData::getPctValue($key);
	$tpl['new_pct'][$currency_id]['miner_pct'] = round((pow(1 + ParseData::getPctValue($key), 3600 * 24 * 365) - 1) * 100, 2);

	// определяем % для юзеров
	$pct_arr = ParseData::makePctArray($data['user_pct']);
	$pct_y = array_search($new_pct['currency'][$currency_id]['miner_pct'], $PctArray);
	$max_user_pct_y = round($pct_y/2, 2);
	$user_max_key = find_user_pct($max_user_pct_y);
	// отрезаем лишнее, т.к. поиск идет ровно до макимального возможного, т.е. до miner_pct/2
	$pct_arr = del_user_pct($pct_arr, $user_max_key);

	$key = get_max_vote($pct_arr, 0, $user_max_key, 100);
	$new_pct['currency'][$currency_id]['user_pct'] = ParseData::getPctValue($key);
	$tpl['new_pct'][$currency_id]['user_pct'] = round((pow(1 + ParseData::getPctValue($key), 3600 * 24 * 365) - 1) * 100, 2);
}

/*
 * %/год
 * */
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
	$tpl['currency_pct'][$row['id']]['miner'] =  round((pow(1+$pct['miner'], 120)-1)*100, 6);
	$tpl['currency_pct'][$row['id']]['user'] = round((pow(1+$pct['user'], 120)-1)*100, 6);
}

$tpl['currency_list'] = get_currency_list($db, 'full');

require_once( ABSPATH . 'templates/statistic_voting.tpl' );

?>