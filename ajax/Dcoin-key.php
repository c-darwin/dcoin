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

$param = array();
$param['nopass']['x'] = 176;
$param['nopass']['y'] = 100;
$param['nopass']['width'] = 100;
$param['nopass']['bg_path'] = ABSPATH.'img/k_bg.png';
$param['pass']['x'] = 167;
$param['pass']['y'] = 93;
$param['pass']['width'] = 118;
$param['pass']['bg_path'] = ABSPATH.'img/k_bg_pass.png';

$rsa = new Crypt_RSA();
extract($rsa->createKey(2048));

$publickey = clear_public_key($publickey);
$priv = $rsa->_parseKey($privatekey,CRYPT_RSA_PRIVATE_FORMAT_PKCS1);

if (!empty($_REQUEST['password'])) {
	$aes = new Crypt_AES( CRYPT_AES_MODE_ECB );
	$aes->setKey(md5($_REQUEST['password']));
	$text = $privatekey;
	$aes_encr = $aes->encrypt($text);
	$private_key = chunk_split(base64_encode($aes_encr), 64);
	$param = $param['pass'];
	$k_bg_path = ABSPATH.'img/k_bg.png';
}
else {
	$private_key = str_replace(array('-----BEGIN RSA PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----'), '', $privatekey);
	$param = $param['nopass'];
}

$iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
$iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
$iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");

if( $iPod || $iPhone || $iPad ) {
	$gd = key_to_img($private_key, $param, $_SESSION['user_id']);
	header('Content-Disposition: attachment; filename="Dcoin-private-key-'.$_SESSION['user_id'].'.png"');
	header('Content-type: image/png');
	imagepng($gd);
}
else {
	header('Content-Disposition: attachment; filename="Dcoin-private-key-'.$_SESSION['user_id'].'.txt"');
	header('Content-type: text/plain');
	echo trim($private_key);
}

/*
echo json_encode(
		array(
					'private_key' => $private_key,
					'public_key' => $publickey,
					'password_hash' => hash('sha256', hash('sha256', @$_POST['password']))
				 )
		);
*/

?>