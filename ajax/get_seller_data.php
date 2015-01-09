<?php
define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$lang = get_lang();
require_once( ABSPATH . 'lang/'.$lang.'.php' );

$get_user_id = intval($_REQUEST['user_id']);
$currency_id = intval($_REQUEST['currency_id']);
$arbitration_trust_list = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `arbitrator_user_id`
		FROM `" . DB_PREFIX . "arbitration_trust_list`
		WHERE `user_id` = {$get_user_id}
		", 'array');

/*
 * Статистика по продавцу
 * */
// оборот всего
$seller_turnover = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT sum(`amount`)
		FROM `" . DB_PREFIX . "orders`
		WHERE `seller` = {$get_user_id} AND
					 `currency_id` = {$currency_id}
		", 'fetch_one');
// оборот за месяц
$seller_turnover_m = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT sum(`amount`)
		FROM `" . DB_PREFIX . "orders`
		WHERE `seller` = {$get_user_id} AND
					 `time` > ".(time()-3600*24*30)." AND
					 `currency_id` = {$currency_id}
		", 'fetch_one');

// Кол-во покупателей за последний месяц
$buyers_count_m = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM (
			SELECT `id`
			FROM `" . DB_PREFIX . "orders`
			WHERE `seller` = {$get_user_id} AND
						 `time` > ".(time()-3600*24*30)." AND
						 `currency_id` = {$currency_id}
			GROUP BY `buyer`
		) as t1
		", 'fetch_one');

// Кол-во покупателей-майнеров за последний месяц
$buyers_miners_count_m = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM (
			SELECT `" . DB_PREFIX . "orders`.`id`
			FROM `" . DB_PREFIX . "orders`
			LEFT JOIN `" . DB_PREFIX . "miners_data` ON `" . DB_PREFIX . "miners_data`.`user_id` =  `" . DB_PREFIX . "orders`.`buyer`
			WHERE `seller` = {$get_user_id} AND
						 `" . DB_PREFIX . "orders`.`time` > ".(time()-3600*24*30)." AND
						 `" . DB_PREFIX . "orders`.`currency_id` = {$currency_id} AND
						 `miner_id` > 0
			GROUP BY `buyer`
		) as t1
		", 'fetch_one');

// Кол-во покупателей всего
$buyers_count = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM (
			SELECT `id`
			FROM `" . DB_PREFIX . "orders`
			WHERE `seller` = {$get_user_id} AND
						 `currency_id` = {$currency_id}
			GROUP BY `buyer`
		) as t1
		", 'fetch_one');

// Кол-во покупателей-майнеров всего
$buyers_miners_count = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT count(`id`)
		FROM (
			SELECT `" . DB_PREFIX . "orders`.`id`
			FROM `" . DB_PREFIX . "orders`
			LEFT JOIN `" . DB_PREFIX . "miners_data` ON `" . DB_PREFIX . "miners_data`.`user_id` =  `" . DB_PREFIX . "orders`.`buyer`
			WHERE `seller` = {$get_user_id} AND
						 `" . DB_PREFIX . "orders`.`currency_id` = {$currency_id} AND
						 `miner_id` > 0
			GROUP BY `buyer`
		) as t1
		", 'fetch_one');

// Заморожено для манибека
$hold_amount = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT sum(`hold_back_amount`)
		FROM `" . DB_PREFIX . "orders`
		LEFT JOIN `" . DB_PREFIX . "miners_data` ON `" . DB_PREFIX . "miners_data`.`user_id` =  `" . DB_PREFIX . "orders`.`buyer`
		WHERE `seller` = {$get_user_id} AND
					 `" . DB_PREFIX . "orders`.`currency_id` = {$currency_id} AND
					 `miner_id` > 0
		GROUP BY `buyer`
		", 'fetch_one');

// Холдбек % на 30 дней
$seller_data = $db->query(__FILE__, __LINE__, __FUNCTION__, __CLASS__, __METHOD__, "
		SELECT `seller_hold_back_pct`,
					 `arbitration_days_refund`
		FROM `" . DB_PREFIX . "users`
		WHERE `user_id` = {$get_user_id}
		", 'fetch_array');
$seller_data['seller_hold_back_pct'] = $seller_data['seller_hold_back_pct']?$seller_data['seller_hold_back_pct']:0;
$seller_data['seller_hold_back_pct'] = $seller_data['seller_hold_back_pct']?$seller_data['seller_hold_back_pct']:0;
$buyers_count = $buyers_count - $buyers_miners_count;
$buyers_count_m = $buyers_count_m - $buyers_miners_count_m;

print json_encode(array('trust_list'=>$arbitration_trust_list, 'seller_hold_back_pct'=>$seller_data['seller_hold_back_pct'],  'arbitration_days_refund'=>intval($seller_data['arbitration_days_refund']), 'buyers_miners_count_m'=>intval($buyers_miners_count_m), 'buyers_miners_count'=>intval($buyers_miners_count), 'buyers_count'=>intval($buyers_count) ,'buyers_count_m'=>intval($buyers_count_m) ,'seller_turnover_m'=>intval($seller_turnover_m), 'seller_turnover'=>intval($seller_turnover), 'hold_amount'=>intval($hold_amount) ));
//print json_encode(array('trust_list'=>$arbitration_trust_list, 'seller_hold_back_pct'=>5,  'arbitration_days_refund'=>intval($seller_data['arbitration_days_refund']), 'buyers_miners_count_m'=>186, 'buyers_miners_count'=>2687, 'buyers_count'=>5314 ,'buyers_count_m'=>379 ,'seller_turnover_m'=>18640, 'seller_turnover'=>368701, 'hold_amount'=>939 ));

?>