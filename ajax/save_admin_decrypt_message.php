<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

$_REQUEST['data'] = str_ireplace("\n", "<br>", $_REQUEST['data']);
$data = json_decode($_REQUEST['data'], true);
$id = intval($_REQUEST['id']);

if ( !check_input_data ($data['parent_id'] , 'int') )
	die('error parent_id');
if ( !check_input_data ($data['type'] , 'int') )
	die('error type');
if ( !check_input_data ($data['subtype'] , 'int') )
	die('error subtype');

$data['subject'] = filter_var($data['subject'], FILTER_SANITIZE_STRING);
$data['subject'] = str_ireplace(array('\'', '"'),  '', $data['subject']);
$data['subject'] = $db->escape($data['subject']);

$data['message'] = filter_var($data['message'], FILTER_SANITIZE_STRING);
$data['message'] = str_ireplace(array('\'', '"'),  '', $data['message']);
$data['message'] = $db->escape($data['message']);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
$sql = "
		UPDATE `".DB_PREFIX.MY_PREFIX."my_admin_messages`
		SET `parent_id` = ".intval($data['parent_id']).",
			   `subject` = '{$data['subject']}',
			   `message` = '{$data['message']}',
			   `message_type` = '{$data['type']}',
			   `message_subtype` = '{$data['subtype']}',
			   `decrypted` = 1
		WHERE `id` = {$id}
		";
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,$sql);
print json_encode(array('parent_id'=>$data['parent_id']));
?>