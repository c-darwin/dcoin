<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die(json_encode(array('error'=>'Permission denied')));

define('MY_PREFIX', get_my_prefix($db));

// получаем паблик-кей на основе e и n
$rsa = new Crypt_RSA();
$key = array();
$key['e'] = new Math_BigInteger($_POST['e'], 16);
$key['n'] = new Math_BigInteger($_POST['n'], 16);
$rsa->setPublicKey($key, CRYPT_RSA_PUBLIC_FORMAT_RAW);
$PublicKey = clear_public_key($rsa->getPublicKey());

if (!$PublicKey)
	die(json_encode(array('error'=>'bad public_key')));

// проверим, есть ли вообще такой публичный ключ
$user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `user_id`
		FROM `".DB_PREFIX."users`
		WHERE `public_key_0` = 0x{$PublicKey}
		", 'fetch_one');
if (!$user_id)
	die(json_encode(array('error'=>'bad public_key')));

// может быть юзер уже майнер?
$miner_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `miner_id`
		FROM `".DB_PREFIX."miners_data`
		WHERE `user_id` = {$user_id}
		", 'fetch_one');
$miner_id = intval($miner_id);
if ($miner_id)
	$status = 'miner';
else
	$status = 'user';

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		TRUNCATE TABLE `".DB_PREFIX."my_keys`
		");

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		INSERT INTO `".DB_PREFIX.MY_PREFIX."my_keys`(
			`public_key`,
			`status`
		)
		VALUES (
			0x{$PublicKey},
			'approved'
		)");

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
		SET  `user_id` = {$user_id},
				`miner_id`= {$miner_id},
				`status`= '{$status}'
		");
print json_encode(array('success'=>'Ready'));
?>