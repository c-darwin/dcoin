<?php
if (!defined('DC')) die("!defined('DC')");

$tpl['currency_list'] = get_currency_list($db);

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

require_once( ABSPATH . 'templates/statistic.tpl' );

?>