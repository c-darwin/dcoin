<?php
session_start();

if ( empty($_SESSION['user_id']) )
	die('!user_id');

define( 'DC', TRUE);

define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );

//require_once( ABSPATH . 'includes/errors.php' );
require_once( ABSPATH . 'db_config.php' );
require_once( ABSPATH . 'includes/autoload.php' );

$db = new MySQLidb(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if (!node_admin_access($db))
	die ('Permission denied');

if ( $_REQUEST['type'] == 'mp4' )
	@unlink( ABSPATH . 'public/'.$_SESSION['user_id'].'_user_video.mp4' );

if ( $_REQUEST['type'] == 'webm_ogg' ) {
	@unlink( ABSPATH . 'public/'.$_SESSION['user_id'].'_user_video.ogv' );
	@unlink( ABSPATH . 'public/'.$_SESSION['user_id'].'_user_video.webm' );
}

?>