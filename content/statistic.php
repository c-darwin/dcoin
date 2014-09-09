<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['currency_list'] = get_currency_list($db, true);

$variables = ParseData::get_all_variables($db);
$time = time();

// получаем кол-во DC на кошельках
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`amount`) as sum_amount
			FROM `".DB_PREFIX."wallets`
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	$sum_wallets[$row['currency_id']] = $row['sum_amount'];
}

// получаем кол-во TDC на обещанных суммах
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`tdc_amount`) as sum_amount
			FROM `".DB_PREFIX."promised_amount`
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	if (!isset($sum_wallets[$row['currency_id']]))
		$sum_wallets[$row['currency_id']] = $row['sum_amount'];
	else
		$sum_wallets[$row['currency_id']] += $row['sum_amount'];
}

// получаем суммы обещанных сумм
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						   sum(`amount`) as sum_amount
			FROM `".DB_PREFIX."promised_amount`
			WHERE `status` = 'mining' AND
						 `del_block_id` = 0 AND
						  (`cash_request_out_time` = 0 OR `cash_request_out_time` > ".($time - $variables['cash_request_time']).")
			GROUP BY `currency_id`
			");
while ( $row = $db->fetchArray( $res ) ) {
	$sum_promised_amount[$row['currency_id']] = $row['sum_amount'];
}

// получаем кол-во майнеров по валютам
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `currency_id`, count(`user_id`) as `count`
					FROM (
							SELECT `currency_id`, `user_id`
							FROM `".DB_PREFIX."promised_amount`
							WHERE  `del_block_id` = 0 AND
										 `del_mining_block_id` = 0 AND
										 `status` IN ('mining', 'repaid')
							GROUP BY  `user_id`, `currency_id`
							) as t1
					GROUP BY  `currency_id`
					");
while ( $row = $db->fetchArray( $res ) )
	$promised_amount_miners[$row['currency_id']] = $row['count'];

// получаем кол-во анонимных юзеров по валютам
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`, count(`user_id`) as `count`
		FROM `".DB_PREFIX."wallets`
		WHERE `amount` > 0
		GROUP BY  `currency_id`
		");
while ( $row = $db->fetchArray( $res ) )
	$wallets_users[$row['currency_id']] = $row['count'];


// таблица обмена на наличные
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT *
		FROM `".DB_PREFIX."cash_requests`
		ORDER BY `id` DESC
		LIMIT 100
		");
while ( $row = $db->fetchArray( $res ) ) {
	$row['time'] = date('d/m/Y H:i:s', $row['time']);
	$tpl['cash_requests'][] = $row;
}


// поиск инфы о юзере
$tpl['user_info_id'] = intval($_REQUEST['parameters']['user_info_id']);
if ($tpl['user_info_id']) {
	$tp['user_info']['wallets'] = get_balances($tpl['user_info_id']);
	// обещанные суммы юзера
	get_promised_amounts($tpl['user_info_id']);

	// кредиты
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT sum(`amount`) as `amount`,
						 `currency_id`
			FROM `".DB_PREFIX."credits`
			WHERE `from_user_id` = {$tpl['user_info_id']} AND
						 `del_block_id` = 0
			GROUP BY `currency_id`
			");
	while ($row = $db->fetchArray($res)) {
		$tpl['credits']['debtor'][] = $row;
	}

}


$tpl['currency_list'] = get_currency_list($db, 'full');

require_once( ABSPATH . 'templates/statistic.tpl' );

?>