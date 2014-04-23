<?php
session_start();

if ( $_SESSION['DC_ADMIN'] != 1 )
	die('!DC_ADMIN');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'includes/fns-main.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/class-mysql.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ( $_REQUEST['type'] == 'mp4' )
	@unlink( ABSPATH . 'public/user_video.mp4' );

if ( $_REQUEST['type'] == 'webm_ogg' ) {
	@unlink( ABSPATH . 'public/user_video.ogv' );
	@unlink( ABSPATH . 'public/user_video.webm' );
}


?>