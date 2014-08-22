<?php
if (!defined('DC')) die("!defined('DC')");

// уведомления
$tpl['alert'] = @$_REQUEST['parameters']['alert'];

$tpl['currency_list'] = get_currency_list($db);

$tpl['currency_id'] = intval(@$_REQUEST['parameters']['currency_id']);
if (!$tpl['currency_id'])
	$tpl['currency_id'] = 150;

if (empty($_SESSION['restricted'])) {
	// то, что еще не попало в блоки.
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, '
			SELECT *
			FROM `'.DB_PREFIX.MY_PREFIX.'my_promised_amount`
			');
	while ($row = $db->fetchArray($res)) {
		$tpl['promised_amount_list']['my_pending'][] = $row;
	}
}

$tpl['variables'] = ParseData::get_all_variables($db);

// то, что уже в блоках
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."promised_amount`
		WHERE `user_id` = {$user_id}
		");
while ($row = $db->fetchArray($res)) {

	// есть ли просроченные запросы
	$cash_request_pending = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."cash_requests`
				WHERE `to_user_id` = {$user_id} AND
							 `del_block_id` = 0 AND
							 `for_repaid_del_block_id` = 0 AND
							 `time` < ".(time() - $tpl['variables']['cash_request_time'])." AND
							 `status` = 'pending'
				LIMIT 1
				", 'fetch_one' );
	if ($cash_request_pending && $row['currency_id'] > 1 && $row['status'] == 'mining') {

		$row['status'] = 'for_repaid';

		// и заодно проверим, можно ли делать актуализацию обещанных сумм
		$tpl['actualization_promised_amounts'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `status` = 'mining' AND
							 `user_id` = {$user_id} AND
							 `currency_id` > 1 AND
							 `del_block_id` = 0 AND
							 `del_mining_block_id` = 0 AND
							 (`cash_request_out_time` > 0 AND `cash_request_out_time` < ".(time() - $tpl['variables']['cash_request_time']).")
				", 'fetch_one');
	}

	$row['tdc'] = $row['tdc_amount'];
	if ($row['del_block_id'])
		continue;
	if ($row['status']=='mining') {
		$row['tdc']+= calc_profit_($row['currency_id'], $row['amount']+$row['tdc_amount'], $my_user_id, $db, $row['tdc_amount_update'], time(), 'mining');
		$row['tdc'] = floor($row['tdc']*100)/100;
	}
	else if ($row['status']=='repaid') {
		$row['tdc'] = $row['tdc_amount'] + calc_profit_($row['currency_id'], $row['tdc_amount'], $my_user_id, $db, $row['tdc_amount_update'], time(), 'repaid');
		$row['tdc'] = floor($row['tdc']*100)/100;
	}
	else
		$row['tdc'] = $row['tdc_amount'];

	$row['status_text'] = $lng['status_'.$row['status']];

	$row['max_amount'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `amount`
				FROM `".DB_PREFIX."max_promised_amounts`
				WHERE `currency_id` = {$row['currency_id']}
				ORDER BY `block_id` DESC
				LIMIT 1
				", 'fetch_one');

	$row['max_other_currencies'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `max_other_currencies`
				FROM `".DB_PREFIX."currency`
				WHERE `id` = {$row['currency_id']}
				LIMIT 1
				", 'fetch_one');

	// для WOC amount не учитывается. Вместо него берется max_promised_amount
	if ($row['currency_id'] == 1) {
		$row['amount'] = $row['max_amount'];
	}
	if ($row['status'] == 'repaid') {
		$row['amount'] = 0;
	}

	// последний статус юзера
	$pct_status = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `status`
				FROM `".DB_PREFIX."points_status`
				WHERE `user_id` = {$user_id}
				ORDER BY `time_start` DESC
				LIMIT 1
				", 'fetch_one');

	if (!$pct_status)
		$pct_status = 'user';
	$pct = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `{$pct_status}`
				FROM `".DB_PREFIX."pct`
				WHERE `currency_id` = {$row['currency_id']}
				ORDER BY `block_id` DESC
				LIMIT 1
				", 'fetch_one');
	$row['pct'] = round((pow(1+$pct, 3600*24*365)-1)*100, 2);

	// тут accepted значит просто попало в блок
	$tpl['promised_amount_list']['accepted'][] = $row;

}


$tpl['limits_text'] = str_ireplace(array('[limit]', '[period]'), array($tpl['variables']['limit_promised_amount'], $tpl['periods'][$tpl['variables']['limit_promised_amount_period']]), $lng['limits_text']);

require_once( ABSPATH . 'templates/promised_amount_list.tpl' );

?>