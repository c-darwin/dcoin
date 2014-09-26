<?php
if (!$argv) die('browser');

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
	exit;
}

$variables = ParseData::get_all_variables($db);
$time = time();
// проверим, прошло ли 2 недели с момента последнего обновления
$admin_time = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `time`
		FROM `".DB_PREFIX."admin`
		", 'fetch_one' );
if ( $time - $admin_time > $variables['new_pct_period'] ) {

	// сколько всего майнеров
	$count_miners = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT count(`miner_id`)
			FROM `".DB_PREFIX."miners`
			WHERE `active` = 1
			", 'fetch_one' );
	if ($count_miners < 1000) {
		main_unlock();
		exit;
	}

	$new_admin = 0;
	// берем все голоса
	$res = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `admin_user_id`,
						  count(`user_id`) as `votes`
			FROM `".DB_PREFIX."votes_admin`
			WHERE `time` > ".($time - $variables['new_pct_period'])."
			GROUP BY  `admin_user_id`
			");
	while ( $row = $db->fetchArray( $res ) ) {
		// если более 50% майнеров проголосовали
		if ($row['votes'] > $count_miners/2) {
			$new_admin = $row['admin_user_id'];
		}
	}

	if (!$new_admin) {
		main_unlock();
		exit;
	}

	$testBlock = new testblock($db, true);
	if (get_community_users($db))
		$my_prefix = $testBlock->user_id.'_';
	else
		$my_prefix = '';
	$node_private_key = get_node_private_key($db, $my_prefix);

	// подписываем нашим нод-ключем данные транзакции
	$data_for_sign = ParseData::findType('new_admin').",{$time},{$my_user_id},{$new_admin}";
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	debug_print( '$data_for_sign='.$data_for_sign."\n", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// создаем тр-ию. пишем $block_id, на момент которого были актуальны голоса в табле 'pct'
	$data = dec_binary (ParseData::findType('new_admin'), 1) .
		dec_binary ($time, 4) .
		ParseData::encode_length_plus_data($my_user_id) .
		ParseData::encode_length_plus_data($new_admin) .
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