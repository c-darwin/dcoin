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

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// $_REQUEST['comment'] - может содержать зловдер
$comment = filter_var($_REQUEST['comment'], FILTER_SANITIZE_STRING);
$comment = str_ireplace(array('\'', '"'),  array('', ''), $comment);
$comment = $db->escape($comment);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "SET NAMES UTF8");
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
		UPDATE `".DB_PREFIX."my_{$_REQUEST['type']}`
		SET `comment`='{$comment}',
			   `comment_status` = 'decrypted'
		WHERE `id` = {$_REQUEST['id']}
		");
print htmlentities($comment);

?>