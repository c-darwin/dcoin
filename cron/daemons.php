<?php
if (!$argv) die('browser');

define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
define( 'CRON_DIR', ABSPATH . 'cron/' );

// возможен запуск еще до установки. просто ждем до 120 сек.
$i=0;
do {
	if (!file_exists(ABSPATH . 'db_config.php')) {
		sleep(1);
		$i++;
	}
} while (!file_exists(ABSPATH . 'db_config.php') && $i<120);

require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );
require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'cron/daemons_inc.php' );

define('WAIT_SCRIPT', 300);

// ****************************************************************************
//  Берем скрипты, которые более 300 сек не отстукивались в таблицу
// Т.к. данный скрипт запускается каждые 60 сек в nix и работает в цикле в windows, то у всех демнов есть ровно 60 сек,
// чтобы сообщить, что они запущены
// ****************************************************************************
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

do{
	// если это первый запуск в авто-установке в винде, то таблы могут не успеть создаться.
	$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');
	if (!in_array(DB_PREFIX."config", $tables_array) || !in_array(DB_PREFIX."main_lock", $tables_array) ||  !in_array(DB_PREFIX."daemons", $tables_array)) {
		sleep(1);
		continue;
	}

	$php_path = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `php_path`
			FROM `".DB_PREFIX."config`
			", 'fetch_one');

	$lock_script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `script_name`
			FROM `".DB_PREFIX."main_lock`
			", 'fetch_one');
	if ($lock_script_name=='my_lock')
		exit;

	foreach ($daemons as $script_name) {
		// проверим, давно ли отстукивался данный демон
		$data = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				SELECT `time`, `script`
				FROM `".DB_PREFIX."daemons`
				WHERE `script` = '{$script_name}'
				", 'fetch_array');
		if ( ($data['time'] > time() - WAIT_SCRIPT) )
			continue;

		if ($data['script'] == 'generate_new_node_key.php' &&  ($data['time'] > time() - NODE_KEY_UPD_TIME) )
			continue;

		if (!$data) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT INTO `".DB_PREFIX."daemons` (
					`script`
				) VALUES (
					'{$script_name}'
				)");
		}

		if ($php_path) {
			$cmd = $php_path.' "'.CRON_DIR.''.$script_name.'"';
			if (OS == 'WIN')
				pclose(popen("start /B ". $cmd, "r"));
			else
				exec( $cmd.' > /dev/null &' );
			debug_print($cmd , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
		}
	}

	// не в винде у нас скрипт запускается по крону раз в минуту, а  винде юзер запускает его сам 1 раз
	if (OS!='WIN')
		break;

	sleep(60);

} while (true);

?>
