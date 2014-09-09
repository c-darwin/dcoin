<?php
define( 'DC', true );
/*
 * Каждые 2 недели собираем инфу о голосах за max_other_currencies и создаем тр-ию, которая
 * попадет в DC сеть только, если мы окажемся генератором блока
 * */

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
if (!defined('PARSEDATA'))
	die('!PARSEDATA');

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
	exit;
}

$variables = ParseData::get_all_variables($db);
$new_max_other_currencies = array();
$time = time();
// проверим, прошло ли 2 недели с момента последнего обновления max_other_currencies
$max_other_currencies_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT max(`time`)
		FROM `".DB_PREFIX."max_other_currencies_time`
		", 'fetch_one' );
if ( $time - $max_other_currencies_time > $variables['new_max_other_currencies'] ) {

	// берем все голоса
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						  `count`,
						    count(`user_id`) as `votes`
			FROM `".DB_PREFIX."votes_max_other_currencies`
			GROUP BY  `currency_id`, `count`
			");
	while ( $row = $db->fetchArray( $res ) )
		$max_other_currencies_votes[$row['currency_id']][$row['count']] = $row['votes'];

	if (!isset($max_other_currencies_votes)) {
		debug_print( '!isset($max_other_currencies_votes)', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		exit;
	}

	debug_print( $max_other_currencies_votes, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$total_count_currencies = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`id`)
			FROM `".DB_PREFIX."currency`
			", 'fetch_one');
	foreach ( $max_other_currencies_votes as $currency_id => $count_and_votes ) {
		$new_max_other_currencies[$currency_id] = get_max_vote($count_and_votes, 0, $total_count_currencies, 10);
	}

	if (get_community_users($db))
		$my_prefix = $testBlock->user_id.'_';
	else
		$my_prefix = '';
	$node_private_key = get_node_private_key($db, $my_prefix);

	$json_data = json_encode($new_max_other_currencies);
	// подписываем нашим нод-ключем данные транзакции
	$data_for_sign = ParseData::findType('new_max_other_currencies').",{$time},{$my_user_id},{$json_data}";
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	debug_print( '$data_for_sign='.$data_for_sign."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// создаем тр-ию. пишем $block_id, на момент которого были актуальны голоса в табле 'pct'
	$data = dec_binary (ParseData::findType('new_max_other_currencies'), 1) .
		dec_binary ($time, 4) .
		ParseData::encode_length_plus_data($my_user_id) .
		ParseData::encode_length_plus_data($json_data) .
		ParseData::encode_length_plus_data($signature) ;
	$hash = ParseData::dsha256($data);

	insert_tx($data, $db);

	$new_tx_data['data'] = $data;
	$new_tx_data['hash'] = hextobin(md5($data));
	tx_parser ($new_tx_data, true);

}

main_unlock();

?>