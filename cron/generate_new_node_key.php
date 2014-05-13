<?php
/*
 * 1 раз в месяц меняем нод-ключ.
 *
 * */
session_start();

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

upd_deamon_time ($db);

main_lock();

	$my_user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `user_id`
					FROM `".DB_PREFIX.MY_PREFIX."my_table`
					", 'fetch_one');

	$node_private_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
					SELECT `private_key`
					FROM `".DB_PREFIX.MY_PREFIX."my_node_keys`
					WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX.MY_PREFIX."my_node_keys` )
					", 'fetch_one');
	if (!$my_user_id || !$node_private_key) {
		main_unlock();
		exit;
	}

	require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
	require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
	require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
	require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
		
	$rsa = new Crypt_RSA();
	extract($rsa->createKey(1024));
	$publickey = clear_public_key($publickey);

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO  `".DB_PREFIX.MY_PREFIX."my_node_keys` (
				`public_key`,
				`private_key`
			)
			VALUES (
				0x{$publickey},
				'{$privatekey}'
			)");

	$time = time();

	// подписываем нашим нод-ключем данные транзакции
	$data_for_sign = ParseData::findType('change_node_key').",{$time},{$my_user_id},{$publickey}";
	$rsa = new Crypt_RSA();
	$rsa->loadKey($node_private_key);
	$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
	$signature = $rsa->sign($data_for_sign);
	print '$node_private_key='.$node_private_key."\n";
	print '$data_for_sign='.$data_for_sign."\n";
	print 'strlen($signature)='.strlen($signature)."\n";
	print 'strlen($publickey)='.strlen($publickey)."\n";

	// создаем новую транзакцию
	$bin_public_key = hextobin($publickey);
	$data = dec_binary (ParseData::findType('change_node_key'), 1) .
		dec_binary ($time, 4) .
		encode_length(strlen($my_user_id)) . $my_user_id .
		encode_length(strlen($bin_public_key)) . $bin_public_key .
		encode_length(strlen($signature)) . $signature;

	insert_tx($data, $db);

main_unlock();

?>