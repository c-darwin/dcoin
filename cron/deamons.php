<?php
define( 'DC', true );
define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
define( 'CRON_DIR', ABSPATH . 'cron/' );

require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );
require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'cron/deamons_inc.php' );

define('WAIT_SCRIPT', 300);

// ****************************************************************************
//  Берем скрипты, которые более 300 сек не отстукивались в таблицу
// Т.к. данный скрипт запускается каждые 60 сек в nix и работает в цикле в windows, то у всех демнов есть ровно 60 сек,
// чтобы сообщить, что они запущены
// ****************************************************************************
$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$php_path = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `php_path`
		FROM `".DB_PREFIX."my_table`
		", 'fetch_one');
do{

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
				FROM `".DB_PREFIX."deamons`
				WHERE `script` = '{$script_name}'
				", 'fetch_array');
		if ( ($data['time'] > time() - WAIT_SCRIPT) )
			continue;

		if ($data['script'] == 'generate_new_node_key.php' &&  ($data['time'] > time() - NODE_KEY_UPD_TIME) )
			continue;

		if (!$data) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
				INSERT INTO `".DB_PREFIX."deamons` (
					`script`
				) VALUES (
					'{$script_name}'
				)");
		}

		$cmd = $php_path.' '.CRON_DIR.''.$script_name;
		if (OS == 'WIN')
			pclose(popen("start /B ". $cmd, "r"));
		else
			exec( $cmd.' > /dev/null &' );
		debug_print($cmd , __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__);
	}

	// не в винде у нас скрипт запускается по крону раз в минуту, а  винде юзер запускает его сам 1 раз
	if (OS!='WIN')
		break;

	sleep(60);

} while (true);

?>
