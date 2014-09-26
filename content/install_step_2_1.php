<?php
if (!defined('DC')) die("!defined('DC')");

if ($_SESSION['install_progress'] < 2)
	die('access denied');

if (file_exists(ABSPATH . 'db_config.php') && !@$_POST['mysql_host']){
	require_once(ABSPATH . 'db_config.php');
	$tpl['mysql_host'] = DB_HOST;
	$tpl['mysql_port'] = DB_PORT;
	$tpl['mysql_db_name'] = DB_NAME;
	$tpl['mysql_username'] = DB_USER;
	$tpl['mysql_password'] = DB_PASSWORD;
	$tpl['mysql_prefix'] = DB_PREFIX;
}
else {
	$tpl['mysql_host'] = clear_quotes($_POST['mysql_host']);
	$tpl['mysql_port'] = clear_quotes($_POST['mysql_port']);
	$tpl['mysql_db_name'] = clear_quotes($_POST['mysql_db_name']);
	$tpl['mysql_username'] = clear_quotes($_POST['mysql_username']);
	$tpl['mysql_password'] = clear_quotes($_POST['mysql_password']);
	$tpl['mysql_prefix'] = clear_quotes(@$_POST['mysql_prefix']);

	if ( !check_input_data ($tpl['mysql_prefix'], 'db_prefix') )
		die('bad mysql_prefix');
}

// проверям, можно ли подключиться к БД
$tpl['mysql_port'] = !empty( $tpl['mysql_port'] ) ? $tpl['mysql_port'] : 3306;
$mysqli_link = mysqli_connect($tpl['mysql_host'], $tpl['mysql_username'], $tpl['mysql_password'], $tpl['mysql_db_name'], $tpl['mysql_port']);
if (mysqli_connect_errno()) {
	$tpl['error'][] = 'Error connecting to MySQL : ' . mysqli_connect_errno() . ' ' .  mysqli_connect_error();
}

if ( !isset($tpl['error']) ) {

	require_once( ABSPATH . 'includes/class-mysql.php' );

	$db = new MySQLidb($tpl['mysql_host'], $tpl['mysql_username'], $tpl['mysql_password'], $tpl['mysql_db_name'], $tpl['mysql_port']);

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

	// возможно идет установка пула, тогда ловим файл c user_id;public_key
	if ($_POST['pool_data']) {

		if ( !check_input_data ($_POST['pool_admin_user_id'], 'int') )
			die('bad pool_admin_user_id');
		$pool_admin_user_id = intval($_POST['pool_admin_user_id']);
		$error = pool_add_users ($_POST['pool_data'], $my_queries, $mysqli_link, $prefix, true);
		if ($error) die ($error);

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `{$prefix}config` (
					`pool_admin_user_id`,
					`auto_reload`
				)
				VALUES (
					{$pool_admin_user_id},
					86400
				)");

	}
	else {

		$my_prefix = '';
		for ($j=0; $j<sizeof($my_queries); $j++) {

			$my_queries[$j] = str_ireplace('[my_prefix]', $my_prefix, $my_queries[$j]);
			mysqli_multi_query($mysqli_link, $my_queries[$j]);
			while (@mysqli_next_result($mysqli_link)) {;}

			if ( mysqli_error($mysqli_link) ) {
				$tpl['error'][] = 'Error performing query (' . $my_queries[$j] . ') - Error message : '. mysqli_error($mysqli_link);
			}
		}

		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".$tpl['mysql_prefix']."my_table` (
					`user_id`
				)
				VALUES (
					0
				)");

		/*$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
				INSERT INTO `".$tpl['mysql_prefix']."my_notifications` (`name`, `email`, `sms`)
				VALUES ('admin_messages',1,1),('change_in_status',1,0),('fc_came_from',1,0),('fc_sent',1,0),('incoming_cash_requests',1,1),('new_version',1,1),('node_time',1,1),('system_error',1,1),('update_email',1,0),('update_primary_key',1,0),('update_sms_request',1,0),('voting_results',1,0),('voting_time',1,0)
			");*/
}

	include ABSPATH.'includes/bad_blocks.php';

}
/*
if ( !$tpl['error'] ) {

	// возможно юзер создал конфиг-файл за нас. Проверим, такие же там данные или нет.
	if ( file_exists( ABSPATH . 'db_config.php' ) ) {

		include ABSPATH . 'db_config.php';

		if ( DB_NAME == $db_name && DB_USER == $_POST['username'] && DB_PASSWORD == $_POST['password'] && DB_HOST == $_POST['host'] && DB_PORT == $_POST['port'] && DB_PREFIX == $prefix ) {
			$step_ok = 3; // GOOD
		}
	}
}
*/
if ( !isset($tpl['error']) ) {

	$config = "<?php\r\ndefine( 'DB_NAME', '{$db_name}' );\r\ndefine( 'DB_USER', '{$tpl['mysql_username']}' );\r\ndefine( 'DB_PASSWORD', '{$tpl['mysql_password']}' );\r\ndefine( 'DB_HOST', '{$tpl['mysql_host']}' );\r\ndefine( 'DB_PORT', '{$tpl['mysql_port']}' );\r\ndefine( 'DB_PREFIX', '{$prefix}' );\r\n?>";

	// пробуем создать конфиг-файл, если его нет
	if (!file_exists(ABSPATH . 'db_config.php') && $_POST['mysql_host']) {
		if ( !file_put_contents( ABSPATH . 'db_config.php', $config ) ) {
			$tpl['error'][] = str_ireplace('[dir]', ABSPATH, $lng['install_db_config_error']).'<br><textarea style="width:400px; height:170px">'.$config.'</textarea>';
		}
	}
}

if (!isset($tpl['error'])) {

	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			INSERT INTO
			`".$tpl['mysql_prefix']."install` (
				`progress`
			)
			VALUES (
				'3'
			)");

	$tpl['php_path'] = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `php_path`
			FROM `".$tpl['mysql_prefix']."config`
			", 'fetch_one');

	/*
	if ( (!$tpl['php_path']) && (OS == 'WIN') ) {
		$tpl['php_path'] = 'C:\Winginx\php5\php.exe';
	}
	else if (!$tpl['php_path']) {
		// Работает во всех версиях PHP
		$defined_constants = get_defined_constants();
		$tpl['php_path'] = $defined_constants['PHP_BINDIR'];
	}*/
	if (!$tpl['php_path']) {
		// Работает во всех версиях PHP
		$defined_constants = get_defined_constants();
		$tpl['php_path'] = $defined_constants['PHP_BINDIR'].'/php';
	}

	$_SESSION['install_progress'] = 2.1;

	require_once( ABSPATH . 'templates/install_step_2_1.tpl' );

}
else {
	require_once( ABSPATH . 'templates/install_step_2.tpl' );
}


?>