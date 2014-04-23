<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(300);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');

$rsa = new Crypt_RSA();
extract($rsa->createKey(2048));

$publickey = clear_public_key($publickey);
$priv = $rsa->_parseKey($privatekey, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
$private_key = $privatekey;
echo json_encode(
		array(
					'private_key' => $private_key,
					'public_key' => $publickey
				 )
		);

?>