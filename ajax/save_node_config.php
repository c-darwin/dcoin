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
		SET  `in_connections_ip_limit` = {$_POST['in_connections_ip_limit']},
				`in_connections` = {$_POST['in_connections']},
				`out_connections` = {$_POST['out_connections']}
		");

@file_put_contents( ABSPATH . 'config.ini', $_POST['config_ini'] );

?>