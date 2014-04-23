<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `public_key_0`
				FROM `".DB_PREFIX."users`
				WHERE `user_id` = {$_REQUEST['to_user_id']}
				LIMIT 1
				", 'fetch_one' );

$rsa = new Crypt_RSA();
$rsa->loadKey($public_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
$enc =  $rsa->encrypt($_REQUEST['comment']);
print bin2hex($enc);


?>