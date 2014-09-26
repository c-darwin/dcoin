<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(300);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

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