<?php
if (!defined('DC')) die("!defined('DC')");

// авто запуск для win
if (OS=='WIN') {

	$tpl['mysql_host'] = 'localhost';
	$tpl['mysql_port'] = '3306';
	$tpl['mysql_db_name'] = 'DC';
	$tpl['mysql_username'] = 'root';
	$tpl['mysql_password'] = '';
	$tpl['mysql_prefix'] = '';
	$php_path = 'php\\\php.exe';

	// создаем базу данных
	$i = 0;
	do {
		$mysqli_link = mysqli_connect($tpl['mysql_host'], $tpl['mysql_username'], $tpl['mysql_password']);
		if (!$mysqli_link) {
			sleep(1);
			$i++;
		}
	} while (!$mysqli_link && $i<120);
	mysqli_query($mysqli_link, "Create database if not exists {$tpl['mysql_db_name']}");
	mysqli_select_db($mysqli_link, $tpl['mysql_db_name']);

	// пишем конфиг для mysql
	$config = "<?php\r\ndefine( 'DB_NAME', '{$tpl['mysql_db_name']}' );\r\ndefine( 'DB_USER', '{$tpl['mysql_username']}' );\r\ndefine( 'DB_PASSWORD', '{$tpl['mysql_password']}' );\r\ndefine( 'DB_HOST', '{$tpl['mysql_host']}' );\r\ndefine( 'DB_PORT', '{$tpl['mysql_port']}' );\r\ndefine( 'DB_PREFIX', '{$tpl['mysql_prefix']}' );\r\n?>";
	if (!file_exists(ABSPATH . 'db_config.php')) {
		if ( !file_put_contents( ABSPATH . 'db_config.php', $config ) ) {
			$tpl['error'][] = str_ireplace('[dir]', ABSPATH, $lng['install_db_config_error']).'<br><textarea style="width:400px; height:170px">'.$config.'</textarea>';
		}
	}

	$db_name = $tpl['mysql_db_name'];
	$prefix = $tpl['mysql_prefix'];
	// пробуем создать таблицы в БД
	include ABSPATH.'schema.php';
	mysqli_query($mysqli_link, 'SET NAMES "utf8" ');
	for ($i=0; $i<sizeof($queries); $i++) {

		mysqli_multi_query($mysqli_link, $queries[$i]);
		while (@mysqli_next_result($mysqli_link)) {;}

		if ( mysqli_error($mysqli_link) ) {
			$tpl['error'][] = 'Error performing query (' . $queries[$i] . ') - Error message : '. mysqli_error($mysqli_link);
		}
	}

	// таблы my_
	$my_prefix = '';
	for ($j=0; $j<sizeof($my_queries); $j++) {

		$my_queries[$j] = str_ireplace('[my_prefix]', $my_prefix, $my_queries[$j]);
		mysqli_multi_query($mysqli_link, $my_queries[$j]);
		while (@mysqli_next_result($mysqli_link)) {;}

		if ( mysqli_error($mysqli_link) ) {
			$tpl['error'][] = 'Error performing query (' . $my_queries[$j] . ') - Error message : '. mysqli_error($mysqli_link);
		}
	}
	mysqli_query($mysqli_link,"
			INSERT INTO `".$tpl['mysql_prefix']."my_table` (
				`user_id`
			)
			VALUES (
				0
			)");

	// конфиг
	mysqli_query($mysqli_link,"
			INSERT INTO `".$tpl['mysql_prefix']."config` (
				`php_path`,
				`auto_reload`
			)
			VALUES (
				'{$php_path}',
				86400
			)");


	// отметим, что установка завершена
	mysqli_query($mysqli_link,"
			INSERT INTO
			`".$tpl['mysql_prefix']."install` (
				`progress`
			)
			VALUES (
				'complete'
			)");

	require_once( ABSPATH . 'templates/after_install.tpl' );

}
else {

	if (is_file( ABSPATH . 'db_config.php' ))
		require_once( ABSPATH . 'db_config.php' );
	if (defined('DB_HOST') && defined('DB_USER') && defined('DB_NAME'))
		$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

	if ($db) {
		// проверим, точно ли в БД есть отметка об установке с нуля
		$progress = get_install_progress();
		if ($progress=='complete')
			die ('access denied');

		require_once(ABSPATH . 'cron/daemons_inc.php');
		foreach ($daemons as $script_name) {
			$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
					INSERT INTO `".DB_PREFIX."daemons` (
						`script`,
						`restart`
					)
					VALUES (
						'{$script_name}',
						1
					) ON DUPLICATE KEY UPDATE restart=1
					");
		}
	}

	unset($_SESSION['user_id']);
	unset($_SESSION['restricted']);

	/* Для защиты от перехода злоумышленника к шагам, которые идут после проверки данных от БД
	 * нужно привязывать шаги в сессии. Если юзер смог ввести верные данные от БД, то другие
	 * шаги ему уже можно открывать. Если писать это в БД, то если юзер пройдет шаг проеврки даных к БД
	 * то любой желающий сможет зайти на следующие шаги тоже.
	 */
	$_SESSION['install_progress'] = 0;

	require_once( ABSPATH . 'templates/install_step_0.tpl' );

}

?>