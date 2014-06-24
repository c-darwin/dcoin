<?php
if (!defined('DC')) die("!defined('DC')");

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

$tpl['data']['type'] = 'votes_complex';
$tpl['data']['type_id'] = ParseData::findType($tpl['data']['type']);
$tpl['data']['time'] = time();
$tpl['data']['user_id'] = $user_id;

$variables = ParseData::get_all_variables($db);

// голосовать майнер может только после того, как пройдет  miner_newbie_time сек
$reg_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `reg_time`
		        FROM `".DB_PREFIX."miners_data`
		        WHERE `user_id` = {$user_id}
		        LIMIT 1
		        ", 'fetch_one');
if ($reg_time > (time() - $variables['miner_newbie_time']) && $this->tx_data['user_id'] != 1) {
	$tpl['miner_newbie'] = str_ireplace('sec', $variables['miner_newbie_time'] - (time() - $reg_time), $lng['hold_time_wait']);
}
else {
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

		// после добавления обещанной суммы должно пройти не менее min_hold_time_promise_amount сек, чтобы за неё можно было голосовать
		if ( $row['start_time'] > (time() - $variables['min_hold_time_promise_amount']) ) {
			$tpl['wait_voting'][$row['currency_id']] = str_ireplace('sec', $variables['min_hold_time_promise_amount'] - (time() - $row['start_time']), $lng['hold_time_wait']);
			continue;
		}

		// если по данной валюте еще не набралось >1000 майнеров, то за неё голосовать нельзя.
		$count_miners = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `user_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `start_time` < ".(time() - $variables['min_hold_time_promise_amount'])." AND
							 `del_block_id` = 0 AND
							 `status` IN ('mining', 'repaid') AND
							 `currency_id` = {$row['currency_id']} AND
							 `del_block_id` = 0
				GROUP BY  `user_id`
				", 'num_rows' );
		if ($count_miners < $variables['min_miners_of_voting']) {

			$tpl['wait_voting'][$row['currency_id']] = str_ireplace( array('[miners_count]', '[remaining]'), array($variables['min_miners_of_voting'], $variables['min_miners_of_voting']-$count_miners), $lng['min_miners_count'] );
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
			$tpl['wait_voting'][$row['currency_id']] = str_ireplace('[sec]', $variables['limit_votes_complex_period'] - (time() - $vote_time), $lng['wait_voting']);
			continue;
		}

		// получим наши предыдущие голоса
		$votes_user_pct= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `pct`
				FROM `".DB_PREFIX."votes_user_pct`
				WHERE `user_id` = {$user_id} AND
							 `currency_id` = {$row['currency_id']}
				LIMIT 1
				", 'fetch_one' );
		$votes_miner_pct= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `pct`
				FROM `".DB_PREFIX."votes_miner_pct`
				WHERE `user_id` = {$user_id} AND
							 `currency_id` = {$row['currency_id']}
				LIMIT 1
				", 'fetch_one' );
		$votes_max_other_currencies= $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `count`
				FROM `".DB_PREFIX."votes_max_other_currencies`
				WHERE `user_id` = {$user_id} AND
							 `currency_id` = {$row['currency_id']}
				LIMIT 1
				", 'fetch_one' );
		$votes_max_promised_amount = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."votes_max_promised_amount`
				WHERE `user_id` = {$user_id} AND
							 `currency_id` = {$row['currency_id']}
				LIMIT 1
				", 'fetch_one' );

		$tpl['promised_amount_currency_list'][$row['currency_id']]['votes_user_pct'] = $votes_user_pct;
		$tpl['promised_amount_currency_list'][$row['currency_id']]['votes_miner_pct'] = $votes_miner_pct;
		$tpl['promised_amount_currency_list'][$row['currency_id']]['votes_max_other_currencies'] = $votes_max_other_currencies;
		$tpl['promised_amount_currency_list'][$row['currency_id']]['votes_max_promised_amount'] = $votes_max_promised_amount;
		$tpl['promised_amount_currency_list'][$row['currency_id']]['name'] =  $row['name'];
	}
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