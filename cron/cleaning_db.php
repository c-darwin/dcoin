<?php

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

define('WAIT_LOCK', 3600);

// если main_lock висит более x минут, значит был какой-то сбой
$main_lock = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `lock_time`
		FROM `".DB_PREFIX."main_lock`
		WHERE `script_name` != 'my_lock'
		", 'fetch_one');
if ($main_lock && (time()-WAIT_LOCK > $main_lock) ) {

	// на всякий случай пометим, что работаем
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."main_lock`
			SET `script_name` = 'cleaning_db'
			");

	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');

	foreach($tables_array as $table) {
		//if (!in_array($table, $exceptions))

		if (!preg_match('/(my_|install|config|daemons|payment_systems|community)/i', $table)) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					TRUNCATE TABLE `".DB_PREFIX."{$table}`
					");
		}
	}
}

?>