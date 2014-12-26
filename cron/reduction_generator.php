<?php
if (!$argv) die('browser');

/*
 * Каждые 2 недели собираем инфу о голосах за % и создаем тр-ию, которая
 * попадет в DC сеть только, если мы окажемся генератором блока
 * */
define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

upd_deamon_time($db);

main_lock();
$block_id = get_block_id($db);
if (!$block_id) {
	main_unlock();
	exit;
}

$testBlock = new testblock($db, true);

// а майнер ли я ?
$my_miner_id = $testBlock->miner_id;
$my_user_id = $testBlock->user_id;
if (!$my_miner_id) {
	main_unlock();
	unset($testBlock);
	die('!$my_miner_id');
}

$variables = ParseData::get_all_variables($db);
$time = time();
$reduction_tx_data = '';
$promised_amount = array();
$reduction_currency_id = false;
$reduction_type = '';

// ===== ручное урезание денежной массы

// получаем кол-во обещанных сумм у разных юзеров по каждой валюте. start_time есть только у тех, у кого статус mining/repaid
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`, count(`user_id`) as `count`
		FROM (
				SELECT `currency_id`, `user_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `start_time` < ".($time - $variables['min_hold_time_promise_amount'])."  AND
							 `del_block_id` = 0 AND
							 `status` IN ('mining', 'repaid')
				GROUP BY  `user_id`, `currency_id`
				) as t1
		GROUP BY  `currency_id`
		");
while ( $row = $db->fetchArray( $res ) ) {
	$promised_amount[$row['currency_id']] = $row['count'];
}

debug_print('$promised_amount_:'.print_r_hex($promised_amount), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
// берем все голоса юзеров
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						  `pct`,
						  count(`currency_id`) as `votes`
			FROM `".DB_PREFIX."votes_reduction`
			WHERE `time` > ".($time - $variables['reduction_period'])."
			GROUP BY  `currency_id`, `pct`
			");
while ( $row = $db->fetchArray( $res ) ) {

	debug_print($row, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	if (!isset($promised_amount[$row['currency_id']]))
		continue;
	// если голосов за урезание > 50% от числа всех держателей данной валюты
	if ($row['votes'] >= $promised_amount[$row['currency_id']] / 2) {
		// проверим, прошло ли 2 недели с последнего урезания
		$reduction_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT max(`time`)
					FROM `".DB_PREFIX."reduction`
					WHERE `currency_id` = {$row['currency_id']} AND
								 `type` = 'manual'
					", 'fetch_one' );
		$reduction_time = intval($reduction_time);
		if ( $time - $reduction_time > $variables['reduction_period'] ) {
			$reduction_currency_id = $row['currency_id'];
			$reduction_pct = $row['pct'];
			$reduction_type = 'manual';
			debug_print("reduction_currency_id={$reduction_currency_id}\nreduction_pct={$reduction_pct}\nreduction_type={$reduction_type}", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
			break;
		}
	}
}
/*
// ======= авто-урезание денежной массы из-за малого кол-ва удовлетворенных запросов на наличные

// получаем кол-во запросов на наличные за последние 48 часов
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					   count(`id`) as count
		FROM `".DB_PREFIX."cash_requests`
		WHERE `time` > ".($time - AUTO_REDUCTION_CASH_PERIOD)." AND
					 `del_block_id` = 0
		GROUP BY `currency_id`
		");
while ( $row = $db->fetchArray( $res ) ) {
	$all_cash_requests[$row['currency_id']] = $row['count'];
}

// получаем кол-во удовлетворенных запросов на наличные за последние 48 часов
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`,
					   count(`id`) as count
		FROM `".DB_PREFIX."cash_requests`
		WHERE `time` > ".($time - AUTO_REDUCTION_CASH_PERIOD)." AND
					 `del_block_id` = 0 AND
					 `status` = 'approved'
		GROUP BY `currency_id`
		");
while ( $row = $db->fetchArray( $res ) ) {
	$approved_cash_requests[$row['currency_id']] = $row['count'];
}

if (isset($all_cash_requests))
foreach ($all_cash_requests as $currency_id => $count) {

	// урезание возможно только если за 48 часов есть более 1000 запросов на наличные по данной валюте
	if ($count < AUTO_REDUCTION_CASH_MIN)
		continue;

	// и недопустимо для WOC
	if ($currency_id == 1)
		continue;

	// если кол-во удовлетворенных запросов менее чем 30% от общего кол-ва
	if ( @$approved_cash_requests[$currency_id] < $count * AUTO_REDUCTION_CASH_PCT ) {
		$reduction_currency_id = $currency_id;
		$reduction_pct = AUTO_REDUCTION_PCT;
		$reduction_type = 'cash';
		break;
	}
}*/


// =======  авто-урезание денежной массы из-за малого объема обещанных сумм

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
					 `del_mining_block_id` = 0 AND
					  (`cash_request_out_time` = 0 OR `cash_request_out_time` > ".($time - $variables['cash_request_time']).")
		GROUP BY `currency_id`
		");
while ( $row = $db->fetchArray( $res ) ) {
	$sum_promised_amount[$row['currency_id']] = $row['sum_amount'];
}

if (isset($sum_wallets))
foreach ($sum_wallets as $currency_id => $sum_amount) {

	// и недопустимо для WOC
	if ($currency_id == 1)
		continue;

	$reduction_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT max(`time`)
			FROM `".DB_PREFIX."reduction`
			WHERE `currency_id` = {$currency_id} AND
						 `type` = 'auto'
			", 'fetch_one' );
	// прошло ли 48 часов
	if ( time() - $reduction_time <= AUTO_REDUCTION_PERIOD )
		continue;

	// если обещанных сумм менее чем 100% от объема DC на кошельках, то запускаем урезание
	if ( @$sum_promised_amount[$currency_id] < $sum_amount * AUTO_REDUCTION_PROMISED_AMOUNT_PCT ) {

		// проверим, есть ли хотябы 1000 юзеров, у которых на кошелках есть или была данная валюты
		$count_users = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT count(`user_id`)
				FROM `".DB_PREFIX."wallets`
				WHERE `currency_id` = {$currency_id}
				", 'fetch_one');
		if ($count_users >= AUTO_REDUCTION_PROMISED_AMOUNT_MIN) {
			$reduction_currency_id = $currency_id;
			$reduction_pct = AUTO_REDUCTION_PCT;
			$reduction_type = 'promised_amount';
			break;
		}
	}
}

//print $reduction_pct."\n";
//print $reduction_type."\n";
//print $reduction_currency_id."\n";

if (isset($reduction_currency_id) && isset($reduction_pct)) {

	if (get_community_users($db))
		$my_prefix = $testBlock->user_id.'_';
	else
		$my_prefix = '';
	$node_private_key = get_node_private_key($db, $my_prefix);

	print $my_prefix."\n";

	// подписываем нашим нод-ключем данные транзакции
	$data_for_sign = ParseData::findType('new_reduction').",{$time},{$my_user_id},{$reduction_currency_id},{$reduction_pct},{$reduction_type}";
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	debug_print( '$data_for_sign='.$data_for_sign."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	print $data_for_sign;
	print $node_private_key;

	// создаем тр-ию. пишем $block_id, на момент которого были актуальны голоса и статусы банкнот
	$reduction_tx_data = dec_binary (ParseData::findType('new_reduction'), 1) .
		dec_binary ($time, 4) .
		ParseData::encode_length_plus_data($my_user_id) .
		ParseData::encode_length_plus_data($reduction_currency_id) .
		ParseData::encode_length_plus_data($reduction_pct) .
		ParseData::encode_length_plus_data($reduction_type) .
		ParseData::encode_length_plus_data($signature) ;

	insert_tx($reduction_tx_data, $db);

	// и не закрывая main_lock переводим нашу тр-ию в verified=1, откатив все несовместимые тр-ии
	// таким образом у нас будут в блоке только актуальные голоса.
	// а если придет другой блок и станет verified=0, то эта тр-ия просто удалится.
	$new_tx_data['data'] = $reduction_tx_data;
	$new_tx_data['hash'] = hextobin(md5($reduction_tx_data));
	tx_parser ($new_tx_data, true);

}

main_unlock();


?>