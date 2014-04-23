<?php

/*
 * Каждые 2 недели собираем инфу о голосах за % и создаем тр-ию, которая
 * попадет в DC сеть только, если мы окажемся генератором блока
 * */
define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

upd_deamon_time($db);

main_lock();
$block_id = get_block_id($db);
if (!$block_id) {
	main_unlock();
	exit;
}
// а майнер ли я ?
$my_miner_id = get_my_miner_id($db);
if (!$my_miner_id) {
	main_unlock();
	exit;
}

$variables = ParseData::get_variables($db, array('min_hold_time_promise_amount', 'reduction_period'));
$time = time();
$reduction_tx_data = '';
$my_user_id = get_my_user_id($db);
$promised_amount = array();
$reduction_currency_id = false;
// получаем кол-во обещанных сумм у разных юзеров по каждой валюте. start_time есть только у тех, у кого статус mining/repaid
$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `currency_id`, count(`user_id`) as `count`
		FROM (
				SELECT `currency_id`, `user_id`
				FROM `".DB_PREFIX."promised_amount`
				WHERE `start_time` < ".(time() - $variables['min_hold_time_promise_amount'])."  AND
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
/*$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						  `pct`,
						  count(`currency_id`) as `votes`
			FROM `".DB_PREFIX."votes_reduction`
			WHERE `time` > ".($time - REDUCTION_PERIOD)."
			GROUP BY  `currency_id`
			");*/
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
	if ($row['votes'] > $promised_amount[$row['currency_id']] / 2) {
		// проверим, прошло ли 2 недели с последнего урезания
		$pct_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT max(`time`)
					FROM `".DB_PREFIX."reduction`
					WHERE `currency_id` = {$row['currency_id']}
					", 'fetch_one' );
		// для тестов = 1 минута
		if ( $time - $pct_time > $variables['reduction_period'] ) {
			$reduction_currency_id = $row['currency_id'];
			$reduction_pct = $row['pct'];
			break;
		}
	}
}

if ($reduction_currency_id) {
	$node_private_key = get_node_private_key($db);

	// подписываем нашим нод-ключем данные транзакции
	$data_for_sign = ParseData::findType('new_reduction').",{$time},{$my_user_id},{$reduction_currency_id},{$reduction_pct}";
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	debug_print( '$data_for_sign='.$data_for_sign."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// создаем тр-ию. пишем $block_id, на момент которого были актуальны голоса и статусы банкнот
	$reduction_tx_data = dec_binary (ParseData::findType('new_reduction'), 1) .
		dec_binary ($time, 4) .
		ParseData::encode_length_plus_data($my_user_id) .
		ParseData::encode_length_plus_data($reduction_currency_id) .
		ParseData::encode_length_plus_data($reduction_pct) .
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