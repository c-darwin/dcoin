<?php

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(30);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ($_POST) {
	$private_key = $_REQUEST['private_key'];
	$rsa = new Crypt_RSA();
	$rsa->loadKey($private_key, CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
	$public_key = clear_public_key($rsa->getPublicKey());
	$user_id = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `user_id`
			FROM `".DB_PREFIX."users`
			WHERE `public_key_0` = 0x{$public_key}
			", 'fetch_one');
	if ($user_id)
		print 'All right! user_id: '.$user_id;
	else
		print 'Sorry, bad key';
}
print '<br><form method="post"><textarea name="private_key" style="width: 700px; height: 400px"></textarea><br><input type="submit"></form>';

?>