<?php
if (!$argv) die('browser');

/*
 * Берем тр-ии из очереди и обрабатываем
 * */


define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

do {

	debug_print("START", __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);

	// отметимся в БД, что мы живы.
	upd_deamon_time($db);

	// проверим, не нужно нам выйти, т.к. обновилась версия скрипта
	if (check_deamon_restart($db))
		exit;

	main_lock();
	$current_block_id = get_block_id($db);
	if (!$current_block_id) {
		main_unlock();
		sleep(1);
		continue;
	}

	// чистим зацикленные
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			DELETE FROM `".DB_PREFIX."transactions`
			WHERE `verified` = 0 AND
						 `used` = 0 AND
						 `counter` > 10
			");

	all_tx_parser();

	main_unlock();

	sleep (1);

} while (true);

?>