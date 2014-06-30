<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!empty($_SESSION['restricted']))
	die('Permission denied');

if (!node_admin_access($db))
	die ('Permission denied');

define('MY_PREFIX', get_my_prefix($db));

$_REQUEST['text'] = $db->escape($_REQUEST['text']);

$sms_http_get_request = $db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		SELECT `sms_http_get_request`
		FROM `".DB_PREFIX.MY_PREFIX."my_table`
		", 'fetch_one');
$ch = curl_init($sms_http_get_request.$_REQUEST['text']);
curl_setopt($ch, CURLOPT_NOBODY, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_exec($ch);
curl_close($ch);
print $sms_http_get_request.$_REQUEST['text'];

?>