<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

if (!empty($_SESSION['restricted']))
	die('Permission denied');

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ( !check_input_data ($_REQUEST['parent_id'] , 'int') )
	die('error parent_id');
if ( !check_input_data ($_REQUEST['message_type'] , 'int') )
	die('error type');
if ( !check_input_data ($_REQUEST['message_subtype'] , 'int') )
	die('error subtype');
$parent_id = intval($_REQUEST['parent_id']);
$message_type = intval($_REQUEST['message_type']);
$message_subtype = intval($_REQUEST['message_subtype']);

$subject = $db->escape($_REQUEST['subject']);
$message = $db->escape($_REQUEST['message']);

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
				{$parent_id},
				'{$subject}',
				'{$message}',
				{$message_type},
				'{$message_subtype}',
				1
			)");
print $db->getInsertId();


?>