<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die ('Permission denied');

copy ($_FILES['file']['tmp_name'], '/tmp/111');
$command="mysql --default-character-set=binary -h ".DB_HOST." -u '".DB_USER."' -p'".DB_PASSWORD."'  '".DB_NAME."' < '{$_FILES['file']['tmp_name']}'";
$res = shell_exec($command);

// теперь нужно заполнить таблу cimmunity

$tables_array = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
			SHOW TABLES
			", 'array');
$arr = array();
foreach($tables_array as $table) {
	if (preg_match('/([0-9]+)_my/i', $table, $m)) {
		$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__,  "
			INSERT IGNORE INTO `".DB_PREFIX."community` (
				`user_id`
			)
			VALUES (
				{$m[1]}
			)");
	}
}

if ($_FILES['file']['error']>0)
	echo json_encode(array('error'=>'error. code '.$_FILES['file']['error']));
else
	echo json_encode(array('success'=>'ok'));

?>