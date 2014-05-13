<?php
if (!defined('DC')) die("!defined('DC')");


require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');

//if ($_POST['type']=='exists') {

$tpl['error'] = array();
// получаем паблик-кей на основе e и n
$rsa = new Crypt_RSA();
$key = array();
$key['e'] = new Math_BigInteger($_POST['e'], 16);
$key['n'] = new Math_BigInteger($_POST['n'], 16);
$rsa->setPublicKey($key, CRYPT_RSA_PUBLIC_FORMAT_RAW);
$PublicKey = clear_public_key($rsa->getPublicKey());
if (!$PublicKey)
	$tpl['error'][] = 'bad $PublicKey: '.$PublicKey;

// пишем в нашу таблу паблик-кей и если нужно, запароленный приватный ключ и хэш пароля
if (!$tpl['error'] && $_POST['save_private_key']) {

	$private_key = $_POST['private_key'];
	$hash_pass = $_POST['hash_pass'];

	if ($private_key && !preg_match('/^[0-9a-z=\-\/\+\s\n\r]{256,4096}$/Di', $private_key))
		$tpl['error'][] = 'bad $private_key: '.$private_key;

	if ($hash_pass && !preg_match('/^[0-9a-z]{32}$/Di', $hash_pass))
		$tpl['error'][] = 'bad $hash_pass: '.$hash_pass;

	if (!$tpl['error']) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".DB_PREFIX."my_keys`(
					`public_key`,
					`private_key`,
					`password_hash`,
					`status`
				)
				VALUES (
					0x{$PublicKey},
					'{$private_key}',
					'{$hash_pass}',
					'approved'
				)");
	}
}
else if (!$tpl['error']) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		INSERT INTO `".DB_PREFIX."my_keys`(
			`public_key`,
			`status`
		)
		VALUES (
			0x{$PublicKey},
			'approved'
		)");
}

if (!$tpl['error']) {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."install`
			SET`progress` = 'complete'
			");

	// пропускаем 5-й шаг, т.к. на 5-м шаге выводится праймари ключ, который генерится на сервере, а оно уже не актуально, т.к. ключ выдает майнер
	require_once( ABSPATH . 'templates/install_step_6.tpl' );
}
else {
	require_once( ABSPATH . 'templates/install_step_4.tpl' );
}

?>