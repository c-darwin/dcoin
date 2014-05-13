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

if (!node_admin_access($db))
	die ('Permission denied');

// таблицы my_ сотаются как есть, поэтому могут быть проблемы с my_keys/my_node_keys
$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			INSERT IGNORE INTO `".DB_PREFIX."main_lock` (
					`lock_time`,
					`script_name`
			)
			VALUES (
					1,
					'nulling'
			)
			ON DUPLICATE KEY UPDATE `lock_time` = 1, `script_name` = 'nulling'
			");


?>