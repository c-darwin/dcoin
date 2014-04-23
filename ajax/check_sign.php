<?php

define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'phpseclib/Math/BigInteger.php');
require_once( ABSPATH . 'phpseclib/Crypt/Random.php');
require_once( ABSPATH . 'phpseclib/Crypt/Hash.php');
require_once( ABSPATH . 'phpseclib/Crypt/RSA.php');
require_once( ABSPATH . 'phpseclib/Crypt/AES.php');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!check_input_data($_POST['sign'], 'hex_sign'))
	die('bad hex_sign');

$sign = hextobin ( $_POST['sign'] );

// получим открытый ключ юзера и для чего проверяем подпись.
$public_key = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `public_key`
		FROM `".DB_PREFIX."my_keys`
		WHERE `block_id` = (SELECT max(`block_id`) FROM `".DB_PREFIX."my_keys` )
		", 'fetch_one' );

$for_sign =$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `login_code`
		FROM `".DB_PREFIX."my_table`
		", 'fetch_one' );

$error =  ParseData::checkSign($public_key, $for_sign, $sign, true);
if ($error)
	print json_encode(array('result'=>0));
else {
	session_start();
	$_SESSION['DC_ADMIN'] = 1;
	$my_user_id = get_my_user_id($db);
	if ($my_user_id==1)
		$_SESSION['ADMIN'] = 1;
	print json_encode(array('result'=>1));
}

?>