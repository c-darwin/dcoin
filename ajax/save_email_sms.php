<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

set_time_limit(0);

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

$db->query( __FILE__, __LINE__,  __FUNCTION__,  __CLASS__, __METHOD__, "
		UPDATE `".DB_PREFIX."my_table`
		SET  `email` = '{$_POST['email']}',
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