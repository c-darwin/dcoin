<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

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
$priv = $rsa->_parseKey($privatekey,CRYPT_RSA_PRIVATE_FORMAT_PKCS1);

if (@$_POST['password']) {
	$aes = new Crypt_AES( CRYPT_AES_MODE_ECB );
	$aes->setKey(md5($_POST['password']));
	$text = $privatekey;
	$aes_encr = $aes->encrypt($text);
	$private_key = chunk_split(base64_encode($aes_encr), 64);
}
else {
	$private_key = $privatekey;
}
echo json_encode(
		array(
					'private_key' => $private_key,
					'public_key' => $publickey,
					'password_hash' => hash('sha256', hash('sha256', @$_POST['password']))
				 )
		);


?>