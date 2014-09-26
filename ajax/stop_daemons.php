<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');
define( 'DC', TRUE);
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die ('Permission denied');

$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `".DB_PREFIX."main_lock`
		", 'fetch_one');
if ($script_name == 'my_lock')
	exit;

do {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."daemons`
			SET `restart` = 1
			");
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT IGNORE INTO `".DB_PREFIX."main_lock` (
					`lock_time`,
					`script_name`
				)
				VALUES (
					".time().",
					'my_lock'
				)");
	$affected_rows = $db->getAffectedRows();
	usleep(50000);
} while ($affected_rows==0);

?>