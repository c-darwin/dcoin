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

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		INSERT INTO `".DB_PREFIX."my_admin_messages` (
				`parent_id`,
				`subject`,
				`message`,
				`message_type`,
				`message_subtype`,
				`decrypted`
			)
			VALUES (
				{$_REQUEST['parent_id']},
				'{$_REQUEST['subject']}',
				'{$_REQUEST['message']}',
				{$_REQUEST['message_type']},
				'{$_REQUEST['message_subtype']}',
				1
			)");
print $db->getInsertId();


?>