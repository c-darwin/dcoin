<?php
if (!defined('DC')) die("!defined('DC')");

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

$tpl['data']['type'] = 'votes_complex';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$variables = ParseData::get_all_variables($db);

// валюты
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
	SELECT `id`, `name` FROM `'.DB_PREFIX.'currency` ORDER BY `name`' );
while ($row = $db->fetchArray($res))
	$tpl['currency_list'][$row['id']] = $row['name'];

$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					  `name`,
					  `full_name`
		FROM `".DB_PREFIX."promised_amount`
        LEFT JOIN `currency` ON `currency`.`id` = `promised_amount`.`currency_id`
		WHERE `user_id` = {$user_id} AND
					 `status` IN ('mining', 'repaid') AND
					 `start_time` > 0 AND
					 `del_block_id` = 0
		GROUP BY `currency_id`
		");
$tpl['wait_voting'] = array();
$tpl['promised_amount_currency_list'] = array();
while ($row = $db->fetchArray($res)) {

	if ( $row['start_time'] > (time() - $variables['min_hold_time_promise_amount']) ) {
		$tpl['wait_voting'][$row['currency_id']] = "hold_time wait ".( $variables['min_hold_time_promise_amount'] - (time() - $row['start_time']) )." sec";
		continue;
	}

	// если по данной валюте еще не набралось >1000 майнеров, то за неё голосовать нельзя.
	$count_miners = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`user_id`)
			FROM `".DB_PREFIX."promised_amount`
			WHERE `start_time` < ".(time() - $variables['min_hold_time_promise_amount'])." AND
						 `del_block_id` = 0 AND
						 `status` IN ('mining', 'repaid') AND
						 `currency_id` = {$row['currency_id']} AND
						 `del_block_id` = 0
			GROUP BY  `user_id`
			", 'fetch_one' );
	if ($count_miners < $variables['min_miners_of_voting']) {
		$tpl['wait_voting'][$row['currency_id']] = "gathered {$count_miners} of the {$variables['min_miners_of_voting']} people";
		continue;
	}

	// голосовать можно не чаще 1 раза в 2 недели
	$vote_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time`
				FROM `".DB_PREFIX."log_time_votes_complex`
				WHERE `user_id` = {$user_id} AND
							 `time` > ".(time() - $variables['limit_votes_complex_period'])."
				LIMIT 1
				", 'fetch_one' );
	if ($vote_time) {
		$tpl['wait_voting'][$row['currency_id']] = "please wait ".( $variables['limit_votes_complex_period'] - (time() - $vote_time) )." sec";
		continue;
	}

	$tpl['promised_amount_currency_list'][$row['currency_id']] =  array('name'=>$row['name']);
}

$tpl['max_currency_id'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT max(`id`)
		FROM `".DB_PREFIX."currency`
		", 'fetch_one');

$tpl['AllMaxPromisedAmount'] = ParseData::getAllMaxPromisedAmount();
$tpl['AllPct'] = ParseData::getPctArray();
$pct_array = ParseData::getPctArray();
$tpl['js_pct'] = '{';
foreach($pct_array as $year_pct=>$sec_pct) {
	$tpl['js_pct'] .= "{$year_pct}: '{$sec_pct}',";
}
$tpl['js_pct'] = substr($tpl['js_pct'], 0, -1);
$tpl['js_pct'] .= '}';

require_once( ABSPATH . 'templates/voting.tpl' );

?>