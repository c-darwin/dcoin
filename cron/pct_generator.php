<?php
if (!$argv) die('browser');

define( 'DC', true );
/*
 * Каждые 2 недели собираем инфу о голосах за % и создаем тр-ию, которая
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
$time = time();
// проверим, прошло ли 2 недели с момента последнего обновления pct
$pct_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT max(`time`)
		FROM `".DB_PREFIX."pct`
		", 'fetch_one' );
if ( $time - $pct_time > $variables['new_pct_period'] ) {

	// берем все голоса miner_pct
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						 `pct`,
						  count(`user_id`) as `votes`
			FROM `".DB_PREFIX."votes_miner_pct`
			GROUP BY  `currency_id`, `pct`
			");
	while ( $row = $db->fetchArray( $res ) )
		$pct_votes[$row['currency_id']]['miner_pct'][$row['pct']] = $row['votes'];

	// берем все голоса user_pct
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `currency_id`,
						 `pct`,
						  count(`user_id`) as `votes`
			FROM `".DB_PREFIX."votes_user_pct`
			GROUP BY  `currency_id`, `pct`
			");
	while ( $row = $db->fetchArray( $res ) )
		$pct_votes[$row['currency_id']]['user_pct'][$row['pct']] = $row['votes'];

	if (!isset($pct_votes)) {
		debug_print( '!isset($pct_votes)', __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		main_unlock();
		exit;
	}

	debug_print( '$pct_votes:'.print_r_hex($pct_votes), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$PctArray = ParseData::getPctArray();
	$new_pct = array();
	foreach ( $pct_votes as $currency_id => $data ) {

		// определяем % для майнеров
		$pct_arr = ParseData::makePctArray($data['miner_pct']);
		$key = get_max_vote($pct_arr, 0, 390, 100);
		$new_pct['currency'][$currency_id]['miner_pct'] = ParseData::getPctValue($key);
		debug_print( '$key miner_pct='.$key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

		// определяем % для юзеров
		$pct_y = array_search($new_pct['currency'][$currency_id]['miner_pct'], $PctArray);
		debug_print( 'miner_pct $pct_y='.$pct_y, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$max_user_pct_y = round($pct_y/2, 2);
		debug_print( '$max_user_pct='.$max_user_pct_y, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$user_max_key = find_user_pct($max_user_pct_y);
		debug_print( '$user_max_key='.$user_max_key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$pct_arr = ParseData::makePctArray($data['user_pct']);
		// отрезаем лишнее, т.к. поиск идет ровно до макимального возможного, т.е. до miner_pct/2
		$pct_arr = del_user_pct($pct_arr, $user_max_key);
		debug_print( '$user_$pct_arr='.print_r_hex($pct_arr), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$key = get_max_vote($pct_arr, 0, $user_max_key, 100);
		debug_print( '$key user_pct='.$key, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		$new_pct['currency'][$currency_id]['user_pct'] = ParseData::getPctValue($key);
		debug_print( 'user_pct='.$new_pct['currency'][$currency_id]['user_pct'], __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		debug_print( 'user pct y='.array_search($new_pct['currency'][$currency_id]['user_pct'], $PctArray), __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	$ref_levels = array('first', 'second', 'third');
	for ($i=0; $i<sizeof($ref_levels); $i++) {
		$level = $ref_levels[$i];
		// берем все голоса
		$votes_referral = array();
		$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `{$level}`,
							  count(`user_id`) as `votes`
				FROM `".DB_PREFIX."votes_referral`
				GROUP BY  `{$level}`
				");
		while ( $row = $db->fetchArray( $res ) )
			$votes_referral[$row[$level]] = $row['votes'];
		$new_pct['referral'][$level] = get_max_vote($votes_referral, 0, 30, 10);
	}

	debug_print($new_pct, __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	$testBlock = new testblock($db, true);
	if (get_community_users($db))
		$my_prefix = $testBlock->user_id.'_';
	else
		$my_prefix = '';
	$node_private_key = get_node_private_key($db, $my_prefix);

	$json_data = json_encode($new_pct);
	// подписываем нашим нод-ключем данные транзакции
	$data_for_sign = ParseData::findType('new_pct').",{$time},{$my_user_id},{$json_data}";
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	debug_print( '$data_for_sign='.$data_for_sign."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// создаем тр-ию. пишем $block_id, на момент которого были актуальны голоса в табле 'pct'
	$data = dec_binary (ParseData::findType('new_pct'), 1) .
		dec_binary ($time, 4) .
		ParseData::encode_length_plus_data($my_user_id) .
		ParseData::encode_length_plus_data($json_data) .
		ParseData::encode_length_plus_data($signature) ;
	$hash = ParseData::dsha256($data);

	insert_tx($data, $db);

	// и не закрывая main_lock переводим нашу тр-ию в verified=1, откатив все несовместимые тр-ии
	// таким образом у нас будут в блоке только актуальные голоса.
	// а если придет другой блок и станет verified=0, то эта тр-ия просто удалится.
	$new_tx_data['data'] = $data;
	$new_tx_data['hash'] = hextobin(md5($data));
	tx_parser ($new_tx_data, true);

}

main_unlock();
?>