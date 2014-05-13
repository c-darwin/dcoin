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

$cur_ver = get_current_version($db); // используется в upd_tasks.php

// т.к. в mysql нет возможности выбрать максимальную версию вида 0.0.9b11, делаем это в php
$new_ver =  $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `version`
		FROM `".DB_PREFIX."new_version`
		WHERE `alert` = 1
		", 'array');
$new_max_ver = 0;
for ($i=0; $i<sizeof($new_ver); $i++) {
	if (version_compare($new_ver[$i], $new_max_ver) == 1)
		$new_max_ver = $new_ver[$i];
}
//print $new_max_ver;

$zip = new ZipArchive();
$filename = ABSPATH."public/{$new_max_ver}.zip";
if ($zip->open($filename)!==TRUE) {
	print "cannot open <$filename>";
}
$extract = $zip->extractTo(ABSPATH);
$zip->close();

$errors = ob_get_contents();
if (!$errors) {

	// убедимся, что файлы обновились
	$ver = trim(file_get_contents(ABSPATH . 'version'));
	if ($ver != $new_max_ver)
		print 'ver error '.$ver.'!='.$new_max_ver;
	else {

		// возможно есть какие-то задания
		require_once( ABSPATH . 'ajax/upd_tasks.php' );

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
				SET `current_version`= '{$new_max_ver}'
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
	}
}
?>