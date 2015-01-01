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

if (!empty($_SESSION['restricted']))
	die('Permission denied');

define('MY_PREFIX', get_my_prefix($db));

if (!node_admin_access($db)) {
	$_POST['sms_http_get_request'] = '';
}

$_POST['email'] = $db->escape($_POST['email']);
$_POST['smtp_server'] = $db->escape($_POST['smtp_server']);
$_POST['use_smtp'] = $db->escape($_POST['use_smtp']);
$_POST['smtp_port'] = $db->escape($_POST['smtp_port']);
$_POST['smtp_ssl'] = $db->escape($_POST['smtp_ssl']);
$_POST['smtp_auth'] = $db->escape($_POST['smtp_auth']);
$_POST['smtp_username'] = $db->escape($_POST['smtp_username']);
$_POST['smtp_password'] = $db->escape($_POST['smtp_password']);
$_POST['sms_http_get_request'] = $db->escape($_POST['sms_http_get_request']);

$email = filter_var($_REQUEST['email'], FILTER_SANITIZE_EMAIL);
if(!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL))
	die(json_encode(array('error'=>'incorrect email')));

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX.MY_PREFIX."my_table`
		SET  `email` = '{$email}',
				`smtp_server` =  '{$_POST['smtp_server']}',
				`use_smtp` =  '{$_POST['use_smtp']}',
				`smtp_port` =  '{$_POST['smtp_port']}',
				`smtp_ssl` =  '{$_POST['smtp_ssl']}',
				`smtp_auth` =  '{$_POST['smtp_auth']}',
				`smtp_username` =  '{$_POST['smtp_username']}',
				`smtp_password` =  '{$_POST['smtp_password']}',
				`sms_http_get_request` = '{$_POST['sms_http_get_request']}'
		");


?>