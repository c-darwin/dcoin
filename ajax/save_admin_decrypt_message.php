<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$_REQUEST['data'] = str_ireplace("\n", "<br>", $_REQUEST['data']);
$data = json_decode($_REQUEST['data'], true);
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
$sql = "
		UPDATE `".DB_PREFIX."my_admin_messages`
		SET `parent_id` = ".intval($data['parent_id']).",
			   `subject` = '{$data['subject']}',
			   `message` = '{$data['message']}',
			   `message_type` = '{$data['type']}',
			   `message_subtype` = '{$data['subtype']}',
			   `decrypted` = 1
		WHERE `id` = {$_REQUEST['id']}
		";
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,$sql);
print json_encode(array('parent_id'=>$data['parent_id']));
?>