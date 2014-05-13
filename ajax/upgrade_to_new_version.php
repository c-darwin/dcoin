<?php
session_start();
if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', (dirname(dirname(__FILE__))) . '/' );
error_reporting( E_ALL );
// определяем режим вывода ошибок
ini_set('display_errors', 'On');
// включаем буфферизацию вывода (вывод скрипта сохраняется во внутреннем буфере)
ob_start();
//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'includes/class-parsedata.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die('Permission denied');

$cur_ver = get_current_version($db);
$file_ver = @file_get_contents( ABSPATH . 'version' );
// т.к. в mysql нет возможности выбрать максимальную версию вида 0.0.9b11, делаем это в php
$new_ver =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `version`
		FROM `".DB_PREFIX."new_version`
		WHERE `alert` = 1
		", 'array');
$new_max_ver = '0';
for ($i=0; $i<sizeof($new_ver); $i++) {
	if (version_compare($new_ver[$i], $new_max_ver) == 1)
		$new_max_ver = $new_ver[$i];
}

// если версия в файле не сотвествует той, которая в блоке
if ($new_max_ver != $file_ver) {

	// Версия в файле больше нашей?
	if (version_compare($file_ver, $cur_ver) == 1) {
		$new_max_ver = $file_ver;
	}
}
if (!$new_max_ver) {
	die('bad version');
}

if (version_compare($file_ver, $cur_ver) == 1) {
	// возможно есть какие-то задания
	require_once( ABSPATH . 'ajax/upd_tasks.php' );
}

// перезапускаем демонов
$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `script_name`
		FROM `".DB_PREFIX."main_lock`
		", 'fetch_one');
if ($script_name != 'my_lock') {

	for ($i=0; $i<1200; $i++) { // 60 сек

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
		if ($affected_rows==1)
			break;
		usleep(50000);
	}
}

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			UPDATE `".DB_PREFIX."info_block`
			SET `current_version` = '{$file_ver}'
			");

$script_name = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SELECT `script_name`
			FROM `".DB_PREFIX."main_lock`
			", 'fetch_one');
if ($script_name == 'my_lock') {
	$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,"
			TRUNCATE TABLE `".DB_PREFIX."main_lock`
			");
}

print 'install '.$new_max_ver.' complete';

?>