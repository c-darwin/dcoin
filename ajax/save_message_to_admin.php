<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

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

if (empty($_SESSION['restricted'])) {

	$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

	if ( !check_input_data ($_REQUEST['parent_id'] , 'int') )
		die('error parent_id');
	if ( !check_input_data ($_REQUEST['message_type'] , 'int') )
		die('error type');
	if ( !check_input_data ($_REQUEST['message_subtype'] , 'int') )
		die('error subtype');

	$_REQUEST['subject'] = $db->escape($_REQUEST['subject']);
	$_REQUEST['message'] = $db->escape($_REQUEST['message']);

	define('MY_PREFIX', get_my_prefix($db));

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO `".DB_PREFIX.MY_PREFIX."my_admin_messages` (
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

}

?>