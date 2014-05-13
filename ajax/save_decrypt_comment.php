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

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

if ( !check_input_data ($_REQUEST['id'] , 'int') )
	die('error id');

if ( !check_input_data ($data['parent_id'] , 'int') )
	die('error parent_id');

if ( $_REQUEST['type']!='dc_transactions' && $_REQUEST['type']!='cash_requests' )
	die('error type');

// $_REQUEST['comment'] - может содержать зловред
$comment = filter_var($_REQUEST['comment'], FILTER_SANITIZE_STRING);
$comment = str_ireplace(array('\'', '"'),  array('', ''), $comment);
$comment = $db->escape($comment);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
		UPDATE `".DB_PREFIX.MY_PREFIX."my_{$_REQUEST['type']}`
		SET `comment`='{$comment}',
			   `comment_status` = 'decrypted'
		WHERE `id` = {$_REQUEST['id']}
		");
print htmlentities($comment);

?>